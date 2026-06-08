<?php

namespace Workdo\EMailBox\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Workdo\EMailBox\Models\Mailbox;
use Workdo\EMailBox\Models\MailboxEmail;

class MailboxController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::user()->can('manage-emailbox')) {
            return back()->with('error', __('Permission denied'));
        }

        $mailboxes = Mailbox::withCount('emails')
            ->where('created_by', creatorId())
            ->get();

        $mailboxId = $request->mailbox_id ?? $mailboxes->first()?->id;

        $emails = collect();
        if ($mailboxId) {
            $emails = MailboxEmail::where('mailbox_id', $mailboxId)
                ->when($request->folder, function ($query, $folder) {
                    $query->where('folder', $folder);
                }, function ($query) {
                    $query->where('folder', 'inbox');
                })
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('subject', 'like', "%{$search}%")
                            ->orWhere('from_email', 'like', "%{$search}%")
                            ->orWhere('body', 'like', "%{$search}%");
                    });
                })
                ->orderBy('received_at', 'desc')
                ->paginate($request->per_page ?? 20);
        }

        $stats = [
            'total' => MailboxEmail::where('mailbox_id', $mailboxId)->count(),
            'unread' => MailboxEmail::where('mailbox_id', $mailboxId)->where('is_read', false)->count(),
            'starred' => MailboxEmail::where('mailbox_id', $mailboxId)->where('is_starred', true)->count(),
        ];

        $folders = [
            ['name' => 'inbox', 'label' => 'Inbox', 'count' => MailboxEmail::where('mailbox_id', $mailboxId)->where('folder', 'inbox')->count()],
            ['name' => 'sent', 'label' => 'Sent', 'count' => MailboxEmail::where('mailbox_id', $mailboxId)->where('folder', 'sent')->count()],
            ['name' => 'drafts', 'label' => 'Drafts', 'count' => MailboxEmail::where('mailbox_id', $mailboxId)->where('folder', 'drafts')->count()],
            ['name' => 'trash', 'label' => 'Trash', 'count' => MailboxEmail::where('mailbox_id', $mailboxId)->where('folder', 'trash')->count()],
        ];

        return response()->json([
            'mailboxes' => $mailboxes,
            'currentMailbox' => $mailboxId ? Mailbox::find($mailboxId) : null,
            'emails' => $emails,
            'stats' => $stats,
            'folders' => $folders,
        ]);
    }

    public function store(Request $request)
    {
        if (!Auth::user()->can('manage-emailbox')) {
            return back()->with('error', __('Permission denied'));
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'imap_host' => 'required|string|max:255',
            'imap_port' => 'required|integer',
            'imap_encryption' => 'nullable|string|max:10',
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer',
            'smtp_encryption' => 'nullable|string|max:10',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', __('Validation failed'));
        }

        Mailbox::create([
            'name' => $request->name,
            'email' => $request->email,
            'imap_host' => $request->imap_host,
            'imap_port' => $request->imap_port,
            'imap_encryption' => $request->imap_encryption,
            'smtp_host' => $request->smtp_host,
            'smtp_port' => $request->smtp_port,
            'smtp_encryption' => $request->smtp_encryption,
            'username' => $request->username,
            'password' => encrypt($request->password),
            'is_active' => true,
            'created_by' => creatorId(),
        ]);

        return back()->with('success', __('Mailbox created successfully.'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->can('manage-emailbox')) {
            return back()->with('error', __('Permission denied'));
        }

        $mailbox = Mailbox::where('created_by', creatorId())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'imap_host' => 'required|string|max:255',
            'imap_port' => 'required|integer',
            'imap_encryption' => 'nullable|string|max:10',
            'smtp_host' => 'required|string|max:255',
            'smtp_port' => 'required|integer',
            'smtp_encryption' => 'nullable|string|max:10',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', __('Validation failed'));
        }

        $data = $request->only(['name', 'email', 'imap_host', 'imap_port', 'imap_encryption', 'smtp_host', 'smtp_port', 'smtp_encryption', 'username']);
        if ($request->filled('password')) {
            $data['password'] = encrypt($request->password);
        }

        $mailbox->update($data);

        return back()->with('success', __('Mailbox updated successfully.'));
    }

    public function destroy($id)
    {
        if (!Auth::user()->can('manage-emailbox')) {
            return back()->with('error', __('Permission denied'));
        }

        $mailbox = Mailbox::where('created_by', creatorId())->findOrFail($id);
        $mailbox->delete();

        return back()->with('success', __('Mailbox deleted successfully.'));
    }

    public function fetchEmails($id)
    {
        if (!Auth::user()->can('manage-emailbox')) {
            return back()->with('error', __('Permission denied'));
        }

        $mailbox = Mailbox::where('created_by', creatorId())->findOrFail($id);

        try {
            $password = decrypt($mailbox->password);

            $imapPath = '{' . $mailbox->imap_host . ':' . $mailbox->imap_port . '/imap/' . $mailbox->imap_encryption . '}INBOX';

            $imapStream = @imap_open($imapPath, $mailbox->username, $password);

            if (!$imapStream) {
                return back()->with('error', __('IMAP connection failed: ') . imap_last_error());
            }

            $emails = imap_search($imapStream, 'ALL');
            if ($emails) {
                rsort($emails);
                $count = 0;
                foreach ($emails as $emailNumber) {
                    if ($count >= 50) break;

                    $overview = imap_fetch_overview($imapStream, $emailNumber, 0);
                    if (empty($overview)) continue;

                    $messageId = $overview[0]->message_id ?? null;

                    $existing = MailboxEmail::where('mailbox_id', $mailbox->id)
                        ->where('message_id', $messageId)
                        ->exists();

                    if ($existing) continue;

                    $body = imap_body($imapStream, $emailNumber);
                    $structure = imap_fetchstructure($imapStream, $emailNumber);

                    $attachments = [];
                    if (isset($structure->parts) && count($structure->parts)) {
                        foreach ($structure->parts as $partNum => $part) {
                            if (isset($part->disposition) && $part->disposition === 'ATTACHMENT') {
                                $filename = $part->dparameters[0]->value ?? 'attachment_' . ($partNum + 1);
                                $attachmentData = imap_fetchbody($imapStream, $emailNumber, $partNum + 1);
                                $attachments[] = [
                                    'filename' => $filename,
                                    'size' => $part->bytes ?? 0,
                                    'type' => $part->subtype ?? 'unknown',
                                ];
                            }
                        }
                    }

                    MailboxEmail::create([
                        'mailbox_id' => $mailbox->id,
                        'message_id' => $messageId,
                        'from_email' => $overview[0]->from ?? 'unknown',
                        'to_email' => $overview[0]->to ?? $mailbox->email,
                        'subject' => $overview[0]->subject ?? '(No Subject)',
                        'body' => $body,
                        'received_at' => date('Y-m-d H:i:s', strtotime($overview[0]->date)),
                        'is_read' => $overview[0]->seen ?? false,
                        'folder' => 'inbox',
                        'attachments' => $attachments,
                    ]);

                    $count++;
                }
            }

            imap_close($imapStream);

            return back()->with('success', __('Emails fetched successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to fetch emails: ') . $e->getMessage());
        }
    }

    public function sendEmail(Request $request)
    {
        if (!Auth::user()->can('manage-emailbox')) {
            return back()->with('error', __('Permission denied'));
        }

        $validator = Validator::make($request->all(), [
            'mailbox_id' => 'required|exists:mailboxes,id',
            'to_email' => 'required|email',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->with('error', __('Validation failed'));
        }

        $mailbox = Mailbox::where('created_by', creatorId())->findOrFail($request->mailbox_id);

        try {
            $password = decrypt($mailbox->password);

            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=utf-8',
                'From: ' . $mailbox->name . ' <' . $mailbox->email . '>',
                'Reply-To: ' . $mailbox->email,
            ];

            $sent = mail($request->to_email, $request->subject, $request->body, implode("\r\n", $headers));

            if ($sent) {
                MailboxEmail::create([
                    'mailbox_id' => $mailbox->id,
                    'message_id' => uniqid('sent_', true),
                    'from_email' => $mailbox->email,
                    'to_email' => $request->to_email,
                    'subject' => $request->subject,
                    'body' => $request->body,
                    'received_at' => now(),
                    'is_read' => true,
                    'folder' => 'sent',
                ]);

                return back()->with('success', __('Email sent successfully.'));
            }

            return back()->with('error', __('Failed to send email.'));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to send email: ') . $e->getMessage());
        }
    }

    public function markAsRead($id)
    {
        if (!Auth::user()->can('manage-emailbox')) {
            return back()->with('error', __('Permission denied'));
        }

        $email = MailboxEmail::whereHas('mailbox', function ($query) {
            $query->where('created_by', creatorId());
        })->findOrFail($id);

        $email->update(['is_read' => !$email->is_read]);

        return response()->json(['success' => true, 'is_read' => $email->is_read]);
    }

    public function toggleStar($id)
    {
        if (!Auth::user()->can('manage-emailbox')) {
            return back()->with('error', __('Permission denied'));
        }

        $email = MailboxEmail::whereHas('mailbox', function ($query) {
            $query->where('created_by', creatorId());
        })->findOrFail($id);

        $email->update(['is_starred' => !$email->is_starred]);

        return response()->json(['success' => true, 'is_starred' => $email->is_starred]);
    }

    public function moveToFolder(Request $request, $id)
    {
        if (!Auth::user()->can('manage-emailbox')) {
            return back()->with('error', __('Permission denied'));
        }

        $email = MailboxEmail::whereHas('mailbox', function ($query) {
            $query->where('created_by', creatorId());
        })->findOrFail($id);

        $email->update(['folder' => $request->folder]);

        return back()->with('success', __('Email moved to ') . $request->folder . '.');
    }

    public function deleteEmail($id)
    {
        if (!Auth::user()->can('manage-emailbox')) {
            return back()->with('error', __('Permission denied'));
        }

        $email = MailboxEmail::whereHas('mailbox', function ($query) {
            $query->where('created_by', creatorId());
        })->findOrFail($id);

        $email->delete();

        return back()->with('success', __('Email deleted successfully.'));
    }
}
