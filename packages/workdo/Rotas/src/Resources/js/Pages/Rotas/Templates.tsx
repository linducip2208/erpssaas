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
import { RotasTemplatesProps, RotaTemplate, RotaTemplateShift } from './types';

const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

export default function Templates() {
    const { t } = useTranslation();
    const { templates, auth } = usePage<RotasTemplatesProps>().props;
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<RotaTemplate | null>(null);
    const [form, setForm] = useState({ name: '', description: '' });
    const [shifts, setShifts] = useState<RotaTemplateShift[]>([]);

    const handleSubmit = () => {
        const data = { ...form, shifts };
        if (editing) {
            router.put(route('rota-templates.update', editing.id), data, {
                onSuccess: () => { setShowModal(false); resetForm(); }
            });
        } else {
            router.post(route('rota-templates.store'), data, {
                onSuccess: () => { setShowModal(false); resetForm(); }
            });
        }
    };

    const handleEdit = (item: RotaTemplate) => {
        setEditing(item);
        setForm({ name: item.name, description: item.description || '' });
        setShifts(item.shifts || []);
        setShowModal(true);
    };

    const handleDelete = (id: number) => {
        if (confirm(t('Are you sure?'))) {
            router.delete(route('rota-templates.destroy', id));
        }
    };

    const addShift = () => {
        setShifts([...shifts, { id: 0, template_id: 0, day_of_week: 1, start_time: '09:00', end_time: '17:00', role: '' }]);
    };

    const updateShift = (index: number, field: string, value: any) => {
        const updated = [...shifts];
        (updated[index] as any)[field] = value;
        setShifts(updated);
    };

    const removeShift = (index: number) => {
        setShifts(shifts.filter((_, i) => i !== index));
    };

    const resetForm = () => {
        setEditing(null);
        setForm({ name: '', description: '' });
        setShifts([]);
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Rota Templates')} />
            <div className="flex-1 space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{t('Rota Templates')}</h1>
                        <p className="text-muted-foreground text-sm">{t('Create reusable shift templates')}</p>
                    </div>
                    {auth.user.can('create-rota-templates') && (
                        <Button onClick={() => { resetForm(); setShowModal(true); }}>
                            <Plus className="mr-2 h-4 w-4" />{t('New Template')}
                        </Button>
                    )}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {templates.map((template) => (
                        <Card key={template.id}>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <h3 className="font-semibold">{template.name}</h3>
                                    <div className="flex gap-1">
                                        {auth.user.can('edit-rota-templates') && (
                                            <Button size="sm" variant="ghost" onClick={() => handleEdit(template)}>
                                                <EditIcon className="h-4 w-4" />
                                            </Button>
                                        )}
                                        {auth.user.can('delete-rota-templates') && (
                                            <Button size="sm" variant="ghost" onClick={() => handleDelete(template.id)}>
                                                <Trash2 className="h-4 w-4 text-red-500" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {template.description && <p className="text-sm text-muted-foreground mb-3">{template.description}</p>}
                                {template.shifts && template.shifts.length > 0 ? (
                                    <div className="space-y-1">
                                        {template.shifts.map((shift, i) => (
                                            <div key={i} className="text-xs flex justify-between py-1 border-b last:border-0">
                                                <span className="font-medium">{DAYS[shift.day_of_week]}</span>
                                                <span>{shift.start_time} - {shift.end_time}</span>
                                                {shift.role && <span className="text-muted-foreground">{shift.role}</span>}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-xs text-muted-foreground">{t('No shifts defined.')}</p>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Dialog open={showModal} onOpenChange={setShowModal}>
                    <DialogContent className="max-w-lg">
                        <DialogHeader>
                            <DialogTitle>{editing ? t('Edit Template') : t('New Template')}</DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4 pt-4 max-h-[60vh] overflow-y-auto">
                            <div>
                                <Label>{t('Name')}</Label>
                                <Input value={form.name} onChange={(e) => setForm({ ...form, name: e.target.value })} />
                            </div>
                            <div>
                                <Label>{t('Description')}</Label>
                                <Input value={form.description} onChange={(e) => setForm({ ...form, description: e.target.value })} />
                            </div>
                            <div>
                                <div className="flex items-center justify-between mb-2">
                                    <Label>{t('Shifts')}</Label>
                                    <Button type="button" size="sm" variant="outline" onClick={addShift}>
                                        <Plus className="h-3 w-3 mr-1" />{t('Add Shift')}
                                    </Button>
                                </div>
                                {shifts.map((shift, i) => (
                                    <div key={i} className="grid grid-cols-4 gap-2 mb-2 items-end">
                                        <select
                                            className="rounded-md border px-2 py-1.5 text-sm"
                                            value={shift.day_of_week}
                                            onChange={(e) => updateShift(i, 'day_of_week', parseInt(e.target.value))}
                                        >
                                            {DAYS.map((d, di) => (
                                                <option key={di} value={di}>{d}</option>
                                            ))}
                                        </select>
                                        <Input type="time" value={shift.start_time} onChange={(e) => updateShift(i, 'start_time', e.target.value)} />
                                        <Input type="time" value={shift.end_time} onChange={(e) => updateShift(i, 'end_time', e.target.value)} />
                                        <div className="flex gap-1">
                                            <Input placeholder={t('Role')} value={shift.role || ''}
                                                onChange={(e) => updateShift(i, 'role', e.target.value)} />
                                            <Button type="button" size="sm" variant="ghost" className="h-8 w-8 p-0"
                                                onClick={() => removeShift(i)}>
                                                <Trash2 className="h-3 w-3 text-red-500" />
                                            </Button>
                                        </div>
                                    </div>
                                ))}
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
