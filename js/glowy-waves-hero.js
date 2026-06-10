/** Glowy Waves Hero — port de glowy-waves-hero-shadcnui (21st.dev) */
import { bindRafVisibility } from "./raf-visibility.js";

const WAVE_PALETTE = [
  { offset: 0, amplitude: 70, frequency: 0.003, color: "rgba(4, 30, 66, 0.85)", opacity: 0.45 },
  { offset: Math.PI / 2, amplitude: 90, frequency: 0.0026, color: "rgba(243, 196, 0, 0.7)", opacity: 0.38 },
  { offset: Math.PI, amplitude: 60, frequency: 0.0034, color: "rgba(16, 24, 32, 0.75)", opacity: 0.32 },
  { offset: Math.PI * 1.5, amplitude: 80, frequency: 0.0022, color: "rgba(126, 184, 255, 0.35)", opacity: 0.28 },
  { offset: Math.PI * 2, amplitude: 55, frequency: 0.004, color: "rgba(255, 255, 255, 0.18)", opacity: 0.22 },
];

export function initGlowyWavesHero() {
  const root = document.querySelector(".hero-waves");
  const canvas = root?.querySelector(".hero-waves__canvas");
  if (!root || !canvas) return;

  const ctx = canvas.getContext("2d");
  if (!ctx) return;

  let time = 0;
  const mouse = { x: 0, y: 0 };
  const targetMouse = { x: 0, y: 0 };

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reducedMotion) return;

  const mouseInfluence = 70;
  const influenceRadius = 320;
  const smoothing = 0.1;

  const resize = () => {
    const rect = root.getBoundingClientRect();
    const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    canvas.width = Math.max(1, Math.floor(rect.width * dpr));
    canvas.height = Math.max(1, Math.floor(rect.height * dpr));
    canvas.style.width = `${rect.width}px`;
    canvas.style.height = `${rect.height}px`;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    recenterMouse(rect.width, rect.height);
  };

  const recenterMouse = (w = canvas.width, h = canvas.height) => {
    const center = { x: w / 2, y: h / 2 };
    mouse.x = center.x;
    mouse.y = center.y;
    targetMouse.x = center.x;
    targetMouse.y = center.y;
  };

  const onMouseMove = (e) => {
    const rect = root.getBoundingClientRect();
    targetMouse.x = e.clientX - rect.left;
    targetMouse.y = e.clientY - rect.top;
  };

  const drawWave = (wave, w, h) => {
    ctx.save();
    ctx.beginPath();

    for (let x = 0; x <= w; x += 6) {
      const dx = x - mouse.x;
      const dy = h / 2 - mouse.y;
      const distance = Math.sqrt(dx * dx + dy * dy);
      const influence = Math.max(0, 1 - distance / influenceRadius);
      const mouseEffect =
        influence * mouseInfluence * Math.sin(time * 0.001 + x * 0.01 + wave.offset);

      const y =
        h / 2 +
        Math.sin(x * wave.frequency + time * 0.002 + wave.offset) * wave.amplitude +
        Math.sin(x * wave.frequency * 0.4 + time * 0.003) * (wave.amplitude * 0.45) +
        mouseEffect;

      if (x === 0) ctx.moveTo(x, y);
      else ctx.lineTo(x, y);
    }

    ctx.lineWidth = 2;
    ctx.strokeStyle = wave.color;
    ctx.globalAlpha = wave.opacity;
    ctx.shadowBlur = 12;
    ctx.shadowColor = wave.color;
    ctx.stroke();
    ctx.restore();
  };

  const frame = () => {
    time += 1;
    mouse.x += (targetMouse.x - mouse.x) * smoothing;
    mouse.y += (targetMouse.y - mouse.y) * smoothing;

    const w = canvas.clientWidth || 1;
    const h = canvas.clientHeight || 1;

    const gradient = ctx.createLinearGradient(0, 0, 0, h);
    gradient.addColorStop(0, "#030303");
    gradient.addColorStop(1, "rgba(8, 13, 26, 0.96)");

    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, w, h);
    ctx.globalAlpha = 1;
    ctx.shadowBlur = 0;

    WAVE_PALETTE.forEach((wave) => drawWave(wave, w, h));
  };

  resize();
  window.addEventListener("resize", resize);
  root.addEventListener("mousemove", onMouseMove, { passive: true });
  root.addEventListener("mouseleave", () => {
    const rect = root.getBoundingClientRect();
    recenterMouse(rect.width, rect.height);
  });

  const stopVisibility = bindRafVisibility(root, frame, { rootMargin: "120px" });

  animateContent(root);

  return () => {
    stopVisibility();
    window.removeEventListener("resize", resize);
    root.removeEventListener("mousemove", onMouseMove);
    root.removeEventListener("mouseleave", resize);
  };
}

async function animateContent(root) {
  const items = root.querySelectorAll("[data-hero-item]");
  const stats = root.querySelector(".hero-waves__stats");
  const statItems = stats ? [...stats.querySelectorAll(".hero-waves__stat")] : [];

  try {
    const { animate, stagger } = await import("https://cdn.jsdelivr.net/npm/motion@12.37.0/+esm");
    animate(items, { opacity: [0, 1], y: [24, 0] }, {
      duration: 0.65,
      delay: stagger(0.1, { start: 0.35 }),
      easing: [0.25, 0.4, 0.25, 1],
    });
    if (stats) {
      animate(stats, { opacity: [0, 1], scale: [0.95, 1] }, {
        duration: 0.6,
        delay: 0.85,
        easing: "ease-out",
      });
      animate(statItems, { opacity: [0, 1], y: [12, 0] }, {
        duration: 0.5,
        delay: stagger(0.08, { start: 0.95 }),
        easing: "ease-out",
      });
    }
  } catch {
    items.forEach((el) => { el.style.opacity = "1"; });
    statItems.forEach((el) => { el.style.opacity = "1"; });
    if (stats) stats.style.opacity = "1";
  }
}
