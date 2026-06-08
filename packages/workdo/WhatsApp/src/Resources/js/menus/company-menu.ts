import { MessageCircle, FileText, History, Settings } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const whatsappCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('WhatsApp API'),
        icon: MessageCircle,
        permission: 'manage-whatsapp',
        order: 380,
        name: 'whatsapp',
        children: [
            {
                title: t('Dashboard'),
                href: route('whatsapp.dashboard'),
                permission: 'manage-whatsapp',
                order: 10,
            },
            {
                title: t('Templates'),
                href: route('whatsapp.templates.index'),
                permission: 'manage-whatsapp-templates',
                order: 20,
            },
            {
                title: t('Message Logs'),
                href: route('whatsapp.logs.index'),
                permission: 'manage-whatsapp-logs',
                order: 30,
            },
            {
                title: t('Settings'),
                href: route('whatsapp.settings.index'),
                permission: 'manage-whatsapp-settings',
                order: 40,
            },
        ],
    },
];
