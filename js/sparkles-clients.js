/** Clients — sparkles + infinite slider + progressive blur (21st.dev demo, vanilla) */
import { bindRafVisibility } from "./raf-visibility.js";

const SLIDER_DURATION = 30;
const BLUR_LAYERS = 4;

export function initSparklesClients() {
  const root = document.querySelector(".clients-sparkles");
  if (!root) return;

  initProgressiveBlur(root);
  initInfiniteSlider(root);
  initSparklesCanvas(root);
  animateIntro(root);
}

function initProgressiveBlur(root) {
  root.querySelectorAll("[data-progressive-blur]").forEach((container) => {
    const direction = container.dataset.progressiveBlur;
    const angle = direction === "left" ? 270 : 90;
    const layers = Math.max(BLUR_LAYERS, 2);
    const segmentSize = 1 / (layers + 1);

    for (let index = 0; index < layers; index += 1) {
      const layer = document.createElement("div");
      layer.className = "clients-sparkles__blur-layer";
      const stops = [index, index + 1, index + 2, index + 3].map((pos, posIndex) => {
        const alpha = posIndex === 1 || posIndex === 2 ? 1 : 0;
        return `rgba(255, 255, 255, ${alpha}) ${pos * segmentSize * 100}%`;
      });
      const gradient = `linear-gradient(${angle}deg, ${stops.join(", ")})`;
      layer.style.maskImage = gradient;
      layer.style.webkitMaskImage = gradient;
      layer.style.backdropFilter = `blur(${index * 1}px)`;
      layer.style.webkitBackdropFilter = `blur(${index * 1}px)`;
      container.appendChild(layer);
    }
  });
}

function initInfiniteSlider(root) {
  const track = root.querySelector("[data-slider-track]");
  if (!track) return;

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const originals = [...track.querySelectorAll(".clients-sparkles__logo-item:not([data-clone])")];
  if (!originals.length) return;

  const rebuildTrack = () => {
    track.querySelectorAll("[data-clone]").forEach((node) => node.remove());
    track.classList.remove("is-animating");
    track.style.transform = "";
    track.style.animation = "";

    if (reducedMotion) return;

    // Tres copias para que el loop se note incluso en pantallas anchas
    for (let copy = 0; copy < 2; copy += 1) {
      originals.forEach((item) => {
        const clone = item.cloneNode(true);
        clone.setAttribute("data-clone", "true");
        clone.setAttribute("aria-hidden", "true");
        track.appendChild(clone);
      });
    }

    track.style.setProperty("--slider-duration", `${SLIDER_DURATION}s`);
    track.style.setProperty("--slider-shift", `${100 / 3}%`);
    track.classList.add("is-animating");
  };

  rebuildTrack();
  window.addEventListener("resize", rebuildTrack);
}

function initSparklesCanvas(root) {
  const canvas = root.querySelector(".clients-sparkles__canvas");
  if (!canvas) return;

  const ctx = canvas.getContext("2d");
  if (!ctx) return;

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const color = getComputedStyle(document.documentElement).getPropertyValue("--sparkles-color").trim() || "#ffffff";
  let particles = [];
  let width = 0;
  let height = 0;

  const getDensity = () => {
    if (reducedMotion) return 80;
    if (window.innerWidth < 520) return 180;
    if (window.innerWidth < 820) return 280;
    return 400;
  };

  const resize = () => {
    const rect = canvas.parentElement.getBoundingClientRect();
    width = Math.max(1, Math.floor(rect.width));
    height = Math.max(1, Math.floor(rect.height));
    canvas.width = width;
    canvas.height = height;
    particles = Array.from({ length: getDensity() }, () => createParticle());
  };

  const createParticle = () => ({
    x: Math.random() * width,
    y: Math.random() * height,
    size: Math.random() * 1.2 + 0.35,
    speedX: (Math.random() - 0.5) * 0.28,
    speedY: (Math.random() - 0.5) * 0.28,
    opacity: Math.random() * 0.85 + 0.1,
    opacityDir: (Math.random() > 0.5 ? 1 : -1) * 0.018,
  });

  const frame = () => {
    ctx.clearRect(0, 0, width, height);

    for (const p of particles) {
      p.x += p.speedX;
      p.y += p.speedY;
      p.opacity += p.opacityDir;
      if (p.opacity <= 0.08 || p.opacity >= 0.95) p.opacityDir *= -1;
      if (p.x < 0) p.x = width;
      if (p.x > width) p.x = 0;
      if (p.y < 0) p.y = height;
      if (p.y > height) p.y = 0;

      ctx.beginPath();
      ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
      ctx.fillStyle = hexToRgba(color, p.opacity);
      ctx.fill();
    }
  };

  resize();
  window.addEventListener("resize", resize);

  if (!reducedMotion) {
    bindRafVisibility(canvas.parentElement || root, frame, { rootMargin: "100px" });
  }
}

function hexToRgba(hex, alpha) {
  const n = hex.replace("#", "");
  const r = parseInt(n.slice(0, 2), 16);
  const g = parseInt(n.slice(2, 4), 16);
  const b = parseInt(n.slice(4, 6), 16);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}

async function animateIntro(root) {
  const items = root.querySelectorAll("[data-clients-item]");
  try {
    const { animate, stagger } = await import("https://cdn.jsdelivr.net/npm/motion@12.37.0/+esm");
    animate(items, { opacity: [0, 1], y: [20, 0] }, {
      duration: 0.6,
      delay: stagger(0.1, { start: 0.15 }),
      easing: "ease-out",
    });
  } catch {
    items.forEach((el) => { el.style.opacity = "1"; });
  }
}
