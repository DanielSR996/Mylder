import { initGlowyWavesHero } from "./js/glowy-waves-hero.js";
import { initHeroPillsMarquee } from "./js/hero-pills-marquee.js";
import { initPhotoGallery } from "./js/photo-gallery.js";
import { initSparklesClients } from "./js/sparkles-clients.js";
import { initGlassBlogCards } from "./js/glass-blog-cards.js";
import { initFeaturesSection } from "./js/features-section.js";
import { initModernFeatureGrid } from "./js/modern-feature-grid.js";
import { initPage } from "./js/page-init.js";
import { onScrollRaf } from "./js/raf-visibility.js";

const WHATSAPP_NUMBER = "524424241707";
const STORAGE_KEY = "milder-booked-slots";
const BOOKING_PROXY_CANDIDATES = [
  "api/proxy.php",
  "./api/proxy.php",
  "/api/proxy.php",
  "proxy.php",
  "./proxy.php",
  "/proxy.php"
];
const GOOGLE_APPOINTMENT_PUBLIC_URL = "https://calendar.app.google/vmwS2T9xCi9qNVsz5";
const START_HOUR = 9;
const END_HOUR = 20;
const DAY_NAMES = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
const MONTH_NAMES = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

const daysGrid = document.querySelector("#daysGrid");
const timeGrid = document.querySelector("#timeGrid");
const selectedDayLabel = document.querySelector("#selectedDayLabel");
const selectedSummary = document.querySelector("#selectedSummary");
const selectedSummaryModal = document.querySelector("#selectedSummaryModal");
const nextSuggestion = document.querySelector("#nextSuggestion");
const bookingStatus = document.querySelector("#bookingStatus");
const bookNow = document.querySelector("#bookNow");
const openBookingModalBtn = document.querySelector("#openBookingModal");
const bookingModal = document.querySelector("#bookingModal");
const closeBookingModalBtn = document.querySelector("#closeBookingModal");
const bookingStepDate = document.querySelector("#bookingStepDate");
const bookingStepTime = document.querySelector("#bookingStepTime");
const bookingStepDetails = document.querySelector("#bookingStepDetails");
const bookingStepSuccess = document.querySelector("#bookingStepSuccess");
const nextToTimeBtn = document.querySelector("#nextToTime");
const backToDateBtn = document.querySelector("#backToDate");
const nextToDetailsBtn = document.querySelector("#nextToDetails");
const backToTimeBtn = document.querySelector("#backToTime");
const closeBookingSuccessBtn = document.querySelector("#closeBookingSuccess");
const successWhatsappLink = document.querySelector("#successWhatsappLink");
const successMessage = document.querySelector("#successMessage");
const wizardStepLabel = document.querySelector("#wizardStepLabel");
const wizardSteps = document.querySelectorAll(".wizard-step");
const toast = document.querySelector("#toast");
const typingLine = document.querySelector("#typewriterLine");
const leadName = document.querySelector("#leadName");
const leadEmail = document.querySelector("#leadEmail");
const leadPhone = document.querySelector("#leadPhone");
const leadPhoneCode = document.querySelector("#leadPhoneCode");
const leadService = document.querySelector("#leadService");
const leadSource = document.querySelector("#leadSource");
const leadContactChannel = document.querySelector("#leadContactChannel");
const leadWhatsappConsent = document.querySelector("#leadWhatsappConsent");
const googleFallbackLink = document.querySelector("#googleFallbackLink");
const serviceCards = document.querySelectorAll(".modern-feature-card, .diff-bento-card, .panel, .timeline-step, .features-card");
const tiltSurfaces = document.querySelectorAll(".tilt-surface");
const heroOrb = document.querySelector(".hero-orb");
const bookingCtaShell = document.querySelector(".booking-cta-shell");
const smartCtaLinks = document.querySelectorAll("[href*='wa.me/524424241707']");
const contactForm = document.querySelector("#contactForm");
const contactCard = document.querySelector("#contactCard");
const thankYouCard = document.querySelector("#thankYouCard");
const contactSubmit = document.querySelector("#contactSubmit");
const contactName = document.querySelector("#contactName");
const contactEmail = document.querySelector("#contactEmail");
const contactPhone = document.querySelector("#contactPhone");
const contactPhoneCode = document.querySelector("#contactPhoneCode");
const contactService = document.querySelector("#contactService");
const contactSource = document.querySelector("#contactSource");
const contactChannel = document.querySelector("#contactChannel");
const contactMessage = document.querySelector("#contactMessage");
const contactWhatsappConsent = document.querySelector("#contactWhatsappConsent");
const header = document.querySelector(".header");
const mobileMenuBtn = document.querySelector("#mobileMenuBtn");
const navSectionLinks = [...document.querySelectorAll(".nav-tubelight__link[href^='#']")];
const navTubelightLamp = document.querySelector("#navTubelightLamp");
const navTubelightTrack = document.querySelector(".nav-tubelight__track");
const whatsappFab = document.querySelector("#whatsappFab");
const whatsappFabBtn = document.querySelector("#whatsappFabBtn");
const whatsappFabCard = document.querySelector("#whatsappFabCard");
const whatsappFabClose = document.querySelector("#whatsappFabClose");
const whatsappFabLink = document.querySelector("#whatsappFabLink");
const showcaseMainImage = document.querySelector("#showcaseMainImage");
const showcaseThumbs = [...document.querySelectorAll(".showcase-thumb")];
const blogCarouselWraps = [...document.querySelectorAll("[data-blog-carousel]")];
const isGoogleMode = BOOKING_PROXY_CANDIDATES.length > 0;
let selectedDateKey = null;
let selectedDateLabel = null;
let selectedTime = null;
let selectedAvailability = { booked: [], recommended: null };

