"use client";

import { useEffect, useState, type FormEvent } from "react";
import { 
  LayoutDashboard, 
  Database, 
  FileText, 
  FileSpreadsheet, 
  Truck, 
  Calendar, 
  Receipt, 
  Wallet, 
  BarChart3,
  Boxes,
  Building2,
  CalendarDays,
  CarFront,
  ChevronRight,
  Package,
  RefreshCw,
  Users,
  Search,
  Filter,
  Plus,
  Camera,
  User,
  X,
  ChevronDown,
  Check
} from "lucide-react";

type MainMenu = 
  | "DASHBOARD" 
  | "DATABASE" 
  | "FORM ORDER" 
  | "FORM PENAWARAN" 
  | "SURAT JALAN" 
  | "PENJADWALAN" 
  | "INVOICE" 
  | "KEUANGAN" 
  | "LAPORAN"
  | "ABSENSI"
  | "USER_MANAGEMENT";

type UserRole = "owner" | "admin" | "teknisi";

type DatabaseTab = "barang" | "karyawan" | "klien" | "armada" | "perusahaan";
type BarangSubTab = "satuan" | "paket";

type MasterItem = Record<string, unknown> & { id: number };
type FormValues = Record<string, string | number | string[]>;

const API_BASE = (process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000/api").replace(/\/$/, "");
const CATEGORY_LIST = [
  "Camera",
  "Sound System",
  "Lighting System",
  "TV",
  "LED Videotron",
  "Streaming & Hybrid",
  "Produksi",
];

const SATUAN_OPTIONS = ["unit", "set", "paket", "meter", "m2", "person", "box", "lot", "roll", "hari"];

// Satuan default per kategori, mengikuti daftar harga (videotron & produksi dihitung per meter)
const CATEGORY_DEFAULT_SATUAN: Record<string, string> = {
  "Camera": "unit",
  "Sound System": "unit",
  "Lighting System": "unit",
  "TV": "unit",
  "LED Videotron": "meter",
  "Streaming & Hybrid": "set",
  "Produksi": "set",
};

type PackageItemRow = {
  barang_id: string;
  nama_item: string;
  qty: number;
  satuan: string;
  harga: number;
};

const emptyPackageRow = (): PackageItemRow => ({ barang_id: "", nama_item: "", qty: 1, satuan: "unit", harga: 0 });



function resourceData<T>(payload: unknown): T[] {
  if (payload && typeof payload === "object" && "data" in payload && Array.isArray((payload as { data: unknown }).data)) {
    return (payload as { data: T[] }).data;
  }
  return [];
}

function valueOf(item: MasterItem, key: string): string {
  const value = item[key];
  return value === null || value === undefined ? "" : String(value);
}

function formatIDR(value: unknown): string {
  const num = Number(value || 0);
  return num.toLocaleString("id-ID");
}

function DashboardView({
  data,
  loading,
  error,
  onRefresh,
}: {
  data: { barang: MasterItem[]; klien: MasterItem[]; orders: MasterItem[] };
  loading: boolean;
  error: string;
  onRefresh: () => void;
}) {
  const activeOrders = data.orders.filter((order) => !["Cancelled", "Dibatalkan"].includes(valueOf(order, "status")));
  const confirmedOrders = data.orders.filter((order) => ["Confirmed", "Dikonfirmasi"].includes(valueOf(order, "status")));
  const rentedUnits = data.barang.reduce((sum, item) => sum + Number(item.di_sewa || 0), 0);
  const stockUnits = data.barang.reduce((sum, item) => sum + Number(item.jumlah_total || 0), 0);
  const upcoming = [...activeOrders].sort((a, b) => valueOf(a, "load_in").localeCompare(valueOf(b, "load_in"))).slice(0, 5);
  const metrics = [
    { label: "Order aktif", value: activeOrders.length, helper: "Sedang diproses", icon: CalendarDays, tone: "blue" },
    { label: "Terkonfirmasi", value: confirmedOrders.length, helper: "Siap dijalankan", icon: Package, tone: "green" },
    { label: "Total klien", value: data.klien.length, helper: "Terdaftar di database", icon: Users, tone: "violet" },
    { label: "Unit disewa", value: `${rentedUnits} / ${stockUnits}`, helper: "Unit sedang digunakan", icon: Boxes, tone: "amber" },
  ];

  return (
    <section className="dashboard-page space-y-6">
      <div className="dashboard-hero flex flex-wrap items-end justify-between gap-6">
        <div className="relative z-10 max-w-2xl">
          <div className="mb-4 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-medium text-blue-50">
            <span className="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_0_4px_rgba(52,211,153,.14)]" />
            Pusat kendali operasional
          </div>
          <h1 className="text-3xl font-bold tracking-tight text-white sm:text-4xl">Ringkasan bisnis Anda</h1>
          <p className="mt-2 max-w-xl text-sm leading-6 text-blue-100/80">Pantau order, aset, dan klien Penta Tri Saka dari satu tempat.</p>
        </div>
        <button onClick={onRefresh} className="relative z-10 inline-flex items-center gap-2 rounded-xl border border-white/20 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white hover:bg-white/15">
          <RefreshCw className="h-4 w-4" />
          Perbarui data
        </button>
      </div>
      {error && <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>}
      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        {metrics.map(({ label, value, helper, icon: Icon, tone }) => (
          <article key={label} className="dashboard-metric">
            <div className={`dashboard-metric-icon dashboard-metric-${tone}`}><Icon className="h-5 w-5" /></div>
            <div className="mt-5">
              <p className="text-sm font-medium text-slate-500">{label}</p>
              <p className="mt-1 text-3xl font-bold tracking-tight text-slate-900">{loading ? "—" : value}</p>
              <p className="mt-2 text-xs text-slate-400">{helper}</p>
            </div>
          </article>
        ))}
      </div>
      <section className="dashboard-orders overflow-hidden">
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-5 sm:px-7">
          <div>
            <h2 className="text-base font-semibold text-slate-900">Jadwal order terdekat</h2>
            <p className="mt-1 text-sm text-slate-500">Agenda operasional yang akan datang</p>
          </div>
          <span className="rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">{upcoming.length} order</span>
        </div>
        {upcoming.length === 0 ? (
          <div className="px-6 py-14 text-center">
            <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400"><CalendarDays className="h-5 w-5" /></div>
            <p className="mt-4 text-sm font-semibold text-slate-700">Belum ada jadwal order</p>
            <p className="mt-1 text-sm text-slate-500">Order aktif akan muncul di sini.</p>
          </div>
        ) : (
          <div className="divide-y divide-slate-100">
            {upcoming.map((order) => (
              <article key={order.id} className="grid gap-3 px-5 py-4 sm:grid-cols-[minmax(180px,1.3fr)_minmax(140px,1fr)_minmax(130px,.8fr)_auto] sm:items-center sm:px-7">
                <div>
                  <p className="font-semibold text-slate-900">{valueOf(order, "nama_event") || valueOf(order, "nomor_order") || "Order tanpa nama"}</p>
                  <p className="mt-1 text-xs text-slate-500">{valueOf(order, "nomor_order")}</p>
                </div>
                <p className="inline-flex items-center gap-2 text-sm text-slate-600"><Building2 className="h-4 w-4 shrink-0 text-slate-400" />{valueOf(order, "venue") || "Venue belum diisi"}</p>
                <p className="inline-flex items-center gap-2 text-sm text-slate-600"><CalendarDays className="h-4 w-4 shrink-0 text-slate-400" />{valueOf(order, "load_in") || "Tanggal belum diisi"}</p>
                <span className="w-fit rounded-full bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700">{valueOf(order, "status") || "Draft"}</span>
              </article>
            ))}
          </div>
        )}
      </section>
    </section>
  );
}

export default function App() {
  const [mounted, setMounted] = useState(false);
  const [token, setToken] = useState<string>("");
  const [userRole, setUserRole] = useState<UserRole>("admin");
  const [activeMenu, setActiveMenu] = useState<MainMenu>("DASHBOARD");
  const [dbTab, setDbTab] = useState<DatabaseTab>("barang");
  const [barangSubTab, setBarangSubTab] = useState<BarangSubTab>("satuan");
  
  // Data states
  const [items, setItems] = useState<MasterItem[]>([]);
  const [barangsList, setBarangsList] = useState<MasterItem[]>([]);
  const [loading, setLoading] = useState(false);
  const [busy, setBusy] = useState(false);
  const [notice, setNotice] = useState("");
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  
  // Filter Dropdown
  const [filterOpen, setFilterOpen] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<string>("");

  // Modal State
  const [modalOpen, setModalOpen] = useState(false);
  const [editingItem, setEditingItem] = useState<MasterItem | null>(null);
  const [form, setForm] = useState<FormValues>({});
  const [packageItems, setPackageItems] = useState<PackageItemRow[]>([]);
  const [credentials, setCredentials] = useState({ email: "", password: "" });
  const [orderItems, setOrderItems] = useState([{ barang_id: "", nama_item: "", qty: 1, harga_harian: 0 }]);
  const [orderForm, setOrderForm] = useState({
    nomor_order: `ORD-${new Date().toISOString().slice(0, 10).replaceAll("-", "")}-001`,
    klien_id: "",
    nama_event: "",
    venue: "",
    ruangan: "",
    lantai: "",
    load_in: "",
    load_out: "",
    status: "Draft",
    catatan: "",
  });
  const [orderClients, setOrderClients] = useState<MasterItem[]>([]);
  const [orderBarang, setOrderBarang] = useState<MasterItem[]>([]);
  const [orders, setOrders] = useState<MasterItem[]>([]);
  const [offers, setOffers] = useState<MasterItem[]>([]);
  const [offerOrders, setOfferOrders] = useState<MasterItem[]>([]);
  const [offerItems, setOfferItems] = useState([{ nama_item: "", qty: 1, harga: 0 }]);
  const [offerForm, setOfferForm] = useState({ nomor_penawaran: `Q-${new Date().toISOString().slice(0, 10).replaceAll("-", "")}-001`, form_order_id: "", tanggal_penawaran: new Date().toISOString().slice(0, 10), berlaku_sampai: "", status: "Draft", diskon: 0, pajak: 0, catatan: "" });
  const [deliveryNotes, setDeliveryNotes] = useState<MasterItem[]>([]);
  const [deliveryOrders, setDeliveryOrders] = useState<MasterItem[]>([]);
  const [deliveryTeknisiOptions, setDeliveryTeknisiOptions] = useState<Array<{ id: number; name: string }>>([]);
  const [deliveryTeknisiIds, setDeliveryTeknisiIds] = useState<number[]>([]);
  const [deliveryTeknisiSearch, setDeliveryTeknisiSearch] = useState("");
  const [deliverySearch, setDeliverySearch] = useState("");
  const [deliveryItems, setDeliveryItems] = useState<Array<{ barang_id: string; nama_item: string; qty: number; status_check: string }>>([]);
  const [deliveryForm, setDeliveryForm] = useState({ nomor_surat_jalan: `SJ-${new Date().toISOString().slice(0, 10).replaceAll("-", "")}-001`, form_order_id: "", tanggal_berangkat: "", status: "Draft", catatan: "" });
  const [schedules, setSchedules] = useState<MasterItem[]>([]);
  const [scheduleOrders, setScheduleOrders] = useState<MasterItem[]>([]);
  const [scheduleCrew, setScheduleCrew] = useState<MasterItem[]>([]);
  const [scheduleVehicles, setScheduleVehicles] = useState<MasterItem[]>([]);
  const [scheduleForm, setScheduleForm] = useState({ form_order_id: "", karyawan_id: "", armada_id: "", tanggal_mulai: "", tanggal_selesai: "", jobdesk: "", status: "Terjadwal", catatan: "" });
  const [invoices, setInvoices] = useState<MasterItem[]>([]);
  const [invoiceOrders, setInvoiceOrders] = useState<MasterItem[]>([]);
  const [invoiceForm, setInvoiceForm] = useState({ nomor_invoice: `INV-${new Date().toISOString().slice(0, 10).replaceAll("-", "")}-001`, form_order_id: "", tanggal_invoice: new Date().toISOString().slice(0, 10), jatuh_tempo: "", subtotal: 0, diskon: 0, pajak: 0, dibayar: 0, status: "Unpaid", catatan: "" });
  const [transactions, setTransactions] = useState<MasterItem[]>([]);
  const [financeSummary, setFinanceSummary] = useState({ pemasukan: 0, pengeluaran: 0, saldo: 0 });
  const [financeForm, setFinanceForm] = useState({ invoice_id: "", tanggal: new Date().toISOString().slice(0, 10), jenis: "Pemasukan", kategori: "Pembayaran Klien", jumlah: 0, metode: "Transfer", referensi: "", keterangan: "" });
  const [reportSummary, setReportSummary] = useState<Record<string, number>>({});
  const [users, setUsers] = useState<MasterItem[]>([]);
  const [roles, setRoles] = useState<Array<{ id: number; name: string; display_name: string }>>([]);
  const [userForm, setUserForm] = useState({ name: "", email: "", password: "", role_id: "" });

  const menuCategories: { name: string; items: MainMenu[] }[] = userRole === "owner" 
    ? [
        { name: "WORKSPACE", items: ["DASHBOARD"] },
        { name: "DATA", items: ["DATABASE", "USER_MANAGEMENT"] },
        { name: "OPERASIONAL", items: ["FORM ORDER", "FORM PENAWARAN", "SURAT JALAN", "PENJADWALAN", "ABSENSI"] },
        { name: "KEUANGAN", items: ["INVOICE", "KEUANGAN", "LAPORAN"] },
      ]
    : userRole === "admin"
    ? [
        { name: "WORKSPACE", items: ["DASHBOARD"] },
        { name: "DATA", items: ["DATABASE"] },
        { name: "OPERASIONAL", items: ["FORM ORDER", "FORM PENAWARAN", "SURAT JALAN", "PENJADWALAN", "ABSENSI"] },
        { name: "KEUANGAN", items: ["INVOICE", "KEUANGAN", "LAPORAN"] },
      ]
    : [
        { name: "WORKSPACE", items: ["DASHBOARD"] },
      ];

  const menuLabels: Record<MainMenu, string> = {
    DASHBOARD: "Dashboard",
    DATABASE: "Database master",
    "FORM ORDER": "Form order",
    "FORM PENAWARAN": "Penawaran",
    "SURAT JALAN": "Surat jalan",
    PENJADWALAN: "Penjadwalan",
    INVOICE: "Invoice",
    KEUANGAN: "Keuangan",
    LAPORAN: "Laporan",
    ABSENSI: "Absensi",
    USER_MANAGEMENT: "User Management",
  };

  const menuIcons: Record<MainMenu, typeof LayoutDashboard> = {
    DASHBOARD: LayoutDashboard,
    DATABASE: Database,
    "FORM ORDER": FileText,
    "FORM PENAWARAN": FileSpreadsheet,
    "SURAT JALAN": Truck,
    PENJADWALAN: Calendar,
    INVOICE: Receipt,
    KEUANGAN: Wallet,
    LAPORAN: BarChart3,
    ABSENSI: Users,
    USER_MANAGEMENT: Building2,
  };
  const [technicianDeliveryForm, setTechnicianDeliveryForm] = useState({ kode_surat_jalan: "", foto: null as File | null, items: [] as Array<{ nama_barang: string; qty: number; status_fisik: string; keterangan: string }> });
  const [dashboardData, setDashboardData] = useState({
    barang: [] as MasterItem[],
    klien: [] as MasterItem[],
    orders: [] as MasterItem[],
  });
  const [additionalItemForm, setAdditionalItemForm] = useState({ nama_barang: "", qty: 1, foto: null as File | null, alasan: "" });
  const [deliveryData, setDeliveryData] = useState<{ id: number; nomor_surat_jalan: string; items: Array<{ id: number; nama_item: string; qty: number }>; teknisi: Array<{ id: number; name: string }>; is_assigned: boolean } | null>(null);
  const [technicianDeliveryStage, setTechnicianDeliveryStage] = useState<"scan" | "checkout" | "checkin" | "additional">("scan");
  const [additionalItems, setAdditionalItems] = useState<Array<{ nama_barang: string; qty: number; alasan: string }>>([]);

  useEffect(() => {
    const savedToken = window.localStorage.getItem("penta_access_token");
    const timer = window.setTimeout(() => {
      setMounted(true);
      if (savedToken) setToken(savedToken);
    }, 0);
    return () => window.clearTimeout(timer);
  }, []);

  useEffect(() => {
    if (token && activeMenu === "DATABASE") {
      void loadDatabaseData();
    }
    if (token && activeMenu === "FORM ORDER") {
      void loadOrderData();
    }
    if (token && activeMenu === "FORM PENAWARAN") {
      void loadOfferData();
    }
    if (token && activeMenu === "SURAT JALAN") {
      void loadDeliveryData();
    }
    if (token && activeMenu === "PENJADWALAN") {
      void loadScheduleData();
    }
    if (token && activeMenu === "INVOICE") {
      void loadInvoiceData();
    }
    if (token && activeMenu === "KEUANGAN") {
      void loadFinanceData();
    }
    if (token && activeMenu === "LAPORAN") {
      void loadReportData();
    }
    if (token && activeMenu === "DASHBOARD") {
      void loadDashboardData();
    }
    if (token && activeMenu === "USER_MANAGEMENT") {
      void loadUserData();
    }
    // Data loading intentionally follows active module and selected database view.
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [token, activeMenu, dbTab, barangSubTab]);

  if (!mounted) {
    return (
      <div className="flex min-h-screen w-full items-center justify-center bg-[#002480] text-white">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-white border-t-transparent"></div>
      </div>
    );
  }

  async function request(path: string, init: RequestInit = {}): Promise<Response> {
    const headers = new Headers(init.headers);
    headers.set("Authorization", `Bearer ${token}`);
    headers.set("Accept", "application/json");
    return fetch(`${API_BASE}/v1/${path}`, { ...init, headers });
  }

  async function loadDatabaseData(): Promise<void> {
    setLoading(true);
    setError("");
    try {
      let endpoint = "master-barang";
      if (dbTab === "barang") {
        endpoint = barangSubTab === "paket" ? "master-paket" : "master-barang";
      } else if (dbTab === "karyawan") {
        endpoint = "master-karyawan";
      } else if (dbTab === "klien") {
        endpoint = "master-klien";
      } else if (dbTab === "armada") {
        endpoint = "master-armada";
      } else if (dbTab === "perusahaan") {
        endpoint = "master-perusahaan";
      }

      const res = await request(`${endpoint}?per_page=100`);
      if (res.status === 401) {
        signOut(false);
        setError("Sesi berakhir. Silakan login kembali.");
        return;
      }
      const payload = await res.json();
      if (!res.ok) throw new Error("Gagal mengambil data dari server.");
      setItems(resourceData<MasterItem>(payload));

      if (dbTab === "barang" && barangSubTab === "paket") {
        const bRes = await request("master-barang?per_page=100");
        const bPayload = await bRes.json();
        setBarangsList(resourceData<MasterItem>(bPayload));
      }
    } catch (err) {
      setError(err instanceof Error ? err.message : "Terjadi kesalahan koneksi.");
    } finally {
      setLoading(false);
    }
  }

  async function loadDashboardData(): Promise<void> {
    setLoading(true);
    setError("");
    try {
      const [barangResponse, clientResponse, ordersResponse] = await Promise.all([
        request("master-barang?per_page=100"),
        request("master-klien?per_page=100"),
        request("form-order?per_page=100"),
      ]);
      if ([barangResponse, clientResponse, ordersResponse].some((response) => response.status === 401)) {
        signOut(false);
        setError("Sesi berakhir. Silakan login kembali.");
        return;
      }
      if (![barangResponse, clientResponse, ordersResponse].every((response) => response.ok)) {
        throw new Error("Gagal mengambil ringkasan dashboard.");
      }
      const [barangPayload, clientPayload, ordersPayload] = await Promise.all([
        barangResponse.json(), clientResponse.json(), ordersResponse.json(),
      ]);
      setDashboardData({
        barang: resourceData<MasterItem>(barangPayload),
        klien: resourceData<MasterItem>(clientPayload),
        orders: resourceData<MasterItem>(ordersPayload),
      });
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Terjadi kesalahan koneksi.");
    } finally {
      setLoading(false);
    }
  }

  async function loadOrderData(): Promise<void> {
    setLoading(true);
    setError("");
    try {
      const [clientsResponse, barangResponse, ordersResponse] = await Promise.all([
        request("master-klien?per_page=100"),
        request("master-barang?per_page=100"),
        request("form-order?per_page=100"),
      ]);
      if ([clientsResponse, barangResponse, ordersResponse].some((response) => response.status === 401)) {
        signOut(false);
        setError("Sesi berakhir. Silakan login kembali.");
        return;
      }
      if (![clientsResponse, barangResponse, ordersResponse].every((response) => response.ok)) {
        throw new Error("Gagal mengambil data form order.");
      }
      const [clientsPayload, barangPayload, ordersPayload] = await Promise.all([
        clientsResponse.json(), barangResponse.json(), ordersResponse.json(),
      ]);
      setOrderClients(resourceData<MasterItem>(clientsPayload));
      setOrderBarang(resourceData<MasterItem>(barangPayload));
      setOrders(resourceData<MasterItem>(ordersPayload));
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Terjadi kesalahan koneksi.");
    } finally {
      setLoading(false);
    }
  }

  async function loadReportData(): Promise<void> {
    setLoading(true); setError(""); try { const response = await request("laporan/summary"); const payload = await response.json(); if (!response.ok) throw new Error(payload?.message || "Gagal memuat laporan."); setReportSummary(payload.data || {}); } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal memuat laporan."); } finally { setLoading(false); }
  }

  async function downloadTransactionsCsv(): Promise<void> {
    try { const response = await request("laporan/transactions.csv"); if (!response.ok) throw new Error("Gagal mengunduh laporan."); const blob = await response.blob(); const url = URL.createObjectURL(blob); const anchor = document.createElement("a"); anchor.href = url; anchor.download = "laporan-keuangan.csv"; anchor.click(); URL.revokeObjectURL(url); } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal mengunduh laporan."); }
  }

  async function loadFinanceData(): Promise<void> {
    setLoading(true); setError("");
    try { const [transactionResponse, invoiceResponse] = await Promise.all([request("transaksi-keuangan?per_page=100"), request("invoice?per_page=100")]); if (!transactionResponse.ok || !invoiceResponse.ok) throw new Error("Gagal memuat data keuangan."); const [transactionPayload, invoicePayload] = await Promise.all([transactionResponse.json(), invoiceResponse.json()]); setTransactions(resourceData<MasterItem>(transactionPayload)); setFinanceSummary((transactionPayload.summary || { pemasukan: 0, pengeluaran: 0, saldo: 0 }) as { pemasukan: number; pengeluaran: number; saldo: number }); setInvoices(resourceData<MasterItem>(invoicePayload)); } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal memuat data keuangan."); } finally { setLoading(false); }
  }

  async function saveFinance(e: FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault(); setBusy(true); setError(""); try { const response = await request("transaksi-keuangan", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...financeForm, invoice_id: financeForm.invoice_id ? Number(financeForm.invoice_id) : null }) }); const payload = await response.json(); if (!response.ok) throw new Error(payload?.message || "Gagal menyimpan transaksi."); setNotice("Transaksi berhasil disimpan."); await loadFinanceData(); } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal menyimpan transaksi."); } finally { setBusy(false); }
  }

  async function loadInvoiceData(): Promise<void> {
    setLoading(true); setError("");
    try { const [invoiceResponse, orderResponse] = await Promise.all([request("invoice?per_page=100"), request("form-order?per_page=100")]); if (!invoiceResponse.ok || !orderResponse.ok) throw new Error("Gagal memuat invoice."); const [invoice, order] = await Promise.all([invoiceResponse.json(), orderResponse.json()]); setInvoices(resourceData<MasterItem>(invoice)); setInvoiceOrders(resourceData<MasterItem>(order)); } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal memuat invoice."); } finally { setLoading(false); }
  }

  async function saveInvoice(e: FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault(); if (!invoiceForm.form_order_id) { setError("Pilih Form Order."); return; } setBusy(true); setError("");
    try { const total = Math.max(0, invoiceForm.subtotal - invoiceForm.diskon + invoiceForm.pajak); const response = await request("invoice", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...invoiceForm, form_order_id: Number(invoiceForm.form_order_id), total }) }); const payload = await response.json(); if (!response.ok) throw new Error(payload?.message || "Gagal menyimpan invoice."); setNotice("Invoice berhasil disimpan."); await loadInvoiceData(); } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal menyimpan invoice."); } finally { setBusy(false); }
  }

  async function loadScheduleData(): Promise<void> {
    setLoading(true); setError("");
    try {
      const [scheduleResponse, ordersResponse, crewResponse, vehicleResponse] = await Promise.all([request("jadwal-operasional?per_page=100"), request("form-order?per_page=100"), request("master-karyawan?per_page=100"), request("master-armada?per_page=100")]);
      if (![scheduleResponse, ordersResponse, crewResponse, vehicleResponse].every((response) => response.ok)) throw new Error("Gagal memuat jadwal.");
      const [schedule, orders, crew, vehicles] = await Promise.all([scheduleResponse.json(), ordersResponse.json(), crewResponse.json(), vehicleResponse.json()]);
      setSchedules(resourceData<MasterItem>(schedule)); setScheduleOrders(resourceData<MasterItem>(orders)); setScheduleCrew(resourceData<MasterItem>(crew)); setScheduleVehicles(resourceData<MasterItem>(vehicles));
    } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal memuat jadwal."); } finally { setLoading(false); }
  }

  async function saveSchedule(e: FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault(); if (!scheduleForm.form_order_id || !scheduleForm.tanggal_mulai || !scheduleForm.tanggal_selesai) { setError("Lengkapi order dan waktu jadwal."); return; }
    setBusy(true); setError(""); try { const response = await request("jadwal-operasional", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...scheduleForm, form_order_id: Number(scheduleForm.form_order_id), karyawan_id: scheduleForm.karyawan_id ? Number(scheduleForm.karyawan_id) : null, armada_id: scheduleForm.armada_id ? Number(scheduleForm.armada_id) : null }) }); const payload = await response.json(); if (!response.ok) throw new Error(payload?.message || "Gagal menyimpan jadwal."); setNotice("Jadwal berhasil disimpan."); await loadScheduleData(); } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal menyimpan jadwal."); } finally { setBusy(false); }
  }

  async function loadDeliveryData(): Promise<void> {
    setLoading(true); setError("");
    try {
      const [notesResponse, ordersResponse, teknisiResponse] = await Promise.all([request("surat-jalan?per_page=100"), request("form-order?per_page=100"), request("surat-jalan/teknisi-options")]);
      if (![notesResponse, ordersResponse, teknisiResponse].every((response) => response.ok)) throw new Error("Gagal mengambil data Surat Jalan.");
      const [notes, orders, teknisi] = await Promise.all([notesResponse.json(), ordersResponse.json(), teknisiResponse.json()]);
      setDeliveryNotes(resourceData<MasterItem>(notes)); setDeliveryOrders(resourceData<MasterItem>(orders)); setDeliveryTeknisiOptions(resourceData<{ id: number; name: string }>(teknisi));
    } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal memuat Surat Jalan."); } finally { setLoading(false); }
  }

  async function saveDelivery(e: FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault();
    const items = deliveryItems.filter((item) => item.nama_item && item.qty > 0);
    if (!deliveryForm.form_order_id) { setError("Pilih order terlebih dahulu."); return; }
    setBusy(true); setError("");
    try {
      const response = await request("surat-jalan", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...deliveryForm, form_order_id: Number(deliveryForm.form_order_id), items, teknisi_ids: deliveryTeknisiIds }) });
      const payload = await response.json(); if (!response.ok) throw new Error(payload?.message || "Gagal menyimpan Surat Jalan.");
      setNotice("Surat Jalan berhasil disimpan."); setDeliveryItems([]); setDeliveryTeknisiIds([]); await loadDeliveryData();
    } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal menyimpan Surat Jalan."); } finally { setBusy(false); }
  }

  async function loadUserData(): Promise<void> {
    setLoading(true); setError("");
    try {
      const [usersResponse, rolesResponse] = await Promise.all([request("users"), request("roles")]);
      if (!usersResponse.ok || !rolesResponse.ok) throw new Error("Gagal mengambil data pengguna.");
      const [usersPayload, rolesPayload] = await Promise.all([usersResponse.json(), rolesResponse.json()]);
      setUsers(resourceData<MasterItem>(usersPayload));
      setRoles(resourceData<{ id: number; name: string; display_name: string }>(rolesPayload));
    } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal memuat pengguna."); } finally { setLoading(false); }
  }

  async function saveUser(e: FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault();
    if (!userForm.name || !userForm.email || !userForm.password || !userForm.role_id) { setError("Lengkapi nama, email, password, dan role."); return; }
    setBusy(true); setError("");
    try {
      const response = await request("users", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...userForm, role_id: Number(userForm.role_id) }) });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload?.message || "Gagal menyimpan pengguna.");
      setNotice("Pengguna berhasil ditambahkan."); setUserForm({ name: "", email: "", password: "", role_id: "" }); await loadUserData();
    } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal menyimpan pengguna."); } finally { setBusy(false); }
  }

  async function deleteUser(id: number): Promise<void> {
    if (!window.confirm("Hapus pengguna ini?")) return;
    setBusy(true); setError("");
    try {
      const response = await request(`users/${id}`, { method: "DELETE" });
      if (!response.ok) { const payload = await response.json(); throw new Error(payload?.message || "Gagal menghapus pengguna."); }
      setNotice("Pengguna dihapus."); await loadUserData();
    } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal menghapus pengguna."); } finally { setBusy(false); }
  }

  async function loadOfferData(): Promise<void> {
    setLoading(true);
    try {
      const [ordersResponse, offersResponse] = await Promise.all([request("form-order?per_page=100"), request("form-penawaran?per_page=100")]);
      if (!ordersResponse.ok || !offersResponse.ok) throw new Error("Gagal mengambil data penawaran.");
      const [ordersPayload, offersPayload] = await Promise.all([ordersResponse.json(), offersResponse.json()]);
      setOfferOrders(resourceData<MasterItem>(ordersPayload));
      setOffers(resourceData<MasterItem>(offersPayload));
    } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal memuat penawaran."); } finally { setLoading(false); }
  }

  async function saveOffer(e: FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault();
    if (!offerForm.form_order_id || offerItems.some((item) => !item.nama_item || item.qty < 1)) { setError("Pilih order dan lengkapi item penawaran."); return; }
    setBusy(true); setError("");
    try {
      const response = await request("form-penawaran", { method: "POST", headers: { "Content-Type": "application/json" }, body: JSON.stringify({ ...offerForm, form_order_id: Number(offerForm.form_order_id), items: offerItems }) });
      const payload = await response.json();
      if (!response.ok) throw new Error(payload?.message || "Gagal menyimpan penawaran.");
      setNotice("Penawaran berhasil disimpan."); await loadOfferData();
    } catch (cause) { setError(cause instanceof Error ? cause.message : "Gagal menyimpan penawaran."); } finally { setBusy(false); }
  }

  async function saveOrder(e: FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault();
    const validItems = orderItems.filter((item) => item.barang_id && item.qty > 0);
    if (validItems.length === 0) {
      setError("Pilih minimal satu barang untuk order.");
      return;
    }
    setBusy(true);
    setError("");
    try {
      const response = await request("form-order", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          ...orderForm,
          klien_id: Number(orderForm.klien_id),
          items: validItems.map((item) => ({
            barang_id: Number(item.barang_id),
            nama_item: item.nama_item,
            qty: Number(item.qty),
            harga_harian: Number(item.harga_harian),
          })),
        }),
      });
      const payload = await response.json();
      if (!response.ok) {
        const firstError = payload?.errors ? Object.values(payload.errors).flat()[0] : undefined;
        throw new Error(typeof firstError === "string" ? firstError : payload?.message || "Gagal menyimpan form order.");
      }
      setNotice("Form order berhasil disimpan.");
      setOrderForm({ ...orderForm, nama_event: "", klien_id: "", venue: "", ruangan: "", lantai: "", load_in: "", load_out: "", catatan: "" });
      setOrderItems([{ barang_id: "", nama_item: "", qty: 1, harga_harian: 0 }]);
      await loadOrderData();
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Gagal menyimpan form order.");
    } finally {
      setBusy(false);
    }
  }

  async function signIn(e: FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault();
    setBusy(true);
    setError("");
    try {
      const response = await fetch(`${API_BASE}/v1/auth/login`, {
        method: "POST",
        headers: { "Content-Type": "application/json", Accept: "application/json" },
        body: JSON.stringify({ ...credentials, device_name: "Penta Web System" }),
      });
      const payload = await response.json();
      if (!response.ok || typeof payload.token !== "string") {
        throw new Error(payload.message || "Email atau kata sandi tidak valid.");
      }
      const role = (payload.user?.role?.name as UserRole) || "admin";
      window.localStorage.setItem("penta_access_token", payload.token);
      window.localStorage.setItem("penta_user_role", role);
      setToken(payload.token);
      setUserRole(role);
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Gagal login.");
    } finally {
      setBusy(false);
    }
  }

  function signOut(callApi = true): void {
    if (callApi && token) void request("auth/logout", { method: "POST" });
    window.localStorage.removeItem("penta_access_token");
    window.localStorage.removeItem("penta_user_role");
    setToken("");
    setUserRole("admin");
    setItems([]);
  }

  function openCreate(): void {
    setEditingItem(null);
    setError("");
    if (dbTab === "barang") {
      if (barangSubTab === "satuan") {
        setForm({
          kategori: "Camera",
          satuan: "unit",
          nama_barang: "",
          deskripsi: "",
          jumlah_total: 0,
          jumlah_rusak: 0,
          di_sewa: 0,
          harga_rental: 0,
          kode_barang: "",
        });
      } else {
        setForm({
          kode_paket: "",
          kategori: "Streaming & Hybrid",
          sub_kategori: "",
          nama_paket: "",
          satuan: "paket",
          harga_paket: 0,
          catatan: "",
        });
        setPackageItems([emptyPackageRow()]);
      }
    } else if (dbTab === "karyawan") {
      setForm({
        nama_lengkap: "",
        nik: "",
        no_telp: "",
        jabatan: "Teknisi",
        no_rekening: "",
        status: "Aktif",
        email: "",
        password: "",
      });
    } else if (dbTab === "klien") {
      setForm({ nama_klien: "", nama_perusahaan: "", alamat: "", cp1_nama: "", cp1_telp: "", cp2_nama: "", cp2_telp: "", cp3_nama: "", cp3_telp: "", npwp: "", kategori: "Corporate" });
    } else if (dbTab === "armada") {
      setForm({ jenis_kendaraan: "Mobil Box", plat_nomor: "", status_stnk: "Aktif", driver: "", status_operasional: "Standby" });
    } else if (dbTab === "perusahaan") {
      setForm({ nama_perusahaan: "", alamat_kantor: "", telepon: "", email: "", kop_surat_path: "", logo_path: "", nama_bank: "", nomor_rekening: "", atas_nama_rekening: "" });
    }
    setModalOpen(true);
  }

  function openEdit(item: MasterItem): void {
    setEditingItem(item);
    setError("");
    if (dbTab === "barang") {
      if (barangSubTab === "satuan") {
        setForm({
          kode_barang: valueOf(item, "kode_barang"),
          nama_barang: valueOf(item, "nama_barang"),
          kategori: valueOf(item, "kategori"),
          satuan: valueOf(item, "satuan") || "unit",
          deskripsi: valueOf(item, "deskripsi"),
          jumlah_total: Number(item.jumlah_total || 0),
          jumlah_rusak: Number(item.jumlah_rusak || 0),
          di_sewa: Number(item.di_sewa || 0),
          harga_rental: Number(item.harga_rental || 0),
        });
      } else {
        setForm({
          kode_paket: valueOf(item, "kode_paket"),
          kategori: valueOf(item, "kategori"),
          sub_kategori: valueOf(item, "sub_kategori"),
          nama_paket: valueOf(item, "nama_paket"),
          satuan: valueOf(item, "satuan") || "paket",
          harga_paket: Number(item.harga_paket || 0),
          catatan: valueOf(item, "catatan"),
        });
        const itemsArr = Array.isArray(item.items) ? (item.items as Record<string, unknown>[]) : [];
        const mapped: PackageItemRow[] = itemsArr.map((i) => ({
          barang_id: i.barang_id ? String(i.barang_id) : "",
          nama_item: String(i.nama_item || ""),
          qty: Number(i.qty || 1),
          satuan: String(i.satuan || "unit"),
          harga: Number(i.harga || 0),
        }));
        setPackageItems(mapped.length > 0 ? mapped : [emptyPackageRow()]);
      }
    } else if (dbTab === "karyawan") {
      setForm({
        nama_lengkap: valueOf(item, "nama_lengkap"),
        nik: valueOf(item, "nik"),
        no_telp: valueOf(item, "no_telp"),
        jabatan: valueOf(item, "jabatan"),
        no_rekening: valueOf(item, "no_rekening"),
        status: valueOf(item, "status") || "Aktif",
        email: valueOf(item, "email"),
        password: "",
      });
    } else if (dbTab === "klien") {
      const contacts = Array.isArray(item.contacts) ? (item.contacts as Record<string, unknown>[]) : [];
      setForm({ nama_klien: valueOf(item, "nama_klien"), nama_perusahaan: valueOf(item, "nama_perusahaan"), alamat: valueOf(item, "alamat"), cp1_nama: String(contacts[0]?.nama || ""), cp1_telp: String(contacts[0]?.telp || ""), cp2_nama: String(contacts[1]?.nama || ""), cp2_telp: String(contacts[1]?.telp || ""), cp3_nama: String(contacts[2]?.nama || ""), cp3_telp: String(contacts[2]?.telp || ""), npwp: valueOf(item, "npwp"), kategori: valueOf(item, "kategori") || "Corporate" });
    } else if (dbTab === "armada") {
      setForm({
        jenis_kendaraan: valueOf(item, "jenis_kendaraan"),
        plat_nomor: valueOf(item, "plat_nomor"),
        status_stnk: valueOf(item, "status_stnk"),
        driver: valueOf(item, "driver"),
        status_operasional: valueOf(item, "status_operasional"),
      });
    } else if (dbTab === "perusahaan") {
      setForm({
        nama_perusahaan: valueOf(item, "nama_perusahaan"),
        alamat_kantor: valueOf(item, "alamat_kantor"),
        telepon: valueOf(item, "telepon"),
        email: valueOf(item, "email"),
        nama_bank: valueOf(item, "nama_bank"),
        nomor_rekening: valueOf(item, "nomor_rekening"),
        atas_nama_rekening: valueOf(item, "atas_nama_rekening"),
      });
    }
    setModalOpen(true);
  }

  async function saveRecord(e: FormEvent<HTMLFormElement>): Promise<void> {
    e.preventDefault();
    setBusy(true);
    setError("");

    let endpoint = "master-barang";
    if (dbTab === "barang") {
      endpoint = barangSubTab === "paket" ? "master-paket" : "master-barang";
    } else if (dbTab === "karyawan") {
      endpoint = "master-karyawan";
    } else if (dbTab === "klien") {
      endpoint = "master-klien";
    } else if (dbTab === "armada") {
      endpoint = "master-armada";
    } else if (dbTab === "perusahaan") {
      endpoint = "master-perusahaan";
    }

    const isMultipart = (dbTab === "barang" && barangSubTab === "satuan") || dbTab === "karyawan";
    let body: FormData | string;

    if (isMultipart) {
      const fd = new FormData();
      if (editingItem) fd.append("_method", "PUT");
      Object.entries(form).forEach(([k, v]) => fd.append(k, String(v)));

      const fileInput = e.currentTarget.querySelector('input[type="file"]') as HTMLInputElement | null;
      if (fileInput?.files?.[0]) {
        fd.append(dbTab === "barang" ? "foto" : "ktp_file", fileInput.files[0]);
      }
      body = fd;
    } else {
      const payload: Record<string, unknown> = { ...form };
      if (dbTab === "barang" && barangSubTab === "paket") {
        const rows = packageItems.filter((row) => row.barang_id !== "" || row.nama_item.trim() !== "");
        if (rows.length === 0) {
          setError("Isi minimal satu baris barang atau spesifikasi untuk paket.");
          setBusy(false);
          return;
        }
        payload.items = rows.map((row, idx) => ({
          barang_id: row.barang_id ? Number(row.barang_id) : null,
          nama_item: row.nama_item.trim() || "Item paket",
          qty: Number(row.qty || 1),
          satuan: row.satuan || "unit",
          harga: Number(row.harga || 0),
          urutan: idx,
        }));
        if (!String(payload.kode_paket || "").trim()) {
          payload.kode_paket = `PKT-${Date.now().toString().slice(-8)}`;
        }
        if (!Number(payload.harga_paket || 0)) {
          payload.harga_paket = rows.reduce((sum, row) => sum + Number(row.qty || 0) * Number(row.harga || 0), 0);
        }
      }
      body = JSON.stringify(payload);
    }

    try {
      const url = `${endpoint}${editingItem ? `/${editingItem.id}` : ""}`;
      const res = await request(url, {
        method: editingItem && !isMultipart ? "PATCH" : "POST",
        headers: body instanceof FormData ? {} : { "Content-Type": "application/json" },
        body,
      });

      const payload = res.status === 204 ? null : await res.json();
      if (!res.ok) {
        const firstErr = payload?.errors ? Object.values(payload.errors).flat()[0] : undefined;
        throw new Error(typeof firstErr === "string" ? firstErr : payload?.message || "Gagal menyimpan data.");
      }

      setModalOpen(false);
      setNotice(editingItem ? "Data berhasil diperbarui." : "Data berhasil disimpan.");
      setTimeout(() => setNotice(""), 3000);
      await loadDatabaseData();
    } catch (err) {
      setError(err instanceof Error ? err.message : "Gagal menyimpan data.");
    } finally {
      setBusy(false);
    }
  }

  async function deleteRecord(item: MasterItem): Promise<void> {
    const label = valueOf(item, dbTab === "barang" ? barangSubTab === "paket" ? "nama_paket" : "nama_barang" : dbTab === "karyawan" ? "nama_lengkap" : "nama_klien");
    if (!window.confirm(`Hapus permanen data ${label || "ini"}? File terkait juga akan dihapus.`)) return;
    setBusy(true);
    setError("");
    try {
      const endpoint = dbTab === "barang" ? barangSubTab === "paket" ? "master-paket" : "master-barang" : dbTab === "karyawan" ? "master-karyawan" : dbTab === "klien" ? "master-klien" : "";
      if (!endpoint) throw new Error("Hapus belum tersedia untuk modul ini.");
      const response = await request(`${endpoint}/${item.id}`, { method: "DELETE" });
      if (!response.ok && response.status !== 204) {
        const payload = await response.json().catch(() => null);
        throw new Error(payload?.message || "Gagal menghapus data.");
      }
      setNotice("Data berhasil dihapus permanen.");
      await loadDatabaseData();
    } catch (cause) {
      setError(cause instanceof Error ? cause.message : "Gagal menghapus data.");
    } finally {
      setBusy(false);
    }
  }

  // Filter items
  const filteredItems = items.filter((item) => {
    const matchSearch = Object.values(item).some((val) =>
      String(val ?? "").toLowerCase().includes(search.toLowerCase())
    );
    const matchCategory = selectedCategory ? String(item.kategori || "") === selectedCategory : true;
    return matchSearch && matchCategory;
  });

  // Login View (Identik Page 1 Design System)
  if (!token) {
    return (
      <main className="login-page">
        <div className="login-layout">
          <section className="login-showcase">
            <div className="login-brand">
              <span className="login-brand-mark"><Boxes className="h-6 w-6" /></span>
              <span>PENTA <strong>TRI SAKA</strong></span>
            </div>
            <div className="login-showcase-copy">
              <span className="login-kicker">ENTERPRISE OPERATIONS</span>
              <h1>Semua operasional.<br /><span>Satu kendali.</span></h1>
              <p>Kelola aset, klien, dan alur kerja rental AV dalam satu ruang kerja yang dirancang untuk tim Anda.</p>
            </div>
            <div className="login-orbit" aria-hidden="true">
              <div className="login-orbit-ring login-orbit-ring-one" />
              <div className="login-orbit-ring login-orbit-ring-two" />
              <div className="login-orbit-core"><Building2 className="h-9 w-9" /></div>
              <div className="login-orbit-node login-orbit-node-one"><Package className="h-5 w-5" /></div>
              <div className="login-orbit-node login-orbit-node-two"><CalendarDays className="h-5 w-5" /></div>
              <div className="login-orbit-node login-orbit-node-three"><Users className="h-5 w-5" /></div>
            </div>
            <p className="login-footer">Rental Audio Visual &amp; Video Documentation</p>
          </section>

          <section className="login-panel">
            <div className="login-form-wrap">
              <div className="login-mobile-brand">
                <span className="login-brand-mark"><Boxes className="h-5 w-5" /></span>
                <span>PENTA <strong>TRI SAKA</strong></span>
              </div>
              <div className="mb-8">
                <p className="text-sm font-semibold text-blue-700">Selamat datang kembali</p>
                <h2 className="mt-2 text-3xl font-bold tracking-tight text-slate-950">Masuk ke akun Anda</h2>
                <p className="mt-2 text-sm leading-6 text-slate-500">Masukkan kredensial Anda untuk melanjutkan ke dashboard.</p>
              </div>
              {error && <div className="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>}
              <form onSubmit={signIn} className="space-y-5">
                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">Username atau email</span>
                  <input
                    required
                    autoComplete="username"
                    type="text"
                    placeholder="Masukkan username"
                    value={credentials.email}
                    onChange={(e) => setCredentials({ ...credentials, email: e.target.value })}
                    className="login-input"
                  />
                </label>
                <label className="block">
                  <span className="mb-2 block text-sm font-medium text-slate-700">Password</span>
                  <input
                    required
                    autoComplete="current-password"
                    type="password"
                    placeholder="Masukkan password"
                    value={credentials.password}
                    onChange={(e) => setCredentials({ ...credentials, password: e.target.value })}
                    className="login-input"
                  />
                </label>
                <button disabled={busy} type="submit" className="login-submit">
                  {busy ? "Memproses..." : "Masuk ke dashboard"}
                  <ChevronRight className="h-4 w-4" />
                </button>
              </form>
              <p className="mt-8 text-center text-xs leading-5 text-slate-400">Akses terbatas untuk tim internal Penta Tri Saka.</p>
            </div>
            <p className="login-panel-foot">Penta Tri Saka <span>•</span> Sistem Operasional</p>
          </section>
        </div>
      </main>
    );
  }

  // Teknisi mobile view
  if (userRole === "teknisi" && token) {
    return (
      <div className="min-h-screen bg-gradient-to-br from-blue-50 to-slate-50 font-sans pb-28">
        {/* Header */}
        <header className="sticky top-0 z-20 bg-white shadow-sm">
          <div className="flex items-center justify-between px-4 py-4">
            <div className="flex items-center gap-2">
              <Boxes className="h-5 w-5 text-blue-600" />
              <div>
                <h1 className="text-sm font-bold text-slate-900">PENTA TRISAKA</h1>
                <p className="text-[10px] text-slate-500">Teknisi Lapangan</p>
              </div>
            </div>
            <button onClick={() => signOut()} className="text-xs font-semibold text-red-600 hover:text-red-700">
              Logout
            </button>
          </div>
        </header>

        {/* Main content */}
        <main className="px-4 py-6 space-y-6">
          {error && <div className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</div>}
          {notice && <div className="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">{notice}</div>}

          {/* Step 1: Scan Surat Jalan */}
          {technicianDeliveryStage === "scan" && !deliveryData && (
            <div className="space-y-4">
              <div className="rounded-xl bg-white p-5 shadow-sm">
                <h2 className="text-lg font-bold text-slate-900">Scan Surat Jalan</h2>
                <p className="mt-1 text-sm text-slate-500">Masukkan kode untuk memulai</p>
              </div>

              <form
                onSubmit={async (e) => {
                  e.preventDefault();
                  setBusy(true);
                  setError("");
                  try {
                    const response = await request("delivery/scan-surat-jalan", {
                      method: "POST",
                      headers: { "Content-Type": "application/json" },
                      body: JSON.stringify({ kode_surat_jalan: technicianDeliveryForm.kode_surat_jalan }),
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || "Surat jalan tidak ditemukan.");
                    setDeliveryData(payload.data);
                    setTechnicianDeliveryForm({
                      ...technicianDeliveryForm,
                      items: (payload.data.items || []).map((item: Record<string, unknown>) => ({
                        nama_barang: String(item.nama_item || ""),
                        qty: Number(item.qty || 0),
                        status_fisik: "Baik",
                        keterangan: "",
                      })),
                    });
                  } catch (cause) {
                    setError(cause instanceof Error ? cause.message : "Gagal scan surat jalan.");
                  } finally {
                    setBusy(false);
                  }
                }}
                className="space-y-4"
              >
                <label className="block">
                  <span className="block text-sm font-semibold text-slate-700 mb-2">Kode Surat Jalan</span>
                  <input
                    required
                    type="text"
                    placeholder="Contoh: SJ-202609-001"
                    value={technicianDeliveryForm.kode_surat_jalan}
                    onChange={(e) => setTechnicianDeliveryForm({ ...technicianDeliveryForm, kode_surat_jalan: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 px-4 py-3 text-center text-lg font-semibold tracking-wider uppercase outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                  />
                </label>
                <button
                  disabled={busy}
                  type="submit"
                  className="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white shadow-lg hover:bg-blue-700 disabled:opacity-60"
                >
                  {busy ? "Memproses..." : "Scan"}
                </button>
              </form>
            </div>
          )}

          {/* Step 2: Checkout */}
          {technicianDeliveryStage === "checkout" && deliveryData && (
            <div className="space-y-4">
              <div className="rounded-xl bg-white p-5 shadow-sm">
                <h2 className="text-lg font-bold text-slate-900">Surat Jalan: {deliveryData.nomor_surat_jalan}</h2>
                <p className="mt-1 text-sm text-slate-500">{deliveryData.items.length} item barang</p>
                <p className="mt-1 text-xs text-slate-500">Teknisi: {deliveryData.teknisi.length > 0 ? deliveryData.teknisi.map((teknisi) => teknisi.name).join(", ") : "Semua teknisi (belum ditentukan)"}</p>
              </div>

              {!deliveryData.is_assigned && (
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                  Anda tidak ditugaskan pada surat jalan ini. Checkout hanya bisa dilakukan teknisi yang ditugaskan atau admin.
                </div>
              )}

              {/* Foto */}
              <div className="rounded-xl bg-white p-4 shadow-sm">
                <label className="block">
                  <span className="block text-sm font-semibold text-slate-700 mb-2">Ambil Foto</span>
                  <div className="flex items-center justify-center rounded-lg border-2 border-dashed border-slate-300 p-6 hover:border-blue-400">
                    <input
                      type="file"
                      accept="image/*"
                      capture="environment"
                      onChange={(e) => setTechnicianDeliveryForm({ ...technicianDeliveryForm, foto: e.target.files?.[0] || null })}
                      className="hidden"
                      id="foto-input"
                    />
                    <label htmlFor="foto-input" className="flex flex-col items-center gap-2 cursor-pointer">
                      <Camera className="h-8 w-8 text-slate-400" />
                      <span className="text-center text-sm font-medium text-slate-600">
                        {technicianDeliveryForm.foto ? "Foto dipilih ✓" : "Tap untuk ambil foto"}
                      </span>
                    </label>
                  </div>
                </label>
              </div>

              {/* Checklist item */}
              <div className="rounded-xl bg-white p-4 shadow-sm space-y-3">
                <h3 className="font-semibold text-slate-900">Checklist Barang</h3>
                {technicianDeliveryForm.items.map((item, idx) => (
                  <div key={idx} className="border-l-4 border-blue-500 pl-3 py-2">
                    <p className="font-medium text-slate-900">{item.nama_barang}</p>
                    <p className="text-xs text-slate-500">Qty: {item.qty}</p>
                    <select
                      value={item.status_fisik}
                      onChange={(e) => {
                        const newItems = [...technicianDeliveryForm.items];
                        newItems[idx].status_fisik = e.target.value;
                        setTechnicianDeliveryForm({ ...technicianDeliveryForm, items: newItems });
                      }}
                      className="mt-2 w-full rounded border border-slate-300 px-2 py-1 text-xs"
                    >
                      <option>Baik</option>
                      <option>Lecet</option>
                      <option>Rusak</option>
                      <option>Hilang</option>
                    </select>
                    <input
                      type="text"
                      placeholder="Catatan (opsional)"
                      value={item.keterangan}
                      onChange={(e) => {
                        const newItems = [...technicianDeliveryForm.items];
                        newItems[idx].keterangan = e.target.value;
                        setTechnicianDeliveryForm({ ...technicianDeliveryForm, items: newItems });
                      }}
                      className="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-xs"
                    />
                  </div>
                ))}
              </div>

              {/* Buttons */}
              <div className="space-y-2">
                <button
                  disabled={!technicianDeliveryForm.foto || busy || !deliveryData.is_assigned}
                  onClick={async () => {
                    setBusy(true);
                    setError("");
                    try {
                      const formData = new FormData();
                      formData.append("surat_jalan_id", String(deliveryData.id));
                      formData.append("foto", technicianDeliveryForm.foto!);
                      formData.append("items", JSON.stringify(technicianDeliveryForm.items));
                      const response = await fetch(`${API_BASE}/v1/delivery/checkout`, {
                        method: "POST",
                        headers: { Authorization: `Bearer ${token}` },
                        body: formData,
                      });
                      const payload = await response.json();
                      if (!response.ok) throw new Error(payload.message || "Gagal checkout.");
                      setNotice("Checkout berhasil. Barang siap diangkut.");
                      setTimeout(() => {
                        setTechnicianDeliveryStage("checkin");
                        setTechnicianDeliveryForm({ kode_surat_jalan: "", foto: null, items: [] });
                      }, 2000);
                    } catch (cause) {
                      setError(cause instanceof Error ? cause.message : "Gagal checkout.");
                    } finally {
                      setBusy(false);
                    }
                  }}
                  className="w-full rounded-lg bg-emerald-600 px-4 py-3 font-semibold text-white shadow-lg hover:bg-emerald-700 disabled:opacity-60"
                >
                  {busy ? "Memproses..." : "✓ Checkout"}
                </button>
                <button
                  onClick={() => {
                    setTechnicianDeliveryStage("additional");
                    setError("");
                  }}
                  className="w-full rounded-lg border border-blue-300 px-4 py-3 font-semibold text-blue-700 hover:bg-blue-50"
                >
                  + Tambah Barang Susulan
                </button>
                <button
                  onClick={() => {
                    setTechnicianDeliveryForm({ kode_surat_jalan: "", foto: null, items: [] });
                    setDeliveryData(null);
                    setTechnicianDeliveryStage("scan");
                  }}
                  className="w-full rounded-lg border border-slate-300 px-4 py-3 font-semibold text-slate-700 hover:bg-slate-50"
                >
                  Kembali
                </button>
              </div>
            </div>
          )}

          {/* Step 2b: Tambah Barang Susulan */}
          {technicianDeliveryStage === "additional" && deliveryData && (
            <div className="space-y-4">
              <div className="rounded-xl bg-white p-5 shadow-sm">
                <h2 className="text-lg font-bold text-slate-900">Tambah Barang Susulan</h2>
                <p className="mt-1 text-sm text-slate-500">Request tambahan dari klien di lapangan</p>
              </div>

              <form
                onSubmit={async (e) => {
                  e.preventDefault();
                  setBusy(true);
                  setError("");
                  try {
                    const formData = new FormData();
                    formData.append("surat_jalan_id", String(deliveryData.id));
                    formData.append("nama_barang", additionalItemForm.nama_barang);
                    formData.append("qty", String(additionalItemForm.qty));
                    formData.append("alasan", additionalItemForm.alasan);
                    if (additionalItemForm.foto) {
                      formData.append("foto", additionalItemForm.foto);
                    }
                    const response = await fetch(`${API_BASE}/v1/delivery-additional-items`, {
                      method: "POST",
                      headers: { Authorization: `Bearer ${token}` },
                      body: formData,
                    });
                    const payload = await response.json();
                    if (!response.ok) throw new Error(payload.message || "Gagal tambah barang susulan.");
                    setNotice("Barang susulan berhasil ditambahkan.");
                    setAdditionalItems([...additionalItems, { nama_barang: additionalItemForm.nama_barang, qty: additionalItemForm.qty, alasan: additionalItemForm.alasan }]);
                    setAdditionalItemForm({ nama_barang: "", qty: 1, foto: null, alasan: "" });
                  } catch (cause) {
                    setError(cause instanceof Error ? cause.message : "Gagal tambah barang susulan.");
                  } finally {
                    setBusy(false);
                  }
                }}
                className="space-y-4"
              >
                <label className="block">
                  <span className="block text-sm font-semibold text-slate-700 mb-2">Nama Barang</span>
                  <input
                    required
                    type="text"
                    placeholder="Contoh: Proyektor tambahan"
                    value={additionalItemForm.nama_barang}
                    onChange={(e) => setAdditionalItemForm({ ...additionalItemForm, nama_barang: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                  />
                </label>

                <label className="block">
                  <span className="block text-sm font-semibold text-slate-700 mb-2">Jumlah</span>
                  <input
                    required
                    type="number"
                    min={1}
                    value={additionalItemForm.qty}
                    onChange={(e) => setAdditionalItemForm({ ...additionalItemForm, qty: Number(e.target.value) })}
                    className="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                  />
                </label>

                <label className="block">
                  <span className="block text-sm font-semibold text-slate-700 mb-2">Alasan Penambahan</span>
                  <textarea
                    required
                    placeholder="Contoh: Klien minta penambahan untuk backup"
                    rows={3}
                    value={additionalItemForm.alasan}
                    onChange={(e) => setAdditionalItemForm({ ...additionalItemForm, alasan: e.target.value })}
                    className="w-full rounded-lg border border-slate-300 px-4 py-3 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                  />
                </label>

                <label className="block">
                  <span className="block text-sm font-semibold text-slate-700 mb-2">Foto Barang (Opsional)</span>
                  <div className="flex items-center justify-center rounded-lg border-2 border-dashed border-slate-300 p-6 hover:border-blue-400">
                    <input
                      type="file"
                      accept="image/*"
                      capture="environment"
                      onChange={(e) => setAdditionalItemForm({ ...additionalItemForm, foto: e.target.files?.[0] || null })}
                      className="hidden"
                      id="foto-additional-input"
                    />
                    <label htmlFor="foto-additional-input" className="flex flex-col items-center gap-2 cursor-pointer">
                      <Camera className="h-8 w-8 text-slate-400" />
                      <span className="text-center text-sm font-medium text-slate-600">
                        {additionalItemForm.foto ? "Foto dipilih ✓" : "Tap untuk ambil foto"}
                      </span>
                    </label>
                  </div>
                </label>

                <button
                  disabled={busy}
                  type="submit"
                  className="w-full rounded-lg bg-blue-600 px-4 py-3 font-semibold text-white shadow-lg hover:bg-blue-700 disabled:opacity-60"
                >
                  {busy ? "Memproses..." : "+ Simpan Barang"}
                </button>
              </form>

              {additionalItems.length > 0 && (
                <div className="rounded-xl bg-blue-50 p-4 space-y-2">
                  <h3 className="font-semibold text-slate-900">Barang Susulan Ditambahkan:</h3>
                  {additionalItems.map((item, idx) => (
                    <p key={idx} className="text-sm text-slate-700">
                      • {item.nama_barang} (qty: {item.qty})
                    </p>
                  ))}
                </div>
              )}

              <button
                onClick={() => setTechnicianDeliveryStage("checkout")}
                className="w-full rounded-lg border border-slate-300 px-4 py-3 font-semibold text-slate-700 hover:bg-slate-50"
              >
                Lanjut ke Checkin
              </button>
            </div>
          )}

          {/* Step 3: Checkin */}
          {technicianDeliveryStage === "checkin" && deliveryData && (
            <div className="space-y-4">
              <div className="rounded-xl bg-white p-5 shadow-sm">
                <h2 className="text-lg font-bold text-slate-900">Checkin: {deliveryData.nomor_surat_jalan}</h2>
                <p className="mt-1 text-sm text-slate-500">Catat kondisi barang saat kembali ke gudang</p>
                <p className="mt-1 text-xs text-slate-500">Teknisi: {deliveryData.teknisi.length > 0 ? deliveryData.teknisi.map((teknisi) => teknisi.name).join(", ") : "Semua teknisi (belum ditentukan)"}</p>
              </div>

              {!deliveryData.is_assigned && (
                <div className="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                  Anda tidak ditugaskan pada surat jalan ini. Checkin hanya bisa dilakukan teknisi yang ditugaskan atau admin.
                </div>
              )}

              <div className="rounded-xl bg-white p-4 shadow-sm">
                <label className="block">
                  <span className="block text-sm font-semibold text-slate-700 mb-2">Ambil Foto</span>
                  <div className="flex items-center justify-center rounded-lg border-2 border-dashed border-slate-300 p-6 hover:border-blue-400">
                    <input
                      type="file"
                      accept="image/*"
                      capture="environment"
                      onChange={(e) => setTechnicianDeliveryForm({ ...technicianDeliveryForm, foto: e.target.files?.[0] || null })}
                      className="hidden"
                      id="foto-checkin-input"
                    />
                    <label htmlFor="foto-checkin-input" className="flex flex-col items-center gap-2 cursor-pointer">
                      <Camera className="h-8 w-8 text-slate-400" />
                      <span className="text-center text-sm font-medium text-slate-600">
                        {technicianDeliveryForm.foto ? "Foto dipilih ✓" : "Tap untuk ambil foto"}
                      </span>
                    </label>
                  </div>
                </label>
              </div>

              <div className="rounded-xl bg-white p-4 shadow-sm space-y-3">
                <h3 className="font-semibold text-slate-900">Kondisi Barang (Saat Kembali)</h3>
                {technicianDeliveryForm.items.map((item, idx) => (
                  <div key={idx} className="border-l-4 border-orange-500 pl-3 py-2">
                    <p className="font-medium text-slate-900">{item.nama_barang}</p>
                    <p className="text-xs text-slate-500">Qty: {item.qty}</p>
                    <select
                      value={item.status_fisik}
                      onChange={(e) => {
                        const newItems = [...technicianDeliveryForm.items];
                        newItems[idx].status_fisik = e.target.value;
                        setTechnicianDeliveryForm({ ...technicianDeliveryForm, items: newItems });
                      }}
                      className="mt-2 w-full rounded border border-slate-300 px-2 py-1 text-xs"
                    >
                      <option>Baik</option>
                      <option>Lecet</option>
                      <option>Rusak</option>
                      <option>Hilang</option>
                    </select>
                    <input
                      type="text"
                      placeholder="Catatan kerusakan (opsional)"
                      value={item.keterangan}
                      onChange={(e) => {
                        const newItems = [...technicianDeliveryForm.items];
                        newItems[idx].keterangan = e.target.value;
                        setTechnicianDeliveryForm({ ...technicianDeliveryForm, items: newItems });
                      }}
                      className="mt-1 w-full rounded border border-slate-300 px-2 py-1 text-xs"
                    />
                  </div>
                ))}
              </div>

              <div className="space-y-2">
                <button
                  disabled={!technicianDeliveryForm.foto || busy || !deliveryData.is_assigned}
                  onClick={async () => {
                    setBusy(true);
                    setError("");
                    try {
                      const formData = new FormData();
                      formData.append("surat_jalan_id", String(deliveryData.id));
                      formData.append("foto", technicianDeliveryForm.foto!);
                      formData.append("items", JSON.stringify(technicianDeliveryForm.items));
                      const response = await fetch(`${API_BASE}/v1/delivery/checkin`, {
                        method: "POST",
                        headers: { Authorization: `Bearer ${token}` },
                        body: formData,
                      });
                      const payload = await response.json();
                      if (!response.ok) throw new Error(payload.message || "Gagal checkin.");
                      setNotice("Checkin berhasil. Barang dikembalikan ke gudang.");
                      setTimeout(() => {
                        setTechnicianDeliveryStage("scan");
                        setTechnicianDeliveryForm({ kode_surat_jalan: "", foto: null, items: [] });
                        setDeliveryData(null);
                      }, 2000);
                    } catch (cause) {
                      setError(cause instanceof Error ? cause.message : "Gagal checkin.");
                    } finally {
                      setBusy(false);
                    }
                  }}
                  className="w-full rounded-lg bg-orange-600 px-4 py-3 font-semibold text-white shadow-lg hover:bg-orange-700 disabled:opacity-60"
                >
                  {busy ? "Memproses..." : "✓ Checkin"}
                </button>
                <button
                  onClick={() => {
                    setTechnicianDeliveryStage("checkout");
                    setError("");
                  }}
                  className="w-full rounded-lg border border-slate-300 px-4 py-3 font-semibold text-slate-700 hover:bg-slate-50"
                >
                  Kembali
                </button>
              </div>
            </div>
          )}
        </main>
      </div>
    );
  }

  // Authenticated application shell (Owner/Admin desktop)
  return (
    <div className="app-shell min-h-screen font-sans antialiased">
      <aside className="app-sidebar fixed inset-y-0 left-0 z-30 flex w-[256px] flex-col">
        <div className="sidebar-brand">
          <span className="sidebar-brand-mark"><Boxes className="h-5 w-5" /></span>
          <div>
            <h1 className="text-sm font-bold tracking-wide text-white">PENTA <span>TRI SAKA</span></h1>
            <p className="mt-1 text-[9px] font-semibold tracking-[.17em] text-blue-200/65">ENTERPRISE OPERATIONS</p>
          </div>
        </div>
        <nav className="sidebar-nav flex-1 overflow-y-auto">
          {menuCategories.map((cat) => (
            <div key={cat.name} className="sidebar-nav-group">
              <h2 className="sidebar-nav-heading">{cat.name}</h2>
              <div className="space-y-1">
                {cat.items.map((m) => {
                  const isActive = activeMenu === m;
                  const Icon = menuIcons[m];
                  return (
                    <button
                      key={m}
                      type="button"
                      title={menuLabels[m]}
                      aria-current={isActive ? "page" : undefined}
                      onClick={() => setActiveMenu(m)}
                      className={`sidebar-link ${isActive ? "sidebar-link-active" : ""}`}
                    >
                      <Icon className="h-[18px] w-[18px] shrink-0" strokeWidth={1.8} />
                      <span className="nav-label">{menuLabels[m]}</span>
                      {isActive && <span className="sidebar-active-mark" />}
                    </button>
                  );
                })}
              </div>
            </div>
          ))}
        </nav>
        <div className="sidebar-footer">
          <div className="sidebar-footer-icon"><Building2 className="h-4 w-4" /></div>
          <div><p className="text-xs font-semibold text-white">Penta Tri Saka</p><p className="mt-1 text-[10px] text-blue-200/55">Workspace internal</p></div>
        </div>
      </aside>

      <div className="app-main flex min-h-screen flex-1 flex-col pl-[256px]">
        <header className="app-header sticky top-0 z-20 flex h-[76px] items-center justify-between px-8">
          <div>
            <p className="text-[11px] font-semibold uppercase tracking-[.14em] text-slate-400">Penta Tri Saka <span className="mx-1.5 text-slate-300">/</span>Workspace</p>
            <h1 className="mt-1 text-base font-semibold text-slate-900">{menuLabels[activeMenu]}</h1>
          </div>
          <div className="flex items-center gap-3">
            <div className="hidden text-right sm:block">
              <p className="text-sm font-semibold text-slate-800">Administrator</p>
              <p className="mt-0.5 text-xs text-slate-400">Workspace owner</p>
            </div>
            <div className="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 ring-4 ring-blue-50"><User className="h-[18px] w-[18px]" /></div>
            <button type="button" onClick={() => signOut()} className="ml-1 rounded-lg px-3 py-2 text-xs font-semibold text-slate-500 hover:bg-slate-100 hover:text-slate-900">Keluar</button>
          </div>
        </header>

        {/* CONTENT AREA */}
        <main className="app-content min-h-screen p-6 md:p-8">
          {activeMenu === "DASHBOARD" && (
            <DashboardView data={dashboardData} loading={loading} error={error} onRefresh={() => void loadDashboardData()} />
          )}

          {activeMenu === "DATABASE" && (
            <section className="database-page space-y-5">
              <div className="flex flex-wrap items-end justify-between gap-4">
                <div>
                  <p className="text-xs font-semibold uppercase tracking-[.14em] text-blue-700">Pengelolaan data</p>
                  <h2 className="mt-1 text-2xl font-bold tracking-tight text-slate-950">Database master</h2>
                  <p className="mt-1 text-sm text-slate-500">Kelola aset, tim, klien, dan kebutuhan operasional perusahaan.</p>
                </div>
                <span className="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-500">{filteredItems.length} data ditampilkan</span>
              </div>
              <div className="database-tabs">
                {[
                  { key: "barang", label: "Barang", icon: Package },
                  { key: "karyawan", label: "Karyawan", icon: Users },
                  { key: "klien", label: "Klien", icon: Building2 },
                  { key: "armada", label: "Armada", icon: CarFront },
                  { key: "perusahaan", label: "Perusahaan", icon: Building2 },
                ].map(({ key, label, icon: Icon }) => {
                  const isActive = dbTab === key;
                  return (
                    <button
                      key={key}
                      type="button"
                      onClick={() => {
                        setDbTab(key as DatabaseTab);
                        setSearch("");
                        setSelectedCategory("");
                      }}
                      className={`database-tab ${isActive ? "database-tab-active" : ""}`}
                    >
                      <Icon className="h-4 w-4" />
                      {label}
                    </button>
                  );
                })}
              </div>
              <div className="database-toolbar">
                <div className="flex flex-wrap items-center gap-3">
                  {dbTab === "barang" && (
                    <div className="database-segmented">
                      {[
                        { key: "satuan", label: "Satuan" },
                        { key: "paket", label: "Paket" },
                      ].map((tab) => (
                        <button
                          key={tab.key}
                          type="button"
                          aria-pressed={barangSubTab === tab.key}
                          onClick={() => setBarangSubTab(tab.key as BarangSubTab)}
                          className={barangSubTab === tab.key ? "database-segment-active" : ""}
                        >
                          {tab.label}
                        </button>
                      ))}
                    </div>
                  )}
                  <label className="database-search">
                    <Search className="h-4 w-4 shrink-0 text-slate-400" />
                    <input
                      type="search"
                      aria-label="Cari data"
                      placeholder="Cari data..."
                      value={search}
                      onChange={(e) => setSearch(e.target.value)}
                    />
                  </label>
                  {dbTab === "barang" && (
                    <div className="relative">
                      <button type="button" onClick={() => setFilterOpen(!filterOpen)} className="database-filter">
                        <Filter className="h-4 w-4" />
                        {selectedCategory || "Semua kategori"}
                        <ChevronDown className="h-4 w-4" />
                      </button>
                      {filterOpen && (
                        <div className="database-filter-menu">
                          <button type="button" onClick={() => { setSelectedCategory(""); setFilterOpen(false); }}>
                            <span>Semua kategori</span>
                            {!selectedCategory && <Check className="h-4 w-4 text-blue-600" />}
                          </button>
                          {CATEGORY_LIST.map((category) => (
                            <button key={category} type="button" onClick={() => { setSelectedCategory(category); setFilterOpen(false); }}>
                              <span>{category}</span>
                              {selectedCategory === category && <Check className="h-4 w-4 text-blue-600" />}
                            </button>
                          ))}
                        </div>
                      )}
                    </div>
                  )}
                </div>
                <button type="button" onClick={openCreate} className="database-add-button">
                  <Plus className="h-4 w-4" />
                  Tambah data
                </button>
              </div>

              {/* Status alerts */}
              {notice && (
                <div className="rounded-lg bg-emerald-500/10 border border-emerald-500/20 px-4 py-2.5 text-xs font-medium text-emerald-800">
                  {notice}
                </div>
              )}
              {error && (
                <div className="rounded-lg bg-red-500/10 border border-red-500/20 px-4 py-2.5 text-xs font-medium text-red-800">
                  {error}
                </div>
              )}

              {/* DATA TABLE */}
              <div className="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-[0_10px_30px_rgba(15,23,42,.06)]">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 text-slate-600">
                    {dbTab === "barang" && barangSubTab === "satuan" && (
                      <tr>
                        <th className="py-3.5 px-4 font-semibold">Code</th>
                        <th className="py-3.5 px-4 font-semibold">Nama Barang</th>
                        <th className="py-3.5 px-4 font-semibold">Kategori</th>
                        <th className="py-3.5 px-4 font-semibold text-center">Foto</th>
                        <th className="py-3.5 px-4 font-semibold text-center">Jumlah / Satuan</th>
                        <th className="py-3.5 px-4 font-semibold text-center">Rusak</th>
                        <th className="py-3.5 px-4 font-semibold text-center">Di Sewa</th>
                        <th className="py-3.5 px-4 font-semibold text-right">Harga</th>
                        <th className="py-3.5 px-4 font-semibold text-right">Aksi</th>
                      </tr>
                    )}
                    {dbTab === "barang" && barangSubTab === "paket" && (
                      <tr>
                        <th className="py-3.5 px-4 font-semibold">Code</th>
                        <th className="py-3.5 px-4 font-semibold">Kategori</th>
                        <th className="py-3.5 px-4 font-semibold">Nama Paket</th>
                        <th className="py-3.5 px-4 font-semibold">List Alat</th>
                        <th className="py-3.5 px-4 font-semibold text-right">Harga</th>
                        <th className="py-3.5 px-4 font-semibold text-right">Aksi</th>
                      </tr>
                    )}
                    {dbTab === "karyawan" && (
                      <tr>
                        <th className="py-3.5 px-4 font-semibold">Nama</th>
                        <th className="py-3.5 px-4 font-semibold">Jabatan</th>
                        <th className="py-3.5 px-4 font-semibold">NIK</th>
                        <th className="py-3.5 px-4 font-semibold text-center">KTP</th>
                        <th className="py-3.5 px-4 font-semibold">No Telpon</th>
                        <th className="py-3.5 px-4 font-semibold text-center">Status</th>
                        <th className="py-3.5 px-4 font-semibold text-right">Aksi</th>
                      </tr>
                    )}
                    {dbTab === "klien" && (
                      <tr>
                        <th className="py-3.5 px-4 font-semibold">Nama Klien</th>
                        <th className="py-3.5 px-4 font-semibold">Nama Perusahaan</th>
                        <th className="py-3.5 px-4 font-semibold">Alamat</th>
                        <th className="py-3.5 px-4 font-semibold">Contact Person</th>
                        <th className="py-3.5 px-4 font-semibold">No Telpon</th>
                        <th className="py-3.5 px-4 font-semibold">NPWP</th>
                        <th className="py-3.5 px-4 font-semibold text-right">Aksi</th>
                      </tr>
                    )}
                    {dbTab === "armada" && (
                      <tr>
                        <th className="py-3.5 px-4 font-semibold">Jenis</th>
                        <th className="py-3.5 px-4 font-semibold">Plat Nomor</th>
                        <th className="py-3.5 px-4 font-semibold">STNK</th>
                        <th className="py-3.5 px-4 font-semibold">Driver</th>
                        <th className="py-3.5 px-4 font-semibold text-center">Operasional</th>
                        <th className="py-3.5 px-4 font-semibold text-right">Aksi</th>
                      </tr>
                    )}
                    {dbTab === "perusahaan" && (
                      <tr>
                        <th className="py-3.5 px-4 font-semibold">Nama Perusahaan</th>
                        <th className="py-3.5 px-4 font-semibold">Alamat Kantor</th>
                        <th className="py-3.5 px-4 font-semibold">Telepon</th>
                        <th className="py-3.5 px-4 font-semibold">Email</th>
                        <th className="py-3.5 px-4 font-semibold text-right">Aksi</th>
                      </tr>
                    )}
                  </thead>

                  <tbody className="divide-y divide-slate-100">
                    {loading ? (
                      <tr>
                        <td colSpan={10} className="py-12 text-center text-slate-400">
                          Memuat data...
                        </td>
                      </tr>
                    ) : filteredItems.length === 0 ? (
                      <tr>
                        <td colSpan={10} className="py-12 text-center text-slate-400">
                          Tidak ada data yang ditemukan.
                        </td>
                      </tr>
                    ) : (
                      filteredItems.map((item) => {
                        if (dbTab === "barang" && barangSubTab === "satuan") {
                          return (
                            <tr key={item.id} className="hover:bg-slate-50/80">
                              <td className="py-4 px-4 align-top font-semibold text-slate-900">{valueOf(item, "kode_barang")}</td>
                              <td className="py-4 px-4 align-top text-slate-700">{valueOf(item, "nama_barang")}</td>
                              <td className="py-4 px-4 align-top text-slate-700">{valueOf(item, "kategori")}</td>
                              <td className="py-4 px-4 align-top text-center">
                                {valueOf(item, "foto_barang_path") ? (
                                                                     <img src={`${API_BASE}/storage/${valueOf(item, "foto_barang_path")}`} alt="Foto" className="h-12 w-12 rounded object-cover mx-auto" />
                                ) : (
                                  <span className="text-slate-400">-</span>
                                )}
                              </td>
                              <td className="py-4 px-4 align-top text-center text-slate-700">{valueOf(item, "jumlah_total")} {valueOf(item, "satuan") || "unit"}</td>
                              <td className="py-4 px-4 align-top text-center text-red-600 font-semibold">{valueOf(item, "jumlah_rusak")}</td>
                              <td className="py-4 px-4 align-top text-center text-blue-600 font-semibold">{valueOf(item, "di_sewa")}</td>
                              <td className="py-4 px-4 align-top text-right font-medium text-slate-900">Rp {formatIDR(item.harga_rental)}</td>
                              <td className="py-4 px-4 align-top text-right">
                                <div className="flex justify-end gap-1.5">
                                  <button onClick={() => openEdit(item)} className="rounded-lg bg-blue-50 px-3 py-1.5 text-xs font-semibold text-blue-700 hover:bg-blue-100">Edit</button>
                                  <button onClick={() => void deleteRecord(item)} disabled={busy} className="rounded-lg bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-100 disabled:opacity-50">Hapus</button>
                                </div>
                              </td>
                            </tr>
                          );
                        }

                        if (dbTab === "barang" && barangSubTab === "paket") {
                          const pItems = Array.isArray(item.items) ? (item.items as Record<string, unknown>[]) : [];
                          return (
                            <tr key={item.id} className="hover:bg-slate-50/80">
                              <td className="py-4 px-4 align-top font-medium text-slate-900">
                                <div>{valueOf(item, "kode_paket")}</div>
                                <div className="flex justify-end gap-1.5">
                                  <button onClick={() => openEdit(item)} className="rounded-full bg-blue-600/10 px-3 py-1 text-[10px] font-bold text-blue-700 hover:bg-blue-600/20">Edit</button>
                                  <button onClick={() => void deleteRecord(item)} disabled={busy} className="rounded-full bg-red-600/10 px-3 py-1 text-[10px] font-bold text-red-700 hover:bg-red-600/20 disabled:opacity-50">Hapus</button>
                                </div>
                              </td>
                              <td className="py-4 px-4 align-top text-slate-700">
                                <div>{valueOf(item, "kategori")}</div>
                                {valueOf(item, "sub_kategori") && (
                                  <div className="text-[11px] text-slate-400">{valueOf(item, "sub_kategori")}</div>
                                )}
                              </td>
                              <td className="py-4 px-4 align-top font-bold text-slate-900">
                                {valueOf(item, "nama_paket")}
                              </td>
                              <td className="py-4 px-4 align-top text-slate-600">
                                <ul className="space-y-0.5">
                                  {pItems.map((pi, idx) => (
                                    <li key={idx}>
                                      • {String(pi.nama_item || (pi.barang as Record<string, unknown>)?.nama_barang || `Item #${pi.barang_id}`)}
                                      {" — "}
                                      {String(Number(pi.qty || 0))} {String(pi.satuan || "unit")}
                                      {Number(pi.harga || 0) > 0 ? ` × Rp ${formatIDR(pi.harga)}` : ""}
                                    </li>
                                  ))}
                                </ul>
                              </td>
                              <td className="py-4 px-4 align-top text-right font-medium text-slate-900">
                                {formatIDR(item.harga_paket)}
                              </td>
                            </tr>
                          );
                        }

                        if (dbTab === "karyawan") {
                          return (
                            <tr key={item.id} className="hover:bg-slate-50/80">
                              <td className="py-4 px-4 font-bold text-slate-900">{valueOf(item, "nama_lengkap")}</td>
                              <td className="py-4 px-4 text-slate-700">{valueOf(item, "jabatan")}</td>
                              <td className="py-4 px-4 text-slate-700">{valueOf(item, "nik")}</td>
                              <td className="py-4 px-4 text-center">
                                {item.ktp_url || item.ktp_path ? (
                                  <img
                                    src={String(item.ktp_url || `${API_BASE.replace(/\/api\/?$/, "")}/storage/${item.ktp_path}`)}
                                    alt="KTP"
                                    className="mx-auto h-10 w-16 rounded object-cover shadow-sm"
                                  />
                                ) : (
                                  <span className="text-slate-400">-</span>
                                )}
                              </td>
                              <td className="py-4 px-4 text-slate-700">{valueOf(item, "no_telp")}</td>
                              <td className="py-4 px-4 text-center">
                                <span
                                  className={`rounded-full px-2.5 py-0.5 text-[10px] font-semibold ${
                                    valueOf(item, "status") === "Aktif"
                                      ? "bg-emerald-100 text-emerald-800"
                                      : "bg-slate-100 text-slate-600"
                                  }`}
                                >
                                  {valueOf(item, "status")}
                                </span>
                              </td>
                              <td className="py-4 px-4 text-right">
                                <button
                                  onClick={() => openEdit(item)}
                                  className="rounded-full bg-[#002480] px-3 py-0.5 text-[10px] font-bold text-white hover:bg-blue-900"
                                >
                                  Edit
                                </button>
                                <button onClick={() => void deleteRecord(item)} disabled={busy} className="ml-1 rounded-full bg-red-600 px-3 py-0.5 text-[10px] font-bold text-white hover:bg-red-700 disabled:opacity-50">Hapus</button>
                              </td>
                            </tr>
                          );
                        }

                        if (dbTab === "klien") {
                          const contacts = Array.isArray(item.contacts) ? (item.contacts as Record<string, unknown>[]) : [];
                          return (
                            <tr key={item.id} className="hover:bg-slate-50/80">
                              <td className="py-4 px-4 font-bold text-slate-900">{valueOf(item, "nama_klien")}</td>
                              <td className="py-4 px-4 text-slate-700">{valueOf(item, "nama_perusahaan")}</td>
                              <td className="py-4 px-4 text-slate-600 max-w-xs">{valueOf(item, "alamat")}</td>
                              <td className="py-4 px-4 text-slate-700">{String(contacts[0]?.nama || "-")}</td>
                              <td className="py-4 px-4 text-slate-700">{String(contacts[0]?.telp || "-")}</td>
                              <td className="py-4 px-4 text-slate-700">{valueOf(item, "npwp") || "-"}</td>
                              <td className="py-4 px-4 text-right">
                                <div className="flex justify-end gap-1.5">
                                  <button onClick={() => openEdit(item)} className="rounded-full bg-blue-600/10 px-3 py-1 text-[10px] font-bold text-blue-700 hover:bg-blue-600/20">Edit</button>
                                  <button onClick={() => void deleteRecord(item)} disabled={busy} className="rounded-full bg-red-600/10 px-3 py-1 text-[10px] font-bold text-red-700 hover:bg-red-600/20 disabled:opacity-50">Hapus</button>
                                </div>
                              </td>
                            </tr>
                          );
                        }

                        return null;
                      })
                    )}
                  </tbody>
                </table>
              </div>
            </section>
          )}

          {activeMenu === "USER_MANAGEMENT" && (
            <section className="space-y-5">
              <div><h2 className="text-xl font-bold text-slate-900">User Management</h2><p className="mt-1 text-sm text-slate-500">Kelola akun login. Tambahkan akun role Teknisi supaya bisa dipilih sebagai teknisi bertugas di Surat Jalan.</p></div>
              {notice && <div className="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</div>}
              {error && <div className="rounded-lg bg-red-50 p-3 text-sm text-red-800">{error}</div>}
              <form onSubmit={saveUser} className="grid gap-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-4">
                <label className="space-y-1 text-sm font-medium text-slate-700">Nama<input required value={userForm.name} onChange={(e) => setUserForm({ ...userForm, name: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
                <label className="space-y-1 text-sm font-medium text-slate-700">Email<input required type="email" value={userForm.email} onChange={(e) => setUserForm({ ...userForm, email: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
                <label className="space-y-1 text-sm font-medium text-slate-700">Password<input required type="password" minLength={8} value={userForm.password} onChange={(e) => setUserForm({ ...userForm, password: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Minimal 8 karakter" /></label>
                <label className="space-y-1 text-sm font-medium text-slate-700">Role<select required value={userForm.role_id} onChange={(e) => setUserForm({ ...userForm, role_id: e.target.value })} className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="">Pilih role</option>{roles.map((role) => <option key={role.id} value={role.id}>{role.display_name}</option>)}</select></label>
                <button disabled={busy} className="rounded-lg bg-blue-600 px-5 py-2 font-semibold text-white md:col-span-4 md:justify-self-start">{busy ? "Menyimpan..." : "Tambah Pengguna"}</button>
              </form>
              <div className="overflow-auto rounded-xl bg-white shadow"><table className="w-full text-left text-sm"><thead className="bg-[#002480] text-white"><tr><th className="p-3">Nama</th><th className="p-3">Email</th><th className="p-3">Role</th><th className="p-3">Aksi</th></tr></thead><tbody className="divide-y">{users.map((user) => <tr key={user.id}><td className="p-3">{valueOf(user, "name")}</td><td className="p-3">{valueOf(user, "email")}</td><td className="p-3">{String((user.role as Record<string, unknown> | undefined)?.display_name || "-")}</td><td className="p-3"><button type="button" disabled={busy} onClick={() => void deleteUser(Number(user.id))} className="text-red-600 hover:underline">Hapus</button></td></tr>)}</tbody></table></div>
            </section>
          )}
          {activeMenu === "LAPORAN" && (
            <section className="space-y-5"><div><h2 className="text-xl font-bold text-slate-900">Laporan</h2><p className="mt-1 text-sm text-slate-500">Ringkasan operasional dan export laporan keuangan.</p></div>{error && <div className="rounded bg-red-50 p-3 text-sm text-red-800">{error}</div>}<div className="grid gap-4 md:grid-cols-3"><div className="rounded-xl bg-blue-50 p-5"><p>Jumlah Order</p><strong className="text-2xl">{reportSummary.orders || 0}</strong></div><div className="rounded-xl bg-emerald-50 p-5"><p>Total Pemasukan</p><strong className="text-2xl">Rp {formatIDR(reportSummary.pemasukan)}</strong></div><div className="rounded-xl bg-red-50 p-5"><p>Total Pengeluaran</p><strong className="text-2xl">Rp {formatIDR(reportSummary.pengeluaran)}</strong></div><div className="rounded-xl bg-slate-100 p-5"><p>Jumlah Invoice</p><strong className="text-2xl">{reportSummary.invoices || 0}</strong></div><div className="rounded-xl bg-slate-100 p-5"><p>Jumlah Barang</p><strong className="text-2xl">{reportSummary.barang || 0}</strong></div></div><button onClick={() => void downloadTransactionsCsv()} className="rounded bg-blue-600 px-5 py-2 font-semibold text-white">Download Laporan Keuangan CSV</button></section>
          )}
          {activeMenu === "KEUANGAN" && (
            <section className="space-y-5"><div><h2 className="text-xl font-bold text-slate-900">Keuangan</h2><p className="mt-1 text-sm text-slate-500">Pencatatan pemasukan dan pengeluaran operasional.</p></div>{error && <div className="rounded bg-red-50 p-3 text-sm text-red-800">{error}</div>}{notice && <div className="rounded bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</div>}<div className="grid gap-4 md:grid-cols-3"><div className="rounded-xl bg-emerald-50 p-5"><p className="text-sm text-slate-600">Pemasukan</p><p className="mt-2 text-2xl font-bold text-emerald-700">Rp {formatIDR(financeSummary.pemasukan)}</p></div><div className="rounded-xl bg-red-50 p-5"><p className="text-sm text-slate-600">Pengeluaran</p><p className="mt-2 text-2xl font-bold text-red-700">Rp {formatIDR(financeSummary.pengeluaran)}</p></div><div className="rounded-xl bg-blue-50 p-5"><p className="text-sm text-slate-600">Saldo</p><p className="mt-2 text-2xl font-bold text-blue-700">Rp {formatIDR(financeSummary.saldo)}</p></div></div><form onSubmit={saveFinance} className="grid gap-4 rounded-xl bg-white p-6 shadow-sm md:grid-cols-3"><label>Jenis<select value={financeForm.jenis} onChange={(e) => setFinanceForm({ ...financeForm, jenis: e.target.value })} className="mt-1 w-full rounded border p-2"><option>Pemasukan</option><option>Pengeluaran</option></select></label><label>Tanggal<input required type="date" value={financeForm.tanggal} onChange={(e) => setFinanceForm({ ...financeForm, tanggal: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Invoice<select value={financeForm.invoice_id} onChange={(e) => setFinanceForm({ ...financeForm, invoice_id: e.target.value })} className="mt-1 w-full rounded border p-2"><option value="">Tidak terkait invoice</option>{invoices.map((invoice) => <option key={invoice.id} value={invoice.id}>{valueOf(invoice, "nomor_invoice")}</option>)}</select></label><label>Kategori<input required value={financeForm.kategori} onChange={(e) => setFinanceForm({ ...financeForm, kategori: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Jumlah<input required type="number" min="0" value={financeForm.jumlah} onChange={(e) => setFinanceForm({ ...financeForm, jumlah: Number(e.target.value) })} className="mt-1 w-full rounded border p-2" /></label><label>Metode<input value={financeForm.metode} onChange={(e) => setFinanceForm({ ...financeForm, metode: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Referensi<input value={financeForm.referensi} onChange={(e) => setFinanceForm({ ...financeForm, referensi: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label className="md:col-span-2">Keterangan<textarea value={financeForm.keterangan} onChange={(e) => setFinanceForm({ ...financeForm, keterangan: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><button disabled={busy} className="self-end rounded bg-blue-600 px-5 py-2 font-semibold text-white">{busy ? "Menyimpan..." : "Simpan Transaksi"}</button></form><div className="overflow-auto rounded-xl bg-white shadow"><table className="w-full text-left text-sm"><thead className="bg-[#002480] text-white"><tr><th className="p-3">Tanggal</th><th className="p-3">Jenis</th><th className="p-3">Kategori</th><th className="p-3">Jumlah</th><th className="p-3">Metode</th></tr></thead><tbody className="divide-y">{transactions.map((transaction) => <tr key={transaction.id}><td className="p-3">{valueOf(transaction, "tanggal")}</td><td className="p-3">{valueOf(transaction, "jenis")}</td><td className="p-3">{valueOf(transaction, "kategori")}</td><td className="p-3">Rp {formatIDR(transaction.jumlah)}</td><td className="p-3">{valueOf(transaction, "metode")}</td></tr>)}</tbody></table></div></section>
          )}
          {activeMenu === "INVOICE" && (
            <section className="space-y-5"><div><h2 className="text-xl font-bold text-slate-900">Invoice</h2><p className="mt-1 text-sm text-slate-500">Catat tagihan dan pembayaran berdasarkan Form Order.</p></div>{notice && <div className="rounded bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</div>}{error && <div className="rounded bg-red-50 p-3 text-sm text-red-800">{error}</div>}<form onSubmit={saveInvoice} className="space-y-5 rounded-xl bg-white p-6 shadow-sm"><div className="grid gap-4 md:grid-cols-3"><label>Nomor Invoice<input required value={invoiceForm.nomor_invoice} onChange={(e) => setInvoiceForm({ ...invoiceForm, nomor_invoice: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Form Order<select required value={invoiceForm.form_order_id} onChange={(e) => setInvoiceForm({ ...invoiceForm, form_order_id: e.target.value })} className="mt-1 w-full rounded border p-2"><option value="">Pilih order</option>{invoiceOrders.map((order) => <option key={order.id} value={order.id}>{valueOf(order, "nomor_order")} — {valueOf(order, "nama_event")}</option>)}</select></label><label>Tanggal Invoice<input required type="date" value={invoiceForm.tanggal_invoice} onChange={(e) => setInvoiceForm({ ...invoiceForm, tanggal_invoice: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Jatuh Tempo<input type="date" value={invoiceForm.jatuh_tempo} onChange={(e) => setInvoiceForm({ ...invoiceForm, jatuh_tempo: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Subtotal<input required type="number" min="0" value={invoiceForm.subtotal} onChange={(e) => setInvoiceForm({ ...invoiceForm, subtotal: Number(e.target.value) })} className="mt-1 w-full rounded border p-2" /></label><label>Diskon<input required type="number" min="0" value={invoiceForm.diskon} onChange={(e) => setInvoiceForm({ ...invoiceForm, diskon: Number(e.target.value) })} className="mt-1 w-full rounded border p-2" /></label><label>Pajak<input required type="number" min="0" value={invoiceForm.pajak} onChange={(e) => setInvoiceForm({ ...invoiceForm, pajak: Number(e.target.value) })} className="mt-1 w-full rounded border p-2" /></label><label>Dibayar<input required type="number" min="0" value={invoiceForm.dibayar} onChange={(e) => setInvoiceForm({ ...invoiceForm, dibayar: Number(e.target.value) })} className="mt-1 w-full rounded border p-2" /></label><label>Status<select value={invoiceForm.status} onChange={(e) => setInvoiceForm({ ...invoiceForm, status: e.target.value })} className="mt-1 w-full rounded border p-2"><option>Unpaid</option><option>DP</option><option>Paid</option><option>Overdue</option></select></label></div><textarea placeholder="Catatan" value={invoiceForm.catatan} onChange={(e) => setInvoiceForm({ ...invoiceForm, catatan: e.target.value })} className="w-full rounded border p-2" /><button disabled={busy} className="rounded bg-blue-600 px-5 py-2 font-semibold text-white">{busy ? "Menyimpan..." : "Simpan Invoice"}</button></form><div className="overflow-auto rounded-xl bg-white shadow"><table className="w-full text-left text-sm"><thead className="bg-[#002480] text-white"><tr><th className="p-3">Nomor</th><th className="p-3">Order</th><th className="p-3">Total</th><th className="p-3">Dibayar</th><th className="p-3">Status</th></tr></thead><tbody className="divide-y">{invoices.map((invoice) => <tr key={invoice.id}><td className="p-3">{valueOf(invoice, "nomor_invoice")}</td><td className="p-3">{String((invoice.order as Record<string, unknown> | undefined)?.nomor_order || "-")}</td><td className="p-3">{formatIDR(invoice.total)}</td><td className="p-3">{formatIDR(invoice.dibayar)}</td><td className="p-3">{valueOf(invoice, "status")}</td></tr>)}</tbody></table></div></section>
          )}
          {activeMenu === "PENJADWALAN" && (
            <section className="space-y-5">
              <div><h2 className="text-xl font-bold text-slate-900">Penjadwalan Operasional</h2><p className="mt-1 text-sm text-slate-500">Atur crew dan armada berdasarkan jadwal event.</p></div>
              {notice && <div className="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</div>}
              {error && <div className="rounded-lg bg-red-50 p-3 text-sm text-red-800">{error}</div>}
              <form onSubmit={saveSchedule} className="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="grid gap-4 md:grid-cols-3">
                  <label className="text-sm font-medium text-slate-700">Form Order<select required value={scheduleForm.form_order_id} onChange={(e) => setScheduleForm({ ...scheduleForm, form_order_id: e.target.value })} className="mt-1 w-full rounded border p-2"><option value="">Pilih order</option>{scheduleOrders.map((order) => <option key={order.id} value={order.id}>{valueOf(order, "nomor_order")} — {valueOf(order, "nama_event")}</option>)}</select></label>
                  <label className="text-sm font-medium text-slate-700">Crew<select value={scheduleForm.karyawan_id} onChange={(e) => setScheduleForm({ ...scheduleForm, karyawan_id: e.target.value })} className="mt-1 w-full rounded border p-2"><option value="">Tidak ditugaskan</option>{scheduleCrew.map((crew) => <option key={crew.id} value={crew.id}>{valueOf(crew, "nama_lengkap")} — {valueOf(crew, "jabatan")}</option>)}</select></label>
                  <label className="text-sm font-medium text-slate-700">Armada<select value={scheduleForm.armada_id} onChange={(e) => setScheduleForm({ ...scheduleForm, armada_id: e.target.value })} className="mt-1 w-full rounded border p-2"><option value="">Tidak ditugaskan</option>{scheduleVehicles.map((vehicle) => <option key={vehicle.id} value={vehicle.id}>{valueOf(vehicle, "plat_nomor")} — {valueOf(vehicle, "jenis_kendaraan")}</option>)}</select></label>
                  <label className="text-sm font-medium text-slate-700">Mulai<input required type="datetime-local" value={scheduleForm.tanggal_mulai} onChange={(e) => setScheduleForm({ ...scheduleForm, tanggal_mulai: e.target.value })} className="mt-1 w-full rounded border p-2" /></label>
                  <label className="text-sm font-medium text-slate-700">Selesai<input required type="datetime-local" value={scheduleForm.tanggal_selesai} onChange={(e) => setScheduleForm({ ...scheduleForm, tanggal_selesai: e.target.value })} className="mt-1 w-full rounded border p-2" /></label>
                  <label className="text-sm font-medium text-slate-700">Status<select value={scheduleForm.status} onChange={(e) => setScheduleForm({ ...scheduleForm, status: e.target.value })} className="mt-1 w-full rounded border p-2"><option>Terjadwal</option><option>Berjalan</option><option>Selesai</option><option>Dibatalkan</option></select></label>
                  <label className="text-sm font-medium text-slate-700 md:col-span-2">Jobdesk<input value={scheduleForm.jobdesk} onChange={(e) => setScheduleForm({ ...scheduleForm, jobdesk: e.target.value })} className="mt-1 w-full rounded border p-2" placeholder="Contoh: Soundman / Driver / Kameramen" /></label>
                  <label className="text-sm font-medium text-slate-700">Catatan<textarea value={scheduleForm.catatan} onChange={(e) => setScheduleForm({ ...scheduleForm, catatan: e.target.value })} className="mt-1 w-full rounded border p-2" /></label>
                </div>
                <button disabled={busy} className="rounded bg-blue-600 px-5 py-2 font-semibold text-white disabled:opacity-60">{busy ? "Menyimpan..." : "Simpan Jadwal"}</button>
              </form>
              <div className="overflow-auto rounded-xl border border-slate-200 bg-white shadow-sm"><table className="w-full text-left text-sm"><thead className="bg-[#002480] text-white"><tr><th className="p-3">Event</th><th className="p-3">Crew</th><th className="p-3">Armada</th><th className="p-3">Mulai</th><th className="p-3">Selesai</th><th className="p-3">Status</th></tr></thead><tbody className="divide-y divide-slate-100">{loading ? <tr><td colSpan={6} className="p-6 text-center">Memuat jadwal...</td></tr> : schedules.length === 0 ? <tr><td colSpan={6} className="p-6 text-center text-slate-500">Belum ada jadwal.</td></tr> : schedules.map((schedule) => <tr key={schedule.id}><td className="p-3">{String((schedule.order as Record<string, unknown> | undefined)?.nama_event || "-")}</td><td className="p-3">{String((schedule.karyawan as Record<string, unknown> | undefined)?.nama_lengkap || "-")}</td><td className="p-3">{String((schedule.armada as Record<string, unknown> | undefined)?.plat_nomor || "-")}</td><td className="p-3">{valueOf(schedule, "tanggal_mulai")}</td><td className="p-3">{valueOf(schedule, "tanggal_selesai")}</td><td className="p-3">{valueOf(schedule, "status")}</td></tr>)}</tbody></table></div>
            </section>
          )}
          {activeMenu === "SURAT JALAN" && (
            <section className="space-y-5">
              <div><h2 className="text-xl font-bold text-slate-900">Surat Jalan</h2><p className="mt-1 text-sm text-slate-500">Terbitkan surat jalan dan pantau check-out/check-in barang.</p></div>
              {notice && <div className="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</div>}{error && <div className="rounded-lg bg-red-50 p-3 text-sm text-red-800">{error}</div>}
              <form onSubmit={saveDelivery} className="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"><div className="grid gap-4 md:grid-cols-3"><label>Nomor Surat Jalan<input required value={deliveryForm.nomor_surat_jalan} onChange={(e) => setDeliveryForm({ ...deliveryForm, nomor_surat_jalan: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Form Order<select required value={deliveryForm.form_order_id} onChange={(e) => { const selected = deliveryOrders.find((order) => String(order.id) === e.target.value); const orderItems = Array.isArray(selected?.items) ? (selected.items as Array<Record<string, unknown>>) : []; setDeliveryForm({ ...deliveryForm, form_order_id: e.target.value }); setDeliveryItems(orderItems.map((item) => ({ barang_id: item.barang_id ? String(item.barang_id) : "", nama_item: String(item.nama_item || ""), qty: Number(item.qty || 1), status_check: String(item.status_check || "Belum Check-out") }))); }} className="mt-1 w-full rounded border p-2"><option value="">Pilih order</option>{deliveryOrders.map((order) => <option key={order.id} value={order.id}>{valueOf(order, "nomor_order")} — {valueOf(order, "nama_event")}</option>)}</select></label><label>Tanggal Berangkat<input required type="datetime-local" value={deliveryForm.tanggal_berangkat} onChange={(e) => setDeliveryForm({ ...deliveryForm, tanggal_berangkat: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Status<select value={deliveryForm.status} onChange={(e) => setDeliveryForm({ ...deliveryForm, status: e.target.value })} className="mt-1 w-full rounded border p-2"><option>Draft</option><option>Berangkat</option><option>Selesai</option><option>Dibatalkan</option></select></label></div><div className="space-y-3"><h3 className="font-semibold">Daftar Barang <span className="text-xs font-normal text-slate-500">— otomatis dari Form Order</span></h3>{deliveryItems.length === 0 && <p className="text-sm text-slate-500">Pilih Form Order dulu — daftar barang akan terisi otomatis.</p>}{deliveryItems.map((item, index) => <div key={index} className="grid gap-3 md:grid-cols-12"><div className="rounded bg-slate-50 px-3 py-2 md:col-span-7">{item.nama_item}</div><label className="flex items-center gap-2 text-sm md:col-span-3">Qty<input required type="number" min="1" value={item.qty} onChange={(e) => setDeliveryItems(deliveryItems.map((row, i) => i === index ? { ...row, qty: Number(e.target.value) } : row))} className="w-20 rounded border p-2" /></label><button type="button" onClick={() => setDeliveryItems(deliveryItems.filter((_, i) => i !== index))} className="text-red-600 md:col-span-2">Hapus</button></div>)}</div><div className="space-y-2"><h3 className="font-semibold">Teknisi Bertugas <span className="text-xs font-normal text-slate-500">— bisa pilih lebih dari satu</span></h3><p className="text-xs text-slate-500">Kosongkan bila belum ditentukan — semua teknisi tetap boleh checkout.{deliveryTeknisiIds.length > 0 && ` Dipilih: ${deliveryTeknisiIds.length} teknisi.`}</p><p className="text-xs text-slate-400">Akun login teknisi diisi di Database → Karyawan (Email Login + Password), supaya bisa scan surat jalan.</p><input type="text" value={deliveryTeknisiSearch} onChange={(e) => setDeliveryTeknisiSearch(e.target.value)} placeholder="Cari nama teknisi..." className="w-full rounded border border-slate-300 px-3 py-2 text-sm md:max-w-xs" /><div className="grid gap-2 md:grid-cols-3">{deliveryTeknisiOptions.length === 0 ? <p className="text-sm text-slate-500">Belum ada karyawan dengan jabatan Teknisi. Tambahkan di menu Database → Karyawan.</p> : deliveryTeknisiOptions.filter((teknisi) => teknisi.name.toLowerCase().includes(deliveryTeknisiSearch.toLowerCase())).length === 0 ? <p className="text-sm text-slate-500">Tidak ada teknisi yang cocok dengan pencarian.</p> : deliveryTeknisiOptions.filter((teknisi) => teknisi.name.toLowerCase().includes(deliveryTeknisiSearch.toLowerCase())).map((teknisi) => <label key={teknisi.id} className="flex items-center gap-2 rounded border p-2 text-sm"><input type="checkbox" checked={deliveryTeknisiIds.includes(teknisi.id)} onChange={(e) => setDeliveryTeknisiIds(e.target.checked ? [...deliveryTeknisiIds, teknisi.id] : deliveryTeknisiIds.filter((id) => id !== teknisi.id))} />{teknisi.name}</label>)}</div></div><textarea placeholder="Catatan" value={deliveryForm.catatan} onChange={(e) => setDeliveryForm({ ...deliveryForm, catatan: e.target.value })} className="w-full rounded border p-2" /><button disabled={busy} className="rounded bg-blue-600 px-5 py-2 font-semibold text-white">{busy ? "Menyimpan..." : "Simpan Surat Jalan"}</button></form>
              <div className="space-y-3"><input type="text" value={deliverySearch} onChange={(e) => setDeliverySearch(e.target.value)} placeholder="Cari nomor surat jalan, order, atau teknisi..." className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm md:max-w-md" /><div className="overflow-auto rounded-xl bg-white shadow"><table className="w-full text-left text-sm"><thead className="bg-[#002480] text-white"><tr><th className="p-3">Nomor</th><th className="p-3">Order</th><th className="p-3">Tanggal</th><th className="p-3">Teknisi</th><th className="p-3">Item</th><th className="p-3">Status</th></tr></thead><tbody className="divide-y">{deliveryNotes.filter((note) => { const teknisiNames = Array.isArray(note.teknisi) ? (note.teknisi as Array<Record<string, unknown>>).map((teknisi) => String(teknisi.name)).join(" ") : ""; const haystack = [valueOf(note, "nomor_surat_jalan"), String((note.order as Record<string, unknown> | undefined)?.nomor_order || ""), valueOf(note, "status"), teknisiNames].join(" ").toLowerCase(); return haystack.includes(deliverySearch.toLowerCase()); }).map((note) => <tr key={note.id}><td className="p-3">{valueOf(note, "nomor_surat_jalan")}</td><td className="p-3">{String((note.order as Record<string, unknown> | undefined)?.nomor_order || "-")}</td><td className="p-3">{valueOf(note, "tanggal_berangkat")}</td><td className="p-3">{Array.isArray(note.teknisi) && note.teknisi.length > 0 ? (note.teknisi as Array<Record<string, unknown>>).map((teknisi) => String(teknisi.name)).join(", ") : <span className="text-slate-400">Semua teknisi</span>}</td><td className="p-3">{Array.isArray(note.items) ? note.items.length : 0} barang</td><td className="p-3">{valueOf(note, "status")}</td></tr>)}</tbody></table></div></div>
            </section>
          )}
          {activeMenu === "FORM PENAWARAN" && (
            <section className="space-y-5">
              <div><h2 className="text-xl font-bold text-slate-900">Form Penawaran</h2><p className="mt-1 text-sm text-slate-500">Buat quotation berdasarkan Form Order.</p></div>
              {notice && <div className="rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{notice}</div>}{error && <div className="rounded-lg bg-red-50 p-3 text-sm text-red-800">{error}</div>}
              <form onSubmit={saveOffer} className="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="grid gap-4 md:grid-cols-3"><label>Nomor Penawaran<input required value={offerForm.nomor_penawaran} onChange={(e) => setOfferForm({ ...offerForm, nomor_penawaran: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Form Order<select required value={offerForm.form_order_id} onChange={(e) => setOfferForm({ ...offerForm, form_order_id: e.target.value })} className="mt-1 w-full rounded border p-2"><option value="">Pilih order</option>{offerOrders.map((order) => <option key={order.id} value={order.id}>{valueOf(order, "nomor_order")} — {valueOf(order, "nama_event")}</option>)}</select></label><label>Tanggal<input required type="date" value={offerForm.tanggal_penawaran} onChange={(e) => setOfferForm({ ...offerForm, tanggal_penawaran: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Berlaku Sampai<input type="date" value={offerForm.berlaku_sampai} onChange={(e) => setOfferForm({ ...offerForm, berlaku_sampai: e.target.value })} className="mt-1 w-full rounded border p-2" /></label><label>Status<select value={offerForm.status} onChange={(e) => setOfferForm({ ...offerForm, status: e.target.value })} className="mt-1 w-full rounded border p-2"><option>Draft</option><option>Dikirim</option><option>Disetujui</option><option>Ditolak</option></select></label></div>
                <div className="space-y-3"><div className="flex justify-between"><h3 className="font-semibold">Item Penawaran</h3><button type="button" onClick={() => setOfferItems([...offerItems, { nama_item: "", qty: 1, harga: 0 }])} className="rounded border px-3 py-2 text-sm text-blue-700">+ Tambah item</button></div>{offerItems.map((item, index) => <div key={index} className="grid gap-3 md:grid-cols-12"><input required placeholder="Nama item" value={item.nama_item} onChange={(e) => setOfferItems(offerItems.map((row, i) => i === index ? { ...row, nama_item: e.target.value } : row))} className="rounded border p-2 md:col-span-7" /><input required type="number" min="1" value={item.qty} onChange={(e) => setOfferItems(offerItems.map((row, i) => i === index ? { ...row, qty: Number(e.target.value) } : row))} className="rounded border p-2 md:col-span-2" /><input required type="number" min="0" value={item.harga} onChange={(e) => setOfferItems(offerItems.map((row, i) => i === index ? { ...row, harga: Number(e.target.value) } : row))} className="rounded border p-2 md:col-span-2" /><button type="button" disabled={offerItems.length === 1} onClick={() => setOfferItems(offerItems.filter((_, i) => i !== index))} className="text-red-600">Hapus</button></div>)}</div>
                <div className="grid gap-4 md:grid-cols-3"><label>Diskon<input type="number" min="0" value={offerForm.diskon} onChange={(e) => setOfferForm({ ...offerForm, diskon: Number(e.target.value) })} className="mt-1 w-full rounded border p-2" /></label><label>Pajak<input type="number" min="0" value={offerForm.pajak} onChange={(e) => setOfferForm({ ...offerForm, pajak: Number(e.target.value) })} className="mt-1 w-full rounded border p-2" /></label><label>Catatan<textarea value={offerForm.catatan} onChange={(e) => setOfferForm({ ...offerForm, catatan: e.target.value })} className="mt-1 w-full rounded border p-2" /></label></div><button disabled={busy} className="rounded bg-blue-600 px-5 py-2 font-semibold text-white">{busy ? "Menyimpan..." : "Simpan Penawaran"}</button>
              </form>
              <div className="overflow-auto rounded-xl bg-white shadow"><table className="w-full text-left text-sm"><thead className="bg-[#002480] text-white"><tr><th className="p-3">Nomor</th><th className="p-3">Order</th><th className="p-3">Tanggal</th><th className="p-3">Total</th><th className="p-3">Status</th></tr></thead><tbody className="divide-y">{offers.map((offer) => <tr key={offer.id}><td className="p-3">{valueOf(offer, "nomor_penawaran")}</td><td className="p-3">{String((offer.order as Record<string, unknown> | undefined)?.nomor_order || "-")}</td><td className="p-3">{valueOf(offer, "tanggal_penawaran")}</td><td className="p-3">{formatIDR(offer.total)}</td><td className="p-3">{valueOf(offer, "status")}</td></tr>)}</tbody></table></div>
            </section>
          )}
          {activeMenu === "FORM ORDER" && (
            <section className="space-y-5">
              <div>
                <h2 className="text-xl font-bold text-slate-900">Form Order</h2>
                <p className="mt-1 text-sm text-slate-500">Catat pemesanan sewa, jadwal event, lokasi, dan barang yang dibutuhkan.</p>
              </div>
              {notice && <div className="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{notice}</div>}
              {error && <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{error}</div>}
              <form onSubmit={saveOrder} className="space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                  <label className="space-y-1 text-sm font-medium text-slate-700">Nomor Order<input required value={orderForm.nomor_order} onChange={(e) => setOrderForm({ ...orderForm, nomor_order: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
                  <label className="space-y-1 text-sm font-medium text-slate-700">Klien<select required value={orderForm.klien_id} onChange={(e) => setOrderForm({ ...orderForm, klien_id: e.target.value })} className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option value="">Pilih klien</option>{orderClients.map((client) => <option key={client.id} value={client.id}>{valueOf(client, "nama_klien")} {valueOf(client, "nama_perusahaan") && `— ${valueOf(client, "nama_perusahaan")}`}</option>)}</select></label>
                  <label className="space-y-1 text-sm font-medium text-slate-700">Nama Event<input required value={orderForm.nama_event} onChange={(e) => setOrderForm({ ...orderForm, nama_event: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Nama acara" /></label>
                  <label className="space-y-1 text-sm font-medium text-slate-700">Venue<input required value={orderForm.venue} onChange={(e) => setOrderForm({ ...orderForm, venue: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" placeholder="Nama venue dan alamat" /></label>
                  <label className="space-y-1 text-sm font-medium text-slate-700">Ruangan<input value={orderForm.ruangan} onChange={(e) => setOrderForm({ ...orderForm, ruangan: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
                  <label className="space-y-1 text-sm font-medium text-slate-700">Lantai<input value={orderForm.lantai} onChange={(e) => setOrderForm({ ...orderForm, lantai: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
                  <label className="space-y-1 text-sm font-medium text-slate-700">Tanggal & waktu load-in<input required type="datetime-local" value={orderForm.load_in} onChange={(e) => setOrderForm({ ...orderForm, load_in: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
                  <label className="space-y-1 text-sm font-medium text-slate-700">Tanggal & waktu load-out<input required type="datetime-local" value={orderForm.load_out} onChange={(e) => setOrderForm({ ...orderForm, load_out: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
                  <label className="space-y-1 text-sm font-medium text-slate-700">Status<select value={orderForm.status} onChange={(e) => setOrderForm({ ...orderForm, status: e.target.value })} className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2"><option>Draft</option><option>Confirmed</option><option>Cancelled</option></select></label>
                </div>
                <div className="space-y-3">
                  <div className="flex items-center justify-between"><h3 className="font-semibold text-slate-900">Daftar barang sewa</h3><button type="button" onClick={() => setOrderItems([...orderItems, { barang_id: "", nama_item: "", qty: 1, harga_harian: 0 }])} className="rounded-lg border border-blue-200 px-3 py-2 text-sm font-semibold text-blue-700 hover:bg-blue-50">+ Tambah barang</button></div>
                  {orderItems.map((item, index) => <div key={index} className="grid gap-3 rounded-lg border border-slate-200 p-3 md:grid-cols-12">
                    <label className="space-y-1 text-xs font-medium text-slate-600 md:col-span-5">Barang<select required value={item.barang_id} onChange={(e) => { const selected = orderBarang.find((barang) => String(barang.id) === e.target.value); setOrderItems(orderItems.map((row, i) => i === index ? { ...row, barang_id: e.target.value, nama_item: selected ? valueOf(selected, "nama_barang") : "", harga_harian: selected ? Number(selected.harga_rental || 0) : 0 } : row)); }} className="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm"><option value="">Pilih barang</option>{orderBarang.map((barang) => <option key={barang.id} value={barang.id}>{valueOf(barang, "nama_barang")} ({valueOf(barang, "kode_barang")})</option>)}</select></label>
                    <label className="space-y-1 text-xs font-medium text-slate-600 md:col-span-2">Jumlah<input required type="number" min="1" value={item.qty} onChange={(e) => setOrderItems(orderItems.map((row, i) => i === index ? { ...row, qty: Number(e.target.value) } : row))} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" /></label>
                    <label className="space-y-1 text-xs font-medium text-slate-600 md:col-span-4">Harga sewa / hari<input required type="number" min="0" value={item.harga_harian} onChange={(e) => setOrderItems(orderItems.map((row, i) => i === index ? { ...row, harga_harian: Number(e.target.value) } : row))} className="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" /></label>
                    <button type="button" aria-label="Hapus barang" disabled={orderItems.length === 1} onClick={() => setOrderItems(orderItems.filter((_, i) => i !== index))} className="self-end rounded-lg px-3 py-2 text-sm text-red-600 hover:bg-red-50 disabled:opacity-30 md:col-span-1">Hapus</button>
                  </div>)}
                </div>
                <label className="block space-y-1 text-sm font-medium text-slate-700">Catatan<textarea rows={3} value={orderForm.catatan} onChange={(e) => setOrderForm({ ...orderForm, catatan: e.target.value })} className="w-full rounded-lg border border-slate-300 px-3 py-2" /></label>
                <button type="submit" disabled={busy} className="rounded-lg bg-[#2563EB] px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">{busy ? "Menyimpan..." : "Simpan Form Order"}</button>
              </form>
              <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm"><table className="w-full text-left text-sm"><thead className="bg-[#002480] text-white"><tr><th className="p-3">Nomor Order</th><th className="p-3">Event</th><th className="p-3">Klien</th><th className="p-3">Venue</th><th className="p-3">Load-in</th><th className="p-3">Load-out</th><th className="p-3">Status</th></tr></thead><tbody className="divide-y divide-slate-100">{loading ? <tr><td colSpan={7} className="p-6 text-center text-slate-500">Memuat order...</td></tr> : orders.length === 0 ? <tr><td colSpan={7} className="p-6 text-center text-slate-500">Belum ada order.</td></tr> : orders.map((order) => <tr key={order.id}><td className="p-3 font-medium">{valueOf(order, "nomor_order")}</td><td className="p-3">{valueOf(order, "nama_event")}</td><td className="p-3">{String((order.klien as Record<string, unknown> | undefined)?.nama_klien || "-")}</td><td className="p-3">{valueOf(order, "venue")}</td><td className="p-3">{valueOf(order, "load_in")}</td><td className="p-3">{valueOf(order, "load_out")}</td><td className="p-3">{valueOf(order, "status")}</td></tr>)}</tbody></table></div>
            </section>
          )}
          {activeMenu !== "DATABASE" && activeMenu !== "FORM ORDER" && (
            <div className="flex h-96 flex-col items-center justify-center rounded-xl bg-white p-8 text-center shadow-sm">
              <h3 className="text-lg font-bold text-slate-800">Modul {activeMenu}</h3>
              <p className="mt-1 text-sm text-slate-500">Modul ini belum tersedia.</p>
            </div>
          )}
        </main>
      </div>

      {modalOpen && (
        <div className="app-modal-backdrop fixed inset-0 z-50 flex items-center justify-center p-4">
          <div className="app-modal relative w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl">
            <div className="app-modal-header flex items-center justify-between px-6 py-5">
              <h3 className="text-base font-bold text-slate-900">
                {editingItem ? "Edit Data" : "Tambahkan"}{" "}
                {dbTab === "barang"
                  ? barangSubTab === "satuan"
                    ? "Barang"
                    : "Paket"
                  : dbTab === "karyawan"
                  ? "Data Karyawan"
                  : "Data Klien"}
              </h3>
              <button
                onClick={() => setModalOpen(false)}
                aria-label="Tutup form"
                className="rounded-lg p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-700"
              >
                <X className="h-4 w-4" />
              </button>
            </div>

            {/* Subtab SATUAN / PAKET switch di header modal jika form barang (Sesuai Page 8 & 10) */}
            {dbTab === "barang" && (
              <div className="app-modal-tabs flex border-y border-slate-100 px-5 text-xs font-bold">
                <button
                  type="button"
                  onClick={() => setBarangSubTab("satuan")}
                  className={`flex-1 py-2.5 transition ${
                    barangSubTab === "satuan" ? "app-modal-tab-active" : ""
                  }`}
                >
                  SATUAN
                </button>
                <button
                  type="button"
                  onClick={() => setBarangSubTab("paket")}
                  className={`flex-1 py-2.5 transition ${
                    barangSubTab === "paket" ? "app-modal-tab-active" : ""
                  }`}
                >
                  PAKET
                </button>
              </div>
            )}

            {/* Modal Form Body */}
            <form onSubmit={saveRecord} className="app-modal-form space-y-3 p-6 text-xs">
              {error && (
                <div className="rounded bg-red-100 p-2 text-red-700 text-center font-medium">
                  {error}
                </div>
              )}

              {/* FORM SATUAN (Page 8 & 9) */}
              {dbTab === "barang" && barangSubTab === "satuan" && (
                <>
                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Code</span>
                    <span className="text-slate-500">:</span>
                    <input required type="text" value={String(form.kode_barang || "")} onChange={(e) => setForm({ ...form, kode_barang: e.target.value.toUpperCase() })} placeholder="Contoh: CAM-001" className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600" />
                  </div>
                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Kategori</span>
                    <span className="text-slate-500">:</span>
                    <select
                      value={String(form.kategori || "Camera")}
                      onChange={(e) => {
                        const kategori = e.target.value;
                        setForm({ ...form, kategori, satuan: CATEGORY_DEFAULT_SATUAN[kategori] || "unit" });
                      }}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    >
                      {CATEGORY_LIST.map((c) => (
                        <option key={c} value={c}>
                          {c}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Satuan</span>
                    <span className="text-slate-500">:</span>
                    <select
                      value={String(form.satuan || "unit")}
                      onChange={(e) => setForm({ ...form, satuan: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    >
                      {SATUAN_OPTIONS.map((s) => (
                        <option key={s} value={s}>
                          {s}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Nama Barang</span>
                    <span className="text-slate-500">:</span>
                    <input
                      required
                      type="text"
                      value={String(form.nama_barang || "")}
                      onChange={(e) => setForm({ ...form, nama_barang: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-start gap-2">
                    <span className="w-24 pt-1.5 text-slate-700">Deskripsi</span>
                    <span className="pt-1.5 text-slate-500">:</span>
                    <textarea
                      rows={3}
                      value={String(form.deskripsi || "")}
                      onChange={(e) => setForm({ ...form, deskripsi: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Jumlah ({String(form.satuan || "unit")})</span>
                    <span className="text-slate-500">:</span>
                    <input
                      required
                      type="number"
                      min={0}
                      value={Number(form.jumlah_total || 0)}
                      onChange={(e) => setForm({ ...form, jumlah_total: Number(e.target.value) })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Harga / {String(form.satuan || "unit")}</span>
                    <span className="text-slate-500">:</span>
                    <input
                      required
                      type="number"
                      min={0}
                      value={Number(form.harga_rental || 0)}
                      onChange={(e) => setForm({ ...form, harga_rental: Number(e.target.value) })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  {/* Upload Foto Dropzone Sesuai Gambar 8 */}
                  <div className="pt-2">
                    <label className="flex h-32 w-full cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-100 hover:bg-slate-200/60">
                      <Camera className="h-10 w-10 text-slate-800" strokeWidth={1.5} />
                      <span className="mt-2 text-xs font-medium text-slate-600">Upload Foto Di sini</span>
                      <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" className="mt-2 block w-full cursor-pointer text-xs text-slate-600 file:mr-3 file:rounded-md file:border-0 file:bg-blue-600 file:px-3 file:py-2 file:font-semibold file:text-white hover:file:bg-blue-700" />
                    </label>
                  </div>
                </>
              )}

              {/* FORM PAKET (Page 10 & 11) */}
              {dbTab === "barang" && barangSubTab === "paket" && (
                <>
                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Kategori</span>
                    <span className="text-slate-500">:</span>
                    <select
                      value={String(form.kategori || "Streaming & Hybrid")}
                      onChange={(e) => setForm({ ...form, kategori: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    >
                      {CATEGORY_LIST.map((c) => (
                        <option key={c} value={c}>
                          {c}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Nama Paket</span>
                    <span className="text-slate-500">:</span>
                    <input
                      required
                      type="text"
                      value={String(form.nama_paket || "")}
                      onChange={(e) => setForm({ ...form, nama_paket: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Sub Kategori</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="text"
                      value={String(form.sub_kategori || "")}
                      onChange={(e) => setForm({ ...form, sub_kategori: e.target.value })}
                      placeholder="Contoh: Videotron Indoor"
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  {/* Dynamic Multi-item Row List Barang (Sesuai Page 10) */}
                  <div className="flex items-start gap-2 pt-1">
                    <span className="w-24 pt-1.5 text-slate-700">List Barang</span>
                    <span className="pt-1.5 text-slate-500">:</span>
                    <div className="flex-1 space-y-2">
                      {packageItems.map((row, idx) => (
                        <div key={idx} className="space-y-1.5 rounded-lg border border-slate-200 bg-slate-50 p-2">
                          <div className="flex items-center gap-1.5">
                            <select
                              value={row.barang_id}
                              onChange={(e) => {
                                const selected = barangsList.find((b) => String(b.id) === e.target.value);
                                const copy = [...packageItems];
                                copy[idx] = {
                                  ...copy[idx],
                                  barang_id: e.target.value,
                                  nama_item: selected ? valueOf(selected, "nama_barang") : copy[idx].nama_item,
                                  harga: selected ? Number(selected.harga_rental || 0) : copy[idx].harga,
                                };
                                setPackageItems(copy);
                              }}
                              className="flex-1 rounded border border-slate-200 bg-white px-2.5 py-1 text-[11px] outline-none focus:border-blue-600"
                            >
                              <option value="">-- Barang bebas / jasa (tanpa stok) --</option>
                              {barangsList.map((b) => (
                                <option key={b.id} value={String(b.id)}>
                                  {valueOf(b, "nama_barang")} ({valueOf(b, "kode_barang")})
                                </option>
                              ))}
                            </select>
                            <button
                              type="button"
                              onClick={() => {
                                if (idx === packageItems.length - 1) {
                                  setPackageItems([...packageItems, emptyPackageRow()]);
                                } else {
                                  setPackageItems(packageItems.filter((_, i) => i !== idx));
                                }
                              }}
                              className="flex h-6 w-6 items-center justify-center rounded bg-slate-200 font-bold text-slate-700 hover:bg-slate-300"
                            >
                              {idx === packageItems.length - 1 ? "+" : "-"}
                            </button>
                          </div>
                          <div className="grid grid-cols-[minmax(0,1fr)_70px_90px_110px] gap-1.5">
                            <input
                              type="text"
                              value={row.nama_item}
                              onChange={(e) => {
                                const copy = [...packageItems];
                                copy[idx] = { ...copy[idx], nama_item: e.target.value };
                                setPackageItems(copy);
                              }}
                              placeholder="Spesifikasi / nama item"
                              className="rounded border border-slate-200 bg-white px-2.5 py-1 text-[11px] outline-none focus:border-blue-600"
                            />
                            <input
                              type="number"
                              min={0.01}
                              step="0.01"
                              value={row.qty}
                              onChange={(e) => {
                                const copy = [...packageItems];
                                copy[idx] = { ...copy[idx], qty: Number(e.target.value) };
                                setPackageItems(copy);
                              }}
                              placeholder="Volume"
                              title="Volume"
                              className="rounded border border-slate-200 bg-white px-2.5 py-1 text-[11px] outline-none focus:border-blue-600"
                            />
                            <select
                              value={row.satuan}
                              onChange={(e) => {
                                const copy = [...packageItems];
                                copy[idx] = { ...copy[idx], satuan: e.target.value };
                                setPackageItems(copy);
                              }}
                              title="Satuan"
                              className="rounded border border-slate-200 bg-white px-2 py-1 text-[11px] outline-none focus:border-blue-600"
                            >
                              {SATUAN_OPTIONS.map((s) => (
                                <option key={s} value={s}>
                                  {s}
                                </option>
                              ))}
                            </select>
                            <input
                              type="number"
                              min={0}
                              value={row.harga}
                              onChange={(e) => {
                                const copy = [...packageItems];
                                copy[idx] = { ...copy[idx], harga: Number(e.target.value) };
                                setPackageItems(copy);
                              }}
                              placeholder="Harga"
                              title="Harga per satuan"
                              className="rounded border border-slate-200 bg-white px-2.5 py-1 text-[11px] outline-none focus:border-blue-600"
                            />
                          </div>
                        </div>
                      ))}
                      <p className="text-right text-[11px] font-semibold text-slate-600">
                        Total item: Rp {formatIDR(packageItems.reduce((sum, row) => sum + Number(row.qty || 0) * Number(row.harga || 0), 0))}
                      </p>
                    </div>
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Satuan Paket</span>
                    <span className="text-slate-500">:</span>
                    <select
                      value={String(form.satuan || "paket")}
                      onChange={(e) => setForm({ ...form, satuan: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    >
                      {SATUAN_OPTIONS.map((s) => (
                        <option key={s} value={s}>
                          {s}
                        </option>
                      ))}
                    </select>
                  </div>

                  <div className="flex items-center gap-2 pt-1">
                    <span className="w-24 text-slate-700">Harga</span>
                    <span className="text-slate-500">:</span>
                    <input
                      required
                      type="number"
                      min={0}
                      value={Number(form.harga_paket || 0)}
                      onChange={(e) => setForm({ ...form, harga_paket: Number(e.target.value) })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-start gap-2">
                    <span className="w-24 pt-1.5 text-slate-700">Catatan</span>
                    <span className="pt-1.5 text-slate-500">:</span>
                    <textarea
                      rows={2}
                      value={String(form.catatan || "")}
                      onChange={(e) => setForm({ ...form, catatan: e.target.value })}
                      placeholder="Contoh: Harga termasuk instalasi, belum termasuk PPN 11%"
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>
                </>
              )}

              {/* FORM KARYAWAN (Page 13 & 14) */}
              {dbTab === "karyawan" && (
                <>
                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Nama</span>
                    <span className="text-slate-500">:</span>
                    <input
                      required
                      type="text"
                      value={String(form.nama_lengkap || "")}
                      onChange={(e) => setForm({ ...form, nama_lengkap: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">NIK</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="text"
                      value={String(form.nik || "")}
                      onChange={(e) => setForm({ ...form, nik: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">No Telp</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="text"
                      value={String(form.no_telp || "")}
                      onChange={(e) => setForm({ ...form, no_telp: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Jabatan</span>
                    <span className="text-slate-500">:</span>
                    <select
                      value={String(form.jabatan || "Teknisi")}
                      onChange={(e) => setForm({ ...form, jabatan: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    >
                      <option value="Teknisi">Teknisi</option>
                      <option value="Leader">Leader</option>
                      <option value="Driver">Driver</option>
                    </select>
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Email Login</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="email"
                      value={String(form.email || "")}
                      onChange={(e) => setForm({ ...form, email: e.target.value })}
                      placeholder="Opsional — untuk akun aplikasi teknisi"
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">Password</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="password"
                      value={String(form.password || "")}
                      onChange={(e) => setForm({ ...form, password: e.target.value })}
                      placeholder={editingItem ? "Kosongkan bila tidak diubah" : "Minimal 8 karakter"}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-24 text-slate-700">No Rekening</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="text"
                      value={String(form.no_rekening || "")}
                      onChange={(e) => setForm({ ...form, no_rekening: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 outline-none focus:border-blue-600"
                    />
                  </div>

                  {/* Upload KTP Dropzone (Page 13) */}
                  <div className="pt-2">
                    <label className="flex h-28 w-full cursor-pointer flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 bg-slate-100 hover:bg-slate-200/60">
                      <Camera className="h-8 w-8 text-slate-800" strokeWidth={1.5} />
                      <span className="mt-1 text-xs font-medium text-slate-600">Upload KTP</span>
                      <input type="file" name="ktp_file" accept="image/*,application/pdf" className="hidden" />
                    </label>
                  </div>
                </>
              )}

              {/* FORM KLIEN (Page 16) */}
              {dbTab === "klien" && (
                <>
                  <div className="flex items-center gap-2">
                    <span className="w-28 text-slate-700">Nama Klien</span>
                    <span className="text-slate-500">:</span>
                    <input
                      required
                      type="text"
                      value={String(form.nama_klien || "")}
                      onChange={(e) => setForm({ ...form, nama_klien: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-28 text-slate-700">Nama Perusahaan</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="text"
                      value={String(form.nama_perusahaan || "")}
                      onChange={(e) => setForm({ ...form, nama_perusahaan: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-start gap-2">
                    <span className="w-28 pt-1 text-slate-700">Alamat</span>
                    <span className="pt-1 text-slate-500">:</span>
                    <textarea
                      rows={2}
                      value={String(form.alamat || "")}
                      onChange={(e) => setForm({ ...form, alamat: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-28 text-slate-700">Contact Person 1</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="text"
                      value={String(form.cp1_nama || "")}
                      onChange={(e) => setForm({ ...form, cp1_nama: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-28 text-slate-700">No Telepon</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="text"
                      value={String(form.cp1_telp || "")}
                      onChange={(e) => setForm({ ...form, cp1_telp: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-28 text-slate-700">Contact Person 2</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="text"
                      value={String(form.cp2_nama || "")}
                      onChange={(e) => setForm({ ...form, cp2_nama: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-28 text-slate-700">No Telepon</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="text"
                      value={String(form.cp2_telp || "")}
                      onChange={(e) => setForm({ ...form, cp2_telp: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-28 text-slate-700">NPWP</span>
                    <span className="text-slate-500">:</span>
                    <input
                      type="text"
                      value={String(form.npwp || "")}
                      onChange={(e) => setForm({ ...form, npwp: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1 outline-none focus:border-blue-600"
                    />
                  </div>

                  <div className="flex items-center gap-2">
                    <span className="w-28 text-slate-700">Kategori</span>
                    <span className="text-slate-500">:</span>
                    <select
                      value={String(form.kategori || "Corporate")}
                      onChange={(e) => setForm({ ...form, kategori: e.target.value })}
                      className="flex-1 rounded-md border border-slate-200 bg-slate-100 px-3 py-1 outline-none focus:border-blue-600"
                    >
                      <option value="Corporate">Corporate</option>
                      <option value="Government">Government</option>
                      <option value="Agency">Agency</option>
                      <option value="Individual">Individual</option>
                    </select>
                  </div>
                </>
              )}

              {dbTab === "armada" && (
                <div className="space-y-3">
                  <label className="block">Jenis Kendaraan<input required value={String(form.jenis_kendaraan || "")} onChange={(e) => setForm({ ...form, jenis_kendaraan: e.target.value })} className="mt-1 w-full rounded-md border p-2" /></label>
                  <label className="block">Plat Nomor<input required value={String(form.plat_nomor || "")} onChange={(e) => setForm({ ...form, plat_nomor: e.target.value.toUpperCase() })} className="mt-1 w-full rounded-md border p-2" /></label>
                  <label className="block">Status STNK<select value={String(form.status_stnk || "Aktif")} onChange={(e) => setForm({ ...form, status_stnk: e.target.value })} className="mt-1 w-full rounded-md border p-2"><option>Aktif</option><option>Jatuh Tempo</option><option>Non-Aktif</option></select></label>
                  <label className="block">Driver<input value={String(form.driver || "")} onChange={(e) => setForm({ ...form, driver: e.target.value })} className="mt-1 w-full rounded-md border p-2" /></label>
                  <label className="block">Status Operasional<select value={String(form.status_operasional || "Standby")} onChange={(e) => setForm({ ...form, status_operasional: e.target.value })} className="mt-1 w-full rounded-md border p-2"><option>Standby</option><option>Jalan</option><option>Servis</option></select></label>
                </div>
              )}
              {dbTab === "perusahaan" && (
                <div className="space-y-3">
                  {[["nama_perusahaan","Nama Perusahaan",true],["alamat_kantor","Alamat Kantor",false],["telepon","Telepon",false],["email","Email",false],["nama_bank","Nama Bank",false],["nomor_rekening","Nomor Rekening",false],["atas_nama_rekening","Atas Nama Rekening",false]].map(([key, label, required]) => <label key={String(key)} className="block">{String(label)}<input required={Boolean(required)} type={key === "email" ? "email" : "text"} value={String(form[String(key)] || "")} onChange={(e) => setForm({ ...form, [String(key)]: e.target.value })} className="mt-1 w-full rounded-md border p-2" /></label>)}
                  <label className="block">KOP Surat Path<input value={String(form.kop_surat_path || "")} onChange={(e) => setForm({ ...form, kop_surat_path: e.target.value })} className="mt-1 w-full rounded-md border p-2" /></label>
                  <label className="block">Logo Path<input value={String(form.logo_path || "")} onChange={(e) => setForm({ ...form, logo_path: e.target.value })} className="mt-1 w-full rounded-md border p-2" /></label>
                </div>
              )}

              {/* ACTION SIMPAN (Navy button bulat memanjang sesuai Gambar Design System) */}
              <div className="pt-4 text-center">
                <button
                  type="submit"
                  disabled={busy}
                  className="w-full rounded-md bg-[#002480] py-2 text-xs font-bold uppercase tracking-wider text-white shadow transition hover:bg-blue-900 active:scale-95 disabled:opacity-60"
                >
                  {busy ? "MENYIMPAN..." : "SIMPAN"}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
