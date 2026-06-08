import { FileText } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const aiDocumentCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('AI Document'),
        icon: FileText,
        permission: 'manage-ai-document',
        order: 340,
        name: 'ai-document',
        children: [
            {
                title: t('Prompts & Generate'),
                href: route('ai-document.prompts.index'),
                permission: 'manage-ai-document',
                order: 5,
                activePaths: [route('ai-document.generations')],
            },
            {
                title: t('Settings'),
                href: route('ai-document.settings.index'),
                permission: 'manage-ai-document-settings',
                order: 10,
            },
        ],
    },
];
