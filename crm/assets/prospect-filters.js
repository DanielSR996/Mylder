/** Filtros compartidos entre Prospectos y Modo llamada */
(function (global) {
  const STORAGE_KEY = "crm_prospect_filters";

  function readForm(root) {
    const el = root || document;
    return {
      ciudad: el.querySelector("#filterCiudad")?.value || "",
      categoria_id: el.querySelector("#filterCategoria")?.value || "",
      estado: el.querySelector("#filterEstado")?.value || "",
      callback_hoy: el.querySelector("#filterCallback")?.checked ? "1" : "",
      solo_organicos: el.querySelector("#filterOrganicos")?.checked ? "1" : "",
      incluir_ocultos: el.querySelector("#filterOcultos")?.checked ? "1" : "",
    };
  }

  function applyToForm(filters, root) {
    const el = root || document;
    const city = el.querySelector("#filterCiudad");
    const cat = el.querySelector("#filterCategoria");
    const est = el.querySelector("#filterEstado");
    const cb = el.querySelector("#filterCallback");
    const org = el.querySelector("#filterOrganicos");
    const occ = el.querySelector("#filterOcultos");
    if (city) city.value = filters.ciudad || "";
    if (cat) cat.value = filters.categoria_id || "";
    if (est) est.value = filters.estado || "";
    if (cb) cb.checked = filters.callback_hoy === "1";
    if (org) org.checked = filters.solo_organicos === "1";
    if (occ) occ.checked = filters.incluir_ocultos === "1";
  }

  function fromSearchParams(search) {
    const p = new URLSearchParams(search || location.search);
    return {
      ciudad: p.get("ciudad") || "",
      categoria_id: p.get("categoria_id") || "",
      estado: p.get("estado") || "",
      callback_hoy: p.get("callback_hoy") === "1" ? "1" : "",
      solo_organicos: p.get("solo_organicos") === "1" ? "1" : "",
      incluir_ocultos: p.get("incluir_ocultos") === "1" ? "1" : "",
    };
  }

  function toSearchParams(filters) {
    const p = new URLSearchParams();
    if (filters.ciudad) p.set("ciudad", filters.ciudad);
    if (filters.categoria_id) p.set("categoria_id", filters.categoria_id);
    if (filters.estado) p.set("estado", filters.estado);
    if (filters.callback_hoy === "1") p.set("callback_hoy", "1");
    if (filters.solo_organicos === "1") p.set("solo_organicos", "1");
    if (filters.incluir_ocultos === "1") p.set("incluir_ocultos", "1");
    return p;
  }

  function save(filters) {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(filters));
    } catch (_) { /* ignore */ }
  }

  function loadStored() {
    try {
      const raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch (_) {
      return null;
    }
  }

  function initForm(root) {
    const fromUrl = fromSearchParams();
    const hasUrl = Object.values(fromUrl).some(Boolean);
    const stored = loadStored() || {};
    const filters = hasUrl ? fromUrl : { ...stored, ...fromUrl };
    applyToForm(filters, root);
    save(filters);
    return filters;
  }

  function activeSummary(filters, estados) {
    const parts = [];
    if (filters.ciudad) {
      parts.push(filters.ciudad);
    }
    if (filters.categoria_id) {
      const sel = document.querySelector("#filterCategoria");
      const label = sel?.selectedOptions?.[0]?.textContent?.trim();
      if (label && label !== "Todas") parts.push(label);
    }
    if (filters.estado && estados?.[filters.estado]) {
      parts.push(estados[filters.estado]);
    }
    if (filters.callback_hoy === "1") parts.push("Callbacks vencidos");
    if (filters.solo_organicos === "1") parts.push("Solo orgánicos");
    if (filters.incluir_ocultos === "1") parts.push("Con ocultos");
    return parts.length ? parts.join(" · ") : "Cola activa (sin filtros extra)";
  }

  global.crmProspectFilters = {
    readForm,
    applyToForm,
    fromSearchParams,
    toSearchParams,
    save,
    loadStored,
    initForm,
    activeSummary,
  };
})(window);
