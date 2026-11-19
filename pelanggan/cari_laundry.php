<?php
$judul = 'Buat Pesanan';
require __DIR__ . '/_header.php';

$data = [];
$r = mysqli_query($conn, 'SELECT * FROM tb_laundry ORDER BY nama_laundry');
while ($row = mysqli_fetch_assoc($r)) {
    $data[] = [
        'id'     => (int)$row['id_laundry'],
        'nama'   => $row['nama_laundry'],
        'lat'    => (float)$row['latitude'],
        'lng'    => (float)$row['longitude'],
        'alamat' => $row['alamat'] ?? '',
        'kontak' => $row['kontak'] ?? '',
        'layanan'=> $row['jenis_layanan'] ?? '',
        'jam'    => $row['jam_operasional'] ?? '',
        'ket'    => $row['keterangan'] ?? '',
        'foto'   => $row['foto_laundry'] ?? '',
        'wilayah'=> $row['wilayah'] ?? '',
    ];
}
$wilayah = array_values(array_unique(array_filter(array_column($data, 'wilayah'))));
sort($wilayah);

$json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/peta.css">

<section class="page-heading">
    <span class="welcome-label">PEMESANAN LAUNDRY</span>
    <h1>Buat Pesanan</h1>
    <p>Langkah 1: pilih laundry (cari lewat nama, layanan, atau peta). Langkah 2: klik <strong>Pesan di sini</strong> pada laundry pilihan Anda, lalu isi pesanannya.</p>
</section>

<?php if (!empty($_GET['pilih'])): ?>
    <div class="alert">Pilih dulu laundry yang ingin Anda gunakan, lalu klik <strong>Pesan di sini</strong> pada informasi laundry tersebut.</div>
<?php endif; ?>

<section class="lp-filter">
    <div class="lp-filter-row">
        <input type="search" id="lpCari" class="lp-input" placeholder="Cari nama, alamat, atau layanan (mis. sepatu, karpet)…" autocomplete="off">
        <select id="lpWilayah" class="lp-input lp-select">
            <option value="">Semua wilayah</option>
            <?php foreach ($wilayah as $w): ?>
                <option value="<?= e($w) ?>"><?= e($w) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="button" id="lpLokasi" class="lp-btn lp-btn-outline">📍 Dekat saya</button>
    </div>

    <div class="lp-chips" id="lpChips">
        <button type="button" class="lp-chip" data-tag="antar">🚚 Antar jemput</button>
        <button type="button" class="lp-chip" data-tag="express">⚡ Express</button>
        <button type="button" class="lp-chip" data-tag="kiloan">⚖️ Kiloan</button>
        <button type="button" class="lp-chip" data-tag="setrika">♨️ Setrika</button>
        <button type="button" class="lp-chip" data-tag="sepatu">👟 Sepatu</button>
        <button type="button" class="lp-chip" data-tag="karpet">🧶 Karpet</button>
        <button type="button" class="lp-chip" data-tag="bedcover">🛏️ Bedcover/Selimut</button>
        <button type="button" class="lp-chip" data-tag="boneka">🧸 Boneka</button>
        <button type="button" class="lp-chip" data-tag="helm">⛑️ Helm/Tas</button>
    </div>
    <p class="lp-status" id="lpStatus" aria-live="polite"></p>
</section>

<section class="lp-layout">
    <div class="lp-map-wrap">
        <div id="lpMap" class="lp-map"></div>
        <p class="lp-map-note" id="lpMapNote" hidden>Peta gagal dimuat. Periksa koneksi internet, daftar laundry di samping tetap bisa digunakan.</p>
    </div>

    <aside class="lp-list-wrap">
        <h2>Daftar Laundry <span id="lpJumlah"></span></h2>
        <div id="lpList" class="lp-list"></div>
    </aside>
</section>

<div class="lp-modal" id="lpModal" hidden>
    <div class="lp-modal-box" role="dialog" aria-modal="true" aria-labelledby="lpModalNama">
        <div id="lpModalBody"></div>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
