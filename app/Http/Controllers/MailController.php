<?php

namespace App\Http\Controllers;

use App\Mail\MassMail;
use Illuminate\Support\Facades\Mail;
use League\Csv\Reader;
use Illuminate\Http\Request;
use App\Models\Email;
use App\Models\EmailRecipient;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class MailController extends Controller
{
    public function index(Request $request)
    {
        // Get all emails with their recipients (eager loading for performance)
        $mails = Email::with(['sender', 'recipients'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Transform the data to match what your Vue component expects
        $transformedMails = $mails->map(function ($mail) {
            return [
                'id' => $mail->id,
                'subject' => $mail->subject,
                'sender' => [
                    'name' => $mail->sender->name ?? 'Unknown',
                    'email' => $mail->sender->email ?? 'unknown@example.com'
                ],
                'status' => $mail->status,
                'created_at' => $mail->created_at->toISOString(),
                'sent_at' => $mail->sent_at?->toISOString(),
                'recipients' => $mail->recipients->map(function ($recipient) {
                    return [
                        'recipient_email' => $recipient->recipient_email,
                        'type' => $recipient->type
                    ];
                })->toArray(),
                'recipient_count' => $mail->recipients->count()
            ];
        });

        // Return the data to Inertia.js
        return Inertia::render('Mails/Index', [
            'mails' => $transformedMails,
            'filters' => [
                'searchTerm' => $request->get('search') ?? '',
            ],
            'pagination' => [
                'current_page' => $mails->currentPage(),
                'last_page' => $mails->lastPage(),
                'per_page' => $mails->perPage(),
                'total' => $mails->total(),
            ]
        ]);
    }

    public function create()
    {
        return Inertia::render('Mails/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'recipients' => 'required|array|min:1',
            'recipients.*.email' => 'required|email',
            'recipients.*.name' => 'nullable|string',
            'recipients.*.type' => 'required|in:to,cc,bcc',
            'status' => 'required|in:draft,sent'
        ]);

        // Create email
        $email = Email::create([
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'sender_id' => auth()->id(),
            'status' => $validated['status'],
            'sent_at' => $validated['status'] === 'sent' ? now() : null,
        ]);

        // Add recipients
        foreach ($validated['recipients'] as $recipientData) {
            EmailRecipient::create([
                'email_id' => $email->id,
                'recipient_email' => $recipientData['email'],
                'recipient_name' => $recipientData['name'] ?? null,
                'type' => $recipientData['type'],
            ]);
        }

        // Actually send the email if status is sent
        if ($validated['status'] === 'sent') {
            try {
                // Create mail data for sending
                $mailData = [
                    'subject' => $email->subject,
                    'body' => $email->body,
                    'recipients' => $email->recipients->toArray(),
                    'sender' => $email->sender
                ];

                // Send email using Laravel's Mail facade
                $toRecipients = $email->recipients->where('type', 'to')->pluck('recipient_email')->toArray();
                $ccRecipients = $email->recipients->where('type', 'cc')->pluck('recipient_email')->toArray();
                $bccRecipients = $email->recipients->where('type', 'bcc')->pluck('recipient_email')->toArray();

                // You can customize this part based on your email sending strategy
                Mail::send('emails.template', ['mailData' => $mailData], function ($message) use ($toRecipients, $ccRecipients, $bccRecipients, $email) {
                    $message->to($toRecipients);
                    if (!empty($ccRecipients)) {
                        $message->cc($ccRecipients);
                    }
                    if (!empty($bccRecipients)) {
                        $message->bcc($bccRecipients);
                    }
                    $message->subject($email->subject);
                });

                // Update sent_at timestamp
                $email->update(['sent_at' => now()]);

                return response()->json([
                    'success' => true,
                    'message' => 'Email sent successfully'
                ]);
            } catch (\Exception $e) {
                \Log::error('Email sending failed: ' . $e->getMessage());
                
                // Update status to failed
                $email->update(['status' => 'failed']);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send email: ' . $e->getMessage()
                ], 500);
            }
        }

        return redirect()->route('mails.index')->with('success', 'Email saved successfully');
    }

    public function show($id)
    {
        $email = Email::with(['sender', 'recipients'])->findOrFail($id);
        
        return Inertia::render('Mails/Show', [
            'email' => $email
        ]);
    }
}