setupSchedule();
setupCountryPickers();
setupHeaderUi();
bookNow.addEventListener("click", handleBookingClick);
openBookingModalBtn.addEventListener("click", openBookingModal);
closeBookingModalBtn.addEventListener("click", closeBookingModal);
bookingModal.addEventListener("click", handleModalBackdropClose);
nextToTimeBtn.addEventListener("click", () => setWizardStep("time"));
backToDateBtn.addEventListener("click", () => setWizardStep("date"));
nextToDetailsBtn.addEventListener("click", () => setWizardStep("details"));
backToTimeBtn.addEventListener("click", () => setWizardStep("time"));
closeBookingSuccessBtn.addEventListener("click", closeBookingModal);
contactForm?.addEventListener("submit", handleContactSubmit);
setupAnimations();
setupPointerEffects();
setupSmartCtas();
setupTypewriter();
updateBookingModeLabel();
setupGoogleFallbackLink();
setupWhatsappFab();
setupShowcaseGallery();
setupBlogCarousel();
initGlowyWavesHero();
initHeroPillsMarquee();
initPhotoGallery();
initSparklesClients();
initGlassBlogCards();
initFeaturesSection();
initModernFeatureGrid();
initPage();

async function setupAnimations() {
  try {
    const { animate } = await import("https://cdn.jsdelivr.net/npm/motion@12.37.0/+esm");

    if (heroOrb) {
      animate(heroOrb, { y: [-10, 8, -10], x: [-7, 6, -7], scale: [1, 1.04, 1] }, { duration: 8.4, repeat: Infinity, easing: "ease-in-out" });
    }
  } catch {
    // Fallback: contenido visible sin animaciones.
  }
}

function setupTypewriter() {
  if (!typingLine) return;
  const text = typingLine.textContent.trim();
  typingLine.textContent = "";
  const textNode = document.createTextNode("");
  const caret = document.createElement("span");
  caret.className = "typewriter-caret";
  caret.setAttribute("aria-hidden", "true");
  typingLine.appendChild(textNode);
  typingLine.appendChild(caret);
  let i = 0;
  const timer = setInterval(() => {
    textNode.nodeValue += text[i] || "";
    i += 1;
    if (i >= text.length) clearInterval(timer);
  }, 34);
}

function setupShowcaseGallery() {
  if (!showcaseMainImage || !showcaseThumbs.length) return;

  const setActiveThumb = (activeThumb) => {
    showcaseThumbs.forEach((thumb) => {
      thumb.classList.toggle("is-active", thumb === activeThumb);
    });
  };

  showcaseThumbs.forEach((thumb) => {
    thumb.addEventListener("click", () => {
      const imageSrc = thumb.dataset.image;
      const imageAlt = thumb.dataset.alt || "Mockup de proyecto Mylder Solutions";
      if (!imageSrc) return;
      showcaseMainImage.src = imageSrc;
      showcaseMainImage.alt = imageAlt;
      setActiveThumb(thumb);
    });
  });
}

function setupBlogCarousel() {
  if (!blogCarouselWraps.length) return;

  const getCardsPerView = () => {
    if (window.innerWidth <= 820) return 1;
    if (window.innerWidth <= 1120) return 2;
    return 3;
  };

  blogCarouselWraps.forEach((wrap) => {
    const viewport = wrap.querySelector(".blog-carousel");
    const track = wrap.querySelector(".blog-track");
    const cards = [...wrap.querySelectorAll(".glass-blog-card")];
    const prevBtn = wrap.querySelector(".blog-carousel-arrow.is-prev");
    const nextBtn = wrap.querySelector(".blog-carousel-arrow.is-next");
    const dotsRoot = wrap.querySelector(".blog-carousel-dots");

    if (!viewport || !track || !cards.length || !prevBtn || !nextBtn || !dotsRoot) return;

    cards.forEach((card) => {
      card.addEventListener("click", () => {
        const href = card.getAttribute("href") || "";
        const slug = href.replace("./", "").replace(".html", "");
        trackEvent("blog_open", { blog_slug: slug || "unknown" });
      });
    });

    let currentIndex = 0;
    let dots = [];

    const buildDots = (pages) => {
      dotsRoot.innerHTML = "";
      dots = Array.from({ length: pages }, (_, index) => {
        const dot = document.createElement("button");
        dot.type = "button";
        dot.setAttribute("aria-label", `Ir al slide ${index + 1}`);
        dot.addEventListener("click", () => {
          currentIndex = index;
          update();
        });
        dotsRoot.appendChild(dot);
        return dot;
      });
    };

    const update = () => {
      const cardsPerView = getCardsPerView();
      const maxIndex = Math.max(0, cards.length - cardsPerView);
      currentIndex = Math.max(0, Math.min(currentIndex, maxIndex));
      const cardWidth = cards[0].getBoundingClientRect().width;
      const gap = parseFloat(window.getComputedStyle(track).gap || "0");
      const step = cardWidth + gap;
      track.style.transform = `translateX(-${currentIndex * step}px)`;
      prevBtn.disabled = currentIndex <= 0;
      nextBtn.disabled = currentIndex >= maxIndex;

      if (dots.length !== maxIndex + 1) buildDots(maxIndex + 1);
      dots.forEach((dot, dotIndex) => dot.classList.toggle("active", dotIndex === currentIndex));
    };

    prevBtn.addEventListener("click", () => {
      currentIndex -= 1;
      update();
    });

    nextBtn.addEventListener("click", () => {
      currentIndex += 1;
      update();
    });

    window.addEventListener("resize", update);
    update();
  });
}

