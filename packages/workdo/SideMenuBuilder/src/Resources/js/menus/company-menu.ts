import { Menu } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const sidemenubuilderCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Side Menu Builder'),
        icon: Menu,
        permission: 'manage-side-menu-builder',
        order: 255,
        parent: 'customization',
        children: [
            {
                title: t('Custom Menus'),
                href: route('side-menu-builder.menus.index'),
                permission: 'manage-custom-menus',
            },
        ],
    },
];
