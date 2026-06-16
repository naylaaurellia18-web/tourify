import { useState } from "react";

const destinations = [
  {
    id: 1,
    name: "Saloka Theme Park",
    location: "Semarang, Jateng",
    desc: "Taman rekreasi keluarga terbesar di Jawa Tengah dengan berbagai wahana seru.",
    price: 120000,
    stock: 85,
    maxStock: 200,
    sold: 115,
    revenue: 13800000,
    img: "🎢",
    color: "#f97316",
    bg: "#fff7ed",
    category: "Hiburan",
  },
  {
    id: 2,
    name: "Candi Borobudur",
    location: "Magelang, Jateng",
    desc: "Candi Buddha terbesar di dunia, warisan budaya UNESCO.",
    price: 300000,
    stock: 12,
    maxStock: 150,
    sold: 138,
    revenue: 41400000,
    img: "🏛️",
    color: "#8b5cf6",
    bg: "#f5f3ff",
    category: "Budaya",
  },
  {
    id: 3,
    name: "Taman Nasional Karimunjawa",
    location: "Jepara, Jateng",
    desc: "Pesona wisata bahari terindah dengan keindahan bawah laut dan pantai pasir putih.",
    price: 200000,
    stock: 43,
    maxStock: 100,
    sold: 57,
    revenue: 11400000,
    img: "🌊",
    color: "#0ea5e9",
    bg: "#f0f9ff",
    category: "Alam",
  },
  {
    id: 4,
    name: "Rasamadu (The Heritage Palace)",
    location: "Sukoharjo, Jateng",
    desc: "Bekas pabrik gula abad ke-19 yang diubah menjadi tempat wisata bergaya Eropa.",
    price: 80000,
    stock: 67,
    maxStock: 120,
    sold: 53,
    revenue: 4240000,
    img: "🏰",
    color: "#10b981",
    bg: "#f0fdf4",
    category: "Heritage",
  },
  {
    id: 5,
    name: "Solo Safari",
    location: "Surakarta, Jateng",
    desc: "Kawasan kebun binatang modern dengan konsep edukasi satwa yang interaktif.",
    price: 60000,
    stock: 5,
    maxStock: 180,
    sold: 175,
    revenue: 10500000,
    img: "🦁",
    color: "#f59e0b",
    bg: "#fffbeb",
    category: "Edukasi",
  },
];

const formatRp = (n) =>
  "Rp " + n.toLocaleString("id-ID");

const StockBar = ({ stock, max, color }) => {
  const pct = Math.round((stock / max) * 100);
  const barColor = pct < 20 ? "#ef4444" : pct < 50 ? "#f59e0b" : color;
  return (
    <div style={{ marginTop: 6 }}>
      <div style={{ display: "flex", justifyContent: "space-between", fontSize: 11, color: "#6b7280", marginBottom: 4 }}>
        <span>Sisa tiket</span>
        <span style={{ color: barColor, fontWeight: 600 }}>{pct}%</span>
      </div>
      <div style={{ background: "#f1f5f9", borderRadius: 99, height: 8, overflow: "hidden" }}>
        <div style={{ width: `${pct}%`, height: "100%", background: barColor, borderRadius: 99, transition: "width .4s" }} />
      </div>
      <div style={{ fontSize: 11, color: "#9ca3af", marginTop: 3 }}>{stock} dari {max} tiket tersedia</div>
    </div>
  );
};

