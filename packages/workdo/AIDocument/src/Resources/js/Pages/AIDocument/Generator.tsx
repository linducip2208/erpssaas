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
import { toast } from 'sonner';
import { Play, Loader2, FileText, Copy, Check } from 'lucide-react';
import type { AIDocumentPrompt, AIDocumentGeneration } from './types';

interface GeneratorProps {
    auth: { user: { permissions: string[] } };
}

export default function Generator() {
    const { t } = useTranslation();
    const { auth } = usePage<GeneratorProps>().props;

    const [prompts, setPrompts] = useState<AIDocumentPrompt[]>([]);
    const [selectedPromptId, setSelectedPromptId] = useState<string>('');
    const [selectedPrompt, setSelectedPrompt] = useState<AIDocumentPrompt | null>(null);
    const [inputData, setInputData] = useState<Record<string, string>>({});
    const [modelOverride, setModelOverride] = useState('');
    const [generating, setGenerating] = useState(false);
    const [generation, setGeneration] = useState<AIDocumentGeneration | null>(null);
    const [copied, setCopied] = useState(false);

    useEffect(() => {
        fetch(route('ai-document.prompts.index'))
            .then((r) => r.json())
            .then((data) => setPrompts(data.prompts?.data || []))
            .catch(() => toast.error(t('Failed to load prompts')));
    }, []);

    useEffect(() => {
        if (selectedPromptId) {
            const prompt = prompts.find((p) => p.id === parseInt(selectedPromptId));
            setSelectedPrompt(prompt || null);
            setInputData({});
            setGeneration(null);
        }
    }, [selectedPromptId, prompts]);

    const placeholders = selectedPrompt ? extractPlaceholders(selectedPrompt.prompt_template) : [];

    function extractPlaceholders(template: string): string[] {
        const matches = template.match(/\{(\w+)\}/g) || [];
        return [...new Set(matches.map((m) => m.slice(1, -1)))];
    }

    const handleGenerate = async () => {
        if (!selectedPromptId) return;
        setGenerating(true);
        try {
            const res = await fetch(route('ai-document.generate'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
                body: JSON.stringify({
                    prompt_id: parseInt(selectedPromptId),
                    input_data: inputData,
                    model: modelOverride || undefined,
                }),
            });
            const data = await res.json();
            if (res.ok && data.success) {
                setGeneration(data.generation);
                toast.success(t('Document generated!'));
            } else {
                toast.error(data.error || t('Generation failed'));
            }
        } catch (e) {
            toast.error(t('Generation failed'));
        } finally {
            setGenerating(false);
        }
    };

    const handleCopy = () => {
        if (generation?.output_content) {
            navigator.clipboard.writeText(generation.output_content);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    };

    if (!auth.user?.permissions?.includes('manage-ai-document')) {
        return (
            <AuthenticatedLayout breadcrumbs={[{ label: t('AI Document'), url: '#' }]} pageTitle={t('Document Generator')}>
                <Card><CardContent className="p-6"><p>{t('Permission denied')}</p></CardContent></Card>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('AI Document'), url: route('ai-document.prompts.index') }, { label: t('Generator'), url: '#' }]} pageTitle={t('Document Generator')}>
            <Head title={t('Document Generator')} />

            <div className="space-y-6 max-w-3xl">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            {t('Generate Document')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <Label>{t('Select Prompt')}</Label>
                            <Select value={selectedPromptId} onValueChange={setSelectedPromptId}>
                                <SelectTrigger>
                                    <SelectValue placeholder={t('Choose a prompt template...')} />
                                </SelectTrigger>
                                <SelectContent>
                                    {prompts.filter((p) => p.is_active).map((p) => (
                                        <SelectItem key={p.id} value={String(p.id)}>{p.name}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {selectedPrompt && (
                            <div className="bg-gray-50 p-3 rounded-lg text-sm">
                                <p className="font-medium">{t('Template')}:</p>
                                <p className="text-muted-foreground">{selectedPrompt.prompt_template}</p>
                                <p className="text-xs mt-1">Temp: {selectedPrompt.temperature} | Max Tokens: {selectedPrompt.max_tokens}</p>
                            </div>
                        )}

                        {placeholders.map((ph) => (
                            <div key={ph}>
                                <Label>{ph}</Label>
                                <Input
                                    value={inputData[ph] || ''}
                                    onChange={(e) => setInputData({ ...inputData, [ph]: e.target.value })}
                                    placeholder={t('Enter value for {field}', { field: ph })}
                                />
                            </div>
                        ))}

                        <div>
                            <Label>{t('Model Override')} <span className="text-xs text-muted-foreground">({t('optional')})</span></Label>
                            <Input
                                value={modelOverride}
                                onChange={(e) => setModelOverride(e.target.value)}
                                placeholder={t('Leave blank to use default model')}
                            />
                        </div>

                        <Button onClick={handleGenerate} disabled={generating || !selectedPromptId} className="w-full">
                            {generating ? <><Loader2 className="h-4 w-4 mr-1 animate-spin" /> {t('Generating...')}</> : <><Play className="h-4 w-4 mr-1" /> {t('Generate Document')}</>}
                        </Button>
                    </CardContent>
                </Card>

                {generation && (
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>{t('Generated Output')}</CardTitle>
                            <Button variant="outline" size="sm" onClick={handleCopy}>
                                {copied ? <Check className="h-4 w-4 mr-1" /> : <Copy className="h-4 w-4 mr-1" />}
                                {copied ? t('Copied!') : t('Copy')}
                            </Button>
                        </CardHeader>
                        <CardContent>
                            <div className="bg-gray-50 p-4 rounded-lg whitespace-pre-wrap text-sm max-h-[60vh] overflow-y-auto">
                                {generation.output_content || t('No content')}
                            </div>
                            <div className="flex gap-4 text-xs text-muted-foreground mt-3">
                                {generation.model_used && <span>{t('Model')}: {generation.model_used}</span>}
                                <span>{t('Input Tokens')}: {generation.input_tokens}</span>
                                <span>{t('Output Tokens')}: {generation.output_tokens}</span>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
