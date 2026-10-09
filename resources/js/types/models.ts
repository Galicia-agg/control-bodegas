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
    products_count?: number;
    stock_value?: string | number;
};

export type Category = {
    id: number;
    name: string;
    description: string | null;
    products_count?: number;
};

export type Supplier = {
    id: number;
    name: string;
    tax_id: string | null;
    contact_name: string | null;
    email: string | null;
    phone: string | null;
    is_active: boolean;
    movements_count?: number;
};

export type Product = {
    id: number;
    sku: string;
    name: string;
    description: string | null;
    category_id: number | null;
    category?: Option | null;
    unit: string;
    cost: string;
    min_stock: string;
    is_active: boolean;
    total_stock?: string | null;
};

export type Stock = {
    id: number;
    warehouse_id: number;
    product_id: number;
    quantity: string;
    product: Product;
    warehouse: WarehouseOption;
};

export type MovementType = 'entrada' | 'salida' | 'transferencia' | 'ajuste';

export type MovementItem = {
    id: number;
    product_id: number;
    quantity: string;
    unit_cost: string;
    product: Pick<Product, 'id' | 'sku' | 'name' | 'unit'>;
};

export type Movement = {
    id: number;
    folio: string;
    type: MovementType;
    warehouse: WarehouseOption;
    destination_warehouse: WarehouseOption | null;
    supplier: (Option & { tax_id?: string | null }) | null;
    user: Option;
    reference: string | null;
    notes: string | null;
    occurred_at: string;
    created_at: string;
    items_count?: number;
    total_quantity?: string | null;
    items?: MovementItem[];
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
