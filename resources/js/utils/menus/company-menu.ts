import { LayoutGrid, Users, Warehouse,ArrowRightLeft, Package, Tag, Tags, Shield, Settings, Image, CreditCard, Headphones, ShoppingCart, Kanban, Calendar, MessageCircle, Replace ,Receipt, Truck, ClipboardList, FileText, HardDrive, Store, Puzzle, Plug, Folder, Clock } from 'lucide-react';
import { NavItem } from '@/types';

export const getCompanyMenu = (t: (key: string) => string): NavItem[] => [
    {
        title: t('Dashboard'),
        icon: LayoutGrid,
        permission: 'manage-dashboard',
        name: 'dashboard',
        order: 1,
    },
    {
        title: t('User Management'),
        icon: Users,
        permission: 'manage-users',
        order: 10,
        children: [
            {
                title: t('Roles'),
                href: route('roles.index'),
                permission: 'manage-roles',
            },
            {
                title: t('Users'),
                href: route('users.index'),
                permission: 'manage-users',
            },
        ],
    },
    {
        title: t('Sales'),
        icon: Store,
        permission: 'manage-sales-invoices',
        order: 20,
        children: [
            {
                title: t('Proposals'),
                href: route('sales-proposals.index'),
                permission: 'manage-sales-proposals',
            },
            {
                title: t('Sales Orders'),
                href: route('sales-orders.index'),
                permission: 'manage-sales-invoices',
            },
            {
                title: t('Delivery Orders'),
                href: route('delivery-orders.index'),
                permission: 'manage-sales-invoices',
            },
            {
                title: t('Sales Invoices'),
                href: route('sales-invoices.index'),
                permission: 'manage-sales-invoices',
            },
            {
                title: t('Sales Returns'),
                href: route('sales-returns.index'),
                permission: 'manage-sales-return-invoices',
            },
        ],
    },
    {
        title: t('Purchase'),
        icon: ShoppingCart,
        permission: 'manage-purchase-invoices',
        order: 30,
        children: [
            {
                title: t('Purchase Requisitions'),
                href: route('purchase-requisitions.index'),
                permission: 'manage-purchase-invoices',
            },
            {
                title: t('Request Quotations'),
                href: route('request-quotations.index'),
                permission: 'manage-purchase-invoices',
            },
            {
                title: t('Purchase Orders'),
                href: route('purchase-orders.index'),
                permission: 'manage-purchase-invoices',
            },
            {
                title: t('Goods Receipt Notes'),
                href: route('goods-receipt-notes.index'),
                permission: 'manage-purchase-invoices',
            },
            {
                title: t('Purchase Invoices'),
                href: route('purchase-invoices.index'),
                permission: 'manage-purchase-invoices',
            },
            {
                title: t('Purchase Returns'),
                href: route('purchase-returns.index'),
                permission: 'manage-purchase-return-invoices',
            },
            {
                title: t('Warehouses'),
                href: route('warehouses.index'),
                permission: 'manage-warehouses',
            },
            {
                title: t('Transfers'),
                href: route('transfers.index'),
                permission: 'manage-transfers',
            },
        ],
    },
    {
        title: t('Fixed Assets'),
        icon: HardDrive,
        permission: 'manage-settings',
        order: 50,
        children: [
            {
                title: t('Asset Categories'),
                href: route('asset-categories.index'),
                permission: 'manage-settings',
            },
            {
                title: t('Fixed Assets'),
                href: route('fixed-assets.index'),
                permission: 'manage-settings',
            },
            {
                title: t('Depreciations'),
                href: route('asset-depreciations.index'),
                permission: 'manage-settings',
            },
            {
                title: t('Disposals'),
                href: route('asset-disposals.index'),
                permission: 'manage-settings',
            },
        ],
    },
    {
        title: t('Rotas'),
        icon: Clock,
        permission: 'manage-rotas',
        name: 'rotas',
        order: 370,
        children: [
            {
                title: t('Rotas'),
                href: route('rotas.index'),
                permission: 'manage-rotas',
            },
            {
                title: t('Templates'),
                href: route('rota-templates.index'),
                permission: 'manage-rota-templates',
            },
        ],
    },
    {
        title: t('Appointment'),
        icon: Calendar,
        permission: 'manage-appointments',
        name: 'appointment',
        order: 410,
        children: [
            {
                title: t('Appointments'),
                href: route('appointments.index'),
                permission: 'manage-appointments',
            },
            {
                title: t('Types'),
                href: route('appointment-types.index'),
                permission: 'manage-appointment-types',
            },
            {
                title: t('Availability'),
                href: route('appointment-availability.index'),
                permission: 'manage-appointment-availability',
            },
        ],
    },
    {
        title: t('File Sharing'),
        icon: Folder,
        permission: 'manage-file-sharing',
        order: 500,
        children: [
            {
                title: t('File Browser'),
                href: route('file-sharing.index'),
                permission: 'manage-file-sharing',
            },
        ],
    },
    {
        title: t('Media Library'),
        href: route('media-library'),
        icon: Image,
        permission: 'manage-media',
        order: 2900,
    },
    {
        title: t('Messenger'),
        href: route('messenger.index'),
        icon: MessageCircle,
        permission: 'manage-messenger',
        order: 2940,
    },
    {
        title: t('Helpdesk'),
        href: route('helpdesk-tickets.index'),
        icon: Headphones,
        permission: 'manage-helpdesk-tickets',
        order: 2950,
    },
    {
        title: t('Plan'),
        icon: CreditCard,
        permission: 'manage-plans',
        order: 2980,
        children: [
            {
                title: t('Setup Subscription Plan'),
                href: route('plans.index'),
                permission: 'manage-plans',
            },
            {
                title: t('Bank Transfer Requests'),
                href: route('bank-transfer.index'),
                permission: 'manage-bank-transfer-requests',
            },
            {
                title: t('Orders'),
                href: route('orders.index'),
                permission: 'manage-orders',
            }
        ]
    },
    {
        title: t('Customization'),
        icon: Puzzle,
        permission: null,
        name: 'customization',
        order: 200,
    },
    {
        title: t('Integration'),
        icon: Plug,
        permission: null,
        name: 'integration',
        order: 210,
    },
    {
        title: t('Settings'),
        icon: Settings,
        permission: 'manage-settings',
        name: 'settings',
        order: 3000,
        children: [
            {
                title: t('General Settings'),
                href: route('settings.index'),
                permission: 'manage-settings',
            },
            {
                title: t('Payment Gateways'),
                href: route('gateways.index'),
                permission: 'manage-settings',
            },
        ],
    },
];