<script>
const DATA = <?= $json ?>;
const BASE_URL = <?= json_encode(BASE_URL) ?>;

const KATA = {
    antar:    /antar\s*jemput|deliver|free\s*ongkir/i,
    express:  /expre|experss|ekspres|kilat/i,
    kiloan:   /kiloan/i,
    setrika:  /setrika/i,
    sepatu:   /sepatu|sepattu/i,
    karpet:   /karpet|tikar/i,
    bedcover: /bed\s*cover|bedcover|selimut/i,
    boneka:   /boneka/i,
    helm:     /helm|\btas\b/i
};

const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const norm = s => String(s ?? '').toLowerCase();

DATA.forEach(d => {
    d.teks = norm([d.nama, d.alamat, d.layanan, d.ket, d.wilayah].join(' '));
    const sumber = d.layanan + ' ' + d.ket + ' ' + d.nama;
    d.tag = Object.keys(KATA).filter(k => KATA[k].test(sumber));
    d.jarak = null;
});

const state = { q: '', wilayah: '', tags: new Set(), posisi: null, aktif: null };

/* ---------- Peta ---------- */
let map = null, cluster = null, userMarker = null;
const markers = new Map();

function iconBulat(aktif) {
    return L.divIcon({
        className: '',
        html: '<div class="lp-pin' + (aktif ? ' lp-pin-aktif' : '') + '"><span>🧺</span></div>',
        iconSize: [34, 34], iconAnchor: [17, 34]
    });
}

function initPeta() {
    if (typeof L === 'undefined') {
        document.getElementById('lpMapNote').hidden = false;
        document.getElementById('lpMap').classList.add('lp-map-gagal');
        return;
    }
    map = L.map('lpMap', { scrollWheelZoom: true, zoomSnap: 0.5, wheelDebounceTime: 30 }).setView([-0.845, 119.89], 14);
    const tiles = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
    let tileOk = false;
    const note = document.getElementById('lpMapNote');
    tiles.on('tileload', () => { tileOk = true; note.hidden = true; });
    tiles.on('tileerror', () => { if (!tileOk) note.hidden = false; });

    cluster = (typeof L.markerClusterGroup === 'function')
        ? L.markerClusterGroup({ maxClusterRadius: 40, showCoverageOnHover: false })
        : L.layerGroup();
    map.addLayer(cluster);

    DATA.forEach(d => {
        const m = L.marker([d.lat, d.lng], { icon: iconBulat(false), title: d.nama });
        m.on('click', () => { setAktif(d.id); bukaDetail(d); });
        markers.set(d.id, m);
    });
}

/* ---------- Filter ---------- */
function terfilter() {
    const kata = norm(state.q).split(/\s+/).filter(Boolean);
    let hasil = DATA.filter(d =>
        kata.every(k => d.teks.includes(k)) &&
        (!state.wilayah || d.wilayah === state.wilayah) &&
        [...state.tags].every(t => d.tag.includes(t))
    );
    if (state.posisi) hasil.sort((a, b) => a.jarak - b.jarak);
    return hasil;
}

function render() {
    const hasil = terfilter();
    renderList(hasil);
    renderPeta(hasil);
}

function renderList(hasil) {

    document.getElementById('lpJumlah').textContent = '(' + hasil.length + ')';
    document.getElementById('lpStatus').textContent =
        hasil.length === DATA.length
            ? 'Menampilkan semua ' + DATA.length + ' laundry.'
            : 'Ditemukan ' + hasil.length + ' dari ' + DATA.length + ' laundry.';

    const list = document.getElementById('lpList');
    if (!hasil.length) {
        list.innerHTML = '<div class="lp-kosong">Tidak ada laundry yang cocok. Coba kata kunci lain atau hapus filter.</div>';
    } else {
        list.innerHTML = hasil.map(d => `
            <button type="button" class="lp-item${state.aktif === d.id ? ' aktif' : ''}" data-id="${d.id}">
                <strong>${esc(d.nama)}</strong>
                <span class="lp-item-alamat">${esc(d.alamat || 'Alamat belum tersedia')}</span>
                <span class="lp-item-meta">
                    ${d.jarak !== null ? '<em>' + fmtJarak(d.jarak) + '</em>' : ''}
                    ${d.tag.includes('antar') ? '<em>🚚 Antar jemput</em>' : ''}
                    ${d.tag.includes('express') ? '<em>⚡ Express</em>' : ''}
                </span>
            </button>`).join('');
    }

}

