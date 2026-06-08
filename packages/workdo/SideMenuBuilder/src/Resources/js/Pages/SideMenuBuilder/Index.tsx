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
import { Plus, Edit as EditIcon, Trash2, Menu, GripVertical } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { PerPageSelector } from '@/components/ui/per-page-selector';
import NoRecordsFound from '@/components/no-records-found';
import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { useForm } from "@inertiajs/react";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Switch } from '@/components/ui/switch';

interface MenuItem {
    id: number;
    title: string;
    href: string;
    icon: string;
    permission: string;
    parent_id: number | null;
    role_id: number | null;
    sort_order: number;
    is_active: boolean;
    children?: MenuItem[];
}

export default function Index() {
    const { t } = useTranslation();
    const { menus, allMenus, auth } = usePage<any>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState({
        title: urlParams.get('title') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || 'sort_order');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [showFilters, setShowFilters] = useState(false);
    const [modalState, setModalState] = useState({
        isOpen: false,
        mode: '',
        data: null as MenuItem | null
    });
    const [dragItem, setDragItem] = useState<number | null>(null);

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'side-menu-builder.menus.destroy',
        defaultMessage: t('Are you sure you want to delete this custom menu?')
    });

    const handleFilter = () => {
        router.get(route('side-menu-builder.menus.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('side-menu-builder.menus.index'), { ...filters, per_page: perPage, sort: field, direction }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ title: '' });
        router.get(route('side-menu-builder.menus.index'), { per_page: perPage });
    };

    const openModal = (mode: 'add' | 'edit', data: MenuItem | null = null) => {
        setModalState({ isOpen: true, mode, data });
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
    };

    const handleDragStart = (id: number) => {
        setDragItem(id);
    };

    const handleDragOver = (e: React.DragEvent) => {
        e.preventDefault();
    };

    const handleDrop = (targetId: number) => {
        if (dragItem === null || dragItem === targetId) return;

        const items = [...(allMenus || [])];
        const dragIndex = items.findIndex((item: any) => item.id === dragItem);
        const targetIndex = items.findIndex((item: any) => item.id === targetId);
        const [movedItem] = items.splice(dragIndex, 1);
        items.splice(targetIndex, 0, movedItem);

        const reorderedItems = items.map((item: any, index: number) => ({
            id: item.id,
            sort_order: index,
            parent_id: item.parent_id,
        }));

        router.post(route('side-menu-builder.menus.reorder'), { items: reorderedItems }, {
            preserveState: true,
            onSuccess: () => setDragItem(null)
        });
    };

    const tableColumns = [
        {
            key: 'drag',
            header: '',
            sortable: false,
            render: (_: any, menu: MenuItem) => (
                <div
                    draggable
                    onDragStart={() => handleDragStart(menu.id)}
                    onDragOver={handleDragOver}
                    onDrop={() => handleDrop(menu.id)}
                    className="cursor-grab active:cursor-grabbing"
                >
                    <GripVertical className="h-4 w-4 text-gray-400" />
                </div>
            )
        },
        {
            key: 'title',
            header: t('Title'),
            sortable: true
        },
        {
            key: 'href',
            header: t('URL'),
            sortable: false,
            render: (value: string) => value ? <span className="text-blue-600 text-xs">{value}</span> : '-'
        },
        {
            key: 'icon',
            header: t('Icon'),
            sortable: false,
            render: (value: string) => value || '-'
        },
        {
            key: 'permission',
            header: t('Permission'),
            sortable: false,
            render: (value: string) => value ? <span className="text-xs font-mono bg-gray-100 px-1 rounded">{value}</span> : '-'
        },
        {
            key: 'sort_order',
            header: t('Order'),
            sortable: true
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
        ...(() => {
            const hasAnyAction = () => {
                const permissions = auth.user?.permissions || [];
                return permissions.includes('edit-custom-menus') || permissions.includes('delete-custom-menus');
            };

            return hasAnyAction() ? [{
                key: 'actions',
                header: t('Actions'),
                render: (_: any, menu: MenuItem) => {
                    if (!hasAnyAction()) return null;

                    return (
                        <div className="flex gap-1">
                            <TooltipProvider>
                                {auth.user?.permissions?.includes('edit-custom-menus') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openModal('edit', menu)}
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
                                {auth.user?.permissions?.includes('delete-custom-menus') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openDeleteDialog(menu.id)}
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
                { label: t('Side Menu Builder') },
                { label: t('Custom Menus') }
            ]}
            pageTitle={t('Manage Custom Menus')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-custom-menus') && (
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button size="sm" onClick={() => openModal('add')}>
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Add Menu')}</p>
                            </TooltipContent>
                        </Tooltip>
                    )}
                </TooltipProvider>
            }
        >
            <Head title={t('Custom Menus')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.title}
                                onChange={(value) => setFilters({ ...filters, title: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Menus...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <PerPageSelector
                                routeName="side-menu-builder.menus.index"
                                filters={{ ...filters }}
                            />
                            <FilterButton
                                showFilters={showFilters}
                                onToggle={() => setShowFilters(!showFilters)}
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="p-0">
                    <div className="overflow-y-auto scrollbar-thin scrollbar-thumb-gray-400 scrollbar-track-gray-100 max-h-[70vh] rounded-none w-full">
                        <div className="min-w-[800px]">
                            <DataTable
                                data={menus?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Menu}
                                        title={t('No Custom Menus found')}
                                        description={t('Get started by creating your first Custom Menu.')}
                                        hasFilters={!!(filters.title)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-custom-menus"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Add Menu')}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={menus || { data: [], links: [], meta: {} }}
                        routeName="side-menu-builder.menus.index"
                        filters={{ ...filters, per_page: perPage }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <CreateMenuForm onSuccess={closeModal} />
                )}
                {modalState.mode === 'edit' && modalState.data && (
                    <CreateMenuForm menu={modalState.data} onSuccess={closeModal} />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete Custom Menu')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}

function CreateMenuForm({ menu, onSuccess }: { menu?: any; onSuccess: () => void }) {
    const { t } = useTranslation();
    const { allMenus, auth } = usePage<any>().props;
    const isEdit = !!menu;

    const { data, setData, post, put, processing, errors } = useForm({
        title: menu?.title ?? '',
        href: menu?.href ?? '',
        icon: menu?.icon ?? '',
        permission: menu?.permission ?? '',
        parent_id: menu?.parent_id?.toString() ?? '',
        role_id: menu?.role_id?.toString() ?? '',
        sort_order: menu?.sort_order ?? 0,
        is_active: menu?.is_active ?? true,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        if (isEdit) {
            put(route('side-menu-builder.menus.update', menu.id), {
                onSuccess: () => onSuccess()
            });
        } else {
            post(route('side-menu-builder.menus.store'), {
                onSuccess: () => onSuccess()
            });
        }
    };

    const parentMenus = (allMenus || []).filter((m: any) => m.parent_id === null && m.id !== menu?.id);

    return (
        <DialogContent className="max-h-[80vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>{isEdit ? t('Edit Custom Menu') : t('Create Custom Menu')}</DialogTitle>
            </DialogHeader>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="title" required>{t('Title')}</Label>
                    <Input
                        id="title"
                        type="text"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        placeholder={t('Enter Menu Title')}
                        required
                    />
                    <InputError message={errors.title} />
                </div>

                <div>
                    <Label>{t('URL')}</Label>
                    <Input
                        type="text"
                        value={data.href}
                        onChange={(e) => setData('href', e.target.value)}
                        placeholder={t('e.g. /dashboard or route name')}
                    />
                    <InputError message={errors.href} />
                </div>

                <div>
                    <Label>{t('Icon')}</Label>
                    <Input
                        type="text"
                        value={data.icon}
                        onChange={(e) => setData('icon', e.target.value)}
                        placeholder={t('e.g. Home, Settings, Users')}
                    />
                    <InputError message={errors.icon} />
                </div>

                <div>
                    <Label>{t('Permission')}</Label>
                    <Input
                        type="text"
                        value={data.permission}
                        onChange={(e) => setData('permission', e.target.value)}
                        placeholder={t('e.g. manage-users')}
                    />
                    <InputError message={errors.permission} />
                </div>

                <div>
                    <Label>{t('Parent Menu')}</Label>
                    <Select value={data.parent_id} onValueChange={(value) => setData('parent_id', value)}>
                        <SelectTrigger>
                            <SelectValue placeholder={t('Select Parent (optional)')} />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">{t('None (Top Level)')}</SelectItem>
                            {parentMenus?.map((p: any) => (
                                <SelectItem key={p.id} value={p.id.toString()}>
                                    {p.title}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.parent_id} />
                </div>

                <div>
                    <Label>{t('Sort Order')}</Label>
                    <Input
                        type="number"
                        value={data.sort_order}
                        onChange={(e) => setData('sort_order', parseInt(e.target.value) || 0)}
                    />
                    <InputError message={errors.sort_order} />
                </div>

                <div className="flex items-center gap-3">
                    <Label>{t('Active')}</Label>
                    <Switch
                        checked={data.is_active}
                        onCheckedChange={(checked) => setData('is_active', checked)}
                    />
                </div>

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={onSuccess}>
                        {t('Cancel')}
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing ? (isEdit ? t('Updating...') : t('Creating...')) : (isEdit ? t('Update') : t('Create'))}
                    </Button>
                </div>
            </form>
        </DialogContent>
    );
}