// ── DETAIL VIEW ────────────────────────────────────────────
function DestinationDetail({ dest, onBack, onUpdate }) {
  const [refillAmt, setRefillAmt] = useState(50);
  const [priceEdit, setPriceEdit] = useState(dest.price);
  const [maxEdit, setMaxEdit] = useState(dest.maxStock);
  const [toast, setToast] = useState("");
  const [tab, setTab] = useState("overview");

  const showToast = (msg) => {
    setToast(msg);
    setTimeout(() => setToast(""), 2500);
  };

  const handleRefill = () => {
    const newStock = Math.min(dest.stock + refillAmt, dest.maxStock);
    onUpdate(dest.id, { stock: newStock });
    showToast(`✅ ${refillAmt} tiket berhasil ditambahkan!`);
  };

  const handleSaveSettings = () => {
    onUpdate(dest.id, { price: priceEdit, maxStock: maxEdit });
    showToast("✅ Pengaturan berhasil disimpan!");
  };

  const pct = Math.round((dest.stock / dest.maxStock) * 100);
  const stockColor = pct < 20 ? "#ef4444" : pct < 50 ? "#f59e0b" : dest.color;

  return (
    <div style={{ minHeight: "100vh", background: "#f8fafc", fontFamily: "'Segoe UI', sans-serif" }}>
      {/* Toast */}
      {toast && (
        <div style={{ position: "fixed", top: 20, right: 20, background: "#1e293b", color: "#fff", padding: "10px 18px", borderRadius: 10, fontSize: 13, zIndex: 999, boxShadow: "0 4px 20px rgba(0,0,0,.2)" }}>
          {toast}
        </div>
      )}

      {/* Header */}
      <div style={{ background: "#fff", borderBottom: "1px solid #e2e8f0", padding: "14px 28px", display: "flex", alignItems: "center", gap: 14 }}>
        <button onClick={onBack} style={{ background: "#f1f5f9", border: "none", borderRadius: 8, padding: "7px 14px", cursor: "pointer", fontSize: 13, color: "#475569", display: "flex", alignItems: "center", gap: 6 }}>
          ← Kembali
        </button>
        <div style={{ fontSize: 22 }}>{dest.img}</div>
        <div>
          <div style={{ fontWeight: 700, fontSize: 16, color: "#1e293b" }}>{dest.name}</div>
          <div style={{ fontSize: 12, color: "#94a3b8" }}>📍 {dest.location}</div>
        </div>
        <div style={{ marginLeft: "auto", background: dest.bg, color: dest.color, fontSize: 12, fontWeight: 600, padding: "4px 12px", borderRadius: 99 }}>
          {dest.category}
        </div>
      </div>

      {/* Tabs */}
      <div style={{ background: "#fff", borderBottom: "1px solid #e2e8f0", padding: "0 28px", display: "flex", gap: 2 }}>
        {["overview", "tiket", "pengaturan"].map((t) => (
          <button key={t} onClick={() => setTab(t)} style={{ padding: "12px 18px", border: "none", background: "none", cursor: "pointer", fontSize: 13, fontWeight: tab === t ? 700 : 400, color: tab === t ? dest.color : "#64748b", borderBottom: tab === t ? `2px solid ${dest.color}` : "2px solid transparent" }}>
            {t === "overview" ? "📊 Overview" : t === "tiket" ? "🎫 Kelola Tiket" : "⚙️ Pengaturan"}
          </button>
        ))}
      </div>

      <div style={{ padding: 28, maxWidth: 900, margin: "0 auto" }}>

        {/* ── OVERVIEW TAB ── */}
        {tab === "overview" && (
          <>
            <div style={{ display: "grid", gridTemplateColumns: "repeat(3,1fr)", gap: 16, marginBottom: 24 }}>
              {[
                { label: "Tiket Terjual", value: dest.sold, icon: "🎫", sub: "total terjual" },
                { label: "Total Pendapatan", value: formatRp(dest.revenue), icon: "💰", sub: "akumulasi" },
                { label: "Sisa Tiket", value: dest.stock, icon: "📦", sub: `dari ${dest.maxStock} kapasitas`, alert: pct < 20 },
              ].map((s) => (
                <div key={s.label} style={{ background: "#fff", border: `1px solid ${s.alert ? "#fca5a5" : "#e2e8f0"}`, borderRadius: 14, padding: 20, background: s.alert ? "#fef2f2" : "#fff" }}>
                  <div style={{ fontSize: 26, marginBottom: 8 }}>{s.icon}</div>
                  <div style={{ fontSize: 22, fontWeight: 800, color: s.alert ? "#dc2626" : "#1e293b" }}>{s.value}</div>
                  <div style={{ fontSize: 12, color: "#64748b", marginTop: 2 }}>{s.label}</div>
                  <div style={{ fontSize: 11, color: "#94a3b8" }}>{s.sub}</div>
                  {s.alert && <div style={{ marginTop: 8, fontSize: 11, color: "#dc2626", fontWeight: 600 }}>⚠️ Stok hampir habis!</div>}
                </div>
              ))}
            </div>

            {/* Stok visual */}
            <div style={{ background: "#fff", border: "1px solid #e2e8f0", borderRadius: 14, padding: 22 }}>
              <div style={{ fontWeight: 700, marginBottom: 16, color: "#1e293b" }}>Status Kapasitas Tiket</div>
              <div style={{ display: "flex", alignItems: "center", gap: 16 }}>
                <div style={{ flex: 1 }}>
                  <div style={{ background: "#f1f5f9", borderRadius: 99, height: 20, overflow: "hidden", position: "relative" }}>
                    <div style={{ width: `${(dest.sold / dest.maxStock) * 100}%`, height: "100%", background: dest.color, borderRadius: 99, transition: "width .5s" }} />
                  </div>
                  <div style={{ display: "flex", justifyContent: "space-between", fontSize: 11, color: "#94a3b8", marginTop: 6 }}>
                    <span>🔴 Terjual: {dest.sold}</span>
                    <span>🟢 Tersedia: {dest.stock}</span>
                    <span>📦 Kapasitas: {dest.maxStock}</span>
                  </div>
                </div>
                <div style={{ fontSize: 32, fontWeight: 900, color: stockColor }}>{pct}%</div>
              </div>
              <div style={{ marginTop: 14, padding: 12, background: dest.bg, borderRadius: 10, fontSize: 13, color: dest.color }}>
                💡 Harga tiket saat ini: <strong>{formatRp(dest.price)}</strong> / orang
              </div>
            </div>
          </>
        )}

        {/* ── TIKET TAB ── */}
        {tab === "tiket" && (
          <>
            <div style={{ background: "#fff", border: "1px solid #e2e8f0", borderRadius: 14, padding: 24, marginBottom: 20 }}>
              <div style={{ fontWeight: 700, fontSize: 15, color: "#1e293b", marginBottom: 4 }}>Status Stok Saat Ini</div>
              <div style={{ display: "flex", alignItems: "center", gap: 20, marginTop: 16 }}>
                <div style={{ textAlign: "center", flex: 1, background: pct < 20 ? "#fef2f2" : "#f0fdf4", borderRadius: 12, padding: 16 }}>
                  <div style={{ fontSize: 42, fontWeight: 900, color: stockColor }}>{dest.stock}</div>
                  <div style={{ fontSize: 12, color: "#64748b" }}>tiket tersisa</div>
                </div>
                <div style={{ fontSize: 28, color: "#cbd5e1" }}>→</div>
                <div style={{ textAlign: "center", flex: 1, background: "#f8fafc", borderRadius: 12, padding: 16 }}>
                  <div style={{ fontSize: 42, fontWeight: 900, color: "#94a3b8" }}>{dest.maxStock}</div>
                  <div style={{ fontSize: 12, color: "#64748b" }}>kapasitas maksimal</div>
                </div>
              </div>
              <StockBar stock={dest.stock} max={dest.maxStock} color={dest.color} />
            </div>

            {/* Refill panel */}
            <div style={{ background: "#fff", border: `2px solid ${dest.color}20`, borderRadius: 14, padding: 24 }}>
              <div style={{ fontWeight: 700, fontSize: 15, color: "#1e293b", marginBottom: 4 }}>🔄 Refill Tiket</div>
              <div style={{ fontSize: 13, color: "#64748b", marginBottom: 20 }}>Tambahkan stok tiket untuk destinasi ini</div>

              {/* Quick refill buttons */}
              <div style={{ marginBottom: 16 }}>
                <div style={{ fontSize: 12, fontWeight: 600, color: "#475569", marginBottom: 8 }}>Pilih cepat:</div>
                <div style={{ display: "flex", gap: 8, flexWrap: "wrap" }}>
                  {[10, 25, 50, 100].map((v) => (
                    <button key={v} onClick={() => setRefillAmt(v)} style={{ padding: "8px 18px", borderRadius: 8, border: `2px solid ${refillAmt === v ? dest.color : "#e2e8f0"}`, background: refillAmt === v ? dest.color : "#fff", color: refillAmt === v ? "#fff" : "#475569", fontWeight: 600, cursor: "pointer", fontSize: 13, transition: "all .2s" }}>
                      +{v}
                    </button>
                  ))}
                </div>
              </div>

              {/* Custom amount */}
              <div style={{ marginBottom: 20 }}>
                <div style={{ fontSize: 12, fontWeight: 600, color: "#475569", marginBottom: 6 }}>Atau masukkan jumlah manual:</div>
                <div style={{ display: "flex", gap: 10, alignItems: "center" }}>
                  <input
                    type="number"
                    value={refillAmt}
                    min={1}
                    max={dest.maxStock - dest.stock}
                    onChange={(e) => setRefillAmt(Number(e.target.value))}
                    style={{ padding: "10px 14px", border: "1.5px solid #e2e8f0", borderRadius: 8, fontSize: 15, fontWeight: 700, width: 100, outline: "none" }}
                  />
                  <div style={{ fontSize: 13, color: "#94a3b8" }}>tiket (maks. {dest.maxStock - dest.stock} tersisa)</div>
                </div>
              </div>

              {/* Preview */}
              <div style={{ background: dest.bg, borderRadius: 10, padding: 14, marginBottom: 18, display: "flex", justifyContent: "space-between", alignItems: "center" }}>
                <span style={{ fontSize: 13, color: "#475569" }}>Setelah refill:</span>
                <span style={{ fontWeight: 800, fontSize: 18, color: dest.color }}>
                  {Math.min(dest.stock + refillAmt, dest.maxStock)} tiket
                </span>
              </div>

              <button
                onClick={handleRefill}
                disabled={dest.stock >= dest.maxStock}
                style={{ width: "100%", padding: "13px", background: dest.stock >= dest.maxStock ? "#e2e8f0" : dest.color, color: dest.stock >= dest.maxStock ? "#94a3b8" : "#fff", border: "none", borderRadius: 10, fontWeight: 700, fontSize: 14, cursor: dest.stock >= dest.maxStock ? "not-allowed" : "pointer", transition: "opacity .2s" }}
              >
                {dest.stock >= dest.maxStock ? "✅ Stok Sudah Penuh" : `🔄 Tambah ${refillAmt} Tiket Sekarang`}
              </button>
            </div>
          </>
        )}

        {/* ── PENGATURAN TAB ── */}
        {tab === "pengaturan" && (
          <div style={{ background: "#fff", border: "1px solid #e2e8f0", borderRadius: 14, padding: 24 }}>
            <div style={{ fontWeight: 700, fontSize: 15, color: "#1e293b", marginBottom: 20 }}>⚙️ Pengaturan Destinasi</div>
            <div style={{ display: "grid", gap: 18 }}>
              <div>
                <label style={{ fontSize: 12, fontWeight: 600, color: "#475569", display: "block", marginBottom: 6 }}>Harga Tiket (Rp)</label>
                <input type="number" value={priceEdit} onChange={(e) => setPriceEdit(Number(e.target.value))} style={{ width: "100%", padding: "10px 14px", border: "1.5px solid #e2e8f0", borderRadius: 8, fontSize: 14, outline: "none" }} />
              </div>
              <div>
                <label style={{ fontSize: 12, fontWeight: 600, color: "#475569", display: "block", marginBottom: 6 }}>Kapasitas Maksimal Tiket</label>
                <input type="number" value={maxEdit} onChange={(e) => setMaxEdit(Number(e.target.value))} style={{ width: "100%", padding: "10px 14px", border: "1.5px solid #e2e8f0", borderRadius: 8, fontSize: 14, outline: "none" }} />
              </div>
              <div>
                <label style={{ fontSize: 12, fontWeight: 600, color: "#475569", display: "block", marginBottom: 6 }}>Nama Destinasi</label>
                <input type="text" defaultValue={dest.name} disabled style={{ width: "100%", padding: "10px 14px", border: "1.5px solid #e2e8f0", borderRadius: 8, fontSize: 14, background: "#f8fafc", color: "#94a3b8" }} />
              </div>
              <button onClick={handleSaveSettings} style={{ padding: "12px", background: dest.color, color: "#fff", border: "none", borderRadius: 10, fontWeight: 700, fontSize: 14, cursor: "pointer" }}>
                💾 Simpan Perubahan
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

// ── MAIN DASHBOARD ──────────────────────────────────────────
export default function TourifyAdmin() {
  const [dests, setDests] = useState(destinations);
  const [selected, setSelected] = useState(null);
  const [search, setSearch] = useState("");
  const [filterCat, setFilterCat] = useState("Semua");

  const updateDest = (id, changes) => {
    setDests((prev) => prev.map((d) => (d.id === id ? { ...d, ...changes } : d)));
    if (selected?.id === id) setSelected((s) => ({ ...s, ...changes }));
  };

  const totalRevenue = dests.reduce((a, d) => a + d.revenue, 0);
  const totalSold = dests.reduce((a, d) => a + d.sold, 0);
  const totalStock = dests.reduce((a, d) => a + d.stock, 0);
  const lowStock = dests.filter((d) => (d.stock / d.maxStock) < 0.2);

  const categories = ["Semua", ...new Set(dests.map((d) => d.category))];
  const filtered = dests.filter((d) => {
    const matchSearch = d.name.toLowerCase().includes(search.toLowerCase()) || d.location.toLowerCase().includes(search.toLowerCase());
    const matchCat = filterCat === "Semua" || d.category === filterCat;
    return matchSearch && matchCat;
  });

  if (selected) {
    const live = dests.find((d) => d.id === selected.id);
    return <DestinationDetail dest={live} onBack={() => setSelected(null)} onUpdate={updateDest} />;
  }

  return (
    <div style={{ minHeight: "100vh", background: "#f8fafc", fontFamily: "'Segoe UI', sans-serif" }}>

      {/* Topbar */}
      <div style={{ background: "#fff", borderBottom: "1px solid #e2e8f0", padding: "14px 28px", display: "flex", alignItems: "center", justifyContent: "space-between" }}>
        <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
          <div style={{ background: "#f97316", width: 32, height: 32, borderRadius: 8, display: "flex", alignItems: "center", justifyContent: "center", fontSize: 18 }}>🗺️</div>
          <div>
            <div style={{ fontWeight: 800, fontSize: 16, color: "#1e293b" }}>TOURIFY</div>
            <div style={{ fontSize: 11, color: "#94a3b8" }}>Admin Dashboard</div>
          </div>
        </div>
        <div style={{ display: "flex", alignItems: "center", gap: 10 }}>
          {lowStock.length > 0 && (
            <div style={{ background: "#fef2f2", color: "#dc2626", fontSize: 12, fontWeight: 600, padding: "5px 12px", borderRadius: 99, border: "1px solid #fca5a5" }}>
              ⚠️ {lowStock.length} destinasi stok kritis
            </div>
          )}
          <div style={{ background: "#f1f5f9", borderRadius: 8, padding: "6px 12px", fontSize: 12, color: "#475569", fontWeight: 600 }}>
            👤 Admin
          </div>
        </div>
      </div>

      <div style={{ padding: 28 }}>

        {/* Summary cards */}
        <div style={{ display: "grid", gridTemplateColumns: "repeat(4,1fr)", gap: 16, marginBottom: 28 }}>
          {[
            { label: "Total Pendapatan", value: formatRp(totalRevenue), icon: "💰", color: "#f97316", bg: "#fff7ed" },
            { label: "Total Tiket Terjual", value: totalSold.toLocaleString(), icon: "🎫", color: "#8b5cf6", bg: "#f5f3ff" },
            { label: "Total Stok Tersisa", value: totalStock.toLocaleString(), icon: "📦", color: "#0ea5e9", bg: "#f0f9ff" },
            { label: "Destinasi Aktif", value: dests.length, icon: "📍", color: "#10b981", bg: "#f0fdf4" },
          ].map((s) => (
            <div key={s.label} style={{ background: "#fff", border: "1px solid #e2e8f0", borderRadius: 14, padding: 20 }}>
              <div style={{ width: 40, height: 40, background: s.bg, borderRadius: 10, display: "flex", alignItems: "center", justifyContent: "center", fontSize: 20, marginBottom: 12 }}>{s.icon}</div>
              <div style={{ fontSize: 22, fontWeight: 800, color: "#1e293b" }}>{s.value}</div>
              <div style={{ fontSize: 12, color: "#64748b", marginTop: 2 }}>{s.label}</div>
            </div>
          ))}
        </div>

        {/* Alert kritis */}
        {lowStock.length > 0 && (
          <div style={{ background: "#fef2f2", border: "1px solid #fca5a5", borderRadius: 12, padding: "14px 18px", marginBottom: 24, display: "flex", alignItems: "center", gap: 12 }}>
            <div style={{ fontSize: 20 }}>🚨</div>
            <div>
              <div style={{ fontWeight: 700, color: "#dc2626", fontSize: 13 }}>Stok Tiket Kritis!</div>
              <div style={{ fontSize: 12, color: "#b91c1c", marginTop: 2 }}>
                {lowStock.map((d) => d.name).join(", ")} — segera lakukan refill
              </div>
            </div>
            <button onClick={() => setSelected(lowStock[0])} style={{ marginLeft: "auto", background: "#dc2626", color: "#fff", border: "none", borderRadius: 8, padding: "6px 14px", fontSize: 12, fontWeight: 600, cursor: "pointer" }}>
              Refill Sekarang →
            </button>
          </div>
        )}

        {/* Filter & Search */}
        <div style={{ display: "flex", alignItems: "center", gap: 12, marginBottom: 20, flexWrap: "wrap" }}>
          <input
            placeholder="🔍  Cari destinasi..."
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            style={{ padding: "9px 14px", border: "1.5px solid #e2e8f0", borderRadius: 10, fontSize: 13, outline: "none", minWidth: 220, background: "#fff" }}
          />
          <div style={{ display: "flex", gap: 6 }}>
            {categories.map((c) => (
              <button key={c} onClick={() => setFilterCat(c)} style={{ padding: "7px 14px", borderRadius: 8, border: `1.5px solid ${filterCat === c ? "#f97316" : "#e2e8f0"}`, background: filterCat === c ? "#fff7ed" : "#fff", color: filterCat === c ? "#f97316" : "#475569", fontWeight: filterCat === c ? 700 : 400, cursor: "pointer", fontSize: 12 }}>
                {c}
              </button>
            ))}
          </div>
          <div style={{ marginLeft: "auto", fontSize: 12, color: "#94a3b8" }}>{filtered.length} destinasi</div>
        </div>

        {/* Destination cards grid */}
        <div style={{ display: "grid", gridTemplateColumns: "repeat(auto-fill, minmax(300px,1fr))", gap: 16 }}>
          {filtered.map((d) => {
            const pct = Math.round((d.stock / d.maxStock) * 100);
            const isLow = pct < 20;
            const isMid = pct < 50;
            const stockColor = isLow ? "#ef4444" : isMid ? "#f59e0b" : d.color;
            return (
              <div key={d.id} style={{ background: "#fff", border: `1.5px solid ${isLow ? "#fca5a5" : "#e2e8f0"}`, borderRadius: 16, overflow: "hidden", cursor: "pointer", transition: "transform .15s, box-shadow .15s", boxShadow: "0 1px 4px rgba(0,0,0,.06)" }}
                onClick={() => setSelected(d)}
                onMouseEnter={(e) => { e.currentTarget.style.transform = "translateY(-3px)"; e.currentTarget.style.boxShadow = "0 8px 24px rgba(0,0,0,.1)"; }}
                onMouseLeave={(e) => { e.currentTarget.style.transform = ""; e.currentTarget.style.boxShadow = "0 1px 4px rgba(0,0,0,.06)"; }}
              >
                {/* Card top */}
                <div style={{ background: d.bg, padding: "20px 20px 14px", borderBottom: "1px solid #f1f5f9", display: "flex", alignItems: "flex-start", justifyContent: "space-between" }}>
                  <div style={{ display: "flex", alignItems: "center", gap: 12 }}>
                    <div style={{ fontSize: 36 }}>{d.img}</div>
                    <div>
                      <div style={{ fontWeight: 700, fontSize: 14, color: "#1e293b", lineHeight: 1.3 }}>{d.name}</div>
                      <div style={{ fontSize: 11, color: "#94a3b8", marginTop: 2 }}>📍 {d.location}</div>
                    </div>
                  </div>
                  <div style={{ background: d.color, color: "#fff", fontSize: 10, fontWeight: 700, padding: "3px 9px", borderRadius: 99 }}>{d.category}</div>
                </div>

                {/* Card body */}
                <div style={{ padding: "16px 20px" }}>
                  {/* Stok besar */}
                  <div style={{ display: "flex", justifyContent: "space-between", alignItems: "flex-end", marginBottom: 10 }}>
                    <div>
                      <div style={{ fontSize: 11, color: "#94a3b8", fontWeight: 600, textTransform: "uppercase", letterSpacing: ".04em" }}>Sisa Tiket</div>
                      <div style={{ fontSize: 32, fontWeight: 900, color: stockColor, lineHeight: 1.1 }}>{d.stock}</div>
                      <div style={{ fontSize: 11, color: "#94a3b8" }}>dari {d.maxStock}</div>
                    </div>
                    <div style={{ textAlign: "right" }}>
                      <div style={{ fontSize: 11, color: "#94a3b8" }}>Terjual</div>
                      <div style={{ fontSize: 18, fontWeight: 700, color: "#475569" }}>{d.sold}</div>
                    </div>
                  </div>

                  {/* Bar */}
                  <div style={{ background: "#f1f5f9", borderRadius: 99, height: 8, overflow: "hidden", marginBottom: 6 }}>
                    <div style={{ width: `${pct}%`, height: "100%", background: stockColor, borderRadius: 99 }} />
                  </div>

                  {isLow && (
                    <div style={{ fontSize: 11, color: "#dc2626", fontWeight: 600, marginBottom: 8 }}>⚠️ Stok hampir habis, segera refill!</div>
                  )}

                  <div style={{ display: "flex", justifyContent: "space-between", alignItems: "center", marginTop: 12, paddingTop: 12, borderTop: "1px solid #f1f5f9" }}>
                    <div>
                      <div style={{ fontSize: 11, color: "#94a3b8" }}>Harga tiket</div>
                      <div style={{ fontWeight: 700, color: d.color, fontSize: 14 }}>{formatRp(d.price)}</div>
                    </div>
                    <div style={{ fontSize: 12, color: d.color, fontWeight: 600 }}>Kelola →</div>
                  </div>
                </div>
              </div>
            );
          })}
        </div>

      </div>
    </div>
  );
}