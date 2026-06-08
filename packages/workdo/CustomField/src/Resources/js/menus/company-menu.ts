import { Puzzle } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const customfieldCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Custom Field'),
        icon: Puzzle,
        permission: 'manage-custom-fields',
        order: 240,
        parent: 'customization',
        children: [
            {
                title: t('Field Groups'),
                href: route('custom-field.groups.index'),
                permission: 'manage-custom-field-groups',
            },
            {
                title: t('Fields'),
                href: route('custom-field.fields.index'),
                permission: 'manage-custom-field-definitions',
            },
        ],
    },
];
