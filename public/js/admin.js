/* Backoffice – interaksi umum */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
  const loader = $("#loader");
  if (loader && !reduce) {
    document.addEventListener("click", e => {
      const a = e.target.closest("a[href]");
      if (!a || a.target === "_blank" || e.ctrlKey || e.metaKey || a.getAttribute("href").startsWith("#")) return;
      const url = new URL(a.href, location.href);
      if (url.origin !== location.origin || url.href === location.href) return;
      $("#ldText").textContent = a.dataset.label ? "Membuka " + a.dataset.label.toLowerCase() + "…" : "Memuat…";
      loader.classList.add("show");
    });
    document.addEventListener("submit", e => { if (!e.defaultPrevented && !e.target.matches("[data-noload]")) { $("#ldText").textContent = "Menyimpan…"; loader.classList.add("show"); } });
    addEventListener("pageshow", () => loader.classList.remove("show"));
  }
  $("#burger")?.addEventListener("click", () => $("#side").classList.toggle("open"));

  /* Konfirmasi hapus */
  const modal = $("#modal"); let pending = null;
  document.addEventListener("submit", e => {
    const f = e.target; if (!f.matches("[data-confirm]") || f.dataset.ok) return;
    e.preventDefault(); pending = f;
    $("#mdText").textContent = f.dataset.confirm; modal.classList.add("on"); setTimeout(() => $("#mdNo").focus(), 50);
  }, true);
  $("#mdNo")?.addEventListener("click", () => { modal.classList.remove("on"); pending = null; });
  $("#mdYes")?.addEventListener("click", () => { if (pending) { pending.dataset.ok = "1"; modal.classList.remove("on"); pending.requestSubmit(); } });
  document.addEventListener("keydown", e => { if (e.key === "Escape") { modal?.classList.remove("on"); $("#side")?.classList.remove("open"); } });

  /* Toast */
  window.toast = (m) => { const t = document.createElement("div"); t.className = "toast"; t.textContent = m; $("#toasts").appendChild(t); setTimeout(() => { t.style.transition = "opacity .3s"; t.style.opacity = 0; setTimeout(() => t.remove(), 300); }, 3000); };

  /* Badge live chat di sidebar */
  const badge = $("#lcBadge"); const url = document.body.dataset.lcCount;
  if (badge && url) {
    let last = +badge.dataset.n || 0;
    const tick = async () => {
      if (document.hidden) return;
      try {
        const j = await (await fetch(url, { headers: { Accept: "application/json" } })).json();
        badge.textContent = j.count || ""; badge.hidden = !j.count;
        if (j.count > last && !location.pathname.includes("/admin/livechat")) toast("Ada pesan live chat baru");
        last = j.count;
      } catch (e) {}
    };
    tick(); setInterval(tick, 15000);
  }

  /* Urutan menu situs (naik/turun) */
  const rows = $("#menuRows");
  if (rows) {
    const sync = () => {
      $("#menuOrder").value = [...rows.children].map(li => li.dataset.id).join(",");
      [...rows.children].forEach((li, i, all) => { li.querySelector("[data-up]").disabled = i === 0; li.querySelector("[data-down]").disabled = i === all.length - 1; li.classList.toggle("hid", !li.querySelector("[data-vis]").checked); });
      const prev = $("#navPrev .items");
      prev.innerHTML = [...rows.children].filter(li => li.querySelector("[data-vis]").checked).map((li, i) => `<span class="${i === 0 ? "first" : ""}">${li.querySelector("input[type=text]").value.replace(/[<>&]/g, "")}</span>`).join("");
    };
    rows.addEventListener("click", e => {
      const b = e.target.closest("[data-up],[data-down]"); if (!b) return;
      const li = b.closest("li");
      if (b.dataset.up !== undefined && li.previousElementSibling) li.parentNode.insertBefore(li, li.previousElementSibling);
      if (b.dataset.down !== undefined && li.nextElementSibling) li.parentNode.insertBefore(li.nextElementSibling, li);
      sync();
    });
    rows.addEventListener("input", sync); rows.addEventListener("change", sync); sync();
  }

  /* Baris jam pelayanan & nomor telepon (tambah/hapus) */
  document.addEventListener("click", e => {
    const add = e.target.closest("[data-add-row]");
    if (add) {
      const tb = $(add.dataset.addRow), tpl = $(add.dataset.tpl);
      const i = Date.now();
      tb.insertAdjacentHTML("beforeend", tpl.innerHTML.replaceAll("__i__", i));
      tb.lastElementChild.querySelector("input")?.focus();
    }
    const del = e.target.closest("[data-del-row]");
    if (del) { const tb = del.closest("tbody,div[data-rows]"); if (tb.children.length > 1) del.closest("tr,[data-row]").remove(); }
  });

  /* Pratinjau logo sebelum disimpan */
  document.querySelectorAll("input[type=file][data-preview]").forEach(inp => inp.addEventListener("change", () => {
    const f = inp.files[0], box = $(inp.dataset.preview); if (!f || !box) return;
    const r = new FileReader(); r.onload = () => box.innerHTML = `<img src="${r.result}" alt="">`; r.readAsDataURL(f);
  }));

  /* Uji chatbot */
  const bf = $("#botTest");
  if (bf) bf.addEventListener("submit", async e => {
    e.preventDefault();
    const inp = $("#botIn"), q = inp.value.trim(); if (!q) return; inp.value = "";
    const log = $("#botLog"), add = (t, c) => { const m = document.createElement("div"); m.className = "msg " + c; m.textContent = t; log.appendChild(m); log.scrollTop = 1e9; return m; };
    add(q, "me"); const out = add("…", "bot");
    try {
      const r = await fetch(bf.action, { method: "POST", headers: { "Content-Type": "application/json", Accept: "application/json", "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content }, body: JSON.stringify({ message: q }) });
      out.textContent = (await r.json()).answer || "Tidak ada jawaban.";
    } catch (x) { out.textContent = "Uji coba gagal. Coba lagi."; }
  });
})();
