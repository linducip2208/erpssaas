import { Mail, PenLine, Settings } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const emailboxCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Email Box'),
        icon: Mail,
        permission: 'manage-emailbox',
        order: 385,
        name: 'emailbox',
        children: [
            {
                title: t('Inbox'),
                href: route('emailbox.index'),
                permission: 'manage-emailbox',
                order: 10,
            },
            {
                title: t('Compose'),
                href: route('emailbox.index'),
                params: { compose: 'true' },
                permission: 'send-emails',
                order: 20,
            },
            {
                title: t('Mailbox Settings'),
                href: route('emailbox.index'),
                params: { settings: 'true' },
                permission: 'manage-emailbox-settings',
                order: 30,
            },
        ],
    },
];
