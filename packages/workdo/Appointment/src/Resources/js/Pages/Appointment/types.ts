export interface AppointmentType {
    id: number;
    name: string;
    duration_minutes: number;
    color: string;
    is_active: boolean;
    created_by: number;
}

export interface AppointmentAvailability {
    id: number;
    user_id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    user?: { id: number; name: string };
}

export interface Appointment {
    id: number;
    title: string;
    type_id: number | null;
    start_datetime: string;
    end_datetime: string;
    attendee_name: string;
    attendee_email: string;
    attendee_phone: string | null;
    status: string;
    notes: string | null;
    type?: AppointmentType;
    created_by: number;
}

export interface AppointmentIndexProps {
    appointments: any;
    types: AppointmentType[];
    auth: { user: { id: number; name: string; can: (p: string) => boolean } };
}

export interface AppointmentTypesProps {
    types: AppointmentType[];
    auth: { user: { id: number; name: string; can: (p: string) => boolean } };
}

export interface AppointmentAvailabilityProps {
    availabilities: AppointmentAvailability[];
    users: { id: number; name: string }[];
    auth: { user: { id: number; name: string; can: (p: string) => boolean } };
}
