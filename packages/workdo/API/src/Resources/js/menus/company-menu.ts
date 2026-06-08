import { Webhook } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const apiCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('REST API'),
        icon: Webhook,
        permission: 'manage-api',
        order: 250,
        parent: 'integration',
        children: [
            {
                title: t('API Tokens'),
                href: route('api.tokens.index'),
                permission: 'manage-api-tokens',
            },
            {
                title: t('API Logs'),
                href: route('api.logs.index'),
                permission: 'view-api-logs',
            },
        ],
    },
];