function setupPointerEffects() {
  const canTilt = () => window.innerWidth >= 980 && !window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  serviceCards.forEach((card) => {
    let raf = 0;
    let lastX = 0;
    let lastY = 0;
    card.addEventListener("pointermove", (event) => {
      lastX = event.clientX;
      lastY = event.clientY;
      if (raf) return;
      raf = requestAnimationFrame(() => {
        raf = 0;
        const rect = card.getBoundingClientRect();
        card.style.setProperty("--mx", `${lastX - rect.left}px`);
        card.style.setProperty("--my", `${lastY - rect.top}px`);
      });
    }, { passive: true });
  });

  tiltSurfaces.forEach((surface) => {
    let raf = 0;
    let lastEvent = null;
    surface.addEventListener("pointermove", (event) => {
      if (!canTilt()) return;
      lastEvent = event;
      if (raf) return;
      raf = requestAnimationFrame(() => {
        raf = 0;
        if (!lastEvent) return;
        const rect = surface.getBoundingClientRect();
        const x = (lastEvent.clientX - rect.left) / rect.width - 0.5;
        const y = (lastEvent.clientY - rect.top) / rect.height - 0.5;
        surface.style.transform = `perspective(900px) rotateY(${x * 4}deg) rotateX(${y * -3.2}deg)`;
      });
    }, { passive: true });
    surface.addEventListener("pointerleave", () => {
      surface.style.transform = "";
    });
  });
}

function setupSmartCtas() {
  smartCtaLinks.forEach((link) => {
    link.addEventListener("click", () => {
      const section = link.closest("section")?.id || "general";
      localStorage.setItem("milder-last-cta-source", section);
      trackEvent("whatsapp_click", { source: section });
    });
  });
}

function setupHeaderUi() {
  if (!header) return;

  const updateScrolledState = () => {
    header.classList.toggle("scrolled", window.scrollY > 14);
  };

  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener("click", () => {
      const isOpen = header.classList.toggle("menu-open");
      mobileMenuBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
    });
  }

  navSectionLinks.forEach((link) => {
    link.addEventListener("click", () => {
      header.classList.remove("menu-open");
      mobileMenuBtn?.setAttribute("aria-expanded", "false");
    });
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth > 820) {
      header.classList.remove("menu-open");
      mobileMenuBtn?.setAttribute("aria-expanded", "false");
    }
  });

  const sections = navSectionLinks
    .map((link) => {
      const target = document.querySelector(link.getAttribute("href"));
      return target ? { link, target } : null;
    })
    .filter(Boolean);

  let activeSectionId = "";
  let lampRaf = 0;

  const positionTubelightLamp = () => {
    if (!navTubelightLamp || !navTubelightTrack || window.innerWidth <= 820) {
      navTubelightLamp?.classList.remove("is-visible");
      return;
    }
    const activeLink = navSectionLinks.find((link) => link.classList.contains("active"));
    if (!activeLink) {
      navTubelightLamp.classList.remove("is-visible");
      return;
    }
    const trackRect = navTubelightTrack.getBoundingClientRect();
    const linkRect = activeLink.getBoundingClientRect();
    const x = linkRect.left - trackRect.left;
    navTubelightLamp.style.width = `${linkRect.width}px`;
    navTubelightLamp.style.transform = `translateX(${x}px)`;
    navTubelightLamp.classList.add("is-visible");
  };

  const scheduleTubelightLamp = () => {
    if (lampRaf) return;
    lampRaf = requestAnimationFrame(() => {
      lampRaf = 0;
      positionTubelightLamp();
    });
  };

  const setActiveLink = (id, { force = false } = {}) => {
    if (!id) return;
    if (!force && id === activeSectionId) return;
    activeSectionId = id;
    sections.forEach(({ link, target }) => {
      link.classList.toggle("active", target.id === id);
    });
    scheduleTubelightLamp();
    // Segundo frame por si el layout del nav cambió al activar
    requestAnimationFrame(scheduleTubelightLamp);
  };

  const hasSection = (id) => sections.some(({ target }) => target.id === id);

  /** Marca en viewport (px desde arriba) — misma referencia que getBoundingClientRect */
  const getScrollMarker = () => (header.getBoundingClientRect().height || 0) + 56;

  const getActiveSectionByScroll = () => {
    if (!sections.length) return "";

    const marker = getScrollMarker();
    let currentId = sections[0].target.id;
    let intersectingId = "";

    sections.forEach(({ target }) => {
      const rect = target.getBoundingClientRect();
      const { top, bottom } = rect;

      // La sección cruza la línea del header → prioridad máxima
      if (top <= marker && bottom > marker + 24) {
        intersectingId = target.id;
      }

      // Fallback: última sección cuyo inicio ya pasó el marcador
      if (top <= marker) {
        currentId = target.id;
      }
    });

    const reachedBottom =
      window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 12;
    if (reachedBottom) {
      return sections[sections.length - 1].target.id;
    }

    return intersectingId || currentId;
  };

  const syncActiveLink = () => {
    const currentId = getActiveSectionByScroll();
    if (currentId) setActiveLink(currentId);
  };

  const onScrollFrame = () => {
    updateScrolledState();
    syncActiveLink();
    scheduleTubelightLamp();
    updateBookingScrollFx();
  };

  navSectionLinks.forEach((link) => {
    link.addEventListener("click", () => {
      const targetId = (link.getAttribute("href") || "").replace("#", "");
      if (!targetId || !hasSection(targetId)) return;
      setActiveLink(targetId, { force: true });
      // Re-sincronizar durante scroll suave hasta asentar el indicador
      let frames = 0;
      const followSmoothScroll = () => {
        syncActiveLink();
        scheduleTubelightLamp();
        if (frames < 45) {
          frames += 1;
          requestAnimationFrame(followSmoothScroll);
        }
      };
      requestAnimationFrame(followSmoothScroll);
    });
  });

  onScrollRaf(onScrollFrame);
  window.addEventListener("resize", () => {
    onScrollFrame();
    scheduleTubelightLamp();
  });
  updateScrolledState();
  onScrollFrame();
  window.addEventListener("load", () => {
    syncActiveLink();
    scheduleTubelightLamp();
  });
  window.addEventListener("hashchange", () => {
    const hashId = window.location.hash.replace("#", "");
    if (hashId && hasSection(hashId)) setActiveLink(hashId);
    else syncActiveLink();
  });

  const initialHashId = window.location.hash.replace("#", "");
  if (initialHashId && hasSection(initialHashId)) setActiveLink(initialHashId);
  else syncActiveLink();
}

