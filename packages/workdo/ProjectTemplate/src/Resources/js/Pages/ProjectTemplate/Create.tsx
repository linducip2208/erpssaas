import { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { toast } from 'sonner';
import { Plus, Trash2, GripVertical, Loader2 } from 'lucide-react';
import type { ProjectTemplate, ProjectTemplateTask, ProjectTemplateMilestone } from './types';

interface CreateProps {
    onSuccess: () => void;
    template: ProjectTemplate | null;
}

export default function Create({ onSuccess, template }: CreateProps) {
    const { t } = useTranslation();
    const isEdit = !!template;

    const [saving, setSaving] = useState(false);
    const [form, setForm] = useState({
        name: template?.name || '',
        description: template?.description || '',
        is_active: template?.is_active ?? true,
    });

    const [tasks, setTasks] = useState<Partial<ProjectTemplateTask>[]>(
        template?.tasks?.map((t) => ({ ...t })) || [{ title: '', description: '', priority: 'Low', order: 1, estimated_hours: undefined }]
    );

    const [milestones, setMilestones] = useState<Partial<ProjectTemplateMilestone>[]>(
        template?.milestones?.map((m) => ({ ...m })) || [{ name: '', description: '', order: 1, due_days_offset: 7 }]
    );

    const addTask = () => {
        setTasks([...tasks, { title: '', description: '', priority: 'Low', order: tasks.length + 1, estimated_hours: undefined }]);
    };

    const removeTask = (index: number) => {
        setTasks(tasks.filter((_, i) => i !== index));
    };

    const updateTask = (index: number, field: string, value: any) => {
        setTasks(tasks.map((t, i) => (i === index ? { ...t, [field]: value } : t)));
    };

    const addMilestone = () => {
        setMilestones([...milestones, { name: '', description: '', order: milestones.length + 1, due_days_offset: 7 }]);
    };

    const removeMilestone = (index: number) => {
        setMilestones(milestones.filter((_, i) => i !== index));
    };

    const updateMilestone = (index: number, field: string, value: any) => {
        setMilestones(milestones.map((m, i) => (i === index ? { ...m, [field]: value } : m)));
    };

    const handleSave = async () => {
        setSaving(true);
        try {
            const payload = {
                ...form,
                tasks: tasks.filter((t) => t.title).map((t, i) => ({ ...t, order: i + 1 })),
                milestones: milestones.filter((m) => m.name).map((m, i) => ({ ...m, order: i + 1 })),
            };

            let res;
            if (isEdit && template) {
                res = await fetch(route('project-template.update', template.id), {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
                    body: JSON.stringify({ name: form.name, description: form.description, is_active: form.is_active }),
                });
            } else {
                res = await fetch(route('project-template.store'), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
                    body: JSON.stringify(payload),
                });
            }

            const data = await res.json();
            if (res.ok) {
                toast.success(data.message || t('Saved successfully'));
                onSuccess();
            } else {
                toast.error(data.error || t('Save failed'));
            }
        } catch (e) {
            toast.error(t('Save failed'));
        } finally {
            setSaving(false);
        }
    };

    return (
        <DialogContent className="max-w-2xl max-h-[85vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{isEdit ? t('Edit Template') : t('Create Template')}</DialogTitle>
            </DialogHeader>

            <div className="space-y-6">
                <div className="space-y-3">
                    <div>
                        <Label>{t('Template Name')} *</Label>
                        <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} placeholder={t('e.g., Website Development')} />
                    </div>
                    <div>
                        <Label>{t('Description')}</Label>
                        <Textarea value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} placeholder={t('Describe this template...')} rows={2} />
                    </div>
                </div>

                {/* Tasks Section */}
                <div className="border rounded-lg p-4">
                    <div className="flex justify-between items-center mb-3">
                        <Label className="font-semibold">{t('Tasks')}</Label>
                        <Button variant="outline" size="sm" onClick={addTask}>
                            <Plus className="h-3 w-3 mr-1" /> {t('Add Task')}
                        </Button>
                    </div>
                    <div className="space-y-3">
                        {tasks.map((task, index) => (
                            <div key={index} className="flex gap-2 items-start border rounded-lg p-3 bg-gray-50">
                                <GripVertical className="h-4 w-4 text-gray-400 mt-3 flex-shrink-0" />
                                <div className="flex-1 space-y-2">
                                    <Input
                                        value={task.title || ''}
                                        onChange={(e) => updateTask(index, 'title', e.target.value)}
                                        placeholder={t('Task title')}
                                        className="h-8 text-sm"
                                    />
                                    <Textarea
                                        value={task.description || ''}
                                        onChange={(e) => updateTask(index, 'description', e.target.value)}
                                        placeholder={t('Task description (optional)')}
                                        rows={2}
                                        className="text-sm"
                                    />
                                    <div className="flex gap-2">
                                        <Select value={task.priority || 'Low'} onValueChange={(v) => updateTask(index, 'priority', v)}>
                                            <SelectTrigger className="h-8 text-sm w-28">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="Low">{t('Low')}</SelectItem>
                                                <SelectItem value="Medium">{t('Medium')}</SelectItem>
                                                <SelectItem value="High">{t('High')}</SelectItem>
                                                <SelectItem value="Urgent">{t('Urgent')}</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <Input
                                            type="number"
                                            value={task.estimated_hours || ''}
                                            onChange={(e) => updateTask(index, 'estimated_hours', e.target.value ? parseFloat(e.target.value) : undefined)}
                                            placeholder={t('Est. hours')}
                                            className="h-8 text-sm w-24"
                                            min="0"
                                            step="0.5"
                                        />
                                    </div>
                                </div>
                                <Button variant="ghost" size="sm" onClick={() => removeTask(index)} className="flex-shrink-0">
                                    <Trash2 className="h-4 w-4 text-red-500" />
                                </Button>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Milestones Section */}
                <div className="border rounded-lg p-4">
                    <div className="flex justify-between items-center mb-3">
                        <Label className="font-semibold">{t('Milestones')}</Label>
                        <Button variant="outline" size="sm" onClick={addMilestone}>
                            <Plus className="h-3 w-3 mr-1" /> {t('Add Milestone')}
                        </Button>
                    </div>
                    <div className="space-y-3">
                        {milestones.map((milestone, index) => (
                            <div key={index} className="flex gap-2 items-start border rounded-lg p-3 bg-gray-50">
                                <GripVertical className="h-4 w-4 text-gray-400 mt-3 flex-shrink-0" />
                                <div className="flex-1 space-y-2">
                                    <Input
                                        value={milestone.name || ''}
                                        onChange={(e) => updateMilestone(index, 'name', e.target.value)}
                                        placeholder={t('Milestone name')}
                                        className="h-8 text-sm"
                                    />
                                    <Textarea
                                        value={milestone.description || ''}
                                        onChange={(e) => updateMilestone(index, 'description', e.target.value)}
                                        placeholder={t('Milestone description (optional)')}
                                        rows={2}
                                        className="text-sm"
                                    />
                                    <Input
                                        type="number"
                                        value={milestone.due_days_offset || 7}
                                        onChange={(e) => updateMilestone(index, 'due_days_offset', parseInt(e.target.value) || 7)}
                                        placeholder={t('Days from start')}
                                        className="h-8 text-sm w-36"
                                        min="0"
                                    />
                                </div>
                                <Button variant="ghost" size="sm" onClick={() => removeMilestone(index)} className="flex-shrink-0">
                                    <Trash2 className="h-4 w-4 text-red-500" />
                                </Button>
                            </div>
                        ))}
                    </div>
                </div>

                <div className="flex gap-2 justify-end">
                    <Button variant="outline" onClick={onSuccess}>{t('Cancel')}</Button>
                    <Button onClick={handleSave} disabled={saving || !form.name}>
                        {saving && <Loader2 className="h-4 w-4 mr-1 animate-spin" />}
                        {isEdit ? t('Update') : t('Create')}
                    </Button>
                </div>
            </div>
        </DialogContent>
    );
}
