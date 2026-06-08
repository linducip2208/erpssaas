<?php

namespace Workdo\EMailBox\Services;

use Workdo\EMailBox\Models\Mailbox;
use Workdo\EMailBox\Models\MailboxEmail;

class EmailBoxService
{
    public function fetchEmails(Mailbox $mailbox, int $limit = 50)
    {
        $password = decrypt($mailbox->password);

        $imapPath = '{' . $mailbox->imap_host . ':' . $mailbox->imap_port . '/imap/' . $mailbox->imap_encryption . '}INBOX';

        $imapStream = @imap_open($imapPath, $mailbox->username, $password);

        if (!$imapStream) {
            throw new \Exception('IMAP connection failed: ' . imap_last_error());
        }

        $emails = imap_search($imapStream, 'ALL');
        $count = 0;

        if ($emails) {
            rsort($emails);

            foreach ($emails as $emailNumber) {
                if ($count >= $limit) break;

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

        return $count;
    }
}