function setupCountryPickers() {
  [leadPhoneCode, contactPhoneCode].forEach((select) => {
    if (!select || select.dataset.enhanced === "true") return;
    enhanceCountryPicker(select);
  });
}

function enhanceCountryPicker(select) {
  const wrapper = document.createElement("div");
  wrapper.className = "country-picker";
  select.parentElement.insertBefore(wrapper, select);
  wrapper.appendChild(select);
  select.classList.add("country-picker-native");
  select.dataset.enhanced = "true";

  const trigger = document.createElement("button");
  trigger.type = "button";
  trigger.className = "country-picker-trigger";
  trigger.setAttribute("aria-haspopup", "listbox");
  trigger.setAttribute("aria-expanded", "false");

  const menu = document.createElement("div");
  menu.className = "country-picker-menu";
  menu.setAttribute("role", "listbox");

  const syncTrigger = () => {
    const selected = select.options[select.selectedIndex];
    const label = selected?.textContent?.trim() || "";
    const iso = (selected?.dataset?.iso || "mx").toLowerCase();
    trigger.innerHTML = `
      <span class="country-picker-label">
        <img src="https://flagcdn.com/w20/${iso}.png" alt="${iso.toUpperCase()} flag" loading="lazy" />
        <span>${label}</span>
      </span>
      <span class="country-picker-caret">▼</span>
    `;
    [...menu.querySelectorAll(".country-picker-option")].forEach((optionBtn) => {
      optionBtn.classList.toggle("is-active", optionBtn.dataset.value === select.value);
    });
  };

  [...select.options].forEach((option) => {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = "country-picker-option";
    btn.dataset.value = option.value;
    const iso = (option.dataset.iso || "mx").toLowerCase();
    btn.innerHTML = `<img src="https://flagcdn.com/w20/${iso}.png" alt="" loading="lazy" /><span>${option.textContent.trim()}</span>`;
    btn.addEventListener("click", () => {
      select.value = option.value;
      select.dispatchEvent(new Event("change", { bubbles: true }));
      wrapper.classList.remove("open");
      trigger.setAttribute("aria-expanded", "false");
      syncTrigger();
    });
    menu.appendChild(btn);
  });

  trigger.addEventListener("click", () => {
    const nextState = !wrapper.classList.contains("open");
    document.querySelectorAll(".country-picker.open").forEach((node) => node.classList.remove("open"));
    wrapper.classList.toggle("open", nextState);
    trigger.setAttribute("aria-expanded", nextState ? "true" : "false");
  });

  document.addEventListener("click", (event) => {
    if (!wrapper.contains(event.target)) {
      wrapper.classList.remove("open");
      trigger.setAttribute("aria-expanded", "false");
    }
  });

  select.addEventListener("change", syncTrigger);
  wrapper.appendChild(trigger);
  wrapper.appendChild(menu);
  syncTrigger();
}

