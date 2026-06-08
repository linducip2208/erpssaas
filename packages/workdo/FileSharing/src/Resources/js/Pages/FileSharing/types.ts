export interface SharedFolder {
    id: number;
    name: string;
    parent_id: number | null;
    created_by: number;
    children?: SharedFolder[];
    files?: SharedFile[];
    permissions?: FolderPermission[];
    created_at: string;
    updated_at: string;
}

export interface SharedFile {
    id: number;
    folder_id: number;
    file_name: string;
    file_path: string;
    file_size: number;
    mime_type: string | null;
    uploaded_by: number;
    uploader?: { id: number; name: string };
    shared_links?: SharedLink[];
    created_at: string;
    updated_at: string;
}

export interface FolderPermission {
    id: number;
    folder_id: number;
    user_id: number;
    permission: 'read' | 'write' | 'admin';
    user?: { id: number; name: string; email: string };
    created_at: string;
}

export interface SharedLink {
    id: number;
    file_id: number;
    token: string;
    expires_at: string | null;
    is_active: boolean;
    created_by: number;
    created_at: string;
}

export interface FileSharingProps {
    folders: SharedFolder[];
    folder?: SharedFolder;
    auth: { user: { id: number; name: string; can: (p: string) => boolean } };
}
