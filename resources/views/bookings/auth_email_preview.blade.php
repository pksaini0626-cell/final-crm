@extends('layouts.app')

@section('content')
<div>
    <!-- Top Header -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 fw-bold text-dark mb-0 d-flex align-items-center gap-2">
                    <i class="bi bi-send-check text-dark"></i> Generate Authorization Email &amp; Rich Text Editor
                </h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle font-monospace fs-6">
                    #{{ $booking->booking_id }}
                </span>
            </div>
            <p class="text-secondary small mb-0">Review email details, customize fields, edit the template live in the rich text editor, and send to the customer.</p>
        </div>
        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary btn-sm fw-semibold d-inline-flex align-items-center gap-2">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border-danger shadow-sm mb-4">
            <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i> Cannot Dispatch Authorization Email:
            </div>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main 2-Column Grid -->
    <div class="row g-4 items-start">
        
        <!-- LEFT COLUMN: Email Controls & Settings Form -->
        <div class="col-lg-5">
            <form id="auth-email-form" action="{{ route('bookings.auth-email.send', $booking->id) }}" method="POST" class="card bg-white border-light-subtle shadow-sm">
                @csrf
                <input type="hidden" name="custom_html" id="custom_html_input">

                <div class="card-header bg-white border-bottom border-light-subtle py-3">
                    <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-2">
                        <i class="bi bi-sliders text-primary"></i> Email Configuration
                    </h2>
                </div>

                <div class="card-body p-4 vstack gap-3">
                    <!-- Email Language Selector -->
                    <div>
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Email Template Language <span class="text-danger">*</span></label>
                        <select name="email_language" onchange="window.location.href='{{ route('bookings.auth-email.preview', $booking->id) }}?lang=' + this.value" class="form-select font-semibold text-primary">
                            <option value="english" {{ $selectedLanguage === 'english' ? 'selected' : '' }}>English (Inglés)</option>
                            <option value="spanish" {{ $selectedLanguage === 'spanish' ? 'selected' : '' }}>Spanish (Español)</option>
                        </select>
                        @if(($agentLanguageOption ?? 'english') === 'both')
                            <div class="form-text text-info small mt-1"><i class="bi bi-info-circle me-1"></i> Agent language set to BOTH. Toggle freely between English and Spanish.</div>
                        @endif
                    </div>

                    <!-- Recipient Email -->
                    <div>
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Recipient Email <span class="text-danger">*</span></label>
                        <input type="email" name="email_address" value="{{ old('email_address', $booking->email_address) }}" required class="form-control">
                    </div>

                    <!-- Subject Line -->
                    <div>
                        <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Subject Line <span class="text-danger">*</span></label>
                        <input type="text" name="subject" value="{{ old('subject', $defaultSubject) }}" required class="form-control">
                    </div>

                    <!-- Sender Settings -->
                    <div class="row g-2">
                        <div class="col-sm-6">
                            <label class="form-label text-secondary small fw-bold text-uppercase mb-1">From Name</label>
                            <input type="text" name="from_name" value="{{ old('from_name', $defaultFromName) }}" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-secondary small fw-bold text-uppercase mb-1">From Email</label>
                            <input type="email" name="from_email" value="{{ old('from_email', $defaultFromEmail) }}" class="form-control">
                        </div>
                    </div>

                    <!-- Agent Signature Details -->
                    <div class="row g-2 pt-2 border-top border-light-subtle">
                        <div class="col-sm-6">
                            <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Agent Name</label>
                            <input type="text" name="agent_name" value="{{ old('agent_name', $agentName) }}" class="form-control">
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label text-secondary small fw-bold text-uppercase mb-1">Agent Extension</label>
                            <input type="text" name="agent_ext" value="{{ old('agent_ext', $agentExt) }}" class="form-control font-monospace">
                        </div>
                    </div>

                    <!-- Summary Callout -->
                    <div class="p-3 bg-light rounded border border-light-subtle small text-secondary">
                        <div class="fw-bold text-primary mb-1 d-flex align-items-center gap-1">
                            <i class="bi bi-shield-check"></i> Authorization Details
                        </div>
                        <div><strong class="text-dark">Customer:</strong> {{ $booking->card_holder_name ?: 'N/A' }}</div>
                        <div><strong class="text-dark">Card Last 4:</strong> ****-****-****-{{ $booking->card_last_4 ?: 'XXXX' }}</div>
                        <div><strong class="text-dark">Total Amount:</strong> <span class="text-success font-monospace fw-bold">{{ $booking->currency }} {{ number_format($booking->total_amount, 2) }}</span></div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-2 vstack gap-2">
                        <button type="submit" class="btn btn-primary fw-bold py-2.5 shadow-sm d-flex align-items-center justify-content-center gap-2">
                            <i class="bi bi-send-fill"></i>
                            <span>Send {{ strtoupper($selectedLanguage) }} Auth Mail to Customer</span>
                        </button>
                        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary fw-semibold py-2">
                            Cancel &amp; Return
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- RIGHT COLUMN: Real-Time Rich Text Editor & Live Preview Pane -->
        <div class="col-lg-7">
            <div class="card bg-white border-light-subtle shadow-sm">
                <!-- Editor Header Toolbar -->
                <div class="card-header bg-white border-bottom border-light-subtle py-2.5 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <h2 class="h6 font-bold text-dark mb-0 text-uppercase d-flex align-items-center gap-1.5">
                            <i class="bi bi-pencil-square text-primary"></i> Rich Text Email Editor ({{ strtoupper($selectedLanguage) }})
                        </h2>
                        <span class="badge bg-success-subtle text-success border border-success-subtle d-none d-sm-inline-block">Live Editable</span>
                    </div>

                    <div class="d-flex align-items-center gap-1">
                        <button type="button" onclick="resetTemplate()" class="btn btn-outline-warning btn-sm py-1 px-2 fw-semibold" title="Reset to Original Default Template">
                            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Template
                        </button>
                    </div>
                </div>

                <!-- Rich Text Formatting Toolbar -->
                <div class="bg-light border-bottom border-light-subtle p-2 d-flex flex-wrap align-items-center gap-1">
                    <div class="btn-group btn-group-sm me-1" role="group" aria-label="Text Formatting">
                        <button type="button" onclick="formatText('bold')" class="btn btn-outline-secondary py-1 px-2" title="Bold (Ctrl+B)">
                            <i class="bi bi-type-bold fw-bold"></i>
                        </button>
                        <button type="button" onclick="formatText('italic')" class="btn btn-outline-secondary py-1 px-2" title="Italic (Ctrl+I)">
                            <i class="bi bi-type-italic"></i>
                        </button>
                        <button type="button" onclick="formatText('underline')" class="btn btn-outline-secondary py-1 px-2" title="Underline (Ctrl+U)">
                            <i class="bi bi-type-underline"></i>
                        </button>
                        <button type="button" onclick="formatText('strikeThrough')" class="btn btn-outline-secondary py-1 px-2" title="Strikethrough">
                            <i class="bi bi-type-strikethrough"></i>
                        </button>
                    </div>

                    <div class="btn-group btn-group-sm me-1" role="group" aria-label="Alignment">
                        <button type="button" onclick="formatText('justifyLeft')" class="btn btn-outline-secondary py-1 px-2" title="Align Left">
                            <i class="bi bi-text-left"></i>
                        </button>
                        <button type="button" onclick="formatText('justifyCenter')" class="btn btn-outline-secondary py-1 px-2" title="Align Center">
                            <i class="bi bi-text-center"></i>
                        </button>
                        <button type="button" onclick="formatText('justifyRight')" class="btn btn-outline-secondary py-1 px-2" title="Align Right">
                            <i class="bi bi-text-right"></i>
                        </button>
                    </div>

                    <div class="btn-group btn-group-sm me-1" role="group" aria-label="Lists">
                        <button type="button" onclick="formatText('insertUnorderedList')" class="btn btn-outline-secondary py-1 px-2" title="Bullet List">
                            <i class="bi bi-list-ul"></i>
                        </button>
                        <button type="button" onclick="formatText('insertOrderedList')" class="btn btn-outline-secondary py-1 px-2" title="Numbered List">
                            <i class="bi bi-list-ol"></i>
                        </button>
                    </div>

                    <div class="d-flex align-items-center gap-1 me-1">
                        <span class="text-secondary small me-1">Color:</span>
                        <input type="color" onchange="formatText('foreColor', this.value)" class="form-control form-control-color form-control-sm p-0 border-0" title="Text Color" style="width: 28px; height: 28px; cursor: pointer;">
                        <input type="color" value="#ffffaa" onchange="formatText('hiliteColor', this.value)" class="form-control form-control-color form-control-sm p-0 border-0 ms-1" title="Highlight Color" style="width: 28px; height: 28px; cursor: pointer;">
                    </div>

                    <div class="btn-group btn-group-sm" role="group" aria-label="Insert Link">
                        <button type="button" onclick="addLink()" class="btn btn-outline-primary py-1 px-2" title="Insert Link">
                            <i class="bi bi-link-45deg"></i>
                        </button>
                        <button type="button" onclick="formatText('removeFormat')" class="btn btn-outline-secondary py-1 px-2" title="Clear Formatting">
                            <i class="bi bi-eraser"></i>
                        </button>
                    </div>
                </div>

                <!-- Rich Text Editable Container -->
                <div class="card-body p-0 bg-white text-dark overflow-auto position-relative" style="min-height: 500px; max-height: 75vh; border-bottom-left-radius: 0.375rem; border-bottom-right-radius: 0.375rem;">
                    <div id="email-preview-container" contenteditable="true" style="outline: none; min-height: 500px; padding: 0;">
                        @if($selectedLanguage === 'spanish')
                            @include('emails.customer_auth_email_es', ['booking' => $booking])
                        @else
                            @include('emails.customer_auth_email', ['booking' => $booking])
                        @endif
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
    let defaultTemplateHtml = '';

    function formatText(command, value = null) {
        document.execCommand(command, false, value);
        syncHtml();
    }

    function addLink() {
        const url = prompt('Enter URL link for insertion:', 'https://');
        if (url) {
            formatText('createLink', url);
        }
    }

    function resetTemplate() {
        if (confirm('Revert all live edits back to the default original template?')) {
            const container = document.getElementById('email-preview-container');
            if (container) {
                container.innerHTML = defaultTemplateHtml;
                syncHtml();
            }
        }
    }

    function syncHtml() {
        const container = document.getElementById('email-preview-container');
        const hiddenInput = document.getElementById('custom_html_input');
        if (container && hiddenInput) {
            hiddenInput.value = container.innerHTML;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('email-preview-container');
        const form = document.getElementById('auth-email-form');

        if (container) {
            defaultTemplateHtml = container.innerHTML;
            syncHtml();

            container.addEventListener('input', syncHtml);
            container.addEventListener('keyup', syncHtml);
            container.addEventListener('blur', syncHtml);
        }

        if (form) {
            form.addEventListener('submit', function () {
                syncHtml();
            });
        }
    });
</script>
@endsection
