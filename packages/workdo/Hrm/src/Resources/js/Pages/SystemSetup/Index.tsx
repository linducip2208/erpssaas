import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import SystemSetupSidebar from './SystemSetupSidebar';

export default function Index() {
    const { t } = useTranslation();

    return (
        <AuthenticatedLayout breadcrumbs={[{ label: t('System Setup') }]} pageTitle={t('System Setup')}>
            <div className="flex flex-col gap-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold tracking-tight">
                        {t('System Setup')}
                    </h1>
                    <p className="text-muted-foreground">
                        {t('Configure HRM system settings')}
                    </p>
                </div>
                <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
                    <div className="lg:col-span-1">
                        <SystemSetupSidebar />
                    </div>
                    <div className="lg:col-span-3 flex items-center justify-center min-h-[400px] border rounded-lg bg-muted/30">
                        <div className="text-center p-8">
                            <h2 className="text-xl font-semibold text-muted-foreground mb-2">
                                {t('System Setup')}
                            </h2>
                            <p className="text-muted-foreground">
                                {t('Select a section from the sidebar to configure HRM system settings.')}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
