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
import { Plus, Trash2, RefreshCw, Ban, Webhook, Copy } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FilterButton } from '@/components/ui/filter-button';
import { Pagination } from "@/components/ui/pagination";
import { SearchInput } from "@/components/ui/search-input";
import { ListGridToggle } from '@/components/ui/list-grid-toggle';
import { PerPageSelector } from '@/components/ui/per-page-selector';
import NoRecordsFound from '@/components/no-records-found';
import { DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { useForm } from "@inertiajs/react";
import { Switch } from '@/components/ui/switch';

interface Token {
    id: number;
    name: string;
    token: string;
    permissions: string[];
    last_used_at: string;
    expires_at: string;
    is_active: boolean;
}

export default function Index() {
    const { t } = useTranslation();
    const { tokens, auth } = usePage<any>().props;
    const urlParams = new URLSearchParams(window.location.search);

    const [filters, setFilters] = useState({
        name: urlParams.get('name') || '',
    });

    const [perPage] = useState(urlParams.get('per_page') || '10');
    const [sortField, setSortField] = useState(urlParams.get('sort') || '');
    const [sortDirection, setSortDirection] = useState(urlParams.get('direction') || 'asc');
    const [viewMode, setViewMode] = useState<'list' | 'grid'>(urlParams.get('view') as 'list' | 'grid' || 'list');
    const [showFilters, setShowFilters] = useState(false);
    const [modalState, setModalState] = useState({
        isOpen: false,
        mode: '',
        data: null as Token | null
    });
    const [newToken, setNewToken] = useState('');

    const { deleteState, openDeleteDialog, closeDeleteDialog, confirmDelete } = useDeleteHandler({
        routeName: 'api.tokens.destroy',
        defaultMessage: t('Are you sure you want to delete this API token?')
    });

    const handleFilter = () => {
        router.get(route('api.tokens.index'), { ...filters, per_page: perPage, sort: sortField, direction: sortDirection, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const handleSort = (field: string) => {
        const direction = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        setSortField(field);
        setSortDirection(direction);
        router.get(route('api.tokens.index'), { ...filters, per_page: perPage, sort: field, direction, view: viewMode }, {
            preserveState: true,
            replace: true
        });
    };

    const clearFilters = () => {
        setFilters({ name: '' });
        router.get(route('api.tokens.index'), { per_page: perPage, view: viewMode });
    };

    const openModal = (mode: 'add' | 'edit', data: Token | null = null) => {
        setModalState({ isOpen: true, mode, data });
        setNewToken('');
    };

    const closeModal = () => {
        setModalState({ isOpen: false, mode: '', data: null });
        setNewToken('');
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
    };

    const tableColumns = [
        {
            key: 'name',
            header: t('Name'),
            sortable: true
        },
        {
            key: 'token',
            header: t('Token'),
            sortable: false,
            render: (value: string) => (
                <div className="flex items-center gap-2">
                    <span className="font-mono text-xs truncate max-w-[200px]">{value.substring(0, 16)}...</span>
                    <TooltipProvider>
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => copyToClipboard(value)}
                                    className="h-6 w-6 p-0"
                                >
                                    <Copy className="h-3 w-3" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Copy')}</p>
                            </TooltipContent>
                        </Tooltip>
                    </TooltipProvider>
                </div>
            )
        },
        {
            key: 'last_used_at',
            header: t('Last Used'),
            sortable: false,
            render: (value: string) => value ? new Date(value).toLocaleString() : '-'
        },
        {
            key: 'expires_at',
            header: t('Expires'),
            sortable: false,
            render: (value: string) => value ? new Date(value).toLocaleDateString() : t('Never')
        },
        {
            key: 'is_active',
            header: t('Status'),
            sortable: false,
            render: (value: boolean) => (
                <span className={`px-2 py-1 rounded-full text-sm ${value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                    {value ? t('Active') : t('Revoked')}
                </span>
            )
        },
        ...(() => {
            const hasAnyAction = () => {
                const permissions = auth.user?.permissions || [];
                return permissions.includes('regenerate-api-tokens') || permissions.includes('revoke-api-tokens') || permissions.includes('delete-api-tokens');
            };

            return tokens?.data?.some(() => hasAnyAction()) ? [{
                key: 'actions',
                header: t('Actions'),
                render: (_: any, token: Token) => {
                    if (!hasAnyAction()) return null;

                    return (
                        <div className="flex gap-1">
                            <TooltipProvider>
                                {auth.user?.permissions?.includes('regenerate-api-tokens') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => router.post(route('api.tokens.regenerate', token.id))}
                                                className="h-8 w-8 p-0 text-blue-600 hover:text-blue-700"
                                            >
                                                <RefreshCw className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Regenerate')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                                {token.is_active && auth.user?.permissions?.includes('revoke-api-tokens') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => router.post(route('api.tokens.revoke', token.id))}
                                                className="h-8 w-8 p-0 text-orange-600 hover:text-orange-700"
                                            >
                                                <Ban className="h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            <p>{t('Revoke')}</p>
                                        </TooltipContent>
                                    </Tooltip>
                                )}
                                {auth.user?.permissions?.includes('delete-api-tokens') && (
                                    <Tooltip delayDuration={0}>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => openDeleteDialog(token.id)}
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
                { label: t('REST API') },
                { label: t('API Tokens') }
            ]}
            pageTitle={t('Manage API Tokens')}
            pageActions={
                <TooltipProvider>
                    {auth.user?.permissions?.includes('create-api-tokens') && (
                        <Tooltip delayDuration={0}>
                            <TooltipTrigger asChild>
                                <Button size="sm" onClick={() => openModal('add')}>
                                    <Plus className="h-4 w-4" />
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>{t('Generate Token')}</p>
                            </TooltipContent>
                        </Tooltip>
                    )}
                </TooltipProvider>
            }
        >
            <Head title={t('API Tokens')} />

            <Card className="shadow-sm">
                <CardContent className="p-6 border-b bg-gray-50/50">
                    <div className="flex items-center justify-between gap-4">
                        <div className="flex-1 max-w-md">
                            <SearchInput
                                value={filters.name}
                                onChange={(value) => setFilters({ ...filters, name: value })}
                                onSearch={handleFilter}
                                placeholder={t('Search Tokens...')}
                            />
                        </div>
                        <div className="flex items-center gap-3">
                            <ListGridToggle
                                currentView={viewMode}
                                routeName="api.tokens.index"
                                filters={{ ...filters, per_page: perPage }}
                            />
                            <PerPageSelector
                                routeName="api.tokens.index"
                                filters={{ ...filters, view: viewMode }}
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
                                data={tokens?.data || []}
                                columns={tableColumns}
                                onSort={handleSort}
                                sortKey={sortField}
                                sortDirection={sortDirection as 'asc' | 'desc'}
                                className="rounded-none"
                                emptyState={
                                    <NoRecordsFound
                                        icon={Webhook}
                                        title={t('No API Tokens found')}
                                        description={t('Get started by generating your first API Token.')}
                                        hasFilters={!!(filters.name)}
                                        onClearFilters={clearFilters}
                                        createPermission="create-api-tokens"
                                        onCreateClick={() => openModal('add')}
                                        createButtonText={t('Generate Token')}
                                        className="h-auto"
                                    />
                                }
                            />
                        </div>
                    </div>
                </CardContent>

                <CardContent className="px-4 py-2 border-t bg-gray-50/30">
                    <Pagination
                        data={tokens || { data: [], links: [], meta: {} }}
                        routeName="api.tokens.index"
                        filters={{ ...filters, per_page: perPage, view: viewMode }}
                    />
                </CardContent>
            </Card>

            <Dialog open={modalState.isOpen} onOpenChange={closeModal}>
                {modalState.mode === 'add' && (
                    <CreateTokenForm
                        onSuccess={(token) => { setNewToken(token); }}
                        onClose={closeModal}
                    />
                )}
            </Dialog>

            <ConfirmationDialog
                open={deleteState.isOpen}
                onOpenChange={closeDeleteDialog}
                title={t('Delete API Token')}
                message={deleteState.message}
                confirmText={t('Delete')}
                onConfirm={confirmDelete}
                variant="destructive"
            />
        </AuthenticatedLayout>
    );
}

function CreateTokenForm({ onSuccess, onClose }: { onSuccess: (token: string) => void; onClose: () => void }) {
    const { t } = useTranslation();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        permissions: [] as string[],
        expires_at: '',
    });

    const [generatedToken, setGeneratedToken] = useState('');

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('api.tokens.store'), {
            onSuccess: (page: any) => {
                onSuccess('');
                onClose();
            }
        });
    };

    return (
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{t('Generate API Token')}</DialogTitle>
            </DialogHeader>
            {generatedToken ? (
                <div className="space-y-4">
                    <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p className="text-sm font-medium text-yellow-800 mb-2">{t('Make sure to copy your token now. You won\'t be able to see it again!')}</p>
                        <div className="flex items-center gap-2">
                            <Input value={generatedToken} readOnly className="font-mono text-sm" />
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => navigator.clipboard.writeText(generatedToken)}
                            >
                                <Copy className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                    <Button className="w-full" onClick={onClose}>{t('Done')}</Button>
                </div>
            ) : (
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <Label htmlFor="name">{t('Token Name')}</Label>
                        <Input
                            id="name"
                            type="text"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            placeholder={t('Enter Token Name')}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div>
                        <Label>{t('Expires At')}</Label>
                        <Input
                            type="date"
                            value={data.expires_at}
                            onChange={(e) => setData('expires_at', e.target.value)}
                        />
                        <InputError message={errors.expires_at} />
                    </div>

                    <div className="flex justify-end gap-2">
                        <Button type="button" variant="outline" onClick={onClose}>
                            {t('Cancel')}
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? t('Generating...') : t('Generate')}
                        </Button>
                    </div>
                </form>
            )}
        </DialogContent>
    );
}
