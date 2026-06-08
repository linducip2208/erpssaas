import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Plus, Folder, File, Download, Trash2, Link as LinkIcon, Shield, ChevronRight, FolderPlus } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { FileSharingProps, SharedFolder, SharedFile } from './types';

export default function Index() {
    const { t } = useTranslation();
    const { folders, folder, auth } = usePage<FileSharingProps>().props;
    const [showCreateFolder, setShowCreateFolder] = useState(false);
    const [newFolderName, setNewFolderName] = useState('');
    const [currentFolder, setCurrentFolder] = useState<SharedFolder | null>(folder || null);

    const handleCreateFolder = () => {
        router.post(route('shared-folders.store'), {
            name: newFolderName,
            parent_id: currentFolder?.id || null,
        }, {
            onSuccess: () => {
                setShowCreateFolder(false);
                setNewFolderName('');
            }
        });
    };

    const handleDeleteFolder = (id: number) => {
        if (confirm(t('Are you sure you want to delete this folder?'))) {
            router.delete(route('shared-folders.destroy', id));
        }
    };

    const handleFileUpload = (folderId: number, file: File) => {
        const formData = new FormData();
        formData.append('folder_id', folderId.toString());
        formData.append('file', file);

        router.post(route('shared-files.store'), formData, {
            forceFormData: true,
        });
    };

    const handleDeleteFile = (id: number) => {
        if (confirm(t('Are you sure?'))) {
            router.delete(route('shared-files.destroy', id));
        }
    };

    const handleCreateLink = (fileId: number) => {
        router.post(route('shared-links.store'), { file_id: fileId });
    };

    const formatFileSize = (bytes: number) => {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / 1048576).toFixed(1) + ' MB';
    };

    const navigateToFolder = (folder: SharedFolder) => {
        router.get(route('file-sharing.show', folder.id));
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('File Sharing')} />
            <div className="flex-1 space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{t('File Sharing')}</h1>
                        <p className="text-muted-foreground text-sm">
                            {currentFolder ? currentFolder.name : t('Root Folder')}
                        </p>
                    </div>
                    {auth.user.can('create-shared-folders') && (
                        <Button onClick={() => setShowCreateFolder(true)}>
                            <FolderPlus className="mr-2 h-4 w-4" />
                            {t('New Folder')}
                        </Button>
                    )}
                </div>

                {showCreateFolder && (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex gap-3">
                                <input
                                    type="text"
                                    className="flex-1 rounded-md border px-3 py-2 text-sm"
                                    placeholder={t('Folder name')}
                                    value={newFolderName}
                                    onChange={(e) => setNewFolderName(e.target.value)}
                                    onKeyDown={(e) => e.key === 'Enter' && handleCreateFolder()}
                                />
                                <Button onClick={handleCreateFolder}>{t('Create')}</Button>
                                <Button variant="outline" onClick={() => setShowCreateFolder(false)}>{t('Cancel')}</Button>
                            </div>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <h2 className="font-semibold">{t('Folders')}</h2>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                            {(currentFolder ? currentFolder.children : folders)?.map((f: SharedFolder) => (
                                <div
                                    key={f.id}
                                    className="flex flex-col items-center p-4 rounded-lg border hover:bg-accent cursor-pointer group relative"
                                    onClick={() => navigateToFolder(f)}
                                >
                                    <Folder className="h-10 w-10 text-indigo-500 mb-2" />
                                    <span className="text-sm text-center truncate w-full">{f.name}</span>
                                    <div className="absolute top-1 right-1 opacity-0 group-hover:opacity-100 flex gap-1">
                                        {auth.user.can('delete-shared-folders') && (
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                className="h-6 w-6 p-0"
                                                onClick={(e) => { e.stopPropagation(); handleDeleteFolder(f.id); }}
                                            >
                                                <Trash2 className="h-3 w-3 text-red-500" />
                                            </Button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <h2 className="font-semibold">{t('Files')}</h2>
                    </CardHeader>
                    <CardContent>
                        {currentFolder?.files && currentFolder.files.length > 0 ? (
                            <div className="space-y-2">
                                {currentFolder.files.map((file: SharedFile) => (
                                    <div key={file.id} className="flex items-center justify-between p-3 rounded-lg border hover:bg-accent">
                                        <div className="flex items-center gap-3">
                                            <File className="h-5 w-5 text-blue-500" />
                                            <div>
                                                <p className="text-sm font-medium">{file.file_name}</p>
                                                <p className="text-xs text-muted-foreground">{formatFileSize(file.file_size)}</p>
                                            </div>
                                        </div>
                                        <div className="flex gap-1">
                                            <TooltipProvider>
                                                <Tooltip>
                                                    <TooltipTrigger asChild>
                                                        <Button size="sm" variant="ghost" className="h-8 w-8 p-0"
                                                            onClick={() => window.open(route('shared-files.download', file.id), '_blank')}>
                                                            <Download className="h-4 w-4" />
                                                        </Button>
                                                    </TooltipTrigger>
                                                    <TooltipContent>{t('Download')}</TooltipContent>
                                                </Tooltip>
                                            </TooltipProvider>
                                            {auth.user.can('create-shared-links') && (
                                                <TooltipProvider>
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <Button size="sm" variant="ghost" className="h-8 w-8 p-0"
                                                                onClick={() => handleCreateLink(file.id)}>
                                                                <LinkIcon className="h-4 w-4" />
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>{t('Create Share Link')}</TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>
                                            )}
                                            {auth.user.can('delete-shared-files') && (
                                                <TooltipProvider>
                                                    <Tooltip>
                                                        <TooltipTrigger asChild>
                                                            <Button size="sm" variant="ghost" className="h-8 w-8 p-0"
                                                                onClick={() => handleDeleteFile(file.id)}>
                                                                <Trash2 className="h-4 w-4 text-red-500" />
                                                            </Button>
                                                        </TooltipTrigger>
                                                        <TooltipContent>{t('Delete')}</TooltipContent>
                                                    </Tooltip>
                                                </TooltipProvider>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground text-sm py-4 text-center">{t('No files in this folder.')}</p>
                        )}
                        {currentFolder && auth.user.can('create-shared-files') && (
                            <div className="mt-4">
                                <label className="cursor-pointer inline-flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-700">
                                    <Plus className="h-4 w-4" />
                                    {t('Upload File')}
                                    <input
                                        type="file"
                                        className="hidden"
                                        onChange={(e) => {
                                            const file = e.target.files?.[0];
                                            if (file && currentFolder) handleFileUpload(currentFolder.id, file);
                                        }}
                                    />
                                </label>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {currentFolder?.permissions && (
                    <Card>
                        <CardHeader>
                            <h2 className="font-semibold flex items-center gap-2">
                                <Shield className="h-5 w-5" />
                                {t('Folder Permissions')}
                            </h2>
                        </CardHeader>
                        <CardContent>
                            {currentFolder.permissions.length > 0 ? (
                                <div className="space-y-2">
                                    {currentFolder.permissions.map((perm) => (
                                        <div key={perm.id} className="flex items-center justify-between p-2 rounded border">
                                            <span className="text-sm">{perm.user?.name} ({perm.user?.email})</span>
                                            <span className="text-xs px-2 py-1 rounded bg-indigo-100 text-indigo-700">{perm.permission}</span>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">{t('No permissions set.')}</p>
                            )}
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
