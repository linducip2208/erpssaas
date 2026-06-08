import { LayoutTemplate } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const projectTemplateCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Templates'),
        icon: LayoutTemplate,
        permission: 'manage-project-template',
        order: 160,
        name: 'project-template',
        children: [
            {
                title: t('Project Templates'),
                href: route('project-template.index'),
                permission: 'manage-project-template',
                order: 5,
            },
        ],
    },
];
