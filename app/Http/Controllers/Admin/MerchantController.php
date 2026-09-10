<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Exception;

class MerchantController extends Controller
{
    /**
     * Display a listing of the merchants.
     */
    public function index()
    {
        $merchants = Merchant::latest()->paginate(10);
        return view('admin.merchants.index', compact('merchants'));
    }

    /**
     * Show the form for creating a new merchant.
     */
    public function create()
    {
        return view('admin.merchants.create');
    }

    /**
     * Store a newly created merchant in storage.
     */
    public function store(Request $request)
    {
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => $request->boolean('is_active'),
            ]);
        }
        if ($request->has('is_smtp_active')) {
            $request->merge([
                'is_smtp_active' => $request->boolean('is_smtp_active'),
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'merchant_code' => 'required|string|unique:merchants,merchant_code|max:255',
            'security_key' => 'nullable|string|max:255',
            'api_url' => 'nullable|url|max:255',
            'tokenization_key' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'support_mail' => 'nullable|email|max:255',
            'wallet_balance' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
            
            // SMTP Settings
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|string|in:ssl,tls,none',
            'from_email' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'reply_to_email' => 'nullable|email|max:255',
            'reply_to_name' => 'nullable|string|max:255',
            'is_smtp_active' => 'nullable|boolean',

            // Extra details
            'code' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'currency' => 'required|string|max:3',
        ]);

        // Default toggles if missing
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;
        $validated['is_smtp_active'] = $request->has('is_smtp_active') ? $request->boolean('is_smtp_active') : false;

        Merchant::create($validated);

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Merchant created successfully.');
    }

    /**
     * Show the form for editing the specified merchant.
     */
    public function edit(Merchant $merchant)
    {
        return view('admin.merchants.edit', compact('merchant'));
    }

    /**
     * Update the specified merchant in storage.
     */
    public function update(Request $request, Merchant $merchant)
    {
        if ($request->has('is_active')) {
            $request->merge([
                'is_active' => $request->boolean('is_active'),
            ]);
        }
        if ($request->has('is_smtp_active')) {
            $request->merge([
                'is_smtp_active' => $request->boolean('is_smtp_active'),
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'merchant_code' => 'required|string|max:255|unique:merchants,merchant_code,' . $merchant->id,
            'security_key' => 'nullable|string|max:255',
            'api_url' => 'nullable|url|max:255',
            'tokenization_key' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:255',
            'support_mail' => 'nullable|email|max:255',
            'wallet_balance' => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string',
            
            // SMTP Settings
            'smtp_host' => 'nullable|string|max:255',
            'smtp_port' => 'nullable|integer|min:1',
            'smtp_username' => 'nullable|string|max:255',
            'smtp_password' => 'nullable|string',
            'smtp_encryption' => 'nullable|string|in:ssl,tls,none',
            'from_email' => 'nullable|email|max:255',
            'from_name' => 'nullable|string|max:255',
            'reply_to_email' => 'nullable|email|max:255',
            'reply_to_name' => 'nullable|string|max:255',
            'is_smtp_active' => 'nullable|boolean',

            // Extra details
            'code' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:255',
            'currency' => 'required|string|max:3',
        ]);

        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : false;
        $validated['is_smtp_active'] = $request->has('is_smtp_active') ? $request->boolean('is_smtp_active') : false;

        // Only update password if a new one is provided
        if (empty($validated['smtp_password'])) {
            unset($validated['smtp_password']);
        }

        $merchant->update($validated);

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Merchant updated successfully.');
    }

    /**
     * Toggle active status of a merchant.
     */
    public function toggleActive(Merchant $merchant)
    {
        $merchant->update([
            'is_active' => !$merchant->is_active
        ]);

        return redirect()->route('admin.merchants.index')
            ->with('success', 'Merchant status updated.');
    }

    /**
     * Test SMTP connection credentials.
     */
    public function testSmtp(Request $request, Merchant $merchant)
    {
        if (empty($merchant->smtp_host) || empty($merchant->smtp_port)) {
            return redirect()->back()->withErrors(['smtp_error' => 'SMTP Host and Port are required to test connection.']);
        }

        try {
            $factory = new EsmtpTransportFactory();
            
            $scheme = 'smtp';
            if ($merchant->smtp_encryption === 'ssl') {
                $scheme = 'smtps';
            }
            
            $username = urlencode($merchant->smtp_username ?? '');
            $password = urlencode($merchant->smtp_password ?? '');
            
            $dsnString = "{$scheme}://{$username}:{$password}@{$merchant->smtp_host}:{$merchant->smtp_port}";
            
            $transport = $factory->create(Dsn::fromString($dsnString));
            $transport->start();
            
            return redirect()->back()->with('success', 'SMTP Connection check succeeded!');
        } catch (TransportExceptionInterface $e) {
            return redirect()->back()->withErrors(['smtp_error' => 'SMTP Connection failed: ' . $e->getMessage()]);
        } catch (Exception $e) {
            return redirect()->back()->withErrors(['smtp_error' => 'SMTP Connection failed: ' . $e->getMessage()]);
        }
    }
}
