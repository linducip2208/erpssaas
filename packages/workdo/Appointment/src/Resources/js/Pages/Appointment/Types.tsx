import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus, Edit as EditIcon, Trash2 } from "lucide-react";
import { AppointmentTypesProps, AppointmentType } from './types';

export default function Types() {
    const { t } = useTranslation();
    const { types, auth } = usePage<AppointmentTypesProps>().props;
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<AppointmentType | null>(null);
    const [form, setForm] = useState({ name: '', duration_minutes: '30', color: '#6366f1' });

    const handleSubmit = () => {
        if (editing) {
            router.put(route('appointment-types.update', editing.id), form, {
                onSuccess: () => { setShowModal(false); resetForm(); }
            });
        } else {
            router.post(route('appointment-types.store'), form, {
                onSuccess: () => { setShowModal(false); resetForm(); }
            });
        }
    };

    const handleEdit = (item: AppointmentType) => {
        setEditing(item);
        setForm({ name: item.name, duration_minutes: item.duration_minutes.toString(), color: item.color });
        setShowModal(true);
    };

    const handleDelete = (id: number) => {
        if (confirm(t('Are you sure?'))) {
            router.delete(route('appointment-types.destroy', id));
        }
    };

    const resetForm = () => {
        setEditing(null);
        setForm({ name: '', duration_minutes: '30', color: '#6366f1' });
    };

    const PRESET_COLORS = ['#6366f1', '#ec4899', '#f59e0b', '#10b981', '#06b6d4', '#8b5cf6', '#ef4444', '#84cc16'];

    return (
        <AuthenticatedLayout>
            <Head title={t('Appointment Types')} />
            <div className="flex-1 space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{t('Appointment Types')}</h1>
                    </div>
                    {auth.user.can('create-appointment-types') && (
                        <Button onClick={() => { resetForm(); setShowModal(true); }}>
                            <Plus className="mr-2 h-4 w-4" />{t('Add Type')}
                        </Button>
                    )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {types.map((type) => (
                        <Card key={type.id}>
                            <CardContent className="pt-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="w-4 h-4 rounded-full" style={{ backgroundColor: type.color }} />
                                        <div>
                                            <p className="font-medium">{type.name}</p>
                                            <p className="text-sm text-muted-foreground">{type.duration_minutes} {t('minutes')}</p>
                                        </div>
                                    </div>
                                    <div className="flex gap-1">
                                        {auth.user.can('edit-appointment-types') && (
                                            <Button size="sm" variant="ghost" onClick={() => handleEdit(type)}>
                                                <EditIcon className="h-4 w-4" />
                                            </Button>
                                        )}
                                        {auth.user.can('delete-appointment-types') && (
                                            <Button size="sm" variant="ghost" onClick={() => handleDelete(type.id)}>
                                                <Trash2 className="h-4 w-4 text-red-500" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Dialog open={showModal} onOpenChange={setShowModal}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{editing ? t('Edit Type') : t('New Type')}</DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4 pt-4">
                            <div>
                                <Label>{t('Name')}</Label>
                                <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
                            </div>
                            <div>
                                <Label>{t('Duration (minutes)')}</Label>
                                <Input type="number" value={form.duration_minutes} onChange={(e) => setForm({ ...form, duration_minutes: e.target.value })} />
                            </div>
                            <div>
                                <Label>{t('Color')}</Label>
                                <div className="flex gap-2 mt-1">
                                    {PRESET_COLORS.map((c) => (
                                        <button
                                            key={c}
                                            type="button"
                                            className={`w-8 h-8 rounded-full border-2 ${form.color === c ? 'border-stone-900 scale-110' : 'border-transparent'}`}
                                            style={{ backgroundColor: c }}
                                            onClick={() => setForm({ ...form, color: c })}
                                        />
                                    ))}
                                </div>
                            </div>
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