function renderPeta(hasil) {
    if (!map) return;
    cluster.clearLayers();
    hasil.forEach(d => cluster.addLayer(markers.get(d.id)));
}

function fmtJarak(km) { return km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1) + ' km'; }

function hitungJarak(lat1, lon1, lat2, lon2) {
    const R = 6371, rad = x => x * Math.PI / 180;
    const a = Math.sin(rad(lat2 - lat1) / 2) ** 2 +
              Math.cos(rad(lat1)) * Math.cos(rad(lat2)) * Math.sin(rad(lon2 - lon1) / 2) ** 2;
    return 2 * R * Math.asin(Math.sqrt(a));
}

/* ---------- Pilih, popup & detail ---------- */
function setAktif(id) {
    state.aktif = id;
    if (map) markers.forEach((m, mid) => m.setIcon(iconBulat(mid === id)));
    document.querySelectorAll('#lpList .lp-item').forEach(el => {
        const on = parseInt(el.dataset.id, 10) === id;
        el.classList.toggle('aktif', on);
        if (on) el.scrollIntoView({ block: 'nearest' });
    });
}

function fokusPeta(d) {
    setAktif(d.id);
    if (!map) return;
    const m = markers.get(d.id);
    if (cluster.zoomToShowLayer) {
        cluster.zoomToShowLayer(m, () => map.panTo([d.lat, d.lng]));
    } else {
        map.setView([d.lat, d.lng], 17);
    }
}

function nomorWA(kontak) {
    const m = String(kontak || '').match(/0\d{8,13}/);
    if (!m) return null;
    return '62' + m[0].slice(1);
}

