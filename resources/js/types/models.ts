export type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    per_page: number;
};

export type Option = { id: number; name: string };
export type WarehouseOption = { id: number; code: string; name: string };

export type Warehouse = {
    id: number;
    code: string;
    name: string;
    address: string | null;
    phone: string | null;
    manager_id: number | null;
    manager?: Option | null;
    is_active: boolean;
    active_receipts_count?: number;
    stored_units_count?: number;
};

export type Customer = {
    id: number;
    name: string;
    tax_id: string | null;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    address: string | null;
    is_active: boolean;
    receipts_count?: number;
};

export type TransportMode = 'maritimo' | 'aereo' | 'terrestre';
export type ReceiptStatus = 'activo' | 'parcial' | 'finalizado';
export type StorageUnitType = 'contenedor' | 'palet' | 'caja' | 'bulto';

export type StorageUnit = {
    id: number;
    receipt_id: number;
    parent_id: number | null;
    type: StorageUnitType;
    code: string;
    description: string | null;
    pieces: number | null;
    weight_kg: string | null;
    volume_m3: string | null;
    location: string | null;
    status: 'almacenado' | 'entregado';
    delivered_at: string | null;
};

export type Receipt = {
    id: number;
    folio: string;
    customer: Option & {
        tax_id?: string | null;
        contact_name?: string | null;
        phone?: string | null;
        email?: string | null;
    };
    warehouse: WarehouseOption;
    user: Option;
    transport_mode: TransportMode;
    transport_document: string | null;
    duca: string | null;
    carrier: string | null;
    origin: string | null;
    status: ReceiptStatus;
    received_at: string;
    notes: string | null;
    units_count?: number;
    stored_units_count?: number;
    units?: StorageUnit[];
};

export type ManagedUser = {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    created_at: string;
    roles: Option[];
    warehouses: WarehouseOption[];
};

export type Role = {
    id: number;
    name: string;
    users_count: number;
    permissions: string[];
    locked: boolean;
};

export type PermissionGroups = Record<
    string,
    { label: string; permissions: Record<string, string> }
>;
