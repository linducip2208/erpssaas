import { useState, useEffect } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Plus, Trash2, GripVertical, Save } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface WorkflowBuilderProps {
    workflow: {
        id: number;
        name: string;
        description: string;
        trigger_type: string;
        trigger_module: string;
        is_active: boolean;
        actions: Array<{
            id: number;
            action_type: string;
            action_module: string;
            config: Record<string, any>;
            order: number;
        }>;
    } | null;
}

const TRIGGER_TYPES = [
    { value: 'record_created', label: 'Record Created' },
    { value: 'record_updated', label: 'Record Updated' },
    { value: 'record_deleted', label: 'Record Deleted' },
    { value: 'scheduled', label: 'Scheduled' },
    { value: 'webhook', label: 'Webhook' },
];

const ACTION_TYPES = [
    { value: 'send_email', label: 'Send Email' },
    { value: 'send_whatsapp', label: 'Send WhatsApp' },
    { value: 'create_record', label: 'Create Record' },
    { value: 'update_record', label: 'Update Record' },
    { value: 'webhook', label: 'Call Webhook' },
    { value: 'notification', label: 'Send Notification' },
];

export default function Builder() {
    const { t } = useTranslation();
    const { workflow } = usePage<WorkflowBuilderProps>().props;
    const isNew = !workflow;

    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [triggerType, setTriggerType] = useState('record_created');
    const [triggerModule, setTriggerModule] = useState('');
    const [actions, setActions] = useState<Array<{
        id?: number;
        action_type: string;
        action_module: string;
        config: Record<string, any>;
        order: number;
    }>>([]);

    useEffect(() => {
        if (workflow) {
            setName(workflow.name);
            setDescription(workflow.description || '');
            setTriggerType(workflow.trigger_type);
            setTriggerModule(workflow.trigger_module);
            setActions(workflow.actions.map(a => ({
                id: a.id,
                action_type: a.action_type,
                action_module: a.action_module,
                config: a.config || {},
                order: a.order,
            })));
        }
    }, [workflow]);

    const addAction = () => {
        setActions([...actions, {
            action_type: 'send_email',
            action_module: '',
            config: {},
            order: actions.length,
        }]);
    };

    const removeAction = (index: number) => {
        setActions(actions.filter((_, i) => i !== index));
    };

    const updateAction = (index: number, field: string, value: any) => {
        const updated = [...actions];
        (updated[index] as any)[field] = value;
        setActions(updated);
    };

    const handleSave = async () => {
        if (!name.trim()) {
            toast.error(t('Workflow name is required.'));
            return;
        }

        try {
            const payload = {
                name,
                description,
                trigger_type: triggerType,
                trigger_module: triggerModule,
                actions: actions.map((a, i) => ({
                    action_type: a.action_type,
                    action_module: a.action_module,
                    config: a.config,
                    order: i,
                })),
            };

            if (isNew) {
                await axios.post(route('workflow.store'), payload);
                toast.success(t('Workflow created.'));
            } else {
                await axios.put(route('workflow.update', workflow!.id), payload);
                toast.success(t('Workflow updated.'));
            }
            router.visit(route('workflow.index'));
        } catch (error) {
            toast.error(t('Failed to save workflow.'));
        }
    };

    return (
        <AuthenticatedLayout>
            <Head title={isNew ? t('Create Workflow') : t('Edit Workflow')} />
            <div className="p-6 space-y-6 max-w-3xl">
                <h1 className="text-2xl font-bold text-stone-900">
                    {isNew ? t('Create Workflow') : t('Edit Workflow')}
                </h1>

                <Card>
                    <CardContent className="p-6 space-y-4">
                        <h2 className="font-semibold text-lg">{t('Workflow Details')}</h2>
                        <div className="grid grid-cols-2 gap-4">
                            <div className="col-span-2">
                                <label className="block text-sm font-medium text-stone-700 mb-1">{t('Name')}</label>
                                <input type="text" className="w-full border rounded-lg px-3 py-2" value={name}
                                    onChange={(e) => setName(e.target.value)} required />
                            </div>
                            <div className="col-span-2">
                                <label className="block text-sm font-medium text-stone-700 mb-1">{t('Description')}</label>
                                <textarea className="w-full border rounded-lg px-3 py-2" value={description}
                                    onChange={(e) => setDescription(e.target.value)} rows={2} />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-stone-700 mb-1">{t('Trigger Type')}</label>
                                <select className="w-full border rounded-lg px-3 py-2" value={triggerType}
                                    onChange={(e) => setTriggerType(e.target.value)}>
                                    {TRIGGER_TYPES.map(t => (
                                        <option key={t.value} value={t.value}>{t.label}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-stone-700 mb-1">{t('Trigger Module')}</label>
                                <input type="text" className="w-full border rounded-lg px-3 py-2"
                                    placeholder="e.g. Lead, Invoice"
                                    value={triggerModule}
                                    onChange={(e) => setTriggerModule(e.target.value)} />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent className="p-6 space-y-4">
                        <div className="flex justify-between items-center">
                            <h2 className="font-semibold text-lg">{t('Actions')}</h2>
                            <Button variant="outline" size="sm" onClick={addAction}>
                                <Plus className="w-4 h-4 mr-1" />{t('Add Action')}
                            </Button>
                        </div>

                        {actions.length === 0 && (
                            <p className="text-stone-500 text-sm py-4 text-center">
                                {t('No actions yet. Add an action to define what happens when this workflow triggers.')}
                            </p>
                        )}

                        {actions.map((action, index) => (
                            <Card key={index} className="border border-stone-200">
                                <CardContent className="p-4 space-y-3">
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-2">
                                            <GripVertical className="w-4 h-4 text-stone-400" />
                                            <span className="font-medium text-sm">{t('Action')} {index + 1}</span>
                                        </div>
                                        <Button variant="ghost" size="sm" onClick={() => removeAction(index)}>
                                            <Trash2 className="w-4 h-4 text-red-500" />
                                        </Button>
                                    </div>
                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-xs font-medium text-stone-600 mb-1">{t('Type')}</label>
                                            <select className="w-full border rounded-lg px-3 py-1.5 text-sm"
                                                value={action.action_type}
                                                onChange={(e) => updateAction(index, 'action_type', e.target.value)}>
                                                {ACTION_TYPES.map(a => (
                                                    <option key={a.value} value={a.value}>{a.label}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <label className="block text-xs font-medium text-stone-600 mb-1">{t('Module')}</label>
                                            <input type="text" className="w-full border rounded-lg px-3 py-1.5 text-sm"
                                                value={action.action_module}
                                                onChange={(e) => updateAction(index, 'action_module', e.target.value)} />
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </CardContent>
                </Card>

                <div className="flex gap-2">
                    <Button onClick={handleSave}>
                        <Save className="w-4 h-4 mr-2" />{t('Save Workflow')}
                    </Button>
                    <Button variant="outline" onClick={() => router.visit(route('workflow.index'))}>
                        {t('Cancel')}
                    </Button>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