function updateBookingModeLabel() {
  if (!bookingStatus) return;
  bookingStatus.style.display = "none";
  bookingStatus.textContent = isGoogleMode
    ? "Modo agenda: proxy seguro conectado a Google Calendar (disponibilidad real)."
    : "Modo agenda: personalizado local. Para tiempo real, conecta Google Apps Script.";
}

function setupGoogleFallbackLink() {
  if (!googleFallbackLink) return;
  googleFallbackLink.href = GOOGLE_APPOINTMENT_PUBLIC_URL;
  googleFallbackLink.style.display = isGoogleMode ? "none" : "inline-block";
}

function redirectToThanksPage(path, params = {}) {
  const qs = new URLSearchParams(params);
  const suffix = qs.toString() ? `?${qs.toString()}` : "";
  window.location.href = `${path}${suffix}`;
}

function trackEvent(eventName, params = {}) {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({
    event: eventName,
    ...params
  });
}

function openBookingModal() {
  trackEvent("booking_start", { source: "agenda_section" });
  resetWizardFlow();
  setWizardStep("date");
  bookingModal.classList.add("is-open");
  bookingModal.setAttribute("aria-hidden", "false");
}

function closeBookingModal() {
  bookingModal.classList.remove("is-open");
  bookingModal.setAttribute("aria-hidden", "true");
}

function handleModalBackdropClose(event) {
  if (event.target?.dataset?.closeModal === "true") closeBookingModal();
}

function setWizardStep(step) {
  bookingStepDate.classList.toggle("active", step === "date");
  bookingStepTime.classList.toggle("active", step === "time");
  bookingStepDetails.classList.toggle("active", step === "details");
  bookingStepSuccess.classList.toggle("active", step === "success");

  const stepIndex = step === "date" ? 1 : step === "time" ? 2 : step === "details" ? 3 : 4;
  if (wizardStepLabel) {
    const labels = {
      date: "Paso 1 de 3 - Selecciona fecha",
      time: "Paso 2 de 3 - Elige horario",
      details: "Paso 3 de 3 - Completa tus datos",
      success: "Reservacion completada"
    };
    wizardStepLabel.textContent = labels[step];
  }
  wizardSteps.forEach((item, index) => {
    const num = index + 1;
    item.classList.toggle("active", stepIndex === num);
    item.classList.toggle("done", stepIndex > num || step === "success");
  });
}

function resetWizardFlow() {
  selectedDateKey = null;
  selectedDateLabel = null;
  selectedTime = null;
  selectedAvailability = { booked: [], recommended: null };
  selectedDayLabel.textContent = "Primero selecciona un día disponible";
  nextSuggestion.textContent = "Sugerencia: selecciona una fecha para ver horarios recomendados.";
  nextToTimeBtn.classList.add("btn-disabled");
  nextToDetailsBtn.classList.add("btn-disabled");
  renderDays(getUpcomingAvailableDays(10));
  timeGrid.innerHTML = "";
  updateSummary();
}

function updateBookingScrollFx() {
  if (!bookingCtaShell) return;
  const rect = bookingCtaShell.getBoundingClientRect();
  const vh = window.innerHeight || 1;
  const progress = Math.max(-1, Math.min(1, (vh * 0.55 - rect.top) / vh));
  const translate = progress * -8;
  const scale = 1 + Math.max(0, progress) * 0.018;
  bookingCtaShell.style.transform = `translate3d(0, ${translate}px, 0) scale(${scale})`;
}

