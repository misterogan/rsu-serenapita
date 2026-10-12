/* RSU Serenapita – interaksi situs publik */
(function () {
  const C = window.SERENA || {};
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = s => String(s ?? "").replace(/[&<>"']/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
  const post = (url, body) => fetch(url, { method: "POST", headers: { "Content-Type": "application/json", "Accept": "application/json", "X-CSRF-TOKEN": C.csrf }, body: JSON.stringify(body) });

  /* ---------- Loading perpindahan halaman ---------- */
  const loader = $("#loader");
  if (loader && C.loader && !reduce) {
    document.addEventListener("click", e => {
      const a = e.target.closest("a[href]");
      if (!a || a.target === "_blank" || e.ctrlKey || e.metaKey || e.shiftKey || a.hasAttribute("download")) return;
      const url = new URL(a.href, location.href);
      if (url.origin !== location.origin || (url.pathname === location.pathname && url.search === location.search)) return;
      loader.classList.add("show");
    });
    document.addEventListener("submit", e => { if (!e.target.matches("[data-noload]")) loader.classList.add("show"); });
    window.addEventListener("pageshow", () => loader.classList.remove("show"));
  }

  /* ---------- Header & menu ---------- */
  const hdr = $("header.site");
  addEventListener("scroll", () => { const y = scrollY; if (y > 60) hdr.classList.add("scrolled"); else if (y < 10) hdr.classList.remove("scrolled"); }, { passive: true });
  const mb = $("#menuBtn");
  mb && mb.addEventListener("click", () => { const o = $("#nav").classList.toggle("open"); mb.setAttribute("aria-expanded", o); });

  /* ---------- Slider ---------- */
  const slides = $$(".slide"), dots = $$("[data-go]");
  if (slides.length > 1) {
    let cur = 0;
    const show = i => { cur = i; slides.forEach((s, k) => s.classList.toggle("on", k === i)); dots.forEach((d, k) => d.setAttribute("aria-current", k === i)); };
    const timer = reduce ? null : setInterval(() => show((cur + 1) % slides.length), 7000);
    dots.forEach(d => d.addEventListener("click", () => { show(+d.dataset.go); clearInterval(timer); $(".dots").classList.add("paused"); }));
  }

  /* ---------- Formulir janji temu ---------- */
  const bf = $("#bookForm");
  if (bf) {
    const poli = $("#bPoli"), doc = $("#bDoc"), date = $("#bDate"), slots = $("#bSlots"), hint = $("#dateHint");
    const allDocs = [...doc.options].map(o => ({ v: o.value, t: o.text, p: o.dataset.poli, days: o.dataset.days }));
    const fillDocs = keep => {
      doc.innerHTML = allDocs.filter(d => d.p === poli.value).map(d => `<option value="${d.v}" data-days="${esc(d.days)}"${d.v === keep ? " selected" : ""}>${esc(d.t)}</option>`).join("");
      docChanged();
    };
    const docChanged = () => { const o = doc.selectedOptions[0]; hint.textContent = o ? "Praktik: " + o.dataset.days : ""; loadTimes(); };
    const oldTime = slots.dataset.old || "";
    const loadTimes = async () => {
      if (!doc.value || !date.value) { slots.innerHTML = '<span class="empty">Pilih dokter dan tanggal terlebih dahulu.</span>'; return; }
      slots.innerHTML = '<span class="empty">Memuat jam tersedia…</span>';
      try {
        const r = await fetch(`${C.routes.times}?doctor=${encodeURIComponent(doc.value)}&date=${encodeURIComponent(date.value)}`, { headers: { Accept: "application/json" } });
        const j = await r.json();
        slots.innerHTML = j.times && j.times.length
          ? j.times.map(t => `<label><input type="radio" name="time" value="${t}" ${t === oldTime ? "checked" : ""} required><span>${t}</span></label>`).join("")
          : `<span class="err-text">${esc(j.message || "Tidak ada jam tersedia.")}</span>`;
      } catch (e) { slots.innerHTML = '<span class="err-text">Jam tersedia gagal dimuat. Periksa koneksi lalu coba lagi.</span>'; }
    };
    poli.addEventListener("change", () => fillDocs());
    doc.addEventListener("change", docChanged);
    date.addEventListener("change", loadTimes);
    const pay = () => { const bpjs = $('input[name="payment"]:checked')?.value === "BPJS Kesehatan"; $$(".bpjs-only").forEach(el => el.hidden = !bpjs); };
    $$('input[name="payment"]').forEach(r => r.addEventListener("change", pay));
    pay();
    fillDocs(doc.dataset.selected || "");
  }

  /* ---------- Chat widget ---------- */
  const chat = $("#chat"); if (!chat) return;
  const fab = $("#chatFab");
  const open = () => { chat.classList.add("open"); fab.classList.add("hide"); fab.setAttribute("aria-expanded", "true"); if (!$("#botLog").children.length) botMsg(C.bot.greeting, "bot"); setTimeout(() => $("#botIn")?.focus(), 200); };
  const close = () => { chat.classList.remove("open"); fab.classList.remove("hide"); fab.setAttribute("aria-expanded", "false"); fab.focus(); };
  fab.addEventListener("click", open);
  $("#chatClose").addEventListener("click", close);
  $$("[data-open-chat]").forEach(a => a.addEventListener("click", e => { e.preventDefault(); open(); }));
  document.addEventListener("keydown", e => { if (e.key === "Escape" && chat.classList.contains("open")) close(); });
  $$("[data-ct]").forEach(b => b.addEventListener("click", () => {
    const live = b.dataset.ct === "live";
    $$("[data-ct]").forEach(x => x.setAttribute("aria-selected", x === b));
    $("#botPane").hidden = live; $("#livePane").hidden = !live;
    $(".chat-head small").textContent = live ? "Live chat dengan petugas" : "Asisten informasi";
    if (live) Live.start(); else Live.stop();
  }));

  /* Chatbot */
  const history = [];
  function botMsg(t, who) { const m = document.createElement("div"); m.className = "msg " + who; m.textContent = t; $("#botLog").appendChild(m); $("#botLog").scrollTop = 1e9; return m; }
  let busy = false;
  async function ask(q) {
    if (busy || !q) return; busy = true;
    botMsg(q, "me");
    const m = botMsg("", "bot"); m.innerHTML = '<span class="typing" aria-label="Mengetik"><i></i><i></i><i></i></span>';
    try {
      const r = await post(C.routes.chatbot, { message: q, history });
      const j = await r.json();
      m.textContent = j.answer || "Maaf, terjadi kendala. Coba lagi sebentar.";
      history.push({ role: "user", content: q }, { role: "assistant", content: m.textContent });
    } catch (e) { m.textContent = "Maaf, koneksi bermasalah. Coba lagi sebentar."; }
    busy = false; $("#botLog").scrollTop = 1e9;
  }
  $("#botForm")?.addEventListener("submit", e => { e.preventDefault(); const i = $("#botIn"); const v = i.value.trim(); i.value = ""; ask(v); });
  $("#botQuick")?.addEventListener("click", e => { if (e.target.tagName === "BUTTON") ask(e.target.textContent); });

  /* Live chat pengunjung */
  const Live = (() => {
    const root = $("#livePane"); const KEY = "serenapita_livechat_token";
    const st = { token: null, conv: null, msgs: [], last: 0, timer: null, busy: false, form: false };
    try { st.token = localStorage.getItem(KEY); } catch (e) {}
    const TOPICS = ["Informasi umum", "Jadwal dokter", "Janji temu", "BPJS dan administrasi", "Lainnya"];
    const statusText = s => s === "aktif" ? "Terhubung dengan " + esc(C.livechat.label) : s === "selesai" ? "Chat selesai" : "Menunggu petugas…";

    async function start() {
      if (!C.livechat.enabled) { root.innerHTML = `<div class="vc-na"><b>Live chat sedang tidak aktif.</b>Silakan hubungi kami di ${esc(C.phone)}.</div>`; return; }
      if (!st.token) { render(); return; }
      if (!st.conv) { root.innerHTML = '<div class="vc-na">Menghubungkan…</div>'; await poll(); }
      schedule();
    }
    function stop() { clearTimeout(st.timer); st.timer = null; }
    function schedule() { stop(); if (st.token && !document.hidden) st.timer = setTimeout(async () => { await poll(); schedule(); }, C.pollMs || 2500); }
    document.addEventListener("visibilitychange", () => { if (!$("#livePane").hidden && chat.classList.contains("open")) document.hidden ? stop() : schedule(); });

    async function poll() {
      try {
        const r = await fetch(`${C.routes.lcPoll}?token=${encodeURIComponent(st.token)}&after=${st.last}`, { headers: { Accept: "application/json" } });
        if (r.status === 404) { forget(); render(); return; }
        const j = await r.json(); apply(j);
      } catch (e) {}
    }
    function forget() { st.token = null; st.conv = null; st.msgs = []; st.last = 0; try { localStorage.removeItem(KEY); } catch (e) {} }
    function apply(j) {
      const prevStatus = st.conv?.status;
      st.conv = { status: j.status, name: j.name, phone: j.phone, topic: j.topic };
      (j.messages || []).forEach(m => { if (m.id > st.last) { st.msgs.push(m); st.last = m.id; } });
      if (prevStatus !== j.status || !$(".vc-log", root)) render(); else renderMsgs();
    }
    function render() {
      const c = st.conv;
      if (!c || st.form) {
        root.innerHTML = `<form class="vc-form" novalidate data-noload>
          <p class="vc-intro">Isi nama dan nomor HP agar petugas bisa mengenali dan menghubungi Anda kembali. Jam layanan ${esc(C.livechat.hours)}.</p>
          <label>Nama lengkap<input name="name" autocomplete="name" value="${esc(c?.name)}"></label>
          <label>Nomor HP / WhatsApp<input name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="08xxxxxxxxxx" value="${esc(c?.phone)}"></label>
          <label>Topik<select name="topic">${TOPICS.map(t => `<option${t === c?.topic ? " selected" : ""}>${t}</option>`).join("")}</select></label>
          <label>Pesan<textarea name="message" rows="3" placeholder="Tulis pertanyaan Anda"></textarea></label>
          <label class="vc-chk"><input type="checkbox" name="consent"> Saya setuju nama dan nomor HP saya disimpan untuk keperluan layanan ini.</label>
          <div class="vc-err" role="alert"></div>
          <button class="vc-btn" type="submit">Mulai chat</button>${c ? '<button type="button" class="vc-link" data-vc="cancel">Kembali ke riwayat chat</button>' : ""}
        </form>`;
        return;
      }
      const draft = $(".vc-in", root)?.value || "";
      root.innerHTML = `<div class="vc-head"><span class="vc-st ${c.status}"></span><div><b>${statusText(c.status)}</b><small>${esc(c.name)} · ${esc(c.phone)}</small></div>${c.status !== "selesai" ? '<button class="vc-link" data-vc="end">Akhiri chat</button>' : ""}</div>
        <div class="vc-log" aria-live="polite"></div>
        ${c.status === "selesai" ? '<div class="vc-foot"><button class="vc-btn" data-vc="new">Mulai chat baru</button></div>'
          : '<form class="vc-send" data-noload><textarea class="vc-in" rows="1" placeholder="Tulis pesan" aria-label="Pesan"></textarea><button class="vc-btn" type="submit">Kirim</button></form><div class="vc-err" role="alert" style="padding:0 14px;background:var(--surface)"></div>'}`;
      const ta = $(".vc-in", root); if (ta) ta.value = draft;
      renderMsgs();
    }
    function renderMsgs() {
      const lg = $(".vc-log", root); if (!lg) return;
      let h = st.msgs.map(m => m.from === "system" ? `<div class="vc-sys">${esc(m.text)}</div>`
        : m.from === "visitor" ? `<div class="vc-m me">${esc(m.text)}<time>${esc(m.time)}</time></div>`
        : `<div class="vc-m ag"><small>${esc(m.label || C.livechat.label)}</small>${esc(m.text)}<time>${esc(m.time)}</time></div>`).join("");
      if (st.conv?.status === "menunggu") h += '<div class="vc-sys">Pesan Anda sudah diterima. Petugas akan segera membalas.</div>';
      const atBottom = lg.scrollHeight - lg.scrollTop - lg.clientHeight < 60;
      lg.innerHTML = h; if (atBottom || st.justSent) lg.scrollTop = lg.scrollHeight; st.justSent = false;
    }
    async function errors(r, el) {
      try { const j = await r.json(); el.textContent = j.errors ? Object.values(j.errors)[0][0] : (j.message || "Terjadi kesalahan."); }
      catch (e) { el.textContent = "Terjadi kesalahan. Coba lagi."; }
    }
    root.addEventListener("submit", async e => {
      e.preventDefault(); if (st.busy) return;
      const f = e.target, err = $(".vc-err", root);
      if (f.classList.contains("vc-form")) {
        const F = f.elements;
        st.busy = true; f.querySelector("button[type=submit]").disabled = true; err.textContent = "";
        try {
          const r = await post(C.routes.lcStart, { name: F.name.value, phone: F.phone.value, topic: F.topic.value, message: F.message.value, consent: F.consent.checked ? 1 : 0, token: st.token });
          if (!r.ok) { await errors(r, err); f.querySelector("button[type=submit]").disabled = false; }
          else { const j = await r.json(); st.token = j.token; try { localStorage.setItem(KEY, j.token); } catch (x) {} st.msgs = []; st.last = 0; st.form = false; st.justSent = true; apply(j); schedule(); }
        } catch (x) { err.textContent = "Koneksi bermasalah. Coba lagi."; f.querySelector("button[type=submit]").disabled = false; }
        st.busy = false; return;
      }
      if (f.classList.contains("vc-send")) {
        const ta = $(".vc-in", root), text = ta.value.trim(); if (!text) return;
        st.busy = true; ta.value = "";
        try {
          const r = await post(C.routes.lcSend, { token: st.token, message: text });
          if (!r.ok) { ta.value = text; await errors(r, err); } else { err.textContent = ""; st.justSent = true; await poll(); }
        } catch (x) { ta.value = text; err.textContent = "Pesan gagal terkirim. Coba lagi."; }
        st.busy = false; ta.focus();
      }
    });
    root.addEventListener("keydown", e => { if (e.target.classList.contains("vc-in") && e.key === "Enter" && !e.shiftKey) { e.preventDefault(); e.target.form.requestSubmit(); } });
    root.addEventListener("click", async e => {
      const b = e.target.closest("[data-vc]"); if (!b) return;
      if (b.dataset.vc === "new") { st.form = true; render(); }
      if (b.dataset.vc === "cancel") { st.form = false; render(); }
      if (b.dataset.vc === "end") { await post(C.routes.lcEnd, { token: st.token }); await poll(); }
    });
    return { start, stop };
  })();
})();
