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

/* ---------- Editor teks (WYSIWYG) ---------- */
(function () {
  const editors = document.querySelectorAll("[data-rte]");
  if (!editors.length) return;
  try { document.execCommand("defaultParagraphSeparator", false, "p"); } catch (e) {}
  const KEEP = { P: 1, BR: 1, STRONG: 1, B: 1, EM: 1, I: 1, U: 1, UL: 1, OL: 1, LI: 1, A: 1, H3: 1, H4: 1, BLOCKQUOTE: 1 };
  const DROP = { SCRIPT: 1, STYLE: 1, META: 1, LINK: 1, TITLE: 1, IMG: 1, SVG: 1, IFRAME: 1, OBJECT: 1, VIDEO: 1, AUDIO: 1 };
  const MAP = { DIV: "P", H1: "H3", H2: "H3", H5: "H4", H6: "H4" };
  // Membersihkan teks yang ditempel dari Word, WhatsApp Web, atau situs lain
  function cleanNode(node) {
    [...node.childNodes].forEach(ch => {
      if (ch.nodeType === 8) { ch.remove(); return; }
      if (ch.nodeType !== 1) return;
      if (DROP[ch.tagName]) { ch.remove(); return; }
      let el = ch;
      if (MAP[el.tagName]) { const n = document.createElement(MAP[el.tagName]); while (el.firstChild) n.appendChild(el.firstChild); el.replaceWith(n); el = n; }
      cleanNode(el);
      if (!KEEP[el.tagName]) { el.replaceWith(...el.childNodes); return; }
      const href = el.tagName === "A" ? el.getAttribute("href") : null;
      [...el.attributes].forEach(a => el.removeAttribute(a.name));
      if (href && /^(https?:|mailto:|tel:|\/)/i.test(href)) el.setAttribute("href", href);
    });
  }
  const esc = s => s.replace(/[&<>]/g, c => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;" }[c]));
  function textToHtml(t) {
    return t.replace(/\r/g, "").split(/\n\s*\n/).map(block => {
      const lines = block.split("\n").map(l => l.trim()).filter(Boolean);
      if (!lines.length) return "";
      if (lines.every(l => /^[•\-\*·]\s*/.test(l))) return "<ul>" + lines.map(l => "<li>" + esc(l.replace(/^[•\-\*·]\s*/, "")) + "</li>").join("") + "</ul>";
      if (lines.every(l => /^\d{1,2}[.)]\s+/.test(l))) return "<ol>" + lines.map(l => "<li>" + esc(l.replace(/^\d{1,2}[.)]\s+/, "")) + "</li>").join("") + "</ol>";
      return "<p>" + lines.map(esc).join("<br>") + "</p>";
    }).join("");
  }
  editors.forEach(box => {
    const area = box.querySelector(".rte-area"), ta = box.querySelector("textarea"), bar = box.querySelector(".rte-bar");
    const sync = () => { const h = area.innerHTML.trim(); ta.value = /^(<p>(<br>)?<\/p>|<br>)?$/i.test(h) ? "" : h; };
    const refresh = () => bar.querySelectorAll("[data-cmd]").forEach(b => {
      const c = b.dataset.cmd; let on = false;
      try { on = ["bold", "italic", "underline"].includes(c) && document.queryCommandState(c); } catch (e) {}
      if (c === "insertUnorderedList" || c === "insertOrderedList") { const s2 = getSelection(); const n = s2.rangeCount && s2.anchorNode; const l = n && area.contains(n) && (n.nodeType === 1 ? n : n.parentElement).closest("ul,ol"); on = !!l && l.nodeName === (c === "insertOrderedList" ? "OL" : "UL"); }
      if (c === "h3") { try { on = /^h3$/i.test(document.queryCommandValue("formatBlock")); } catch (e) {} }
      b.classList.toggle("on", !!on);
    });
    // Daftar poin / bernomor dibuat langsung, supaya hasilnya rapi di semua browser
    function toggleList(tag) {
      const sel = getSelection(); if (!sel.rangeCount) return;
      const range = sel.getRangeAt(0);
      let tops = [...area.childNodes].filter(n => range.intersectsNode(n) && !(n.nodeType === 3 && !n.textContent.trim()));
      if (!tops.length) { const p = document.createElement("p"); p.innerHTML = "<br>"; area.appendChild(p); tops = [p]; }
      const caretLi = (sel.anchorNode.nodeType === 1 ? sel.anchorNode : sel.anchorNode.parentElement).closest("li");
      let focus = null;
      if (tops.length === 1 && /^(UL|OL)$/.test(tops[0].nodeName) && caretLi && tops[0].contains(caretLi)) {
        const list = tops[0];
        if (list.nodeName === tag) {
          // Matikan daftar untuk poin yang sedang dipilih: poin menjadi paragraf, daftar dipisah bila perlu
          const items = [...list.children].filter(li => range.intersectsNode(li));
          const after = []; let nx = items[items.length - 1].nextSibling; while (nx) { after.push(nx); nx = nx.nextSibling; }
          let anchor = list;
          items.forEach(li => { const p = document.createElement("p"); while (li.firstChild) p.appendChild(li.firstChild); if (!p.childNodes.length) p.innerHTML = "<br>"; anchor.after(p); anchor = p; focus = focus || p; li.remove(); });
          if (after.length) { const nl = document.createElement(tag); after.forEach(n => nl.appendChild(n)); anchor.after(nl); }
          if (!list.children.length) list.remove();
        } else {
          const nl = document.createElement(tag); while (list.firstChild) nl.appendChild(list.firstChild); list.replaceWith(nl); focus = caretLi;
        }
      } else {
        let list = null;
        tops.forEach(n => {
          if (/^(UL|OL)$/.test(n.nodeName)) { if (!list) { list = document.createElement(tag); n.before(list); } while (n.firstChild) list.appendChild(n.firstChild); n.remove(); return; }
          if (!list) { list = document.createElement(tag); n.before(list); }
          const li = document.createElement("li");
          if (n.nodeType === 3) li.appendChild(n); else { while (n.firstChild) li.appendChild(n.firstChild); n.remove(); }
          if (!li.textContent.length && !li.querySelector("br")) li.innerHTML = "<br>";
          list.appendChild(li); focus = focus || li;
        });
        const prev = list.previousElementSibling; if (prev && prev.nodeName === tag) { while (list.firstChild) prev.appendChild(list.firstChild); list.remove(); }
      }
      if (focus) { const r = document.createRange(); r.selectNodeContents(focus); r.collapse(false); sel.removeAllRanges(); sel.addRange(r); }
    }
    bar.addEventListener("mousedown", e => { if (e.target.closest("button")) e.preventDefault(); });
    bar.addEventListener("click", e => {
      const b = e.target.closest("[data-cmd]"); if (!b) return;
      area.focus(); const c = b.dataset.cmd;
      if (c === "h3") {
        let cur = ""; try { cur = document.queryCommandValue("formatBlock"); } catch (x) {}
        document.execCommand("formatBlock", false, /^h3$/i.test(cur) ? "p" : "h3");
      } else if (c === "link") {
        const url = prompt("Alamat tautan (contoh: https://rsuserenapita.id/jadwal):", "https://");
        if (url && url !== "https://") document.execCommand("createLink", false, url.trim());
      } else if (c === "insertUnorderedList" || c === "insertOrderedList") {
        toggleList(c === "insertOrderedList" ? "OL" : "UL");
      } else if (c === "removeFormat") {
        document.execCommand("removeFormat"); document.execCommand("unlink");
      } else {
        document.execCommand(c);
      }
      sync(); refresh();
    });
    // Enter pada poin yang kosong: keluar dari daftar dan kembali ke paragraf biasa
    area.addEventListener("keydown", e => {
      if (e.key !== "Enter" || e.shiftKey) return;
      const sel = getSelection(); if (!sel.rangeCount) return;
      const n = sel.anchorNode; const li = (n && (n.nodeType === 1 ? n : n.parentElement))?.closest("li");
      if (!li || !area.contains(li) || li.textContent.trim() !== "" || li.querySelector("ul,ol")) return;
      e.preventDefault();
      const list = li.parentElement, p = document.createElement("p");
      p.appendChild(document.createElement("br"));
      const rest = []; let nx = li.nextSibling; while (nx) { rest.push(nx); nx = nx.nextSibling; }
      list.after(p);
      if (rest.length) { const nl = document.createElement(list.tagName); rest.forEach(r => nl.appendChild(r)); p.after(nl); }
      li.remove(); if (!list.children.length) list.remove();
      const r = document.createRange(); r.setStart(p, 0); r.collapse(true); sel.removeAllRanges(); sel.addRange(r);
      sync(); refresh();
    });
    // Ketik "- ", "• ", atau "1. " di awal baris untuk langsung membuat daftar
    area.addEventListener("keydown", e => {
      if (e.key !== " ") return;
      const sel = getSelection(); if (!sel.rangeCount || !sel.isCollapsed) return;
      const at = n => (n.nodeType === 1 ? n : n.parentElement).closest("p,h3,h4,div,li,.rte-area");
      let blk = at(sel.anchorNode);
      if (!blk || !area.contains(blk) || blk.tagName === "LI") return;
      const textBefore = b => { const r = document.createRange(); r.selectNodeContents(b); r.setEnd(sel.anchorNode, sel.anchorOffset); return r; };
      const before = textBefore(blk).toString();
      const cmd = /^[-•*]$/.test(before) ? "insertUnorderedList" : (/^1[.)]$/.test(before) ? "insertOrderedList" : null);
      if (!cmd) return;
      e.preventDefault();
      if (blk === area) { document.execCommand("formatBlock", false, "p"); blk = at(getSelection().anchorNode); if (!blk || blk === area) return; }
      textBefore(blk).deleteContents();
      const list = document.createElement(cmd === "insertOrderedList" ? "ol" : "ul"), li = document.createElement("li");
      while (blk.firstChild) li.appendChild(blk.firstChild);
      if (!li.textContent.length) li.innerHTML = "<br>";
      list.appendChild(li); blk.replaceWith(list);
      const prev = list.previousElementSibling;
      if (prev && prev.tagName === list.tagName) { prev.appendChild(li); list.remove(); }
      const r = document.createRange(); r.setStart(li, 0); r.collapse(true); sel.removeAllRanges(); sel.addRange(r);
      sync(); refresh();
    });
    area.addEventListener("paste", e => {
      const cd = e.clipboardData; if (!cd) return;
      e.preventDefault();
      const html = cd.getData("text/html"), text = cd.getData("text/plain");
      let out;
      if (html) { const d = new DOMParser().parseFromString(html, "text/html"); cleanNode(d.body); out = d.body.innerHTML; }
      else out = textToHtml(text || "");
      document.execCommand("insertHTML", false, out); sync();
    });
    ["input", "blur"].forEach(ev => area.addEventListener(ev, sync));
    ["keyup", "mouseup", "focus"].forEach(ev => area.addEventListener(ev, refresh));
    const form = box.closest("form"); if (form) form.addEventListener("submit", sync);
    sync();
  });
})();