function setupWhatsappFab() {
  if (!whatsappFab || !whatsappFabBtn || !whatsappFabCard || !whatsappFabLink) return;

  const message = "Hola, quiero información sobre sus servicios digitales.";
  whatsappFabLink.href = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(message)}`;
  whatsappFabLink.addEventListener("click", () => {
    trackEvent("whatsapp_click", { source: "floating_fab" });
  });

  let isOpen = false;
  let hasSeenChat = false;
  const setFabState = (nextOpen) => {
    isOpen = nextOpen;
    whatsappFab.classList.toggle("open", isOpen);
    whatsappFabBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
    whatsappFabCard.hidden = !isOpen;
    if (isOpen && !hasSeenChat) {
      hasSeenChat = true;
      whatsappFab.classList.remove("hint");
      whatsappFab.classList.add("dismissed");
    }
  };

  whatsappFabBtn.addEventListener("click", () => {
    setFabState(!isOpen);
  });

  whatsappFabClose?.addEventListener("click", () => setFabState(false));

  document.addEventListener("click", (event) => {
    if (!whatsappFab.contains(event.target)) setFabState(false);
  });

  setTimeout(() => {
    if (!isOpen && !hasSeenChat) whatsappFab.classList.add("hint");
  }, 2200);
}

function setupSchedule() {
  const upcomingDays = getUpcomingAvailableDays(10);
  renderDays(upcomingDays);
  updateSummary();
}

function getUpcomingAvailableDays(limit) {
  const result = [];
  const now = new Date();
  let dayCursor = 0;
  while (result.length < limit) {
    const current = new Date(now);
    current.setDate(now.getDate() + dayCursor);
    const day = current.getDay();
    if (day >= 1 && day <= 6) result.push(current);
    dayCursor += 1;
  }
  return result;
}

function renderDays(days) {
  daysGrid.innerHTML = "";
  days.forEach((date) => {
    const dateKey = formatDateKey(date);
    const day = DAY_NAMES[date.getDay()];
    const shortDay = day.slice(0, 3);
    const dayNumber = String(date.getDate()).padStart(2, "0");
    const month = MONTH_NAMES[date.getMonth()].slice(0, 3).replace("ié", "ie");
    const btn = document.createElement("button");
    btn.className = "slot-btn";
    btn.type = "button";
    btn.dataset.key = dateKey;
    btn.innerHTML = `<strong>${dayNumber} ${month}</strong><small>${shortDay}</small>`;
    btn.addEventListener("click", () => selectDay(date));
    daysGrid.appendChild(btn);
  });
}

async function selectDay(date) {
  selectedDateKey = formatDateKey(date);
  selectedDateLabel = formatDateDisplay(date, true);
  selectedTime = null;

  [...daysGrid.querySelectorAll(".slot-btn")].forEach((btn) => {
    btn.classList.toggle("active", btn.dataset.key === selectedDateKey);
  });

  selectedDayLabel.textContent = `Horarios para ${selectedDateLabel}`;
  selectedAvailability = await getAvailabilityForDate(selectedDateKey);
  renderTimeSlots();
  nextToTimeBtn.classList.remove("btn-disabled");
  updateSummary();
}

async function getAvailabilityForDate(dateKey) {
  if (isGoogleMode) {
    try {
      const data = await callBookingProxy("availability", { date: dateKey }, "GET");
      return {
        booked: Array.isArray(data.booked) ? data.booked : [],
        recommended: data.recommended || null
      };
    } catch {
      return getLocalAvailability(dateKey);
    }
  }
  return getLocalAvailability(dateKey);
}

function getLocalAvailability(dateKey) {
  const booked = getBookedSlots();
  const busy = booked[dateKey] || [];
  const recommended = pickRecommendedTime(busy);
  return { booked: busy, recommended };
}

function renderTimeSlots() {
  timeGrid.innerHTML = "";
  if (!selectedDateKey) return;

  const takenForDay = new Set(selectedAvailability.booked || []);
  const recommended = selectedAvailability.recommended;

  for (let hour = START_HOUR; hour <= END_HOUR; hour += 1) {
    const time = `${String(hour).padStart(2, "0")}:00`;
    const btn = document.createElement("button");
    btn.className = "slot-btn available";
    btn.type = "button";
    btn.innerHTML = `<strong>${time}</strong><small>Disponible</small>`;

    if (takenForDay.has(time)) {
      btn.disabled = true;
      btn.classList.add("busy");
      btn.classList.remove("available");
      btn.innerHTML = `<strong>${time}</strong><small>Ocupado</small>`;
    } else {
      if (recommended === time) {
        btn.classList.add("recommended");
        btn.innerHTML = `<strong>${time}</strong><small>Recomendado</small>`;
      }
      btn.addEventListener("click", () => selectTime(time));
    }
    timeGrid.appendChild(btn);
  }

  nextSuggestion.textContent = recommended
    ? `Sugerencia: horario recomendado ${recommended}.`
    : "No hay recomendaciones por ahora, elige cualquier horario disponible.";
}

function pickRecommendedTime(busyTimes) {
  const preferred = ["10:00", "11:00", "12:00", "17:00", "18:00"];
  const found = preferred.find((time) => !busyTimes.includes(time));
  return found || null;
}

function selectTime(time) {
  selectedTime = time;
  [...timeGrid.querySelectorAll(".slot-btn")].forEach((btn) => {
    const isCurrent = btn.querySelector("strong")?.textContent === time;
    btn.classList.toggle("active", isCurrent);
  });
  nextToDetailsBtn.classList.remove("btn-disabled");
  updateSummary();
}

function updateSummary() {
  if (!selectedDateLabel || !selectedTime) {
    selectedSummary.textContent = "Sin fecha seleccionada.";
    if (selectedSummaryModal) selectedSummaryModal.textContent = "Sin fecha seleccionada.";
    bookNow.classList.add("btn-disabled");
    nextToDetailsBtn.classList.add("btn-disabled");
    bookNow.href = "#";
    return;
  }

  const fromCta = localStorage.getItem("milder-last-cta-source");
  const sourceText = fromCta ? ` Vengo de la sección: ${fromCta}.` : "";
  const leadData = getLeadData();
  const sourceLeadText = leadData.source ? ` ¿Cómo supe de ustedes?: ${leadData.source}.` : "";
  const contactLeadText = leadData.contactChannel ? ` Contacto preferido: ${leadData.contactChannel}.` : "";
  const text = `Hola, quiero agendar una reunión para el ${selectedDateLabel} a las ${selectedTime}. Nombre: ${leadData.name || "No indicado"}. Servicio: ${leadData.service}.${sourceLeadText}${contactLeadText}${sourceText}`;
  const link = `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(text)}`;

  const summaryText = `Reunión seleccionada: ${selectedDateLabel} • ${selectedTime}`;
  selectedSummary.textContent = summaryText;
  if (selectedSummaryModal) selectedSummaryModal.textContent = summaryText;
  bookNow.classList.remove("btn-disabled");
  bookNow.href = link;
}

async function handleBookingClick(event) {
  event.preventDefault();
  if (bookNow.classList.contains("btn-disabled")) return;
  if (!selectedDateKey || !selectedTime) return;

  const whatsappLink = bookNow.href;
  const previousText = bookNow.textContent;
  bookNow.textContent = "Reservando...";
  bookNow.classList.add("btn-disabled");

  let reserved = false;
  let reserveError = "";

  if (isGoogleMode) {
    const reserveResult = await reserveOnGoogle(selectedDateKey, selectedTime);
    reserved = reserveResult.ok;
    reserveError = reserveResult.error || "";
  } else {
    saveBookedSlot(selectedDateKey, selectedTime);
    reserved = true;
  }

  if (reserved) {
    const bookedTime = selectedTime;
    selectedAvailability = await getAvailabilityForDate(selectedDateKey);
    renderTimeSlots();
    selectedTime = null;
    nextToDetailsBtn.classList.add("btn-disabled");
    showToast("Horario reservado correctamente.");
    successWhatsappLink.href = whatsappLink;
    successMessage.textContent = `Tu reunión para ${selectedDateLabel} a las ${bookedTime} quedó apartada con éxito.`;
    setWizardStep("success");
    trackEvent("booking_success", {
      source: "agenda_section",
      date: selectedDateKey || "",
      time: bookedTime || "",
      service: getLeadData().service || ""
    });
    redirectToThanksPage("./gracias-cita.html", {
      fecha: selectedDateLabel || "",
      hora: bookedTime || "",
      date: selectedDateKey || ""
    });
  } else {
    showToast(reserveError ? `No se pudo reservar: ${reserveError}` : "No se pudo reservar en este momento.");
  }

  bookNow.textContent = previousText;
  updateSummary();
}

async function reserveOnGoogle(dateKey, time) {
  const leadData = getLeadData();
  try {
    const data = await callBookingProxy("reserve", {
      date: dateKey,
      time,
      name: leadData.name || "Lead Web Mylder",
      email: leadData.email || "contacto@mylder.mx",
      phone: leadData.phone || "",
      service: leadData.service || "No definido",
      source: leadData.source || localStorage.getItem("milder-last-cta-source") || "web-consultoria",
      contactChannel: leadData.contactChannel || "No especificado",
      whatsappOptIn: Boolean(leadData.whatsappOptIn)
    }, "POST");
    return { ok: Boolean(data.ok), error: data.error || "" };
  } catch {
    return { ok: false, error: "Error de conexión con agenda" };
  }
}

function getLeadData() {
  const phoneNumber = leadPhone?.value.trim() || "";
  const phoneCode = leadPhoneCode?.value || "+52";
  return {
    name: leadName?.value.trim() || "",
    email: leadEmail?.value.trim() || "",
    phone: buildInternationalPhone(phoneCode, phoneNumber),
    service: leadService?.value || "Landing Page",
    source: leadSource?.value || "",
    contactChannel: leadContactChannel?.value || "No especificado",
    whatsappOptIn: Boolean(leadWhatsappConsent?.checked)
  };
}

async function handleContactSubmit(event) {
  event.preventDefault();
  if (!contactForm) return;

  const payload = {
    phoneCode: contactPhoneCode?.value || "+52",
    name: contactName?.value.trim() || "",
    email: contactEmail?.value.trim() || "",
    phone: "",
    service: contactService?.value || "Landing Page",
    source: contactSource?.value || "No especificado",
    contactChannel: contactChannel?.value || "No especificado",
    message: contactMessage?.value.trim() || "",
    whatsappOptIn: Boolean(contactWhatsappConsent?.checked)
  };
  payload.phone = buildInternationalPhone(payload.phoneCode, contactPhone?.value.trim() || "");

  if (!payload.name || !payload.email || !payload.phone || !payload.message) {
    showToast("Completa todos los campos del formulario.");
    return;
  }

  const previousText = contactSubmit?.textContent || "Enviar formulario";
  if (contactSubmit) {
    contactSubmit.textContent = "Enviando...";
    contactSubmit.disabled = true;
  }

  let sent = true;
  let errorText = "";
  if (isGoogleMode) {
    const result = await submitContactOnGoogle(payload);
    sent = result.ok;
    errorText = result.error || "";
  }

  if (sent) {
    trackEvent("contact_submit", {
      status: "success",
      method: "proxy",
      service: payload.service || "",
      source: payload.source || ""
    });
    contactForm.reset();
    redirectToThanksPage("./gracias.html");
  } else {
    const fallbackResult = await submitContactByFallbackEmail(payload);
    if (fallbackResult.ok) {
      trackEvent("contact_submit", {
        status: "success",
        method: "fallback_formsubmit",
        service: payload.service || "",
        source: payload.source || ""
      });
      showToast("Formulario enviado correctamente.");
      contactForm.reset();
      redirectToThanksPage("./gracias.html");
    } else {
      trackEvent("contact_submit", {
        status: "fail",
        method: "proxy_and_fallback",
        service: payload.service || "",
        source: payload.source || "",
        reason: fallbackResult.error || errorText || "unknown"
      });
      const finalError = fallbackResult.error || errorText || "No se pudo enviar tu formulario.";
      showToast(`No se pudo enviar: ${finalError}`);
      const fallbackUrl = buildContactFallbackWhatsappUrl(payload);
      const popup = window.open(fallbackUrl, "_blank", "noopener,noreferrer");
      if (!popup) window.location.href = fallbackUrl;
    }
  }

  if (contactSubmit) {
    contactSubmit.textContent = previousText;
    contactSubmit.disabled = false;
  }
}

async function submitContactOnGoogle(payload) {
  try {
    const data = await callBookingProxy("contact", {
      name: payload.name,
      email: payload.email,
      phone: payload.phone,
      service: payload.service,
      source: payload.source,
      contactChannel: payload.contactChannel,
      message: payload.message,
      whatsappOptIn: payload.whatsappOptIn
    }, "POST");
    return { ok: Boolean(data.ok), error: data.error || "" };
  } catch {
    return { ok: false, error: "Error de conexión al enviar el formulario" };
  }
}

async function callBookingProxy(action, payload = {}, method = "POST") {
  if (!BOOKING_PROXY_CANDIDATES.length) throw new Error("Proxy no configurado");

  let lastError = "No disponible";
  for (const candidate of BOOKING_PROXY_CANDIDATES) {
    try {
      if (method === "GET") {
        const query = new URLSearchParams({ action, ...payload });
        const response = await fetch(`${candidate}?${query.toString()}`, { method: "GET" });
        const data = await response.json().catch(() => null);
        if (!response.ok || !data) {
          lastError = `GET ${candidate} (${response.status})`;
          continue;
        }
        return data;
      }

      const response = await fetch(candidate, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ action, ...payload })
      });
      const data = await response.json().catch(() => null);
      if (!response.ok || !data) {
        lastError = `POST ${candidate} (${response.status})`;
        continue;
      }
      return data;
    } catch {
      lastError = `Sin conexión en ${candidate}`;
    }
  }

  throw new Error(`Proxy no disponible: ${lastError}`);
}

function buildContactFallbackWhatsappUrl(payload) {
  const text = [
    "Hola, no se pudo enviar el formulario web y quiero continuar por WhatsApp.",
    `Nombre: ${payload.name}`,
    `Email: ${payload.email}`,
    `Telefono: ${payload.phone}`,
    `Servicio: ${payload.service}`,
    `Origen: ${payload.source}`,
    `Contacto preferido: ${payload.contactChannel}`,
    `Mensaje: ${payload.message}`
  ].join("\n");
  return `https://wa.me/${WHATSAPP_NUMBER}?text=${encodeURIComponent(text)}`;
}

