import { useState, useEffect, useCallback } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
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
import { Plus, Edit, Trash2, Copy, Play, FileText, LayoutTemplate, List, Milestone, Loader2 } from 'lucide-react';
import Create from './Create';
import type { ProjectTemplate } from './types';

interface IndexProps {
    auth: { user: { permissions: string[] } };
}

export default function Index() {
    const { t } = useTranslation();
    const { auth } = usePage<IndexProps>().props;

    const [templates, setTemplates] = useState<ProjectTemplate[]>([]);
    const [loading, setLoading] = useState(true);
    const [showCreate, setShowCreate] = useState(false);
    const [editTemplate, setEditTemplate] = useState<ProjectTemplate | null>(null);
    const [showEdit, setShowEdit] = useState(false);
    const [showCreateProject, setShowCreateProject] = useState(false);
    const [selectedTemplate, setSelectedTemplate] = useState<ProjectTemplate | null>(null);
    const [creatingProject, setCreatingProject] = useState(false);
    const [projectForm, setProjectForm] = useState({ name: '', description: '', start_date: '', end_date: '', budget: '' });

    const fetchTemplates = async () => {
        try {
            const res = await fetch(route('project-template.index'));
            const data = await res.json();
            setTemplates(data.templates?.data || []);
        } catch (e) {
            toast.error(t('Failed to load templates'));
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchTemplates();
    }, []);

    const handleDelete = async (id: number) => {
        if (!confirm(t('Are you sure?'))) return;
        try {
            const res = await fetch(route('project-template.destroy', id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
            });
            const data = await res.json();
            if (res.ok) {
                toast.success(data.message);
                fetchTemplates();
            } else {
                toast.error(data.error || t('Delete failed'));
            }
        } catch (e) {
            toast.error(t('Delete failed'));
        }
    };

    const handleDuplicate = async (id: number) => {
        try {
            const res = await fetch(route('project-template.duplicate', id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
            });
            const data = await res.json();
            if (res.ok) {
                toast.success(data.message);
                fetchTemplates();
            } else {
                toast.error(data.error || t('Duplicate failed'));
            }
        } catch (e) {
            toast.error(t('Duplicate failed'));
        }
    };

    const openCreateProject = (template: ProjectTemplate) => {
        setSelectedTemplate(template);
        setProjectForm({
            name: template.name,
            description: template.description || '',
            start_date: '',
            end_date: '',
            budget: '',
        });
        setShowCreateProject(true);
    };

    const handleCreateProject = async () => {
        if (!selectedTemplate) return;
        setCreatingProject(true);
        try {
            const res = await fetch(route('project-template.create-project', selectedTemplate.id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content },
                body: JSON.stringify(projectForm),
            });
            const data = await res.json();
            if (res.ok) {
                toast.success(data.message);
                setShowCreateProject(false);
                router.visit(route('project.show', data.project?.id));
            } else {
                toast.error(data.error || t('Failed to create project'));
            }
        } catch (e) {
            toast.error(t('Failed to create project'));
        } finally {
            setCreatingProject(false);
        }
    };

    const canManage = auth.user?.permissions?.includes('manage-project-template');
    const canCreate = auth.user?.permissions?.includes('create-project-template');
    const canEdit = auth.user?.permissions?.includes('edit-project-template');
    const canDelete = auth.user?.permissions?.includes('delete-project-template');
    const canDuplicate = auth.user?.permissions?.includes('duplicate-project-template');

    if (!canManage) {
        return (
            <AuthenticatedLayout breadcrumbs={[{ label: t('Project Templates'), url: '#' }]} pageTitle={t('Project Templates')}>
                <Card><CardContent className="p-6"><p>{t('Permission denied')}</p></CardContent></Card>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('Project'), url: route('project.index') }, { label: t('Templates'), url: '#' }]} pageTitle={t('Project Templates')}>
            <Head title={t('Project Templates')} />

            <div className="space-y-6">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-xl font-semibold">{t('Project Templates')}</h2>
                        <p className="text-sm text-muted-foreground">{t('Create reusable project templates with tasks and milestones.')}</p>
                    </div>
                    {canCreate && (
                        <Button size="sm" onClick={() => { setEditTemplate(null); setShowCreate(true); }}>
                            <Plus className="h-4 w-4 mr-1" /> {t('New Template')}
                        </Button>
                    )}
                </div>

                {loading ? (
                    <Card><CardContent className="p-6 text-center text-muted-foreground">{t('Loading...')}</CardContent></Card>
                ) : templates.length === 0 ? (
                    <Card>
                        <CardContent className="p-6 text-center text-muted-foreground">
                            <LayoutTemplate className="h-10 w-10 mx-auto mb-3 text-gray-400" />
                            <p>{t('No templates yet. Create one to quickly bootstrap new projects.')}</p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4">
                        {templates.map((template) => (
                            <Card key={template.id}>
                                <CardContent className="p-4">
                                    <div className="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 className="font-semibold flex items-center gap-2">
                                                <LayoutTemplate className="h-4 w-4 text-primary" />
                                                {template.name}
                                                {!template.is_active && <span className="text-xs bg-gray-200 px-1.5 py-0.5 rounded">{t('Inactive')}</span>}
                                            </h3>
                                            {template.description && (
                                                <p className="text-sm text-muted-foreground mt-1">{template.description}</p>
                                            )}
                                        </div>
                                        <div className="flex gap-1">
                                            {canDuplicate && (
                                                <Button variant="ghost" size="sm" onClick={() => handleDuplicate(template.id)}>
                                                    <Copy className="h-4 w-4 text-purple-500" />
                                                </Button>
                                            )}
                                            {canEdit && (
                                                <Button variant="ghost" size="sm" onClick={() => { setEditTemplate(template); setShowEdit(true); }}>
                                                    <Edit className="h-4 w-4" />
                                                </Button>
                                            )}
                                            {canDelete && (
                                                <Button variant="ghost" size="sm" onClick={() => handleDelete(template.id)}>
                                                    <Trash2 className="h-4 w-4 text-red-500" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>

                                    <div className="flex gap-6 text-sm text-muted-foreground mb-3">
                                        <span className="flex items-center gap-1"><Milestone className="h-3.5 w-3.5" /> {template.milestones?.length || 0} {t('Milestones')}</span>
                                        <span className="flex items-center gap-1"><List className="h-3.5 w-3.5" /> {template.tasks?.length || 0} {t('Tasks')}</span>
                                    </div>

                                    {template.tasks && template.tasks.length > 0 && (
                                        <div className="border-t pt-2 mb-3">
                                            <p className="text-xs font-medium text-muted-foreground mb-1">{t('Tasks')}:</p>
                                            <div className="grid grid-cols-2 gap-1">
                                                {template.tasks.slice(0, 6).map((task) => (
                                                    <div key={task.id} className="text-xs flex gap-1">
                                                        <span className={`inline-block w-1.5 h-1.5 rounded-full mt-1.5 flex-shrink-0 ${
                                                            task.priority === 'Urgent' ? 'bg-red-500' :
                                                            task.priority === 'High' ? 'bg-orange-500' :
                                                            task.priority === 'Medium' ? 'bg-yellow-500' : 'bg-gray-400'
                                                        }`} />
                                                        <span>{task.title}</span>
                                                    </div>
                                                ))}
                                                {template.tasks.length > 6 && (
                                                    <span className="text-xs text-muted-foreground">+{template.tasks.length - 6} more</span>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    {template.milestones && template.milestones.length > 0 && (
                                        <div className="border-t pt-2 mb-3">
                                            <p className="text-xs font-medium text-muted-foreground mb-1">{t('Milestones')}:</p>
                                            <div className="grid grid-cols-2 gap-1">
                                                {template.milestones.slice(0, 4).map((ms) => (
                                                    <div key={ms.id} className="text-xs flex items-center gap-1">
                                                        <Milestone className="h-3 w-3 text-blue-500" />
                                                        <span>{ms.name}</span>
                                                        <span className="text-muted-foreground">(+{ms.due_days_offset}d)</span>
                                                    </div>
                                                ))}
                                                {template.milestones.length > 4 && (
                                                    <span className="text-xs text-muted-foreground">+{template.milestones.length - 4} more</span>
                                                )}
                                            </div>
                                        </div>
                                    )}

                                    <div className="border-t pt-3">
                                        <Button size="sm" variant="outline" onClick={() => openCreateProject(template)}>
                                            <Play className="h-4 w-4 mr-1" /> {t('Create Project from Template')}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}
            </div>

            {/* Create Template Dialog */}
            <Dialog open={showCreate} onOpenChange={setShowCreate}>
                <Create onSuccess={() => { setShowCreate(false); fetchTemplates(); }} template={null} />
            </Dialog>

            {/* Edit Template Dialog */}
            <Dialog open={showEdit} onOpenChange={setShowEdit}>
                {editTemplate && (
                    <Create onSuccess={() => { setShowEdit(false); fetchTemplates(); }} template={editTemplate} />
                )}
            </Dialog>

            {/* Create Project from Template Dialog */}
            <Dialog open={showCreateProject} onOpenChange={setShowCreateProject}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            {t('Create Project from Template')}
                        </DialogTitle>
                    </DialogHeader>
                    {selectedTemplate && (
                        <div className="space-y-4">
                            <p className="text-sm bg-blue-50 p-2 rounded-lg">
                                {t('Template')}: <strong>{selectedTemplate.name}</strong>
                            </p>
                            <div>
                                <Label>{t('Project Name')} *</Label>
                                <Input value={projectForm.name} onChange={(e) => setProjectForm({ ...projectForm, name: e.target.value })} />
                            </div>
                            <div>
                                <Label>{t('Description')}</Label>
                                <Textarea value={projectForm.description} onChange={(e) => setProjectForm({ ...projectForm, description: e.target.value })} rows={3} />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>{t('Start Date')}</Label>
                                    <Input type="date" value={projectForm.start_date} onChange={(e) => setProjectForm({ ...projectForm, start_date: e.target.value })} />
                                </div>
                                <div>
                                    <Label>{t('End Date')}</Label>
                                    <Input type="date" value={projectForm.end_date} onChange={(e) => setProjectForm({ ...projectForm, end_date: e.target.value })} />
                                </div>
                            </div>
                            <div>
                                <Label>{t('Budget')}</Label>
                                <Input type="number" value={projectForm.budget} onChange={(e) => setProjectForm({ ...projectForm, budget: e.target.value })} placeholder="0.00" />
                            </div>
                            <div className="flex gap-2 justify-end">
                                <Button variant="outline" onClick={() => setShowCreateProject(false)}>{t('Cancel')}</Button>
                                <Button onClick={handleCreateProject} disabled={creatingProject}>
                                    {creatingProject && <Loader2 className="h-4 w-4 mr-1 animate-spin" />}
                                    {t('Create Project')}
                                </Button>
                            </div>
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </AuthenticatedLayout>
    );
}
