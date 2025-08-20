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
                    'sender' => $email->sender
                ];

                // Get all recipients
                $allRecipients = $email->recipients;
                
                // Send individual emails to each recipient
                foreach ($allRecipients as $recipient) {
                    try {
                        Mail::send('emails.template', ['mailData' => $mailData], function ($message) use ($recipient, $email) {
                            $message->from($email->from_email);
                            
                            // Send to individual recipient based on their type
                            switch ($recipient->type) {
                                case 'to':
                                    $message->to($recipient->recipient_email, $recipient->recipient_name);
                                    break;
                                case 'cc':
                                    // For CC recipients, you might want to include original TO recipients
                                    // Or handle CC differently based on your business logic
                                    $message->to($recipient->recipient_email, $recipient->recipient_name);
                                    break;
                                case 'bcc':
                                    $message->to($recipient->recipient_email, $recipient->recipient_name);
                                    break;
                            }
                            
                            $message->subject($email->subject);
                        });
                        
                        // Optional: Log successful individual sends
                        \Log::info("Email sent successfully to: " . $recipient->recipient_email);
                        
                    } catch (\Exception $individualError) {
                        // Log individual recipient failures but continue with others
                        \Log::error("Failed to send email to {$recipient->recipient_email}: " . $individualError->getMessage());
                    }
                }

                // Update sent_at timestamp
                $email->update(['sent_at' => now()]);

                return response()->json([
                    'success' => true,
                    'message' => 'Email sent successfully to all recipients'
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