async function submitContactByFallbackEmail(payload) {
  try {
    const fallbackEndpoint = "https://formsubmit.co/ajax/danielsilvaramirez.dsr@gmail.com";
    const bodyParams = new URLSearchParams({
      name: payload.name,
      email: payload.email,
      message: [
        `Telefono: ${payload.phone}`,
        `Servicio: ${payload.service}`,
        `Origen: ${payload.source}`,
        `Contacto preferido: ${payload.contactChannel}`,
        "",
        payload.message
      ].join("\n"),
      _subject: `Nuevo contacto web: ${payload.name} | ${payload.service}`,
      _captcha: "false",
      _template: "table"
    });
    const response = await fetch(fallbackEndpoint, {
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
      },
      body: bodyParams.toString()
    });
    const data = await response.json().catch(() => ({}));
    if (String(data?.success || "").toLowerCase() === "true") {
      return { ok: true };
    }
    const message = String(data?.message || "");
    if (/activation/i.test(message)) {
      return { ok: false, error: "Activa el formulario de FormSubmit desde el correo de verificación." };
    }
    return { ok: false, error: message || "No se pudo enviar por fallback." };
  } catch {
    return { ok: false, error: "Error de conexión en el fallback de correo." };
  }
}

function showThankYouSection() {
  if (contactCard) contactCard.hidden = true;
  if (thankYouCard) {
    thankYouCard.hidden = false;
    thankYouCard.scrollIntoView({ behavior: "smooth", block: "center" });
  }
}

