import { useState } from 'react';
import { Head, usePage, router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from "@/layouts/authenticated-layout";
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from "@/components/ui/card";
import { Dialog } from "@/components/ui/dialog";
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { FileSharingProps, SharedLink } from './types';

export default function Share() {
    const { t } = useTranslation();
    const { auth } = usePage().props as any;
    const [links, setLinks] = useState<SharedLink[]>([]);
    const [showCreate, setShowCreate] = useState(false);
    const [fileId, setFileId] = useState<number | null>(null);
    const [expiresAt, setExpiresAt] = useState('');

    const handleCreateLink = () => {
        if (!fileId) return;
        router.post(route('shared-links.store'), {
            file_id: fileId,
            expires_at: expiresAt || null,
        }, {
            onSuccess: () => setShowCreate(false),
        });
    };

    const handleDeleteLink = (id: number) => {
        if (confirm(t('Delete this link?'))) {
            router.delete(route('shared-links.destroy', id));
        }
    };

    const handleToggleLink = (id: number) => {
        router.patch(route('shared-links.toggle', id));
    };

    return (
        <AuthenticatedLayout>
            <Head title={t('Share Links')} />
            <div className="flex-1 space-y-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{t('Share Links')}</h1>
                        <p className="text-muted-foreground text-sm">{t('Manage shared file links')}</p>
                    </div>
                </div>

                <Card>
                    <CardContent className="pt-6">
                        <p className="text-muted-foreground text-sm">{t('Create share links from the File Browser.')}</p>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
