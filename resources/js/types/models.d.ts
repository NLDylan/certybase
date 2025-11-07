// Shared model types generated from Laravel models/migrations

export type UUID = string;

// Enums (string literal unions mirroring PHP Enums)
export type DesignStatus = 'draft' | 'active' | 'inactive' | 'archived';
export type CampaignStatus = 'draft' | 'active' | 'completed' | 'cancelled';
export type CertificateStatus = 'pending' | 'issued' | 'expired' | 'revoked';

export type CampaignCompletionReason = 'limit_reached' | 'date_reached' | 'manual';

// Organization
export interface Organization {
    id: UUID;
    name: string;
    description?: string | null;
    email?: string | null;
    phone_number?: string | null;
    website?: string | null;
    status: 'active' | 'suspended' | string; // keep flexible if enum extends
    settings?: Record<string, unknown> | null;
    created_at: string;
    updated_at: string;
}

// User (subset used in UI lists)
export interface UserSummary {
    id: UUID;
    name: string;
}

// Design
export interface Design {
    id: UUID;
    organization_id: UUID;
    creator_id?: UUID | null;
    name: string;
    description?: string | null;
    design_data?: Record<string, unknown> | null;
    variables?: Record<string, unknown> | null;
    settings?: Record<string, unknown> | null;
    status: DesignStatus;
    created_at: string;
    updated_at: string;
    // Eager loaded relationships commonly used in UI
    creator?: UserSummary | null;
}

// Campaign
export interface CampaignVariableMapping {
    recipient_name?: string | null;
    recipient_email?: string | null;
    variables?: Record<string, string> | Array<Record<string, string>> | null;
}

export interface Campaign {
    id: UUID;
    organization_id: UUID;
    design_id: UUID;
    creator_id?: UUID | null;
    name: string;
    description?: string | null;
    variable_mapping?: CampaignVariableMapping | null;
    status: CampaignStatus;
    start_date?: string | null; // ISO date
    end_date?: string | null;   // ISO date
    certificate_limit?: number | null;
    certificates_issued: number;
    completed_at?: string | null; // ISO datetime
    completion_reason?: CampaignCompletionReason | string | null;
    created_at: string;
    updated_at: string;
    // Common eager relations
    design?: Pick<Design, 'id' | 'name'> | null;
    creator?: UserSummary | null;
    certificates_count?: number; // when loaded with ->withCount('certificates')
}

// Certificate
export interface Certificate {
    id: UUID;
    organization_id: UUID;
    design_id: UUID;
    campaign_id?: UUID | null;
    issued_to_user_id?: UUID | null;
    recipient_name: string;
    recipient_email: string;
    recipient_data?: Record<string, unknown> | null;
    certificate_data?: Record<string, unknown> | null;
    verification_token: string;
    status: CertificateStatus;
    issued_at?: string | null;  // ISO datetime
    expires_at?: string | null; // ISO datetime
    revoked_at?: string | null; // ISO datetime
    revocation_reason?: string | null;
    created_at: string;
    updated_at: string;
    // Common eager relations
    design?: Pick<Design, 'id' | 'name'> | null;
    campaign?: Pick<Campaign, 'id' | 'name'> | null;
}

// Generic pagination type matching Laravel paginator JSON
export interface PaginationLinks {
    url: string | null;
    label: string;
    active: boolean;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: PaginationLinks[];
}


