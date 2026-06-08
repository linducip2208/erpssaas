import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Send, ArrowLeft } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface ComposeProps {
    mailboxes: Array<{ id: number; name: string; email: string }>;
}

export default function Compose() {
    const { t } = useTranslation();
    const { mailboxes } = usePage<ComposeProps>().props;
    const [form, setForm] = useState({
        mailbox_id: mailboxes[0]?.id || '',
        to_email: '',
        subject: '',
        body: '',
    });

    const handleSend = async (e: React.FormEvent) => {
        e.preventDefault();
        try {
            await axios.post(route('emailbox.send'), form);
            toast.success(t('Email sent successfully.'));
            router.visit(route('emailbox.index'));
        } catch (error) {
            toast.error(t('Failed to send email.'));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Compose Email')} />
            <div className="p-6 max-w-3xl space-y-6">
                <div className="flex items-center gap-3">
                    <Button variant="ghost" onClick={() => router.visit(route('emailbox.index'))}>
                        <ArrowLeft className="w-4 h-4" />
                    </Button>
                    <h1 className="text-2xl font-bold text-stone-900">{t('Compose Email')}</h1>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <form onSubmit={handleSend} className="space-y-4">
                            {mailboxes.length > 1 && (
                                <div>
                                    <label className="block text-sm font-medium text-stone-700 mb-1">{t('From')}</label>
                                    <select className="w-full border rounded-lg px-3 py-2"
                                        value={form.mailbox_id}
                                        onChange={(e) => setForm({ ...form, mailbox_id: e.target.value })}>
                                        {mailboxes.map(mb => (
                                            <option key={mb.id} value={mb.id}>{mb.name} ({mb.email})</option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            <div>
                                <label className="block text-sm font-medium text-stone-700 mb-1">{t('To')}</label>
                                <input type="email" className="w-full border rounded-lg px-3 py-2"
                                    placeholder="recipient@example.com"
                                    value={form.to_email}
                                    onChange={(e) => setForm({ ...form, to_email: e.target.value })}
                                    required />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-stone-700 mb-1">{t('Subject')}</label>
                                <input type="text" className="w-full border rounded-lg px-3 py-2"
                                    placeholder={t('Email subject...')}
                                    value={form.subject}
                                    onChange={(e) => setForm({ ...form, subject: e.target.value })}
                                    required />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-stone-700 mb-1">{t('Message')}</label>
                                <textarea className="w-full border rounded-lg px-3 py-2 min-h-[300px]"
                                    placeholder={t('Write your message...')}
                                    value={form.body}
                                    onChange={(e) => setForm({ ...form, body: e.target.value })}
                                    required />
                            </div>

                            <Button type="submit">
                                <Send className="w-4 h-4 mr-2" />
                                {t('Send Email')}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
