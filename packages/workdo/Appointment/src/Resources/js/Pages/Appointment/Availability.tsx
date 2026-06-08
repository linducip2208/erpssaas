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
import { Plus, Trash2, Edit as EditIcon } from "lucide-react";
import { AppointmentAvailabilityProps, AppointmentAvailability } from './types';

const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

export default function Availability() {
    const { t } = useTranslation();
    const { availabilities, users, auth } = usePage<AppointmentAvailabilityProps>().props;
    const [showModal, setShowModal] = useState(false);
    const [editing, setEditing] = useState<AppointmentAvailability | null>(null);
    const [form, setForm] = useState({ user_id: '', day_of_week: '1', start_time: '09:00', end_time: '17:00' });

    const handleSubmit = () => {
        if (editing) {
            router.put(route('appointment-availability.update', editing.id), form, {
                onSuccess: () => { setShowModal(false); resetForm(); }
            });
        } else {
            router.post(route('appointment-availability.store'), form, {
                onSuccess: () => { setShowModal(false); resetForm(); }
            });
        }
    };

    const handleEdit = (item: AppointmentAvailability) => {
        setEditing(item);
        setForm({
            user_id: item.user_id.toString(),
            day_of_week: item.day_of_week.toString(),
            start_time: item.start_time,
            end_time: item.end_time,
        });
        setShowModal(true);
    };

    const handleDelete = (id: number) => {
        if (confirm(t('Are you sure?'))) {
            router.delete(route('appointment-availability.destroy', id));
        }
    };

    const resetForm = () => {
        setEditing(null);
        setForm({ user_id: '', day_of_week: '1', start_time: '09:00', end_time: '17:00' });
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Availability')} />
            <div className="flex-1 space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{t('Availability')}</h1>
                        <p className="text-muted-foreground text-sm">{t('Set staff working hours')}</p>
                    </div>
                    {auth.user.can('create-appointment-availability') && (
                        <Button onClick={() => { resetForm(); setShowModal(true); }}>
                            <Plus className="mr-2 h-4 w-4" />{t('Add Availability')}
                        </Button>
                    )}
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <div className="space-y-2">
                            {availabilities.map((av) => (
                                <div key={av.id} className="flex items-center justify-between p-3 rounded-lg border">
                                    <div>
                                        <p className="font-medium">{av.user?.name}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {DAYS[av.day_of_week]} · {av.start_time} - {av.end_time}
                                        </p>
                                    </div>
                                    <div className="flex gap-1">
                                        {auth.user.can('edit-appointment-availability') && (
                                            <Button size="sm" variant="ghost" onClick={() => handleEdit(av)}>
                                                <EditIcon className="h-4 w-4" />
                                            </Button>
                                        )}
                                        {auth.user.can('delete-appointment-availability') && (
                                            <Button size="sm" variant="ghost" onClick={() => handleDelete(av.id)}>
                                                <Trash2 className="h-4 w-4 text-red-500" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                            {availabilities.length === 0 && (
                                <p className="text-muted-foreground text-sm text-center py-4">{t('No availability set.')}</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Dialog open={showModal} onOpenChange={setShowModal}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{editing ? t('Edit Availability') : t('Add Availability')}</DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4 pt-4">
                            {!editing && (
                                <div>
                                    <Label>{t('Staff')}</Label>
                                    <Select value={form.user_id} onValueChange={(v) => setForm({ ...form, user_id: v })}>
                                        <SelectTrigger><SelectValue placeholder={t('Select staff')} /></SelectTrigger>
                                        <SelectContent>
                                            {users.map((u) => (
                                                <SelectItem key={u.id} value={u.id.toString()}>{u.name}</SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div>
                                <Label>{t('Day')}</Label>
                                <Select value={form.day_of_week} onValueChange={(v) => setForm({ ...form, day_of_week: v })}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        {DAYS.map((d, i) => (
                                            <SelectItem key={i} value={i.toString()}>{d}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>{t('Start Time')}</Label>
                                    <Input type="time" value={form.start_time} onChange={(e) => setForm({ ...form, start_time: e.target.value })} />
                                </div>
                                <div>
                                    <Label>{t('End Time')}</Label>
                                    <Input type="time" value={form.end_time} onChange={(e) => setForm({ ...form, end_time: e.target.value })} />
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
