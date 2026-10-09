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
    deliveries?: Delivery[];
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

export type RateService = 'almacenaje' | 'manejo' | 'domicilio';

export type Rate = {
    id: number;
    service: RateService;
    unit_type: StorageUnitType | null;
    customer_id: number | null;
    customer?: Option | null;
    amount: string;
    free_days: number;
    is_active: boolean;
};

export type AccrualLine = {
    unit_id: number;
    code: string;
    type: StorageUnitType;
    stored: boolean;
    days: number;
    free_days: number;
    billable_days: number;
    rate: number | null;
    amount: number;
};

export type Accrual = {
    lines: AccrualLine[];
    total: number;
    pending_total: number;
    missing_rates: number;
};

export type DeliveryType = 'retiro' | 'domicilio';
export type DeliveryStatus =
    | 'programada'
    | 'en_ruta'
    | 'entregada'
    | 'cancelada';
export type VoucherStatus = 'pendiente' | 'pagado' | 'anulado';

export type Carrier = {
    id: number;
    name: string;
    kind: 'propio' | 'externo';
    driver_name: string | null;
    vehicle_plate: string | null;
    phone: string | null;
    is_active: boolean;
    deliveries_count?: number;
};

export type VoucherSummary = {
    id: number;
    folio: string;
    total: string;
    status: VoucherStatus;
};

export type VoucherLine = {
    id: number;
    concept: RateService;
    description: string;
    quantity: string;
    unit_price: string;
    amount: string;
};

export type Voucher = {
    id: number;
    folio: string;
    total: string;
    status: VoucherStatus;
    issued_at: string;
    paid_at: string | null;
    payment_method: string | null;
    payment_reference: string | null;
    customer: Option & { tax_id?: string | null; address?: string | null };
    receipt: {
        id: number;
        folio: string;
        transport_mode: TransportMode;
        transport_document: string | null;
        duca: string | null;
    };
    delivery: {
        id: number;
        folio: string;
        type: DeliveryType;
        recipient_name: string;
    } | null;
    user: Option;
    collector: Option | null;
    lines?: VoucherLine[];
};

export type Delivery = {
    id: number;
    folio: string;
    type: DeliveryType;
    status: DeliveryStatus;
    recipient_name: string;
    recipient_document: string | null;
    destination_address: string | null;
    destination_phone: string | null;
    released_at: string;
    scheduled_at: string | null;
    completed_at: string | null;
    proof_note: string | null;
    notes: string | null;
    receipt: {
        id: number;
        folio: string;
        customer: Option & { tax_id?: string | null };
        warehouse?: Option;
    };
    carrier: Carrier | null;
    user?: Option;
    units?: StorageUnit[];
    units_count?: number;
    voucher: VoucherSummary | null;
};