function bukaDetail(d) {
    const wa = nomorWA(d.kontak);
    const layananList = d.layanan ? d.layanan.split(',').map(s => s.trim()).filter(Boolean) : [];
    let foto = '';
    if (/^https?:\/\//i.test(d.foto)) foto = `<p><a href="${esc(d.foto)}" target="_blank" rel="noopener">🖼️ Lihat foto</a></p>`;
    else if (d.foto) foto = `<img class="lp-foto" loading="lazy" src="${esc(BASE_URL)}/uploads/laundry/${encodeURIComponent(d.foto)}" alt="Foto ${esc(d.nama)}" onerror="this.remove()">`;

    const rute = `https://www.google.com/maps/dir/?api=1&destination=${d.lat},${d.lng}`;
    document.getElementById('lpModalBody').innerHTML = `
        ${foto}
        <div class="lp-badges">
            <span class="lp-badge">${esc(d.wilayah || 'Palu')}</span>
            ${d.jarak !== null ? '<span class="lp-badge lp-badge-biru">' + fmtJarak(d.jarak) + ' dari Anda</span>' : ''}
        </div>
        <h2 id="lpModalNama">${esc(d.nama)}</h2>
        <p class="lp-detail-alamat">📍 ${esc(d.alamat || 'Alamat belum tersedia')}</p>

        <h3>Layanan</h3>
        ${layananList.length
            ? '<div class="lp-tags">' + layananList.map(s => '<span>' + esc(s) + '</span>').join('') + '</div>'
            : '<p class="lp-muted">Belum terverifikasi</p>'}

        <h3>Jam operasional</h3>
        <p>${d.jam ? esc(d.jam) : '<span class="lp-muted">Belum terverifikasi</span>'}</p>

        ${d.ket ? '<h3>Keterangan</h3><p class="lp-pre">' + esc(d.ket) + '</p>' : ''}

        <h3>Kontak</h3>
        <p>${d.kontak ? esc(d.kontak) : '<span class="lp-muted">Belum tersedia</span>'}</p>

        <div class="lp-actions">
            <a class="lp-btn lp-btn-biru" href="${esc(BASE_URL)}/pelanggan/pesanan_baru.php?laundry=${d.id}">🧺 Pesan di sini</a>
            ${wa ? `<a class="lp-btn lp-btn-hijau" href="https://wa.me/${wa}" target="_blank" rel="noopener">💬 Hubungi WhatsApp</a>` : ''}
            <button type="button" class="lp-btn lp-btn-outline" id="lpLihatPeta">🗺️ Lihat di peta</button>
            <a class="lp-btn lp-btn-outline" href="${rute}" target="_blank" rel="noopener">🧭 Rute</a>
            <button type="button" class="lp-btn lp-btn-outline" id="lpTutup">Tutup</button>
        </div>`;
    document.getElementById('lpModal').hidden = false;
    document.getElementById('lpLihatPeta').onclick = () => {
        tutupDetail();
        if (map) { fokusPeta(d); document.getElementById('lpMap').scrollIntoView({ behavior: 'smooth', block: 'center' }); }
    };
    document.getElementById('lpTutup').onclick = tutupDetail;
}

function tutupDetail() { document.getElementById('lpModal').hidden = true; }

/* ---------- Event ---------- */
let timer;
document.getElementById('lpCari').addEventListener('input', e => {
    clearTimeout(timer);
    timer = setTimeout(() => { state.q = e.target.value; render(); fitHasil(); }, 200);
});
document.getElementById('lpWilayah').addEventListener('change', e => { state.wilayah = e.target.value; render(); fitHasil(); });
document.getElementById('lpChips').addEventListener('click', e => {
    const b = e.target.closest('.lp-chip'); if (!b) return;
    const t = b.dataset.tag;
    state.tags.has(t) ? state.tags.delete(t) : state.tags.add(t);
    b.classList.toggle('aktif', state.tags.has(t));
    render(); fitHasil();
});
document.getElementById('lpList').addEventListener('click', e => {
    const b = e.target.closest('.lp-item'); if (!b) return;
    const d = DATA.find(x => x.id === parseInt(b.dataset.id, 10));
    if (d) { fokusPeta(d); bukaDetail(d); }
});
document.getElementById('lpModal').addEventListener('click', e => { if (e.target.id === 'lpModal') tutupDetail(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupDetail(); });

function fitHasil() {
    if (!map) return;
    const hasil = terfilter();
    if (!hasil.length) return;
    map.fitBounds(L.latLngBounds(hasil.map(d => [d.lat, d.lng])), { padding: [40, 40], maxZoom: 17 });
}

document.getElementById('lpLokasi').addEventListener('click', () => {
    const btn = document.getElementById('lpLokasi');
    if (!navigator.geolocation) { alert('Peramban Anda tidak mendukung lokasi.'); return; }
    btn.disabled = true; btn.textContent = 'Mencari lokasi…';
    navigator.geolocation.getCurrentPosition(pos => {
        state.posisi = [pos.coords.latitude, pos.coords.longitude];
        DATA.forEach(d => d.jarak = hitungJarak(state.posisi[0], state.posisi[1], d.lat, d.lng));
        if (map) {
            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.circleMarker(state.posisi, { radius: 8, color: '#fff', weight: 2, fillColor: '#1769d2', fillOpacity: 1 })
                .addTo(map).bindTooltip('Lokasi Anda');
        }
        btn.disabled = false; btn.textContent = '📍 Dekat saya ✓';
        render();
        const terdekat = terfilter()[0];
        if (map && terdekat) map.fitBounds(L.latLngBounds([state.posisi, [terdekat.lat, terdekat.lng]]), { padding: [60, 60], maxZoom: 16 });
    }, () => {
        btn.disabled = false; btn.textContent = '📍 Dekat saya';
        alert('Lokasi tidak dapat diakses. Izinkan akses lokasi pada peramban Anda.');
    }, { enableHighAccuracy: true, timeout: 10000 });
});

initPeta();
render();
fitHasil();
</script>

<?php require __DIR__ . '/_footer.php'; ?>
