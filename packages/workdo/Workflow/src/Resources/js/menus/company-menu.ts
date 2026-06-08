import { Workflow, History, Settings } from 'lucide-react';

declare global {
    function route(name: string): string;
}

export const workflowCompanyMenu = (t: (key: string) => string) => [
    {
        title: t('Workflow Automation'),
        icon: Workflow,
        permission: 'manage-workflows',
        order: 490,
        name: 'workflow',
        children: [
            {
                title: t('Workflows'),
                href: route('workflow.index'),
                permission: 'manage-workflows',
                order: 10,
            },
            {
                title: t('Execution Logs'),
                href: route('workflow.logs.index'),
                permission: 'view-workflow-logs',
                order: 20,
            },
        ],
    },
];
