import { useState, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { toast } from 'sonner';
import { Plus, Edit, Trash2, Play, FileText, Loader2 } from 'lucide-react';
import type { AIDocumentPrompt } from './types';

interface AIDocumentIndexProps {
    auth: { user: { permissions: string[] } };
}

export default function Index() {
    const { t } = useTranslation();
    const { auth } = usePage<AIDocumentIndexProps>().props;

    const [prompts, setPrompts] = useState<AIDocumentPrompt[]>([]);
    const [loading, setLoading] = useState(true);
    const [showForm, setShowForm] = useState(false);
    const [editPrompt, setEditPrompt] = useState<AIDocumentPrompt | null>(null);
    const [saving, setSaving] = useState(false);

    const [generating, setGenerating] = useState<number | null>(null);
    const [showResult, setShowResult] = useState(false);
    const [resultContent, setResultContent] = useState('');
    const [resultPrompt, setResultPrompt] = useState<AIDocumentPrompt | null>(null);

    const [form, setForm] = useState({
        name: '',
        system_prompt: '',
        prompt_template: '',
        temperature: '0.7',
        max_tokens: '2000',
        is_active: true,
    });

    const [inputFields, setInputFields] = useState<Record<string, string>>({});
    const [modelOverride, setModelOverride] = useState('');

    const fetchPrompts = async () => {
        try {
            const res = await fetch(route('ai-document.prompts.index'));
            const data = await res.json();
            setPrompts(data.prompts?.data || []);
        } catch (e) {
            toast.error(t('Failed to load prompts'));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchPrompts();
    }, []);

    const resetForm = () => {
        setForm({ name: '', system_prompt: '', prompt_template: '', temperature: '0.7', max_tokens: '2000', is_active: true });
        setEditPrompt(null);
        setShowForm(false);
    };

    const openEdit = (prompt: AIDocumentPrompt) => {
        setForm({
            name: prompt.name,
            system_prompt: prompt.system_prompt || '',
            prompt_template: prompt.prompt_template,
            temperature: String(prompt.temperature),
            max_tokens: String(prompt.max_tokens),
            is_active: prompt.is_active,
        });
        setEditPrompt(prompt);
        setShowForm(true);
    };

    const handleSave = async () => {
        setSaving(true);
        try {
            const payload = {
                name: form.name,
                system_prompt: form.system_prompt || null,
                prompt_template: form.prompt_template,
                temperature: parseFloat(form.temperature),
                max_tokens: parseInt(form.max_tokens),
                is_active: form.is_active,
            };

            let res;
            if (editPrompt) {
                res = await fetch(route('ai-document.prompts.update', editPrompt.id), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
                    body: JSON.stringify(payload),
                });
            } else {
                res = await fetch(route('ai-document.prompts.store'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
                    body: JSON.stringify(payload),
                });
            }

            const data = await res.json();
            if (res.ok) {
                toast.success(data.message || t('Saved successfully'));
                resetForm();
                fetchPrompts();
            } else {
                toast.error(data.error || data.message || t('Save failed'));
            }
        } catch (e) {
            toast.error(t('Save failed'));
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (id: number) => {
        if (!confirm(t('Are you sure?'))) return;
        try {
            const res = await fetch(route('ai-document.prompts.destroy', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
            });
            const data = await res.json();
            if (res.ok) {
                toast.success(data.message);
                fetchPrompts();
            } else {
                toast.error(data.error || t('Delete failed'));
            }
        } catch (e) {
            toast.error(t('Delete failed'));
        }
    };

    const extractPlaceholders = (template: string): string[] => {
        const matches = template.match(/\{(\w+)\}/g) || [];
        return [...new Set(matches.map((m) => m.slice(1, -1)))];
    };

    const handleGenerate = async (prompt: AIDocumentPrompt) => {
        setGenerating(prompt.id);
        try {
            const res = await fetch(route('ai-document.generate'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
                body: JSON.stringify({
                    prompt_id: prompt.id,
                    input_data: inputFields,
                    model: modelOverride || undefined,
                }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                setResultContent(data.generation?.output_content || '');
                setResultPrompt(prompt);
                setShowResult(true);
                toast.success(t('Document generated!'));
            } else {
                toast.error(data.error || t('Generation failed'));
            }
        } catch (e) {
            toast.error(t('Generation failed'));
        } finally {
            setGenerating(null);
        }
    };

    const canManage = auth.user?.permissions?.includes('manage-ai-document');
    const canCreate = auth.user?.permissions?.includes('create-ai-document');
    const canEdit = auth.user?.permissions?.includes('edit-ai-document');
    const canDelete = auth.user?.permissions?.includes('delete-ai-document');

    if (!canManage) {
        return (
            <AuthenticatedLayout breadcrumbs={[{ label: t('AI Document'), url: '#' }]} pageTitle={t('AI Document')}>
                <Card><CardContent className="p-6"><p>{t('Permission denied')}</p></CardContent></Card>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('AI Document'), url: '#' }]} pageTitle={t('AI Document')}>
            <Head title={t('AI Document')} />

            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <h2 className="text-xl font-semibold">{t('Prompt Templates')}</h2>
                    {canCreate && (
                        <Button size="sm" onClick={() => { resetForm(); setShowForm(true); }}>
                            <Plus className="h-4 w-4 mr-1" /> {t('New Prompt')}
                        </Button>
                    )}
                </div>

                {/* Prompt Form */}
                {showForm && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{editPrompt ? t('Edit Prompt') : t('Create Prompt')}</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>{t('Name')}</Label>
                                <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder={t('e.g., Blog Post Generator')} />
                            </div>
                            <div>
                                <Label>{t('System Prompt')} <span className="text-muted-foreground text-xs">({t('optional')})</span></Label>
                                <Textarea value={form.system_prompt} onChange={(e) => setForm({ ...form, system_prompt: e.target.value })} placeholder={t('System prompt to set AI behavior...')} rows={3} />
                            </div>
                            <div>
                                <Label>{t('Prompt Template')} *</Label>
                                <Textarea value={form.prompt_template} onChange={(e) => setForm({ ...form, prompt_template: e.target.value })} placeholder={t('Write a {document_type} about {topic}...')} rows={4} />
                                <p className="text-xs text-muted-foreground mt-1">{t('Use {placeholder} for dynamic input fields.')}</p>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>{t('Temperature')} (0-2)</Label>
                                    <Input type="number" min="0" max="2" step="0.1" value={form.temperature} onChange={(e) => setForm({ ...form, temperature: e.target.value })} />
                                </div>
                                <div>
                                    <Label>{t('Max Tokens')}</Label>
                                    <Input type="number" min="1" max="32000" value={form.max_tokens} onChange={(e) => setForm({ ...form, max_tokens: e.target.value })} />
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <Button onClick={handleSave} disabled={saving}>
                                    {saving && <Loader2 className="h-4 w-4 mr-1 animate-spin" />}
                                    {t('Save')}
                                </Button>
                                <Button variant="outline" onClick={resetForm}>{t('Cancel')}</Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Prompts List */}
                {loading ? (
                    <Card><CardContent className="p-6 text-center text-muted-foreground">{t('Loading...')}</CardContent></Card>
                ) : prompts.length === 0 ? (
                    <Card><CardContent className="p-6 text-center text-muted-foreground">{t('No prompts yet. Create one to start generating documents.')}</CardContent></Card>
                ) : (
                    <div className="grid gap-4">
                        {prompts.map((prompt) => {
                            const placeholders = extractPlaceholders(prompt.prompt_template);
                            return (
                                <Card key={prompt.id}>
                                    <CardContent className="p-4">
                                        <div className="flex justify-between items-start mb-3">
                                            <div>
                                                <h3 className="font-semibold flex items-center gap-2">
                                                    <FileText className="h-4 w-4 text-primary" />
                                                    {prompt.name}
                                                    {!prompt.is_active && <span className="text-xs bg-gray-200 px-1.5 py-0.5 rounded">{t('Inactive')}</span>}
                                                </h3>
                                                <p className="text-xs text-muted-foreground mt-1">{prompt.prompt_template.substring(0, 100)}...</p>
                                                <div className="flex gap-3 text-xs text-muted-foreground mt-1">
                                                    <span>{t('Temp')}: {prompt.temperature}</span>
                                                    <span>{t('Tokens')}: {prompt.max_tokens}</span>
                                                </div>
                                            </div>
                                            <div className="flex gap-1">
                                                {canEdit && <Button variant="ghost" size="sm" onClick={() => openEdit(prompt)}><Edit className="h-4 w-4" /></Button>}
                                                {canDelete && <Button variant="ghost" size="sm" onClick={() => handleDelete(prompt.id)}><Trash2 className="h-4 w-4 text-red-500" /></Button>}
                                            </div>
                                        </div>

                                        {/* Dynamic Inputs */}
                                        {placeholders.length > 0 && (
                                            <div className="border-t pt-3 space-y-2 mb-3">
                                                <p className="text-xs font-medium text-muted-foreground">{t('Fill in the fields to generate:')}</p>
                                                {placeholders.map((ph) => (
                                                    <div key={ph}>
                                                        <Label className="text-xs">{ph}</Label>
                                                        <Input
                                                            value={inputFields[ph] || ''}
                                                            onChange={(e) => setInputFields({ ...inputFields, [ph]: e.target.value })}
                                                            placeholder={t('Enter {field}', { field: ph })}
                                                            className="h-8 text-sm"
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        )}

                                        <div className="flex gap-2 items-center">
                                            <Button
                                                size="sm"
                                                onClick={() => handleGenerate(prompt)}
                                                disabled={generating === prompt.id}
                                                className="bg-primary"
                                            >
                                                {generating === prompt.id ? (
                                                    <><Loader2 className="h-4 w-4 mr-1 animate-spin" /> {t('Generating...')}</>
                                                ) : (
                                                    <><Play className="h-4 w-4 mr-1" /> {t('Generate')}</>
                                                )}
                                            </Button>
                                            <Input
                                                value={modelOverride}
                                                onChange={(e) => setModelOverride(e.target.value)}
                                                placeholder={t('Model override (optional)')}
                                                className="h-8 text-sm max-w-xs"
                                            />
                                        </div>
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>
                )}
            </div>

            {/* Result Dialog */}
            <Dialog open={showResult} onOpenChange={setShowResult}>
                <DialogContent className="max-w-2xl max-h-[80vh] overflow-y-auto">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            {t('Generated Document')}
                            {resultPrompt && <span className="text-sm font-normal text-muted-foreground">- {resultPrompt.name}</span>}
                        </DialogTitle>
                    </DialogHeader>
                    <div className="prose prose-sm max-w-none whitespace-pre-wrap bg-gray-50 p-4 rounded-lg mt-2 text-sm">
                        {resultContent || t('No content generated.')}
                    </div>
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
