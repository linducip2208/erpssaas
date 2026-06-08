import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/ui/data-table';
import { Search, Filter } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface LogsProps {
    logs: {
        data: Array<{
            id: number;
            to_number: string;
            message: string;
            template_id: number;
            status: string;
            response: any;
            error_message: string;
            created_at: string;
            template?: { name: string };
            sender?: { name: string };
        }>;
        current_page: number;
        last_page: number;
        total: number;
    };
}

export default function Logs() {
    const { t } = useTranslation();
    const { logs } = usePage<LogsProps>().props;
    const [filters, setFilters] = useState({ search: '', status: '' });

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (filters.search) params.search = filters.search;
        if (filters.status) params.status = filters.status;
        router.get(route('whatsapp.logs.index'), params, { preserveState: true });
    };

    const getStatusBadge = (status: string) => {
        const colors: Record<string, string> = {
            sent: 'bg-green-100 text-green-800',
            pending: 'bg-yellow-100 text-yellow-800',
            failed: 'bg-red-100 text-red-800',
        };
        return <Badge className={colors[status] || 'bg-stone-100'}>{status}</Badge>;
    };

    const columns = [
        { key: 'to_number', label: t('To') },
        {
            key: 'message',
            label: t('Message'),
            render: (row: any) => <span className="max-w-xs truncate block">{row.message}</span>,
        },
        {
            key: 'template',
            label: t('Template'),
            render: (row: any) => row.template?.name || '-',
        },
        {
            key: 'status',
            label: t('Status'),
            render: (row: any) => getStatusBadge(row.status),
        },
        {
            key: 'created_at',
            label: t('Sent At'),
            render: (row: any) => new Date(row.created_at).toLocaleString(),
        },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={t('WhatsApp Logs')} />
            <div className="p-6 space-y-6">
                <h1 className="text-2xl font-bold text-stone-900">{t('Message Logs')}</h1>

                <div className="flex gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400" />
                        <input type="text" className="w-full pl-10 border rounded-lg px-3 py-2"
                            placeholder={t('Search logs...')}
                            value={filters.search}
                            onChange={(e) => setFilters({ ...filters, search: e.target.value })} />
                    </div>
                    <select className="border rounded-lg px-3 py-2" value={filters.status}
                        onChange={(e) => setFilters({ ...filters, status: e.target.value })}>
                        <option value="">{t('All Status')}</option>
                        <option value="sent">{t('Sent')}</option>
                        <option value="pending">{t('Pending')}</option>
                        <option value="failed">{t('Failed')}</option>
                    </select>
                    <Button onClick={applyFilters}>{t('Filter')}</Button>
                </div>

                <Card>
                    <CardContent className="p-6">
                        <DataTable
                            columns={columns}
                            data={logs?.data ?? []}
                            pagination={{
                                currentPage: logs?.current_page ?? 1,
                                lastPage: logs?.last_page ?? 1,
                                total: logs?.total ?? 0,
                            }}
                        />
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
