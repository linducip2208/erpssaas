import { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Plus, Edit, Trash2, RefreshCw } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface MailboxSettingsProps {
    mailboxes: Array<{
        id: number;
        name: string;
        email: string;
        imap_host: string;
        imap_port: number;
        smtp_host: string;
        smtp_port: number;
        is_active: boolean;
    }>;
}

export default function Settings() {
    const { t } = useTranslation();
    const { mailboxes } = usePage<MailboxSettingsProps>().props;
    const [showForm, setShowForm] = useState(false);
    const [editData, setEditData] = useState<any>(null);
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
            if (editData) {
                await axios.put(route('emailbox.mailbox.update', editData.id), form);
                toast.success(t('Mailbox updated.'));
            } else {
                await axios.post(route('emailbox.mailbox.store'), form);
                toast.success(t('Mailbox created.'));
            }
            setShowForm(false);
            setEditData(null);
            resetForm();
            window.location.reload();
        } catch (error) {
            toast.error(t('Failed to save mailbox.'));
        }
    };

    const handleEdit = (mb: any) => {
        setEditData(mb);
        setForm({
            name: mb.name,
            email: mb.email,
            imap_host: mb.imap_host,
            imap_port: mb.imap_port,
            imap_encryption: mb.imap_encryption || 'ssl',
            smtp_host: mb.smtp_host,
            smtp_port: mb.smtp_port,
            smtp_encryption: mb.smtp_encryption || 'tls',
            username: mb.username,
            password: '',
        });
        setShowForm(true);
    };

    const handleDelete = async (id: number) => {
        if (confirm(t('Are you sure? This will delete all associated emails.'))) {
            try {
                await axios.delete(route('emailbox.mailbox.destroy', id));
                toast.success(t('Mailbox deleted.'));
                window.location.reload();
            } catch (error) {
                toast.error(t('Failed to delete mailbox.'));
            }
        }
    };

    const handleFetch = async (id: number) => {
        try {
            await axios.post(route('emailbox.fetch', id));
            toast.success(t('Emails fetched.'));
            window.location.reload();
        } catch (error) {
            toast.error(t('Failed to fetch emails.'));
        }
    };

    const resetForm = () => {
        setForm({
            name: '', email: '', imap_host: '', imap_port: 993, imap_encryption: 'ssl',
            smtp_host: '', smtp_port: 587, smtp_encryption: 'tls', username: '', password: '',
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Email Box Settings')} />
            <div className="p-6 space-y-6 max-w-3xl">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold text-stone-900">{t('Email Box Settings')}</h1>
                    <Button onClick={() => { setEditData(null); resetForm(); setShowForm(true); }}>
                        <Plus className="w-4 h-4 mr-2" />{t('Add Mailbox')}
                    </Button>
                </div>

                {showForm && (
                    <Card>
                        <CardContent className="p-6">
                            <h2 className="text-lg font-semibold mb-4">{editData ? t('Edit Mailbox') : t('Add Mailbox')}</h2>
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-sm font-medium mb-1">{t('Name')}</label>
                                        <input type="text" className="w-full border rounded-lg px-3 py-2" required
                                            value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
                                    </div>
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
                                    <div>
                                        <label className="block text-sm font-medium mb-1">{t('Password')}</label>
                                        <input type="password" className="w-full border rounded-lg px-3 py-2"
                                            required={!editData}
                                            value={form.password} onChange={(e) => setForm({ ...form, password: e.target.value })} />
                                    </div>
                                </div>

                                <div className="border-t pt-3">
                                    <h3 className="text-sm font-semibold mb-2">{t('IMAP')}</h3>
                                    <div className="grid grid-cols-3 gap-3">
                                        <div className="col-span-2">
                                            <input type="text" className="w-full border rounded-lg px-3 py-2 text-sm"
                                                placeholder="imap.gmail.com"
                                                value={form.imap_host} onChange={(e) => setForm({ ...form, imap_host: e.target.value })} required />
                                        </div>
                                        <div>
                                            <input type="number" className="w-full border rounded-lg px-3 py-2 text-sm"
                                                value={form.imap_port} onChange={(e) => setForm({ ...form, imap_port: parseInt(e.target.value) })} />
                                        </div>
                                    </div>
                                </div>

                                <div className="border-t pt-3">
                                    <h3 className="text-sm font-semibold mb-2">{t('SMTP')}</h3>
                                    <div className="grid grid-cols-3 gap-3">
                                        <div className="col-span-2">
                                            <input type="text" className="w-full border rounded-lg px-3 py-2 text-sm"
                                                placeholder="smtp.gmail.com"
                                                value={form.smtp_host} onChange={(e) => setForm({ ...form, smtp_host: e.target.value })} required />
                                        </div>
                                        <div>
                                            <input type="number" className="w-full border rounded-lg px-3 py-2 text-sm"
                                                value={form.smtp_port} onChange={(e) => setForm({ ...form, smtp_port: parseInt(e.target.value) })} />
                                        </div>
                                    </div>
                                </div>

                                <div className="flex gap-2">
                                    <Button type="submit">{editData ? t('Update') : t('Save')}</Button>
                                    <Button type="button" variant="outline" onClick={() => setShowForm(false)}>{t('Cancel')}</Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <div className="space-y-4">
                    {mailboxes.map((mb) => (
                        <Card key={mb.id}>
                            <CardContent className="p-4">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <h3 className="font-semibold">{mb.name}</h3>
                                        <p className="text-sm text-stone-500">{mb.email}</p>
                                        <p className="text-xs text-stone-400">
                                            IMAP: {mb.imap_host}:{mb.imap_port} | SMTP: {mb.smtp_host}:{mb.smtp_port}
                                        </p>
                                    </div>
                                    <div className="flex gap-1">
                                        <Button size="sm" variant="ghost" onClick={() => handleFetch(mb.id)}>
                                            <RefreshCw className="w-4 h-4" />
                                        </Button>
                                        <Button size="sm" variant="ghost" onClick={() => handleEdit(mb)}>
                                            <Edit className="w-4 h-4" />
                                        </Button>
                                        <Button size="sm" variant="ghost" onClick={() => handleDelete(mb.id)}>
                                            <Trash2 className="w-4 h-4 text-red-500" />
                                        </Button>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}

                    {mailboxes.length === 0 && (
                        <p className="text-stone-500 text-center py-8">{t('No mailboxes configured.')}</p>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
