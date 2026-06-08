import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { Plus, Edit, Trash2, ToggleLeft, ToggleRight, Play } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';

interface WorkflowIndexProps {
    workflows: {
        data: Array<{
            id: number;
            name: string;
            description: string;
            trigger_type: string;
            trigger_module: string;
            is_active: boolean;
            actions_count: number;
            logs_count: number;
            created_at: string;
        }>;
        current_page: number;
        last_page: number;
        total: number;
    };
}

export default function Index() {
    const { t } = useTranslation();
    const { workflows } = usePage<WorkflowIndexProps>().props;

    const handleToggle = async (id: number) => {
        try {
            await axios.put(route('workflow.toggle', id));
            toast.success(t('Workflow status updated.'));
            router.reload();
        } catch (error) {
            toast.error(t('Failed to toggle workflow.'));
        }
    };

    const handleDelete = async (id: number) => {
        if (confirm(t('Are you sure?'))) {
            try {
                await axios.delete(route('workflow.destroy', id));
                toast.success(t('Workflow deleted.'));
                router.reload();
            } catch (error) {
                toast.error(t('Failed to delete workflow.'));
            }
        }
    };

    const columns = [
        { key: 'name', label: t('Name') },
        { key: 'trigger_type', label: t('Trigger') },
        { key: 'trigger_module', label: t('Module') },
        {
            key: 'is_active',
            label: t('Status'),
            render: (row: any) => (
                <button onClick={() => handleToggle(row.id)} className="cursor-pointer">
                    {row.is_active ? (
                        <ToggleRight className="w-6 h-6 text-green-600" />
                    ) : (
                        <ToggleLeft className="w-6 h-6 text-stone-400" />
                    )}
                </button>
            ),
        },
        {
            key: 'actions_count',
            label: t('Actions'),
            render: (row: any) => <span>{row.actions_count}</span>,
        },
        {
            key: 'actions',
            label: t('Options'),
            render: (row: any) => (
                <div className="flex gap-2">
                    <Button size="sm" variant="ghost"
                        onClick={() => router.get(route('workflow.show', row.id))}>
                        <Edit className="w-4 h-4" />
                    </Button>
                    <Button size="sm" variant="ghost"
                        onClick={() => handleDelete(row.id)}>
                        <Trash2 className="w-4 h-4" />
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={t('Workflow Automation')} />
            <div className="p-6 space-y-6">
                <div className="flex justify-between items-center">
                    <h1 className="text-2xl font-bold text-stone-900">{t('Workflow Automation')}</h1>
                    <Button onClick={() => router.get(route('workflow.show', 'new'))}>
                        <Plus className="w-4 h-4 mr-2" />
                        {t('Create Workflow')}
                    </Button>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <DataTable
                            columns={columns}
                            data={workflows?.data ?? []}
                            pagination={{
                                currentPage: workflows?.current_page ?? 1,
                                lastPage: workflows?.last_page ?? 1,
                                total: workflows?.total ?? 0,
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
