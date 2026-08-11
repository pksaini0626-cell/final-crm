<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CallLogController extends Controller
{
    /**
     * Display call logs listing with search, filtering, sorting, and pagination.
     */
    public function index(Request $request)
    {
        $query = CallLog::with('agent');

        $user = Auth::user();

        // Agents can only see their own call logs unless admin/manager
        if (!$user->hasAnyRole(['admin', 'manager']) && !in_array($user->role, ['admin', 'manager'])) {
            $query->where('agent_id', $user->id);
        } elseif ($request->filled('agent_id')) {
            $query->where('agent_id', $request->input('agent_id'));
        }

        // Search filter (customer_name, phone, email, city)
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('customer_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%")
                  ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        // Follow-up filter
        if ($request->filled('follow_up')) {
            $query->where('follow_up', $request->input('follow_up') === '1');
        }

        // Call Type (Service Provided) filter
        if ($request->filled('service_provided')) {
            $query->where('service_provided', $request->input('service_provided'));
        }

        // Single Date filter
        if ($request->filled('date')) {
            $query->whereDate('call_date', $request->input('date'));
        }

        // Date Range filter (start_date, end_date)
        if ($request->filled('start_date')) {
            $query->whereDate('call_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('call_date', '<=', $request->input('end_date'));
        }

        // Sorting options
        $sort = $request->input('sort', 'call_date_desc');
        match ($sort) {
            'call_date_asc' => $query->orderBy('call_date', 'asc'),
            'customer_name_asc' => $query->orderBy('customer_name', 'asc'),
            'customer_name_desc' => $query->orderBy('customer_name', 'desc'),
            default => $query->orderBy('call_date', 'desc'),
        };

        $callLogs = $query->paginate(15)->withQueryString();

        // Agents list for admin filter dropdown
        $agents = User::where('is_active', true)->orderBy('alias_name')->get();

        return view('call_logs.index', compact('callLogs', 'agents'));
    }

    /**
     * Store a new call log entry.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'phone_number' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'city' => 'nullable|string|max:255',
            'service_provided' => 'required|string|max:255',
            'follow_up' => 'required|boolean',
            'call_date' => 'required|date',
            'remark' => 'nullable|string|max:2000',
        ]);

        $validated['agent_id'] = Auth::id();

        CallLog::create($validated);

        return redirect()->route('call-logs.index')
            ->with('success', 'Call log recorded successfully.');
    }

    /**
     * Display call log details (JSON endpoint for view modal).
     */
    public function show(CallLog $callLog)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['admin', 'manager']) && !in_array($user->role, ['admin', 'manager']) && $callLog->agent_id !== $user->id) {
            abort(403);
        }

        $callLog->load('agent');

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $callLog->id,
                'customer_name' => $callLog->customer_name,
                'phone_number' => $callLog->phone_number,
                'email' => $callLog->email ?: 'N/A',
                'city' => $callLog->city ?: 'N/A',
                'service_provided' => $callLog->service_provided ?: 'N/A',
                'service_provided_label' => $callLog->service_provided_label,
                'follow_up' => $callLog->follow_up ? 'Yes' : 'No',
                'call_date' => $callLog->call_date->format('M d, Y h:i A'),
                'remark' => $callLog->remark ?: 'No remarks recorded.',
                'agent_name' => $callLog->agent ? ($callLog->agent->alias_name ?: $callLog->agent->name) : 'System',
                'created_at' => $callLog->created_at->format('M d, Y H:i'),
            ]
        ]);
    }

    /**
     * Delete call log record.
     */
    public function destroy(CallLog $callLog)
    {
        $user = Auth::user();
        if (!$user->hasAnyRole(['admin', 'manager']) && !in_array($user->role, ['admin', 'manager']) && $callLog->agent_id !== $user->id) {
            abort(403);
        }

        $customer = $callLog->customer_name;
        $callLog->delete();

        return redirect()->back()
            ->with('success', "Call log for {$customer} deleted successfully.");
    }

    /**
     * Export selected or filtered call logs to CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $query = CallLog::with('agent');
        $user = Auth::user();

        if (!$user->hasAnyRole(['admin', 'manager']) && !in_array($user->role, ['admin', 'manager'])) {
            $query->where('agent_id', $user->id);
        } elseif ($request->filled('agent_id')) {
            $query->where('agent_id', $request->input('agent_id'));
        }

        // Selected IDs
        if ($request->filled('ids')) {
            $ids = explode(',', $request->input('ids'));
            $query->whereIn('id', $ids);
        } else {
            // Apply current filters
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('customer_name', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('city', 'like', "%{$search}%")
                      ->orWhere('remark', 'like', "%{$search}%");
                });
            }

            if ($request->filled('follow_up')) {
                $query->where('follow_up', $request->input('follow_up') === '1');
            }

            if ($request->filled('service_provided')) {
                $query->where('service_provided', $request->input('service_provided'));
            }

            if ($request->filled('date')) {
                $query->whereDate('call_date', $request->input('date'));
            }

            if ($request->filled('start_date')) {
                $query->whereDate('call_date', '>=', $request->input('start_date'));
            }
            if ($request->filled('end_date')) {
                $query->whereDate('call_date', '<=', $request->input('end_date'));
            }
        }

        $sort = $request->input('sort', 'call_date_desc');
        match ($sort) {
            'call_date_asc' => $query->orderBy('call_date', 'asc'),
            'customer_name_asc' => $query->orderBy('customer_name', 'asc'),
            'customer_name_desc' => $query->orderBy('customer_name', 'desc'),
            default => $query->orderBy('call_date', 'desc'),
        };

        $callLogs = $query->get();

        $filename = 'call_logs_export_' . date('Y-m-d_His') . '.csv';

        return response()->stream(function () use ($callLogs) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, [
                'Log ID',
                'Agent Name',
                'Agent Email',
                'Customer Name',
                'Phone Number',
                'Email',
                'City',
                'Call Type',
                'Follow Up Required',
                'Call Date & Time',
                'Remark',
                'Created At',
            ]);

            foreach ($callLogs as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->agent ? ($log->agent->alias_name ?: $log->agent->name) : 'N/A',
                    $log->agent ? $log->agent->email : 'N/A',
                    $log->customer_name,
                    $log->phone_number,
                    $log->email ?: 'N/A',
                    $log->city ?: 'N/A',
                    $log->service_provided_label,
                    $log->follow_up ? 'Yes' : 'No',
                    $log->call_date ? $log->call_date->format('Y-m-d H:i:s') : '',
                    $log->remark ?: '',
                    $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
