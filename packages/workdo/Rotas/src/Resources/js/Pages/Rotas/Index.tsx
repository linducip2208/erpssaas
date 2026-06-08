import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Edit as EditIcon, Trash2, Calendar, Users } from "lucide-react";
import { RotasIndexProps, Rota } from './types';
import { formatDate } from '@/utils/helpers';

export default function Index() {
    const { t } = useTranslation();
    const { rotas, templates, auth } = usePage<RotasIndexProps>().props;
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<Rota | null>(null);
    const [form, setForm] = useState({
        name: '',
        start_date: '',
        end_date: '',
        template_id: '',
        generate_from_template: false,
    });

    const handleSubmit = () => {
        if (editing) {
            router.put(route('rotas.update', editing.id), form, {
                onSuccess: () => { setShowModal(false); resetForm(); }
            });
        } else {
            router.post(route('rotas.store'), form, {
                onSuccess: () => { setShowModal(false); resetForm(); }
            });
        }
    };

    const handleEdit = (item: Rota) => {
        setEditing(item);
        setForm({
            name: item.name,
            start_date: item.start_date,
            end_date: item.end_date,
            template_id: item.template_id?.toString() || '',
            generate_from_template: false,
        });
        setShowModal(true);
    };

    const handleDelete = (id: number) => {
        if (confirm(t('Are you sure?'))) {
            router.delete(route('rotas.destroy', id));
        }
    };

    const resetForm = () => {
        setEditing(null);
        setForm({ name: '', start_date: '', end_date: '', template_id: '', generate_from_template: false });
    };

    const getStatusBadge = (status: string) => {
        const colors: Record<string, string> = {
            draft: 'bg-gray-100 text-gray-800',
            published: 'bg-green-100 text-green-800',
            archived: 'bg-slate-100 text-slate-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Rotas')} />
            <div className="flex-1 space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{t('Rotas')}</h1>
                        <p className="text-muted-foreground text-sm">{t('Manage employee shift schedules')}</p>
                    </div>
                    {auth.user.can('create-rotas') && (
                        <Button onClick={() => { resetForm(); setShowModal(true); }}>
                            <Plus className="mr-2 h-4 w-4" />{t('New Rota')}
                        </Button>
                    )}
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <div className="space-y-3">
                            {rotas?.data?.map((rota: Rota) => (
                                <div key={rota.id} className="flex items-center justify-between p-4 rounded-lg border hover:bg-accent">
                                    <div className="flex items-center gap-3">
                                        <Calendar className="h-8 w-8 text-indigo-500" />
                                        <div>
                                            <p className="font-medium">{rota.name}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {formatDate(rota.start_date)} - {formatDate(rota.end_date)}
                                            </p>
                                            {rota.template && (
                                                <p className="text-xs text-muted-foreground">{t('Template')}: {rota.template.name}</p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className={`text-xs px-2 py-1 rounded ${getStatusBadge(rota.status)}`}>
                                            {t(rota.status)}
                                        </span>
                                        {auth.user.can('view-rotas') && (
                                            <Button size="sm" variant="outline" onClick={() => router.get(route('rotas.show', rota.id))}>
                                                <Users className="h-4 w-4 mr-1" />{t('Assign')}
                                            </Button>
                                        )}
                                        {auth.user.can('edit-rotas') && (
                                            <Button size="sm" variant="ghost" onClick={() => handleEdit(rota)}>
                                                <EditIcon className="h-4 w-4" />
                                            </Button>
                                        )}
                                        {auth.user.can('delete-rotas') && (
                                            <Button size="sm" variant="ghost" onClick={() => handleDelete(rota.id)}>
                                                <Trash2 className="h-4 w-4 text-red-500" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                            {(!rotas?.data || rotas.data.length === 0) && (
                                <p className="text-muted-foreground text-sm text-center py-4">{t('No rotas found.')}</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Dialog open={showModal} onOpenChange={setShowModal}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{editing ? t('Edit Rota') : t('New Rota')}</DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4 pt-4">
                            <div>
                                <Label>{t('Name')}</Label>
                                <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>{t('Start Date')}</Label>
                                    <Input type="date" value={form.start_date} onChange={(e) => setForm({ ...form, start_date: e.target.value })} />
                                </div>
                                <div>
                                    <Label>{t('End Date')}</Label>
                                    <Input type="date" value={form.end_date} onChange={(e) => setForm({ ...form, end_date: e.target.value })} />
                                </div>
                            </div>
                            {!editing && (
                                <>
                                    <div>
                                        <Label>{t('Template (optional)')}</Label>
                                        <Select value={form.template_id} onValueChange={(v) => setForm({ ...form, template_id: v })}>
                                            <SelectTrigger><SelectValue placeholder={t('No template')} /></SelectTrigger>
                                            <SelectContent>
                                                {templates.map((t) => (
                                                    <SelectItem key={t.id} value={t.id.toString()}>{t.name}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </>
                            )}
                            <div className="flex justify-end gap-2">
                                <Button variant="outline" onClick={() => setShowModal(false)}>{t('Cancel')}</Button>
                                <Button onClick={handleSubmit}>{editing ? t('Update') : t('Create')}</Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AuthenticatedLayout>
    );
}
