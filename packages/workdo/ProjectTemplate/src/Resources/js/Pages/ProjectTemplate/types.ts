export interface ProjectTemplateTask {
    id: number;
    template_id: number;
    title: string;
    description?: string;
    priority: string;
    order: number;
    estimated_hours?: number;
    created_at: string;
    updated_at: string;
}

export interface ProjectTemplateMilestone {
    id: number;
    template_id: number;
    name: string;
    description?: string;
    order: number;
    due_days_offset: number;
    created_at: string;
    updated_at: string;
}

export interface ProjectTemplate {
    id: number;
    name: string;
    description?: string;
    is_active: boolean;
    tasks: ProjectTemplateTask[];
    milestones: ProjectTemplateMilestone[];
    created_by: number;
    created_at: string;
    updated_at: string;
}
