/* Tur panduan interaktif: menyorot tombol/kolom satu per satu dengan penjelasan, lalu Lanjut ke elemen berikutnya.
   Halaman mengisi window.TOUR = { key, guide, steps: [{ el, title, text }] } lewat @push('tour').
   Langkah tanpa "el" tampil di tengah layar. Elemen yang tidak terlihat dilewati otomatis. */
(function () {
  const cfg = window.TOUR; if (!cfg || !cfg.steps?.length) return;
  const reduce = matchMedia("(prefers-reduced-motion: reduce)").matches;
  /* Tur otomatis paling banyak sekali sehari (di halaman mana pun); selebihnya lewat tombol Panduan */
  const seenKey = "tour:auto";
  const d = new Date(), today = `${d.getFullYear()}-${d.getMonth() + 1}-${d.getDate()}`;
  let steps = [], at = 0, root, hole, tip, lastFocus;

  const shown = el => el && el.getClientRects().length > 0 && !el.closest("[hidden]");
  const esc = s => String(s).replace(/[&<>"]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;" }[c]));

  function build() {
    root = document.createElement("div");
    root.className = "tour"; root.setAttribute("role", "dialog"); root.setAttribute("aria-modal", "true"); root.setAttribute("aria-labelledby", "tourTitle");
    root.innerHTML = '<div class="tour-hole"></div><div class="tour-tip" tabindex="-1"><div class="tour-top"><span class="tour-n"></span>'
      + '<button type="button" class="tour-x" aria-label="Tutup panduan">×</button></div><h3 id="tourTitle"></h3><div class="tour-text"></div>'
      + '<div class="tour-dots" aria-hidden="true"></div><div class="tour-acts"><button type="button" class="btn sm tour-skip">Lewati</button>'
      + '<button type="button" class="btn sm tour-prev">Kembali</button><button type="button" class="btn sm pri tour-next">Lanjut</button></div></div>';
    document.body.appendChild(root);
    hole = root.querySelector(".tour-hole"); tip = root.querySelector(".tour-tip");
    root.querySelector(".tour-x").onclick = root.querySelector(".tour-skip").onclick = () => end();
    root.querySelector(".tour-prev").onclick = () => go(at - 1);
    root.querySelector(".tour-next").onclick = () => at < steps.length - 1 ? go(at + 1) : end();
  }

  function place() {
    const s = steps[at]; if (!s) return;
    const el = s.el ? document.querySelector(s.el) : null;
    const vw = innerWidth, vh = innerHeight, pad = 6, gap = 14, mobile = vw < 640;
    tip.classList.toggle("center", !el);
    if (!el) { hole.style.cssText = `left:${vw / 2}px;top:${vh / 2}px;width:0;height:0`; tip.style.cssText = ""; return; }
    const r = el.getBoundingClientRect();
    hole.style.cssText = `left:${r.left - pad}px;top:${r.top - pad}px;width:${r.width + pad * 2}px;height:${r.height + pad * 2}px`;
    if (mobile) { tip.style.cssText = ""; tip.classList.add("dock"); return; }
    tip.classList.remove("dock");
    const tw = tip.offsetWidth, th = tip.offsetHeight;
    let top = r.bottom + pad + gap;
    if (top + th > vh - 10) top = r.top - pad - gap - th;
    if (top < 10) top = Math.min(vh - th - 10, Math.max(10, r.top + r.height / 2 - th / 2));
    const left = Math.min(vw - tw - 12, Math.max(12, r.left + r.width / 2 - tw / 2));
    tip.style.cssText = `left:${left}px;top:${top}px`;
  }

  function go(i) {
    if (i < 0 || i >= steps.length) return;
    at = i;
    const s = steps[at], el = s.el ? document.querySelector(s.el) : null;
    root.querySelector(".tour-n").textContent = `Langkah ${at + 1} dari ${steps.length}`;
    root.querySelector("#tourTitle").textContent = s.title;
    root.querySelector(".tour-text").innerHTML = s.text;
    root.querySelector(".tour-dots").innerHTML = steps.map((_, k) => `<i class="${k === at ? "on" : ""}"></i>`).join("");
    root.querySelector(".tour-prev").hidden = at === 0;
    const last = at === steps.length - 1;
    root.querySelector(".tour-next").textContent = last ? "Selesai" : "Lanjut";
    root.querySelector(".tour-skip").hidden = last;
    if (el) el.scrollIntoView({ block: "center", inline: "nearest", behavior: reduce ? "auto" : "smooth" });
    place();
    setTimeout(place, reduce ? 0 : 380);
    tip.focus({ preventScroll: true });
  }

  function start() {
    steps = cfg.steps.filter(s => !s.el || shown(document.querySelector(s.el)));
    if (cfg.guide) steps.push({ title: "Butuh penjelasan lebih lengkap?", text: `Panduan tertulis berisi langkah demi langkah dan tanya jawab. <a href="${esc(cfg.guide)}">Buka panduan lengkap</a>. Tur ini bisa diulang kapan saja lewat tombol <b>Panduan</b> di kanan atas.`, el: shown(document.querySelector("#helpBtn")) ? "#helpBtn" : null });
    if (!root) build();
    lastFocus = document.activeElement;
    try { localStorage.setItem(seenKey, today); } catch (e) {}
    root.classList.add("on"); document.documentElement.classList.add("tour-lock");
    go(0);
  }

  function end() {
    root.classList.remove("on"); document.documentElement.classList.remove("tour-lock");
    lastFocus?.focus?.({ preventScroll: true });
  }

  document.addEventListener("keydown", e => {
    if (!root?.classList.contains("on")) return;
    if (e.key === "Escape") { e.stopImmediatePropagation(); end(); }
    else if (e.key === "ArrowRight" || (e.key === "Enter" && e.target.tagName !== "BUTTON" && e.target.tagName !== "A")) { e.preventDefault(); at < steps.length - 1 ? go(at + 1) : end(); }
    else if (e.key === "ArrowLeft") { e.preventDefault(); go(at - 1); }
    else if (e.key === "Tab") { /* fokus tetap di kotak panduan */
      const f = [...tip.querySelectorAll("button:not([hidden]), a[href]")]; if (!f.length) return;
      const i = f.indexOf(document.activeElement);
      if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
      else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
    }
  }, true);
  addEventListener("resize", () => root?.classList.contains("on") && place());
  addEventListener("scroll", () => root?.classList.contains("on") && place(), true);

  /* Tombol Panduan di kanan atas memulai tur; Ctrl/⌘+klik tetap membuka halaman panduan tertulis */
  document.addEventListener("click", e => {
    const b = e.target.closest("#helpBtn");
    if (!b || e.ctrlKey || e.metaKey || e.shiftKey || root?.classList.contains("on")) return;
    e.preventDefault(); e.stopImmediatePropagation(); start();
  }, true);
  const btn = document.getElementById("helpBtn");
  if (btn) btn.title = "Tur panduan halaman ini (Ctrl+klik untuk panduan tertulis)";

  /* Halaman Farmasi pertama yang dibuka hari ini: tur dimulai otomatis */
  let seen = true; try { seen = localStorage.getItem(seenKey) === today; } catch (e) {}
  if (!seen) setTimeout(start, 700);
  window.startTour = start;
})();
