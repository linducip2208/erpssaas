export interface RotaTemplate {
    id: number;
    name: string;
    description: string | null;
    created_by: number;
    shifts?: RotaTemplateShift[];
}

export interface RotaTemplateShift {
    id: number;
    template_id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    role: string | null;
}

export interface Rota {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    template_id: number | null;
    status: string;
    created_by: number;
    template?: RotaTemplate;
    assignments?: RotaAssignment[];
}

export interface RotaAssignment {
    id: number;
    rota_id: number;
    user_id: number | null;
    date: string;
    start_time: string;
    end_time: string;
    status: string;
    notes: string | null;
    user?: { id: number; name: string };
}

export interface RotasIndexProps {
    rotas: any;
    templates: { id: number; name: string }[];
    users: { id: number; name: string }[];
    auth: { user: { id: number; name: string; can: (p: string) => boolean } };
}

export interface RotasTemplatesProps {
    templates: RotaTemplate[];
    auth: { user: { id: number; name: string; can: (p: string) => boolean } };
}

export interface RotasAssignProps {
    rota: Rota;
    users: { id: number; name: string }[];
    auth: { user: { id: number; name: string; can: (p: string) => boolean } };
}
