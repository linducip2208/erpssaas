import { Head, usePage } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';
import AuthenticatedLayout from '@/layouts/authenticated-layout';
import { Card, CardContent } from '@/components/ui/card';
import { MessageCircle, FileText, Send, Clock } from 'lucide-react';

export default function Dashboard() {
    const { t } = useTranslation();
    const { stats } = usePage<{ stats: Record<string, number> }>().props;

    const statCards = [
        { label: t('Templates'), value: stats?.templates_count ?? 0, icon: FileText, color: 'text-blue-600 bg-blue-100' },
        { label: t('Messages Sent'), value: stats?.messages_sent ?? 0, icon: Send, color: 'text-green-600 bg-green-100' },
        { label: t('Sent Today'), value: stats?.messages_today ?? 0, icon: Clock, color: 'text-purple-600 bg-purple-100' },
        { label: t('Active Templates'), value: stats?.active_templates ?? 0, icon: MessageCircle, color: 'text-indigo-600 bg-indigo-100' },
    ];

    return (
        <AuthenticatedLayout>
            <Head title={t('WhatsApp Dashboard')} />
            <div className="p-6 space-y-6">
                <h1 className="text-2xl font-bold text-stone-900">{t('WhatsApp Dashboard')}</h1>
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {statCards.map((card) => (
                        <Card key={card.label}>
                            <CardContent className="p-6 flex items-center gap-4">
                                <div className={`p-3 rounded-lg ${card.color}`}>
                                    <card.icon className="w-6 h-6" />
                                </div>
                                <div>
                                    <p className="text-sm text-stone-500">{card.label}</p>
                                    <p className="text-2xl font-bold text-stone-900">{card.value}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
