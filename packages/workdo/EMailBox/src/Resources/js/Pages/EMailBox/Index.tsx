import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Mail, Star, StarOff, Trash2, RefreshCw, PenLine, Folders, Settings } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface EmailBoxIndexProps {
    mailboxes: Array<{ id: number; name: string; email: string; is_active: boolean }>;
    currentMailbox: { id: number; name: string; email: string } | null;
    emails: {
        data: Array<{
            id: number;
            from_email: string;
            subject: string;
            body: string;
            received_at: string;
            is_read: boolean;
            is_starred: boolean;
            folder: string;
            attachments: any[];
        }>;
        current_page: number;
        last_page: number;
        total: number;
    };
    stats: { total: number; unread: number; starred: number };
    folders: Array<{ name: string; label: string; count: number }>;
}

export default function Index() {
    const { t } = useTranslation();
    const { mailboxes, currentMailbox, emails, stats, folders } = usePage<EmailBoxIndexProps>().props;
    const [activeFolder, setActiveFolder] = useState('inbox');
    const [selectedEmail, setSelectedEmail] = useState<any>(null);
    const [showCompose, setShowCompose] = useState(false);
    const [showSettings, setShowSettings] = useState(false);
    const [composeForm, setComposeForm] = useState({ to_email: '', subject: '', body: '' });

    const handleFetch = async () => {
        if (!currentMailbox) return;
        try {
            await axios.post(route('emailbox.fetch', currentMailbox.id));
            toast.success(t('Emails fetched.'));
            router.reload();
        } catch (error) {
            toast.error(t('Failed to fetch emails.'));
        }
    };

    const handleReadToggle = async (emailId: number) => {
        try {
            const resp = await axios.put(route('emailbox.email.read', emailId));
            router.reload({ preserveState: true });
        } catch (e) { /* ignore */ }
    };

    const handleStarToggle = async (emailId: number) => {
        try {
            await axios.put(route('emailbox.email.star', emailId));
            router.reload({ preserveState: true });
        } catch (e) { /* ignore */ }
    };

    const handleDelete = async (emailId: number) => {
        try {
            await axios.delete(route('emailbox.email.destroy', emailId));
            setSelectedEmail(null);
            toast.success(t('Email deleted.'));
            router.reload();
        } catch (e) {
            toast.error(t('Failed to delete email.'));
        }
    };

    const handleSend = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!currentMailbox) return;
        try {
            await axios.post(route('emailbox.send'), {
                mailbox_id: currentMailbox.id,
                ...composeForm,
            });
            toast.success(t('Email sent.'));
            setShowCompose(false);
            setComposeForm({ to_email: '', subject: '', body: '' });
            router.reload();
        } catch (error) {
            toast.error(t('Failed to send email.'));
        }
    };

    const formatDate = (date: string) => {
        const d = new Date(date);
        const now = new Date();
        if (d.toDateString() === now.toDateString()) {
            return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        return d.toLocaleDateString([], { month: 'short', day: 'numeric' });
    };

    if (!currentMailbox && !showSettings) {
        return (
            <AuthenticatedLayout>
                <Head title={t('Email Box')} />
                <div className="p-6 space-y-6">
                    <h1 className="text-2xl font-bold text-stone-900">{t('Email Box')}</h1>
                    {mailboxes.length === 0 ? (
                        <Card>
                            <CardContent className="p-12 text-center">
                                <Mail className="w-12 h-12 text-stone-300 mx-auto mb-4" />
                                <p className="text-stone-500 mb-4">{t('No mailboxes configured yet.')}</p>
                                <Button onClick={() => setShowSettings(true)}>{t('Add Mailbox')}</Button>
                            </CardContent>
                        </Card>
                    ) : (
                        <p className="text-stone-500">{t('Select a mailbox from settings.')}</p>
                    )}
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout>
            <Head title={t('Email Box')} />
            <div className="flex h-[calc(100vh-64px)]">
                <div className="w-64 border-r bg-stone-50 p-4 flex flex-col">
                    <Button className="mb-4" onClick={() => setShowCompose(true)}>
                        <PenLine className="w-4 h-4 mr-2" />{t('Compose')}
                    </Button>

                    <div className="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2 mt-4">
                        {t('Folders')}
                    </div>
                    {folders.map((f) => (
                        <button key={f.name}
                            className={`flex items-center justify-between px-3 py-2 rounded-lg text-sm mb-1 ${activeFolder === f.name ? 'bg-indigo-100 text-indigo-700 font-medium' : 'text-stone-700 hover:bg-stone-100'}`}
                            onClick={() => setActiveFolder(f.name)}>
                            <span>{f.label}</span>
                            {f.count > 0 && <span className="text-xs bg-stone-200 px-1.5 py-0.5 rounded-full">{f.count}</span>}
                        </button>
                    ))}

                    <div className="mt-auto pt-4 border-t">
                        <div className="text-xs font-semibold text-stone-500 uppercase tracking-wider mb-2">
                            {t('Mailboxes')}
                        </div>
                        {mailboxes.map((mb) => (
                            <div key={mb.id} className="text-sm text-stone-600 px-3 py-1 truncate">
                                {mb.email}
                            </div>
                        ))}
                        <button className="text-sm text-indigo-600 px-3 py-1 mt-2 hover:underline"
                            onClick={() => setShowSettings(true)}>
                            <Settings className="w-3 h-3 inline mr-1" />{t('Settings')}
                        </button>
                    </div>
                </div>

                <div className="flex-1 flex overflow-hidden">
                    <div className="w-96 border-r flex flex-col">
                        <div className="p-3 border-b flex items-center justify-between">
                            <span className="font-semibold text-sm">{currentMailbox?.email}</span>
                            <Button size="sm" variant="ghost" onClick={handleFetch}>
                                <RefreshCw className="w-4 h-4" />
                            </Button>
                        </div>

                        <div className="flex-1 overflow-y-auto">
                            {(emails?.data ?? []).map((email) => (
                                <div key={email.id}
                                    className={`p-3 border-b cursor-pointer hover:bg-stone-50 transition ${selectedEmail?.id === email.id ? 'bg-indigo-50' : ''} ${!email.is_read ? 'bg-stone-50' : ''}`}
                                    onClick={() => { setSelectedEmail(email); if (!email.is_read) handleReadToggle(email.id); }}>
                                    <div className="flex items-center justify-between mb-1">
                                        <span className={`text-sm truncate ${!email.is_read ? 'font-bold' : ''}`}>
                                            {email.from_email}
                                        </span>
                                        <span className="text-xs text-stone-400">{formatDate(email.received_at)}</span>
                                    </div>
                                    <div className="flex items-center justify-between">
                                        <span className={`text-sm truncate ${!email.is_read ? 'font-semibold' : 'text-stone-500'}`}>
                                            {email.subject}
                                        </span>
                                        {email.is_starred && <Star className="w-3 h-3 text-yellow-500 flex-shrink-0" />}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="flex-1 overflow-y-auto p-6">
                        {selectedEmail ? (
                            <div className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <h2 className="text-xl font-bold">{selectedEmail.subject}</h2>
                                    <div className="flex gap-1">
                                        <Button size="sm" variant="ghost" onClick={() => handleStarToggle(selectedEmail.id)}>
                                            {selectedEmail.is_starred ? <Star className="w-4 h-4 text-yellow-500" /> : <StarOff className="w-4 h-4" />}
                                        </Button>
                                        <Button size="sm" variant="ghost" onClick={() => handleDelete(selectedEmail.id)}>
                                            <Trash2 className="w-4 h-4 text-red-500" />
                                        </Button>
                                    </div>
                                </div>
                                <div className="text-sm text-stone-500">
                                    <span className="font-medium">{t('From:')}</span> {selectedEmail.from_email}
                                    <span className="ml-4 text-xs">{new Date(selectedEmail.received_at).toLocaleString()}</span>
                                </div>
                                <div className="border-t pt-4 prose prose-sm max-w-none"
                                    dangerouslySetInnerHTML={{ __html: selectedEmail.body }} />
                            </div>
                        ) : (
                            <div className="flex items-center justify-center h-full text-stone-400">
                                <div className="text-center">
                                    <Mail className="w-16 h-16 mx-auto mb-4 opacity-30" />
                                    <p>{t('Select an email to read')}</p>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {showCompose && (
                <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
                    <Card className="w-full max-w-lg mx-4">
                        <CardContent className="p-6">
                            <h2 className="text-lg font-semibold mb-4">{t('Compose Email')}</h2>
                            <form onSubmit={handleSend} className="space-y-4">
                                <div>
                                    <label className="block text-sm font-medium mb-1">{t('To')}</label>
                                    <input type="email" className="w-full border rounded-lg px-3 py-2" required
                                        value={composeForm.to_email}
                                        onChange={(e) => setComposeForm({ ...composeForm, to_email: e.target.value })} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">{t('Subject')}</label>
                                    <input type="text" className="w-full border rounded-lg px-3 py-2" required
                                        value={composeForm.subject}
                                        onChange={(e) => setComposeForm({ ...composeForm, subject: e.target.value })} />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium mb-1">{t('Message')}</label>
                                    <textarea className="w-full border rounded-lg px-3 py-2 min-h-[200px]" required
                                        value={composeForm.body}
                                        onChange={(e) => setComposeForm({ ...composeForm, body: e.target.value })} />
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit">{t('Send')}</Button>
                                    <Button type="button" variant="outline" onClick={() => setShowCompose(false)}>
                                        {t('Cancel')}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            )}

            {showSettings && (
                <div className="fixed inset-0 bg-black/50 z-50 flex items-center justify-center">
                    <Card className="w-full max-w-lg mx-4">
                        <CardContent className="p-6">
                            <h2 className="text-lg font-semibold mb-4">{t('Mailbox Settings')}</h2>
                            <MailboxSettingsForm onClose={() => setShowSettings(false)} />
                        </CardContent>
                    </Card>
                </div>
            )}
        </AuthenticatedLayout>
    );
}

function MailboxSettingsForm({ onClose }: { onClose: () => void }) {
    const { t } = useTranslation();
    const { mailboxes } = usePage<EmailBoxIndexProps>().props;
    const [form, setForm] = useState({
        name: '',
        email: '',
        imap_host: '',
        imap_port: 993,
        imap_encryption: 'ssl',
        smtp_host: '',
        smtp_port: 587,
        smtp_encryption: 'tls',
        username: '',
        password: '',
    });

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            await axios.post(route('emailbox.mailbox.store'), form);
            toast.success(t('Mailbox created.'));
            onClose();
            window.location.reload();
        } catch (error) {
            toast.error(t('Failed to create mailbox.'));
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-4">
            <div>
                <label className="block text-sm font-medium mb-1">{t('Name')}</label>
                <input type="text" className="w-full border rounded-lg px-3 py-2" required
                    value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
            </div>
            <div className="grid grid-cols-2 gap-3">
                <div>
                    <label className="block text-sm font-medium mb-1">{t('Email')}</label>
                    <input type="email" className="w-full border rounded-lg px-3 py-2" required
                        value={form.email} onChange={(e) => setForm({ ...form, email: e.target.value })} />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">{t('Username')}</label>
                    <input type="text" className="w-full border rounded-lg px-3 py-2" required
                        value={form.username} onChange={(e) => setForm({ ...form, username: e.target.value })} />
                </div>
            </div>
            <div>
                <label className="block text-sm font-medium mb-1">{t('Password')}</label>
                <input type="password" className="w-full border rounded-lg px-3 py-2" required
                    value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
            </div>
            <div className="border-t pt-3">
                <h3 className="text-sm font-semibold mb-2">{t('IMAP Settings')}</h3>
                <div className="grid grid-cols-3 gap-3">
                    <div className="col-span-2">
                        <label className="block text-xs font-medium mb-1">{t('Host')}</label>
                        <input type="text" className="w-full border rounded-lg px-3 py-2 text-sm" required
                            value={form.imap_host} onChange={(e) => setForm({ ...form, imap_host: e.target.value })} />
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">{t('Port')}</label>
                        <input type="number" className="w-full border rounded-lg px-3 py-2 text-sm"
                            value={form.imap_port} onChange={(e) => setForm({ ...form, imap_port: parseInt(e.target.value) })} />
                    </div>
                </div>
            </div>
            <div className="border-t pt-3">
                <h3 className="text-sm font-semibold mb-2">{t('SMTP Settings')}</h3>
                <div className="grid grid-cols-3 gap-3">
                    <div className="col-span-2">
                        <label className="block text-xs font-medium mb-1">{t('Host')}</label>
                        <input type="text" className="w-full border rounded-lg px-3 py-2 text-sm" required
                            value={form.smtp_host} onChange={(e) => setForm({ ...form, smtp_host: e.target.value })} />
                    </div>
                    <div>
                        <label className="block text-xs font-medium mb-1">{t('Port')}</label>
                        <input type="number" className="w-full border rounded-lg px-3 py-2 text-sm"
                            value={form.smtp_port} onChange={(e) => setForm({ ...form, smtp_port: parseInt(e.target.value) })} />
                    </div>
                </div>
            </div>
            <div className="flex gap-2">
                <Button type="submit">{t('Save Mailbox')}</Button>
                <Button type="button" variant="outline" onClick={onClose}>{t('Cancel')}</Button>
            </div>
        </form>
    );
}
