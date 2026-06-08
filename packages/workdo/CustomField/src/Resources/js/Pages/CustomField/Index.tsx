import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import { useDeleteHandler } from '@/hooks/useDeleteHandler';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/ui/data-table";
import { Dialog } from "@/components/ui/dialog";
import { ConfirmationDialog } from '@/components/ui/confirmation-dialog';
import { Plus, Edit as EditIcon, Trash2, Puzzle } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import NoRecordsFound from '@/components/no-records-found';
import Create from './Create';

interface Group {
    id: number;
    name: string;
    target_module: string;
    target_type: string;
    is_active: boolean;
    fields?: any[];
}

export default function Index() {
    const { t } = useTranslation();
    const { groups, auth } = usePage<any>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState({
        name: urlParams.get('name') || '',
        target_module: urlParams.get('target_module') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);
    const [modalState, setModalState] = useState({
        isOpen: false,
        mode: '',
        data: null
    });

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'custom-field.groups.destroy',
        defaultMessage: t('Are you sure you want to delete this custom field group?')
    });

    const handleFilter = () => {
        router.get(route('custom-field.groups.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('custom-field.groups.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ name: '', target_module: '' });
        router.get(route('custom-field.groups.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: Group | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'target_module',
            header: t('Target Module'),
            sortable: false
        },
        {
            key: 'is_active',
            header: t('Active'),
            sortable: false,
            render: (value: boolean) => (
                <span className={`px-2 py-1 rounded-full text-sm ${value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                    {value ? t('Active') : t('Inactive')}
                </span>
            )
        },
        {
            key: 'fields',
            header: t('Fields'),
            sortable: false,
            render: (_: any, row: Group) => row.fields?.length || 0
        },
        ...(() => {
            const hasAnyActionPermission = () => {
                const permissions = auth.user?.permissions || [];
                return permissions.includes('edit-custom-field-groups') || permissions.includes('delete-custom-field-groups');
            };

            return groups?.data?.some(() => hasAnyActionPermission()) ? [{
                key: 'actions',
                header: t('Actions'),
                render: (_: any, group: Group) => {
                    if (!hasAnyActionPermission()) return null;

                    return (
                        <div className="flex gap-1">
                            <TooltipProvider>
                                {auth.user?.permissions?.includes('edit-custom-field-groups') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openModal('edit', group)}
                                                className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                            >
                                                <EditIcon className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Edit')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                                {auth.user?.permissions?.includes('delete-custom-field-groups') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openDeleteDialog(group.id)}
                                                className="h-8 w-8 p-0 text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Delete')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                            </TooltipProvider>
                        </div>
                    );
                }
            }] : [];
        })()
    ];

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: t('Custom Field') },
                { label: t('Field Groups') }
            ]}
            pageTitle={t('Manage Custom Field Groups')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-custom-field-groups') && (
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button size="sm" onClick={() => openModal('add')}>
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Create')}</p>
                            </TooltipContent>
                        </Tooltip>
                    )}
                </TooltipProvider>
            }
        >
            <Head title={t('Custom Field Groups')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.name}
                                onChange={(value) => setFilters({ ...filters, name: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Groups...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="custom-field.groups.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="custom-field.groups.index"
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
                                <label className="block text-sm font-medium text-gray-700 mb-2">{t('Target Module')}</label>
                                <Select value={filters.target_module} onValueChange={(value) => setFilters({ ...filters, target_module: value })}>
                                    <SelectTrigger>
                                        <SelectValue placeholder={t('Filter by Module')} />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="sales">{t('Sales')}</SelectItem>
                                        <SelectItem value="inventory">{t('Inventory')}</SelectItem>
                                        <SelectItem value="hr">{t('HR')}</SelectItem>
                                        <SelectItem value="accounting">{t('Accounting')}</SelectItem>
                                        <SelectItem value="crm">{t('CRM')}</SelectItem>
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
                        <div className="min-w-[800px]">
                            <DataTable
                                data={groups?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Puzzle}
                                        title={t('No Custom Field Groups found')}
                                        description={t('Get started by creating your first Custom Field Group.')}
                                        hasFilters={!!(filters.name || filters.target_module)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-custom-field-groups"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Create Custom Field Group')}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={groups || { data: [], links: [], meta: {} }}
                        routeName="custom-field.groups.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <Create onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <Create
                        group={modalState.data}
                        onSuccess={closeModal}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Custom Field Group')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}