function buildInternationalPhone(code, number) {
  const cleanCode = String(code || "+52").trim();
  const cleanNumber = String(number || "").trim();
  if (!cleanNumber) return "";
  return `${cleanCode} ${cleanNumber}`;
}

function showToast(message) {
  if (!toast) return;
  toast.textContent = message;
  toast.classList.add("show");
  setTimeout(() => toast.classList.remove("show"), 2200);
}

function getBookedSlots() {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return {};
    return JSON.parse(raw);
  } catch {
    return {};
  }
}

function saveBookedSlot(dateKey, time) {
  const booked = getBookedSlots();
  const current = new Set(booked[dateKey] || []);
  current.add(time);
  booked[dateKey] = [...current];
  localStorage.setItem(STORAGE_KEY, JSON.stringify(booked));
}

function formatDateKey(date) {
  const yyyy = date.getFullYear();
  const mm = String(date.getMonth() + 1).padStart(2, "0");
  const dd = String(date.getDate()).padStart(2, "0");
  return `${yyyy}-${mm}-${dd}`;
}

function formatDateDisplay(date, includeDayName = false) {
  const dayName = DAY_NAMES[date.getDay()];
  const day = date.getDate();
  const month = MONTH_NAMES[date.getMonth()];
  return includeDayName ? `${dayName} ${day} de ${month}` : `${day} ${month}`;
}
