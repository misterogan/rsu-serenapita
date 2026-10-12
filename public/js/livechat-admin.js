/* Konsol live chat petugas – polling ke server */
(function () {
  const C = window.LC_CONFIG; if (!C) return;
  const $ = s => document.querySelector(s);
  const esc = s => String(s ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const COLORS = ["#1F6FB2", "#35A3D8", "#1C3B70", "#2E8B6A", "#C8332E", "#7A4FA8", "#B05C8E", "#D08A2E", "#3D7F8F", "#5B6878", "#8A6D3B", "#4E9A5A"];
  const colorFor = s => { let h = 0; for (const ch of String(s)) h = (h * 31 + ch.charCodeAt(0)) >>> 0; return COLORS[h % COLORS.length]; };
  const initials = n => String(n).split(/\s+/).filter(w => /^[A-Z]/.test(w)).slice(0, 2).map(w => w[0]).join("") || "?";
  const STATUS = { menunggu: ["Menunggu", "b-amber"], aktif: ["Aktif", "b-green"], selesai: ["Selesai", "b-grey"] };
  const req = (url, method = "GET", body) => fetch(url, { method, headers: { Accept: "application/json", "Content-Type": "application/json", "X-CSRF-TOKEN": C.csrf }, body: body ? JSON.stringify(body) : undefined })
    .then(r => { if (!r.ok) throw r; return r.json(); });
  const S = { convs: [], sel: null, detail: null, msgs: [], last: 0, tab: "menunggu", q: "", first: true, compStatus: null, feedTimer: null, msgTimer: null };

  async function feed() {
    try {
      const j = await req(C.routes.feed);
      const prev = new Map(S.convs.map(c => [c.id, c]));
      S.convs = j.conversations;
      if (!S.first) S.convs.forEach(c => { const p = prev.get(c.id); if (c.last_from === "visitor" && (!p || c.last_at !== p.last_at) && c.id !== S.sel) window.toast(`${p ? "Pesan baru" : "Chat baru"} dari ${c.name}`); });
      S.first = false;
      const b = $("#lcBadge"); if (b) { b.textContent = j.count || ""; b.hidden = !j.count; }
      renderList();
      const cur = S.convs.find(c => c.id === S.sel);
      if (cur && S.detail && cur.status !== S.detail.status) { S.detail.status = cur.status; renderHead(); renderComposer(); }
    } catch (e) {}
  }
  function loop() { clearTimeout(S.feedTimer); S.feedTimer = setTimeout(async () => { if (!document.hidden) await feed(); loop(); }, Math.max(2000, C.pollMs * 1.5)); }

  function renderList() {
    const n = k => k === "semua" ? S.convs.length : S.convs.filter(c => c.status === k).length;
    $("#lcTabs").innerHTML = [["menunggu", "Menunggu"], ["aktif", "Aktif"], ["selesai", "Selesai"], ["semua", "Semua"]].map(t => `<button data-f="${t[0]}" aria-pressed="${S.tab === t[0]}">${t[1]} ${n(t[0])}</button>`).join("");
    const q = S.q.toLowerCase(), qd = S.q.replace(/\D/g, "");
    const list = S.convs.filter(c => (S.tab === "semua" || c.status === S.tab) && (!q || c.name.toLowerCase().includes(q) || (qd.length > 3 && String(c.phone_norm).includes(qd))));
    $("#lcItems").innerHTML = list.length ? list.map(c => `<button class="lc-item${c.id === S.sel ? " on" : ""}${c.unseen ? " unseen" : ""}" data-conv="${c.id}">
      <div class="av" style="background:${colorFor(c.phone_norm)}">${esc(initials(c.name))}</div>
      <div class="lc-mid"><div class="lc-top"><b>${esc(c.name)}</b><time>${esc(c.last_short || "")}</time></div>
      <div class="lc-sub">${esc(c.phone)} · ${esc(c.topic || "")}${c.same_phone ? " · nomor sama dgn " + c.same_phone + " akun" : ""}</div>
      <div class="lc-last">${c.last_from === "agent" ? "Anda: " : ""}${esc(c.last_text || "")}</div></div>${c.unseen ? '<i class="lc-dot" aria-label="Belum dibaca"></i>' : ""}</button>`).join("")
      : `<div class="empty">${S.tab === "menunggu" ? "Tidak ada pengunjung yang menunggu." : "Belum ada percakapan."}</div>`;
  }
  function renderHead() {
    const h = $("#lcHead"), c = S.detail;
    if (!c) { h.style.display = "none"; return; } h.style.display = "";
    const wa = "https://wa.me/62" + String(c.phone_norm).slice(1), st = STATUS[c.status] || ["", "b-grey"];
    h.innerHTML = `<button class="ibtn lc-back" id="lcBack" aria-label="Kembali ke daftar"><svg class="i" viewBox="0 0 24 24"><path d="M15 6l-6 6 6 6"/></svg></button>
      <div class="av" style="background:${colorFor(c.phone_norm)}">${esc(initials(c.name))}</div>
      <div class="lc-who"><b>${esc(c.name)}</b><span>${esc(c.phone)} · <a href="tel:${esc(c.phone_norm)}">Telepon</a> · <a href="${wa}" target="_blank" rel="noopener">WhatsApp</a></span></div>
      <span class="badge ${st[1]}">${st[0]}</span>
      ${c.status === "menunggu" ? '<button class="btn sm pri" data-act="take">Ambil percakapan</button>' : c.status === "aktif" ? '<button class="btn sm" data-act="finish">Selesaikan</button>' : '<button class="btn sm" data-act="reopen">Buka lagi</button>'}`;
  }
  function renderComposer() {
    const el = $("#lcComp"), st = S.detail ? S.detail.status : "none";
    if (S.compStatus === st) return; S.compStatus = st;
    if (!S.detail) { el.innerHTML = ""; return; }
    if (st === "selesai") { el.innerHTML = '<div class="lc-closed">Percakapan sudah selesai. Pengunjung bisa memulai chat baru kapan saja.</div>'; return; }
    el.innerHTML = `<div class="lc-canned">${C.canned.map((t, i) => `<button type="button" data-canned="${i}" title="${esc(t)}">${esc(t)}</button>`).join("")}</div>
      <form class="lc-send" id="lcSend" data-noload><textarea id="lcIn" rows="2" placeholder="Tulis balasan. Enter untuk kirim, Shift+Enter untuk baris baru." aria-label="Balasan"></textarea><button class="btn pri" type="submit">Kirim</button></form>`;
  }
  function renderMsgs() {
    const lg = $("#lcLog");
    if (!S.detail) { lg.innerHTML = '<div class="lc-empty"><p>Pilih percakapan di sebelah kiri untuk mulai membalas.</p></div>'; return; }
    const atBottom = lg.scrollHeight - lg.scrollTop - lg.clientHeight < 80;
    lg.innerHTML = S.msgs.map(m => m.from === "system" ? `<div class="lc-sys">${esc(m.text)} · ${esc(m.time)}</div>`
      : `<div class="lc-m ${m.from === "visitor" ? "v" : "a"}">${esc(m.text)}<time>${m.from === "agent" ? esc(m.label || "Petugas") + " · " : ""}${esc(m.time)}</time></div>`).join("");
    if (atBottom || S.forceBottom) lg.scrollTop = lg.scrollHeight; S.forceBottom = false;
  }
  function renderInfo() {
    const el = $("#lcInfo"), c = S.detail;
    if (!c) { el.innerHTML = '<div class="empty" style="padding:20px 0">Data pengunjung muncul di sini.</div>'; return; }
    if (document.activeElement && document.activeElement.id === "lcNote") return;
    el.innerHTML = `<h3>Data pengunjung</h3><dl><dt>Nama</dt><dd>${esc(c.name)}</dd><dt>Nomor HP</dt><dd>${esc(c.phone)}</dd><dt>Topik</dt><dd>${esc(c.topic)}</dd><dt>Sesi ini</dt><dd>${esc(c.started_at || "-")}</dd><dt>Kontak pertama</dt><dd>${esc(c.created_at || "-")}</dd>${c.agent ? `<dt>Petugas</dt><dd>${esc(c.agent)}</dd>` : ""}</dl>
      <h3>Pengenalan</h3>
      <span class="lc-pill">${c.sessions > 1 ? `Pengunjung kembali · ${c.sessions} sesi chat` : "Pengunjung baru · sesi pertama"}</span>
      ${c.others.length ? `<span class="lc-pill warn">Nomor yang sama dipakai ${c.others.length} percakapan lain: ${c.others.map(esc).join(", ")}</span>` : ""}
      ${c.appointments.length ? c.appointments.map(a => `<span class="lc-pill">Janji temu ${esc(a)}</span>`).join("") : '<span class="lc-pill">Tidak ada janji temu dengan nomor ini.</span>'}
      <h3>Catatan internal</h3><textarea id="lcNote" rows="4" placeholder="Hanya terlihat oleh petugas">${esc(c.note || "")}</textarea>
      <div style="font-size:11px;color:var(--muted);margin-top:4px">Tersimpan otomatis saat Anda pindah dari kolom ini.</div>`;
  }
  async function loadDetail(full) {
    if (!S.sel) return;
    const id = S.sel;
    try {
      const j = await req(C.routes.show.replace("__id__", id) + "?after=" + (full ? 0 : S.last));
      if (S.sel !== id) return;
      const prevStatus = S.detail?.status;
      S.detail = j.conversation;
      if (full) S.msgs = [];
      j.messages.forEach(m => { if (m.id > S.last) { S.msgs.push(m); S.last = m.id; } });
      if (full || j.messages.length) renderMsgs();
      if (full || prevStatus !== S.detail.status) { renderHead(); renderComposer(); }
      if (full) renderInfo();
    } catch (e) {}
  }
  function msgLoop() { clearTimeout(S.msgTimer); S.msgTimer = setTimeout(async () => { if (!document.hidden) await loadDetail(false); msgLoop(); }, C.pollMs); }
  async function select(id) {
    S.sel = id; S.detail = null; S.msgs = []; S.last = 0; S.compStatus = null; S.forceBottom = true;
    $("#lc").classList.add("chatting"); renderList();
    $("#lcLog").innerHTML = '<div class="lc-sys">Memuat pesan…</div>';
    await loadDetail(true); msgLoop(); feed();
    setTimeout(() => $("#lcIn")?.focus(), 50);
  }

  document.addEventListener("click", async e => {
    const t = e.target.closest("[data-conv],[data-f],[data-act],[data-canned],#lcBack"); if (!t) return;
    if (t.dataset.conv) return select(+t.dataset.conv);
    if (t.dataset.f) { S.tab = t.dataset.f; return renderList(); }
    if (t.dataset.canned) { const i = $("#lcIn"); i.value = C.canned[+t.dataset.canned]; return i.focus(); }
    if (t.id === "lcBack") return $("#lc").classList.remove("chatting");
    if (t.dataset.act) {
      t.disabled = true;
      try { await req(C.routes.status.replace("__id__", S.sel), "POST", { action: t.dataset.act }); await loadDetail(false); feed(); }
      catch (x) { window.toast("Gagal memperbarui percakapan"); t.disabled = false; }
    }
  });
  document.addEventListener("submit", async e => {
    if (e.target.id !== "lcSend") return; e.preventDefault();
    const i = $("#lcIn"), text = i.value.trim(); if (!text || !S.sel) return; i.value = "";
    try { await req(C.routes.send.replace("__id__", S.sel), "POST", { message: text }); S.forceBottom = true; await loadDetail(false); feed(); }
    catch (x) { i.value = text; window.toast("Pesan gagal terkirim"); }
    i.focus();
  });
  document.addEventListener("keydown", e => { if (e.target.id === "lcIn" && e.key === "Enter" && !e.shiftKey) { e.preventDefault(); $("#lcSend").requestSubmit(); } });
  $("#lcSearch").addEventListener("input", e => { S.q = e.target.value; renderList(); });
  document.addEventListener("focusout", async e => {
    if (e.target.id !== "lcNote" || !S.detail) return;
    const v = e.target.value.trim(); if (v === (S.detail.note || "")) return;
    try { await req(C.routes.note.replace("__id__", S.sel), "POST", { note: v }); S.detail.note = v; window.toast("Catatan disimpan"); } catch (x) { window.toast("Catatan gagal disimpan"); }
  });
  document.addEventListener("visibilitychange", () => { if (!document.hidden) { feed(); loadDetail(false); } });

  renderHead(); renderComposer(); renderMsgs(); renderInfo();
  feed().then(loop);
})();
