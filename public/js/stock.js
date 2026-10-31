/* Input stok harian farmasi: hitung sisa, filter, navigasi Enter, dan hanya kirim baris yang diubah */
(function () {
  const $ = (s, r = document) => r.querySelector(s);
  const form = $("#stkForm"); if (!form) return;
  const rows = [...form.querySelectorAll(".stk-row")];
  const grps = [...form.querySelectorAll(".stk-grp")];
  const num = v => { const n = parseInt(String(v).replace(/\D/g, ""), 10); return isNaN(n) ? 0 : n; };
  const fmt = n => n.toLocaleString("id-ID");

  const calc = tr => {
    const start = +tr.dataset.start, f = k => tr.querySelector(`[data-f="${k}"]`);
    const c = f("counted"), base = c && c.value !== "" ? num(c.value) : start;
    const end = tr.querySelector(".end");
    if (end) {
      const v = base + num(f("in")?.value) - num(f("out")?.value);
      end.textContent = fmt(v);
      end.className = "end" + (v < 0 ? " neg" : v === 0 ? " zero" : +tr.dataset.rop && v <= +tr.dataset.rop ? " low" : "");
      end.title = v < 0 ? "Keluar melebihi stok" : v === 0 ? "Stok habis" : +tr.dataset.rop && v <= +tr.dataset.rop ? "Stok menipis, di bawah titik pesan ulang (" + tr.dataset.rop + ")" : "";
      tr.dataset.low = v <= +tr.dataset.rop && +tr.dataset.rop ? 1 : 0;
    }
    const diff = tr.querySelector(".diff");
    if (diff) {
      const v = c.value;
      diff.textContent = v === "" ? "" : (num(v) - start > 0 ? "+" : "") + fmt(num(v) - start);
      diff.className = "diff" + (v === "" ? "" : num(v) === start ? " ok" : " off");
    }
  };

  /* Baris yang diubah diberi atribut name supaya ikut terkirim; baris lain tidak dikirim sama sekali */
  const markDirty = tr => {
    if (tr.classList.contains("dirty")) return;
    tr.classList.add("dirty");
    tr.querySelectorAll("[data-f]").forEach(i => i.name = `rows[${tr.dataset.id}][${i.dataset.f}]`);
    count();
  };
  const count = () => {
    const n = form.querySelectorAll(".stk-row.dirty").length;
    $("#stkDirty").textContent = n ? n + " obat diubah, belum disimpan" : "Belum ada perubahan";
    $("#stkSave").classList.toggle("on", n > 0);
    $("#stkSubmit").disabled = $("#stkReset").disabled = !n;
  };

  form.addEventListener("input", e => {
    const i = e.target, tr = i.closest(".stk-row"); if (!tr) return;
    if (i.classList.contains("q")) { const c = i.value.replace(/\D/g, ""); if (c !== i.value) i.value = c; }
    markDirty(tr); calc(tr);
  });
  form.addEventListener("click", e => {
    const b = e.target.closest(".fill"); if (!b) return;
    const tr = b.closest(".stk-row"), i = tr.querySelector('[data-f="in"]');
    i.value = b.dataset.v; b.remove(); markDirty(tr); calc(tr); i.focus();
  });
  form.addEventListener("focusin", e => { if (e.target.matches("input.q")) e.target.select(); });

  /* Enter / panah atas-bawah: pindah ke kolom yang sama di baris terlihat berikutnya, seperti Excel */
  form.addEventListener("keydown", e => {
    const i = e.target; if (!i.matches("input.q, input.nt, input.dt")) return;
    const up = e.key === "ArrowUp" || (e.key === "Enter" && e.shiftKey);
    if (!(e.key === "Enter" || e.key === "ArrowDown" || up)) return;
    if (i.type === "date" && e.key !== "Enter") return;
    e.preventDefault();
    const vis = rows.filter(r => !r.hidden), at = vis.indexOf(i.closest(".stk-row"));
    const next = vis[at + (up ? -1 : 1)]; if (!next) return;
    const t = next.querySelector(`[data-f="${i.dataset.f}"]`);
    t?.focus(); t?.scrollIntoView({ block: "nearest" });
  });

  /* Filter: cari nama, golongan, sudah diisi, stok menipis */
  const q = $("#stkQ"), g = $("#stkG"), filled = $("#stkFilled"), low = $("#stkLow");
  const apply = () => {
    const words = q.value.toLowerCase().trim().split(/\s+/).filter(Boolean);
    let shown = 0; const seen = new Set();
    rows.forEach(tr => {
      const on = tr.dataset.on === "1" || tr.classList.contains("dirty");
      const ok = (!g.value || tr.dataset.group === g.value)
        && (words.length ? words.every(w => tr.dataset.name.includes(w)) : on)
        && (!filled.checked || tr.dataset.filled === "1" || tr.classList.contains("dirty"))
        && (!low?.checked || tr.dataset.low === "1");
      tr.hidden = !ok; if (ok) { shown++; seen.add(tr.dataset.group); }
    });
    grps.forEach(h => h.hidden = !seen.has(h.dataset.group));
    $("#stkCount").textContent = shown + " " + ($("#stkCount").dataset.item || "obat");
    $("#stkEmpty").hidden = shown > 0;
  };
  [q, g, filled, low].forEach(el => el?.addEventListener("input", apply));
  q.addEventListener("keydown", e => {
    if (e.key === "Enter") { e.preventDefault(); rows.find(r => !r.hidden)?.querySelector("input.q")?.focus(); }
  });
  document.addEventListener("keydown", e => {
    if (e.key === "/" && !e.target.matches("input, textarea, select")) { e.preventDefault(); q.focus(); }
  });

  /* Cegah perubahan hilang saat pindah halaman, unit, atau tanggal */
  let leaving = false;
  const dirty = () => !leaving && !!form.querySelector(".stk-row.dirty");
  document.addEventListener("click", e => {
    const a = e.target.closest("a[href]");
    if (!a || a.target === "_blank" || !dirty()) return;
    if (confirm("Ada angka yang belum disimpan. Tinggalkan halaman ini tanpa menyimpan?")) { leaving = true; return; }
    e.preventDefault(); e.stopPropagation();
  }, true);
  $(".stk-date input[type=date]")?.addEventListener("change", e => {
    const d = e.target;
    if (!d.value) return;
    if (dirty() && !confirm("Ada angka yang belum disimpan. Pindah tanggal tanpa menyimpan?")) { d.value = d.defaultValue; return; }
    leaving = true; d.form.submit();
  });
  $("#stkReset").addEventListener("click", () => { leaving = true; location.reload(); });
  form.addEventListener("submit", () => { leaving = true; $("#stkSubmit").disabled = true; $("#stkSubmit").textContent = "Menyimpan…"; });
  addEventListener("beforeunload", e => { if (dirty()) { e.preventDefault(); e.returnValue = ""; } });

  rows.forEach(calc);
  count();
  apply();
})();
