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
import { Plus, Edit as EditIcon, Trash2, Calendar as CalendarIcon } from "lucide-react";
import { AppointmentIndexProps, Appointment } from './types';
import { formatDate } from '@/utils/helpers';

const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

export default function Index() {
    const { t } = useTranslation();
    const { appointments, types, auth } = usePage<AppointmentIndexProps>().props;
    const [showModal, setShowModal] = useState(false);
    const [editingAppointment, setEditingAppointment] = useState<Appointment | null>(null);
    const [form, setForm] = useState({
        title: '',
        type_id: '',
        start_datetime: '',
        end_datetime: '',
        attendee_name: '',
        attendee_email: '',
        attendee_phone: '',
        notes: '',
        status: 'pending',
    });

    const handleSubmit = () => {
        if (editingAppointment) {
            router.put(route('appointments.update', editingAppointment.id), form, {
                onSuccess: () => { setShowModal(false); resetForm(); }
            });
        } else {
            router.post(route('appointments.store'), form, {
                onSuccess: () => { setShowModal(false); resetForm(); }
            });
        }
    };

    const handleEdit = (appt: Appointment) => {
        setEditingAppointment(appt);
        setForm({
            title: appt.title,
            type_id: appt.type_id?.toString() || '',
            start_datetime: appt.start_datetime?.replace(' ', 'T') || '',
            end_datetime: appt.end_datetime?.replace(' ', 'T') || '',
            attendee_name: appt.attendee_name,
            attendee_email: appt.attendee_email,
            attendee_phone: appt.attendee_phone || '',
            notes: appt.notes || '',
            status: appt.status,
        });
        setShowModal(true);
    };

    const handleDelete = (id: number) => {
        if (confirm(t('Are you sure?'))) {
            router.delete(route('appointments.destroy', id));
        }
    };

    const resetForm = () => {
        setEditingAppointment(null);
        setForm({ title: '', type_id: '', start_datetime: '', end_datetime: '', attendee_name: '', attendee_email: '', attendee_phone: '', notes: '', status: 'pending' });
    };

    const getStatusBadge = (status: string) => {
        const colors: Record<string, string> = {
            pending: 'bg-yellow-100 text-yellow-800',
            confirmed: 'bg-green-100 text-green-800',
            cancelled: 'bg-red-100 text-red-800',
            completed: 'bg-blue-100 text-blue-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Appointments')} />
            <div className="flex-1 space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{t('Appointments')}</h1>
                        <p className="text-muted-foreground text-sm">{t('Manage your appointments and bookings')}</p>
                    </div>
                    {auth.user.can('create-appointments') && (
                        <Button onClick={() => { resetForm(); setShowModal(true); }}>
                            <Plus className="mr-2 h-4 w-4" />
                            {t('New Appointment')}
                        </Button>
                    )}
                </div>

                <Card>
                    <CardHeader>
                        <div className="flex items-center gap-2">
                            <CalendarIcon className="h-5 w-5" />
                            <h2 className="font-semibold">{t('All Appointments')}</h2>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-3">
                            {appointments?.data?.map((appt: Appointment) => (
                                <div key={appt.id} className="flex items-center justify-between p-3 rounded-lg border hover:bg-accent">
                                    <div className="flex items-center gap-3">
                                        <div
                                            className="w-3 h-12 rounded-full"
                                            style={{ backgroundColor: appt.type?.color || '#6366f1' }}
                                        />
                                        <div>
                                            <p className="font-medium">{appt.title}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {formatDate(appt.start_datetime)} - {formatDate(appt.end_datetime)}
                                            </p>
                                            <p className="text-xs text-muted-foreground">{appt.attendee_name} ({appt.attendee_email})</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <span className={`text-xs px-2 py-1 rounded ${getStatusBadge(appt.status)}`}>
                                            {t(appt.status)}
                                        </span>
                                        {auth.user.can('edit-appointments') && (
                                            <Button size="sm" variant="ghost" onClick={() => handleEdit(appt)}>
                                                <EditIcon className="h-4 w-4" />
                                            </Button>
                                        )}
                                        {auth.user.can('delete-appointments') && (
                                            <Button size="sm" variant="ghost" onClick={() => handleDelete(appt.id)}>
                                                <Trash2 className="h-4 w-4 text-red-500" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                            {(!appointments?.data || appointments.data.length === 0) && (
                                <p className="text-muted-foreground text-sm text-center py-4">{t('No appointments found.')}</p>
                            )}
                        </div>
                    </CardContent>
                </Card>

                <Dialog open={showModal} onOpenChange={setShowModal}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>{editingAppointment ? t('Edit Appointment') : t('New Appointment')}</DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4 pt-4">
                            <div>
                                <Label>{t('Title')}</Label>
                                <Input value={form.title} onChange={(e) => setForm({ ...form, title: e.target.value })} />
                            </div>
                            <div>
                                <Label>{t('Type')}</Label>
                                <Select value={form.type_id} onValueChange={(v) => setForm({ ...form, type_id: v })}>
                                    <SelectTrigger><SelectValue placeholder={t('Select type')} /></SelectTrigger>
                                    <SelectContent>
                                        {types.map((t) => (
                                            <SelectItem key={t.id} value={t.id.toString()}>{t.name} ({t.duration_minutes}m)</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label>{t('Start')}</Label>
                                    <Input type="datetime-local" value={form.start_datetime} onChange={(e) => setForm({ ...form, start_datetime: e.target.value })} />
                                </div>
                                <div>
                                    <Label>{t('End')}</Label>
                                    <Input type="datetime-local" value={form.end_datetime} onChange={(e) => setForm({ ...form, end_datetime: e.target.value })} />
                                </div>
                            </div>
                            <div>
                                <Label>{t('Attendee Name')}</Label>
                                <Input value={form.attendee_name} onChange={(e) => setForm({ ...form, attendee_name: e.target.value })} />
                            </div>
                            <div>
                                <Label>{t('Attendee Email')}</Label>
                                <Input type="email" value={form.attendee_email} onChange={(e) => setForm({ ...form, attendee_email: e.target.value })} />
                            </div>
                            <div>
                                <Label>{t('Attendee Phone')}</Label>
                                <Input value={form.attendee_phone} onChange={(e) => setForm({ ...form, attendee_phone: e.target.value })} />
                            </div>
                            <div>
                                <Label>{t('Notes')}</Label>
                                <Input value={form.notes} onChange={(e) => setForm({ ...form, notes: e.target.value })} />
                            </div>
                            {editingAppointment && (
                                <div>
                                    <Label>{t('Status')}</Label>
                                    <Select value={form.status} onValueChange={(v) => setForm({ ...form, status: v })}>
                                        <SelectTrigger><SelectValue /></SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="pending">{t('Pending')}</SelectItem>
                                            <SelectItem value="confirmed">{t('Confirmed')}</SelectItem>
                                            <SelectItem value="cancelled">{t('Cancelled')}</SelectItem>
                                            <SelectItem value="completed">{t('Completed')}</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                            )}
                            <div className="flex justify-end gap-2">
                                <Button variant="outline" onClick={() => setShowModal(false)}>{t('Cancel')}</Button>
                                <Button onClick={handleSubmit}>{editingAppointment ? t('Update') : t('Create')}</Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>
            </div>
        </AuthenticatedLayout>
    );
}
