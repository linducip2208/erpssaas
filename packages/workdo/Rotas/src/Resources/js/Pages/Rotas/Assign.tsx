import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Users, ArrowLeft, Save } from "lucide-react";
import { RotasAssignProps, RotaAssignment } from './types';
import { formatDate } from '@/utils/helpers';

const DAYS = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];

export default function Assign() {
    const { t } = useTranslation();
    const { rota, users, auth } = usePage<RotasAssignProps>().props;
    const [assignments, setAssignments] = useState<Record<number, string>>({});
    const [saving, setSaving] = useState(false);

    const getDayName = (date: string) => {
        const d = new Date(date);
        return DAYS[d.getDay()];
    };

    const handleAssign = (assignmentId: number, userId: string) => {
        router.post(route('rota-assignments.assign'), {
            assignment_id: assignmentId,
            user_id: userId,
        }, {
            onSuccess: () => {
                setAssignments(prev => ({ ...prev, [assignmentId]: userId }));
            }
        });
    };

    const handleBulkSave = () => {
        const data = Object.entries(assignments).map(([assignmentId, userId]) => ({
            assignment_id: parseInt(assignmentId),
            user_id: userId,
        }));

        router.post(route('rota-assignments.bulk-assign', rota.id), { assignments: data });
    };

    const handleGenerateFromTemplate = () => {
        router.post(route('rotas.generate', rota.id));
    };

    const getUserName = (userId: number | null) => {
        if (!userId) return t('Unassigned');
        const user = users.find(u => u.id === userId);
        return user?.name || t('Unknown');
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Assign Rotas')} />
            <div className="flex-1 space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Button variant="ghost" onClick={() => router.get(route('rotas.index'))}>
                            <ArrowLeft className="h-4 w-4 mr-1" />{t('Back')}
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold">{rota.name}</h1>
                            <p className="text-muted-foreground text-sm">
                                {formatDate(rota.start_date)} - {formatDate(rota.end_date)}
                            </p>
                        </div>
                    </div>
                    <div className="flex gap-2">
                        {rota.template && auth.user.can('create-rota-assignments') && (
                            <Button variant="outline" onClick={handleGenerateFromTemplate}>
                                {t('Generate from Template')}
                            </Button>
                        )}
                        {auth.user.can('create-rota-assignments') && (
                            <Button onClick={handleBulkSave}>
                                <Save className="h-4 w-4 mr-1" />{t('Save All')}
                            </Button>
                        )}
                    </div>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        {rota.assignments && rota.assignments.length > 0 ? (
                            <div className="space-y-2">
                                {rota.assignments.map((assignment: RotaAssignment) => (
                                    <div key={assignment.id} className="flex items-center justify-between p-3 rounded-lg border hover:bg-accent">
                                        <div className="flex items-center gap-4">
                                            <div className="text-center min-w-[70px]">
                                                <p className="text-sm font-medium">{getDayName(assignment.date)}</p>
                                                <p className="text-xs text-muted-foreground">{formatDate(assignment.date)}</p>
                                            </div>
                                            <div>
                                                <p className="text-sm">{assignment.start_time} - {assignment.end_time}</p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Select
                                                value={assignments[assignment.id]?.toString() || assignment.user_id?.toString() || ''}
                                                onValueChange={(v) => handleAssign(assignment.id, v)}
                                            >
                                                <SelectTrigger className="w-[180px]">
                                                    <SelectValue placeholder={t('Select employee')} />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="">{t('Unassigned')}</SelectItem>
                                                    {users.map((u) => (
                                                        <SelectItem key={u.id} value={u.id.toString()}>{u.name}</SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="text-center py-8">
                                <Users className="h-12 w-12 mx-auto text-muted-foreground mb-3" />
                                <p className="text-muted-foreground">{t('No shifts assigned yet.')}</p>
                                {rota.template && (
                                    <p className="text-sm text-muted-foreground mt-1">
                                        {t('Click "Generate from Template" to create shifts.')}
                                    </p>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
