import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { FileText } from "lucide-react";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import NoRecordsFound from '@/components/no-records-found';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

interface Log {
    id: number;
    method: string;
    endpoint: string;
    response_code: number;
    ip_address: string;
    created_at: string;
    token?: { name: string };
}

export default function Logs() {
    const { t } = useTranslation();
    const { logs } = usePage<any>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState({
        method: urlParams.get('method') || '',
        endpoint: urlParams.get('endpoint') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || 'created_at');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'desc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);

    const handleFilter = () => {
        router.get(route('api.logs.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('api.logs.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ method: '', endpoint: '' });
        router.get(route('api.logs.index'), { per_page: perPage, view: viewMode });
    };

    const getMethodColor = (method: string) => {
        switch (method?.toUpperCase()) {
            case 'GET': return 'bg-blue-100 text-blue-800';
            case 'POST': return 'bg-green-100 text-green-800';
            case 'PUT': return 'bg-orange-100 text-orange-800';
            case 'PATCH': return 'bg-yellow-100 text-yellow-800';
            case 'DELETE': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusColor = (code: number) => {
        if (code >= 200 && code < 300) return 'bg-green-100 text-green-800';
        if (code >= 300 && code < 400) return 'bg-blue-100 text-blue-800';
        if (code >= 400 && code < 500) return 'bg-orange-100 text-orange-800';
        if (code >= 500) return 'bg-red-100 text-red-800';
        return 'bg-gray-100 text-gray-800';
    };

    const tableColumns = [
        {
            key: 'token',
            header: t('Token'),
            sortable: false,
            render: (_: any, row: Log) => row.token?.name || '-'
        },
        {
            key: 'method',
            header: t('Method'),
            sortable: false,
            render: (value: string) => (
                <span className={`px-2 py-1 rounded-full text-sm font-mono font-semibold ${getMethodColor(value)}`}>
                    {value}
                </span>
            )
        },
        {
            key: 'endpoint',
            header: t('Endpoint'),
            sortable: false,
            render: (value: string) => (
                <span className="font-mono text-xs">{value}</span>
            )
        },
        {
            key: 'response_code',
            header: t('Status'),
            sortable: false,
            render: (value: number) => (
                <span className={`px-2 py-1 rounded-full text-sm font-semibold ${getStatusColor(value)}`}>
                    {value}
                </span>
            )
        },
        {
            key: 'ip_address',
            header: t('IP Address'),
            sortable: false
        },
        {
            key: 'created_at',
            header: t('Date'),
            sortable: true,
            render: (value: string) => value ? new Date(value).toLocaleString() : '-'
        },
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('REST API') },
                { label: t('API Logs') }
            ]}
            pageTitle={t('API Logs')}
        >
            <Head title={t('API Logs')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.endpoint}
                                onChange={(value) => setFilters({ ...filters, endpoint: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Endpoints...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="api.logs.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="api.logs.index"
                                filters={{ ...filters, view: viewMode }}
                            />
                            <FilterButton
                                showFilters={showFilters}
                                onToggle={() => setShowFilters(!showFilters)}
                            />
                        </div>
                    </div>
                </CardContent>

                {showFilters && (
                    <CardContent className="p-6 bg-blue-50/30 border-b">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('HTTP Method')}</label>
                                <Select value={filters.method} onValueChange={(value) => setFilters({ ...filters, method: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Method')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="GET">GET</SelectItem>
                                        <SelectItem value="POST">POST</SelectItem>
                                        <SelectItem value="PUT">PUT</SelectItem>
                                        <SelectItem value="PATCH">PATCH</SelectItem>
                                        <SelectItem value="DELETE">DELETE</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end gap-2">
                                <Button onClick={handleFilter} size="sm">{t('Apply')}</Button>
                                <Button variant="outline" onClick={clearFilters} size="sm">{t('Clear')}</Button>
                            </div>
                        </div>
                    </CardContent>
                )}

                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[900px]">
                            <DataTable
                                data={logs?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={FileText}
                                        title={t('No API Logs found')}
                                        description={t('API request logs will appear here when API endpoints are called.')}
                                        hasFilters={!!(filters.method || filters.endpoint)}
                                        onClearFilters={clearFilters}
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={logs || { data: [], links: [], meta: {} }}
                        routeName="api.logs.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
