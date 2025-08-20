<?php

namespace App\Http\Controllers;

use App\Mail\MassMail;
use Illuminate\Support\Facades\Mail;
use League\Csv\Reader;
use Illuminate\Http\Request;
use App\Models\{
    Email,
    Subscription,
    Package
};
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
        $fromEmails = [
            ['email' => 'noreply@csstrats.dk', 'name' => 'No Reply'],
            ['email' => 'silas@csstrats.dk', 'name' => 'Silas'],
            ['email' => 'asger@csstrats.dk', 'name' => 'Asger'],
            ['email' => 'vkaki@csstrats.dk', 'name' => 'Valdemar'],
            ['email' => 'sales@csstrats.dk', 'name' => 'Sales Team'],
            ['email' => auth()->user()->email, 'name' => auth()->user()->name],
        ];

        $subscriptions = Subscription::with('customerRelation')->get();
        $packages = Package::all()->keyBy('prod_id');

        $groups = $subscriptions->groupBy(function ($subscription) use ($packages) {
            $productId = $subscription->plan['product'] ?? null;
            $amount = $subscription->plan['amount'] / 100;

            if (isset($packages[$productId]) && (float) $packages[$productId]->price === (float) $amount) {
                return $packages[$productId]->name . ' ' . number_format($amount, 2) . ' ' . $subscription->currency;
            }

            return 'Unknown Package ' . number_format($amount, 2) . ' ' . $subscription->currency;
        })->map(function ($group) {
            return $group->map(function ($subscription) {
                return $subscription->customerRelation;
            });
        });

        return Inertia::render('Mails/Create', [
            'recipientGroups' => $groups,
            'fromEmails' => $fromEmails,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'from_email' => 'required|email',  // Add validation for from_email
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'recipients' => 'required|array|min:1',
            'recipients.*.email' => 'required|email',
            'recipients.*.name' => 'nullable|string',
            'recipients.*.type' => 'required|in:to,cc,bcc',
            'status' => 'required|in:draft,sent'
        ]);

        // Create email with from_email
        $email = Email::create([
            'from_email' => $validated['from_email'],  // Add this field
            'subject' => $validated['subject'],
            'body' => $validated['body'],
            'sender_id' => auth()->id(),
            'status' => $validated['status'],
            'sent_at' => $validated['status'] === 'sent' ? now() : null,
        ]);

        // Add recipients (same as before)
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

                // Updated to use the selected from_email
                Mail::send('emails.template', ['mailData' => $mailData], function ($message) use ($toRecipients, $ccRecipients, $bccRecipients, $email) {
                    $message->from($email->from_email);  // Use the selected from email
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
