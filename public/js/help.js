/* Panduan backoffice: pencarian cepat per bagian */
(function () {
  const body = document.getElementById("helpBody"), q = document.getElementById("helpQ");
  const count = document.getElementById("helpCount"), empty = document.getElementById("helpEmpty");
  const fold = s => s.toLowerCase().normalize("NFD").replace(/[\u0300-\u036f]/g, "");

  // Kelompokkan isi setiap bab menjadi bagian-bagian (dipisah judul H2)
  const units = [];
  body.querySelectorAll("section.doc").forEach(sec => {
    const nodes = [...sec.children]; sec.innerHTML = "";
    const newPart = () => { const d = document.createElement("div"); d.className = "doc-part"; sec.appendChild(d); return d; };
    let cur = newPart();
    nodes.forEach(n => {
      if (n.tagName === "H2") {
        const prev = cur.lastElementChild;
        cur = newPart();
        // Bawa penanda tautan (anchor) yang tepat berada sebelum judul
        if (prev && prev.tagName === "P" && prev.querySelector("a.anchor") && !prev.textContent.trim()) cur.appendChild(prev);
      }
      cur.appendChild(n);
    });
    sec.querySelectorAll(".doc-part").forEach(p => {
      if (!p.textContent.trim()) { p.remove(); return; }
      p.dataset.text = fold(p.textContent); units.push({ el: p, sec });
    });
  });

  function clearMarks() { body.querySelectorAll("mark.hl").forEach(m => m.replaceWith(document.createTextNode(m.textContent))); body.normalize(); }
  function mark(el, words) {
    const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT, { acceptNode: n => n.parentNode.closest("script,style,mark") ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT });
    const texts = []; while (walker.nextNode()) texts.push(walker.currentNode);
    texts.forEach(t => {
      const f = fold(t.textContent); let ranges = [];
      words.forEach(w => { let i = f.indexOf(w); while (i > -1) { ranges.push([i, i + w.length]); i = f.indexOf(w, i + w.length); } });
      if (!ranges.length) return;
      ranges.sort((a, b) => a[0] - b[0]);
      const frag = document.createDocumentFragment(); let pos = 0, s = t.textContent;
      ranges.forEach(([a, b]) => { if (a < pos) return; frag.append(s.slice(pos, a)); const m = document.createElement("mark"); m.className = "hl"; m.textContent = s.slice(a, b); frag.append(m); pos = b; });
      frag.append(s.slice(pos)); t.replaceWith(frag);
    });
  }

  let timer;
  function run() {
    clearMarks();
    const words = fold(q.value).split(/\s+/).filter(w => w.length > 1);
    const tocCounts = {};
    if (!words.length) {
      units.forEach(u => u.el.hidden = false); body.querySelectorAll("section.doc").forEach(s => s.hidden = false);
      document.querySelectorAll("[data-toc]").forEach(a => { a.hidden = false; a.querySelector("em").textContent = ""; });
      count.textContent = ""; empty.hidden = true; return;
    }
    let total = 0;
    units.forEach(u => {
      const ok = words.every(w => u.el.dataset.text.includes(w));
      u.el.hidden = !ok;
      if (ok) { total++; const id = u.sec.dataset.chapter; tocCounts[id] = (tocCounts[id] || 0) + 1; mark(u.el, words); }
    });
    body.querySelectorAll("section.doc").forEach(s => s.hidden = !tocCounts[s.dataset.chapter]);
    document.querySelectorAll("[data-toc]").forEach(a => { const n = tocCounts[a.dataset.toc] || 0; a.hidden = !n; a.querySelector("em").textContent = n || ""; });
    count.textContent = total ? `${total} bagian cocok` : "";
    empty.hidden = total > 0;
    const first = body.querySelector("mark.hl"); if (first && !q.dataset.noScroll) first.scrollIntoView({ block: "center", behavior: "smooth" });
  }
  q.addEventListener("input", () => { clearTimeout(timer); timer = setTimeout(run, 180); });
  q.addEventListener("keydown", e => { if (e.key === "Escape") { q.value = ""; run(); } });
  document.addEventListener("keydown", e => { if (e.key === "/" && document.activeElement !== q && !/input|textarea|select/i.test(document.activeElement.tagName)) { e.preventDefault(); q.focus(); } });

  // Tandai bab yang sedang dibaca di daftar isi
  const io = new IntersectionObserver(es => es.forEach(en => { if (en.isIntersecting) { document.querySelectorAll("[data-toc]").forEach(a => a.classList.toggle("on", a.dataset.toc === en.target.dataset.chapter)); } }), { rootMargin: "-20% 0px -70% 0px" });
  body.querySelectorAll("section.doc").forEach(s => io.observe(s));

  // Lompat ulang ke bagian tujuan setelah gambar selesai dimuat
  if (location.hash) window.addEventListener("load", () => document.getElementById(location.hash.slice(1))?.scrollIntoView());

  // Dukung pencarian dari alamat, misalnya /admin/help?q=foto+dokter
  const pq = new URLSearchParams(location.search).get("q"); if (pq) { q.value = pq; run(); }
})();
