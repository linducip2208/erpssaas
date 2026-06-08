import { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { toast } from 'sonner';
import axios from 'axios';

interface SettingsProps {
    settings: {
        whatsapp_base_url: string;
        whatsapp_api_key: string;
        whatsapp_phone_number_id: string;
    };
}

export default function Settings() {
    const { t } = useTranslation();
    const { settings } = usePage<SettingsProps>().props;
    const [form, setForm] = useState({
        whatsapp_base_url: '',
        whatsapp_api_key: '',
        whatsapp_phone_number_id: '',
    });
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (settings) {
            setForm({
                whatsapp_base_url: settings.whatsapp_base_url || '',
                whatsapp_api_key: settings.whatsapp_api_key || '',
                whatsapp_phone_number_id: settings.whatsapp_phone_number_id || '',
            });
        }
    }, [settings]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setSaving(true);
        try {
            await axios.post(route('whatsapp.settings.store'), form);
            toast.success(t('WhatsApp settings saved successfully.'));
        } catch (error) {
            toast.error(t('Failed to save settings.'));
        }
        setSaving(false);
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('WhatsApp Settings')} />
            <div className="p-6 space-y-6 max-w-2xl">
                <h1 className="text-2xl font-bold text-stone-900">{t('WhatsApp Settings')}</h1>

                <Card>
                    <CardContent className="p-6">
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <label className="block text-sm font-medium text-stone-700 mb-1">
                                    {t('Base URL')}
                                </label>
                                <input type="url" className="w-full border rounded-lg px-3 py-2"
                                    placeholder="https://graph.facebook.com/v18.0"
                                    value={form.whatsapp_base_url}
                                    onChange={(e) => setForm({ ...form, whatsapp_base_url: e.target.value })}
                                    required />
                                <p className="text-xs text-stone-500 mt-1">
                                    {t('WhatsApp Cloud API base URL (e.g., Facebook Graph API)')}
                                </p>
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-stone-700 mb-1">
                                    {t('API Key / Access Token')}
                                </label>
                                <input type="password" className="w-full border rounded-lg px-3 py-2"
                                    placeholder="EAA..."
                                    value={form.whatsapp_api_key}
                                    onChange={(e) => setForm({ ...form, whatsapp_api_key: e.target.value })}
                                    required />
                            </div>

                            <div>
                                <label className="block text-sm font-medium text-stone-700 mb-1">
                                    {t('Phone Number ID')}
                                </label>
                                <input type="text" className="w-full border rounded-lg px-3 py-2"
                                    placeholder="1234567890"
                                    value={form.whatsapp_phone_number_id}
                                    onChange={(e) => setForm({ ...form, whatsapp_phone_number_id: e.target.value })} />
                            </div>

                            <Button type="submit" disabled={saving}>
                                {saving ? t('Saving...') : t('Save Settings')}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
