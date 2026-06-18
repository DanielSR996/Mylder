/**
 * Bitácora compartida: notas del prospecto (CSV, import) + historial de contactos.
 */
window.crmBitacora = {
  hasContent(notas, historial) {
    const n = String(notas ?? "").trim();
    return n !== "" || (Array.isArray(historial) && historial.length > 0);
  },

  snippet(notas, historial, maxLen = 120) {
    const n = String(notas ?? "").trim();
    if (n) {
      return n.length > maxLen ? n.slice(0, maxLen) + "…" : n;
    }
    if (!Array.isArray(historial) || !historial.length) return "";
    for (const h of historial) {
      const note = String(h.notas ?? "").trim();
      if (note) {
        return note.length > maxLen ? note.slice(0, maxLen) + "…" : note;
      }
    }
    const last = historial[0];
    const estado = last.resultado || "";
    const agente = last.agente_nombre || "";
    const parts = [estado, agente].filter(Boolean);
    return parts.length ? parts.join(" · ") : "";
  },

  html(notas, historial, estados, opts = {}) {
    const compact = !!opts.compact;
    const maxItems = opts.maxItems ?? (compact ? 4 : 20);
    const parts = [];

    const n = String(notas ?? "").trim();
    if (n) {
      parts.push(
        `<div class="crm-bitacora-box__section">` +
          `<p class="crm-bitacora-box__label">Notas del prospecto</p>` +
          `<p class="crm-bitacora-box__text">${this._esc(n).replace(/\n/g, "<br>")}</p>` +
        `</div>`
      );
    }

    const items = Array.isArray(historial) ? historial.slice(0, maxItems) : [];
    if (items.length) {
      const rows = items.map((h) => {
        const label = estados[h.resultado] || h.resultado || "Contacto";
        const agente = this._esc(h.agente_nombre || "Agente");
        const when = this._esc(h.creado_en || "");
        const note = String(h.notas ?? "").trim();
        const noteHtml = note
          ? `<p class="crm-bitacora-box__entry-note">${this._esc(note).replace(/\n/g, "<br>")}</p>`
          : `<p class="crm-bitacora-box__entry-note crm-bitacora-box__entry-note--muted">Sin notas en este intento</p>`;
        return (
          `<li class="crm-bitacora-box__entry">` +
            `<div class="crm-bitacora-box__entry-head">` +
              `<span class="status-badge status-${this._escAttr(h.resultado)}">${this._esc(label)}</span>` +
              `<time class="crm-bitacora-box__entry-time">${when}</time>` +
            `</div>` +
            `<p class="crm-bitacora-box__entry-agent">${agente}</p>` +
            noteHtml +
          `</li>`
        );
      }).join("");

      parts.push(
        `<div class="crm-bitacora-box__section">` +
          `<p class="crm-bitacora-box__label">Historial de contacto</p>` +
          `<ul class="crm-bitacora-box__list">${rows}</ul>` +
        `</div>`
      );
    }

    if (!parts.length) {
      return opts.emptyHtml || `<p class="crm-bitacora-box__empty">Sin entradas en la bitácora aún.</p>`;
    }

    return `<div class="crm-bitacora-box${compact ? " crm-bitacora-box--compact" : ""}">${parts.join("")}</div>`;
  },

  _esc(s) {
    const d = document.createElement("div");
    d.textContent = s ?? "";
    return d.innerHTML;
  },

  _escAttr(s) {
    return String(s ?? "").replace(/[^a-z0-9_-]/gi, "");
  },
};
