import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { Save, Eye, EyeOff, Shield, Cpu } from 'lucide-react';
import type { AIDocumentProvider, AIDocumentSettings } from './types';

interface SettingsProps {
    auth: { user: { permissions: string[] } };
}

export default function Settings() {
    const { t } = useTranslation();
    const { auth } = usePage<SettingsProps>().props;

    const [loading, setLoading] = useState(false);
    const [providers, setProviders] = useState<Record<string, AIDocumentProvider>>({});
    const [showApiKey, setShowApiKey] = useState(false);
    const canEdit = auth.user?.permissions?.includes('manage-ai-document-settings');

    const [settings, setSettings] = useState<AIDocumentSettings>({
        ai_document_provider: '',
        ai_document_api_key: '',
        ai_document_base_url: '',
        ai_document_default_model: '',
    });

    useEffect(() => {
        fetch(route('ai-document.settings.index'))
            .then((r) => r.json())
            .then((data) => {
                setProviders(data.providers || {});
                setSettings({
                    ai_document_provider: data.settings?.ai_document_provider || '',
                    ai_document_api_key: data.settings?.ai_document_api_key || '',
                    ai_document_base_url: data.settings?.ai_document_base_url || '',
                    ai_document_default_model: data.settings?.ai_document_default_model || '',
                });
            })
            .catch(() => toast.error(t('Failed to load settings')));
    }, []);

    const handleChange = (field: string, value: string) => {
        setSettings((prev) => ({
            ...prev,
            [field]: value,
            ...(field === 'ai_document_provider' ? { ai_document_default_model: '' } : {}),
        }));
    };

    const handleSave = () => {
        setLoading(true);
        router.post(route('ai-document.settings.store'), { settings }, {
            preserveScroll: true,
            onSuccess: (page: any) => {
                setLoading(false);
                const flash = page.props.flash;
                if (flash?.success) toast.success(flash.success);
                else if (flash?.error) toast.error(flash.error);
            },
            onError: () => {
                setLoading(false);
                toast.error(t('Failed to save settings'));
            },
        });
    };

    const selectedProvider = providers[settings.ai_document_provider];
    const models = selectedProvider?.models || [];

    if (!canEdit) {
        return (
            <AuthenticatedLayout breadcrumbs={[{ label: t('AI Document'), url: route('ai-document.prompts.index') }, { label: t('Settings'), url: '#' }]} pageTitle={t('Settings')}>
                <Card><CardContent className="p-6"><p>{t('Permission denied')}</p></CardContent></Card>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('AI Document'), url: route('ai-document.prompts.index') }, { label: t('Settings'), url: '#' }]} pageTitle={t('AI Document Settings')}>
            <Head title={t('AI Document Settings')} />

            <div className="max-w-2xl space-y-6">
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2 text-lg">
                                <Cpu className="h-5 w-5" />
                                {t('AI Document Settings')}
                            </CardTitle>
                            <p className="text-sm text-muted-foreground mt-1">{t('Configure your AI provider for document generation. Bring your own key.')}</p>
                        </div>
                        <Button onClick={handleSave} disabled={loading} size="sm">
                            <Save className="h-4 w-4 mr-2" />
                            {loading ? t('Saving...') : t('Save')}
                        </Button>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {/* BYOK Info */}
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-3 flex gap-2 text-sm">
                            <Shield className="h-5 w-5 text-blue-600 flex-shrink-0 mt-0.5" />
                            <div>
                                <p className="font-medium text-blue-800">{t('Bring Your Own Key (BYOK)')}</p>
                                <p className="text-blue-700">{t('You control your own API key. Supports OpenAI, Anthropic, DeepSeek, Groq, and any OpenAI-compatible provider.')}</p>
                            </div>
                        </div>

                        <div>
                            <Label>{t('AI Provider')}</Label>
                            <Select value={settings.ai_document_provider} onValueChange={(v) => handleChange('ai_document_provider', v)}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Select provider')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(providers).map(([key, p]) => (
                                        <SelectItem key={key} value={key}>{p.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div>
                            <Label>{t('API Key')}</Label>
                            <div className="relative">
                                <Input
                                    type={showApiKey ? 'text' : 'password'}
                                    value={settings.ai_document_api_key}
                                    onChange={(e) => handleChange('ai_document_api_key', e.target.value)}
                                    placeholder={t('Enter your API key')}
                                    className="pr-10"
                                />
                                <Button type="button" variant="ghost" size="sm" className="absolute right-0 top-0 h-full px-3" onClick={() => setShowApiKey(!showApiKey)}>
                                    {showApiKey ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                </Button>
                            </div>
                        </div>

                        <div>
                            <Label>{t('Base URL')} <span className="text-xs text-muted-foreground">({t('optional')})</span></Label>
                            <Input
                                value={settings.ai_document_base_url}
                                onChange={(e) => handleChange('ai_document_base_url', e.target.value)}
                                placeholder={selectedProvider?.default_base_url || t('e.g., https://api.openai.com')}
                            />
                            <p className="text-xs text-muted-foreground mt-1">{t('Leave blank to use default URL for the selected provider.')}</p>
                        </div>

                        <div>
                            <Label>{t('Default Model')}</Label>
                            {models.length > 0 ? (
                                <Select value={settings.ai_document_default_model} onValueChange={(v) => handleChange('ai_document_default_model', v)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Select model')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {models.map((m) => (
                                            <SelectItem key={m} value={m}>{m}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            ) : (
                                <Input
                                    value={settings.ai_document_default_model}
                                    onChange={(e) => handleChange('ai_document_default_model', e.target.value)}
                                    placeholder={t('Enter model name')}
                                />
                            )}
                            {selectedProvider && models.length === 0 && (
                                <p className="text-xs text-muted-foreground mt-1">{t('Enter model name manually for custom/compatible providers.')}</p>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
