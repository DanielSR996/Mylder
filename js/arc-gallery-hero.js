/** Arc Gallery Hero — port arc-gallery-hero-component.tsx (21st.dev) */
const ARC_IMAGES = [
  { src: "./assets/showcase-saggita.png", alt: "Saggita Residencial — desarrollo web" },
  { src: "./assets/showcase-cumbre.png", alt: "La Cumbre Juriquilla — landing inmobiliaria" },
  { src: "./assets/showcase-aduanero.png", alt: "Sistema Aduanero — plataforma a medida" },
  { src: "./assets/showcase-wallet.png", alt: "App wallet financiera" },
  { src: "./assets/showcase-calendar.png", alt: "App de calendario y organización" },
  { src: "./assets/mockup-web-baikal.png", alt: "Cañadas El Mirador — desarrollo web" },
  { src: "./assets/mockup-web-renathya.png", alt: "Renathya Elite Women — sitio corporativo" },
  { src: "./assets/mockup-web-candas.png", alt: "Cañadas — landing inmobiliaria" },
  { src: "./assets/mockup-brand-falcon.png", alt: "Falcon Sheet Metal — branding" },
];

const ARC_DEFAULTS = {
  startAngle: 20,
  endAngle: 160,
  radiusLg: 480,
  radiusMd: 360,
  radiusSm: 260,
  cardSizeLg: 120,
  cardSizeMd: 100,
  cardSizeSm: 80,
};

function probeImage(src) {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => resolve(true);
    img.onerror = () => resolve(false);
    img.src = src;
  });
}

async function resolveArcImages() {
  const checks = await Promise.all(
    ARC_IMAGES.map(async (item) => ((await probeImage(item.src)) ? item : null))
  );
  return checks.filter(Boolean);
}

function getDimensions(width, opts) {
  if (width < 640) return { radius: opts.radiusSm, cardSize: opts.cardSizeSm };
  if (width < 1024) return { radius: opts.radiusMd, cardSize: opts.cardSizeMd };
  return { radius: opts.radiusLg, cardSize: opts.cardSizeLg };
}

function readArcOptions(root) {
  const num = (attr, fallback) => {
    const v = Number(root.dataset[attr]);
    return Number.isFinite(v) ? v : fallback;
  };

  return {
    startAngle: num("startAngle", ARC_DEFAULTS.startAngle),
    endAngle: num("endAngle", ARC_DEFAULTS.endAngle),
    radiusLg: num("radiusLg", ARC_DEFAULTS.radiusLg),
    radiusMd: num("radiusMd", ARC_DEFAULTS.radiusMd),
    radiusSm: num("radiusSm", ARC_DEFAULTS.radiusSm),
    cardSizeLg: num("cardSizeLg", ARC_DEFAULTS.cardSizeLg),
    cardSizeMd: num("cardSizeMd", ARC_DEFAULTS.cardSizeMd),
    cardSizeSm: num("cardSizeSm", ARC_DEFAULTS.cardSizeSm),
  };
}

function buildArcCards(pivot, images, dimensions, opts, reducedMotion) {
  pivot.innerHTML = "";
  const count = Math.max(images.length, 2);
  const step = (opts.endAngle - opts.startAngle) / (count - 1);

  images.forEach((item, i) => {
    const angle = opts.startAngle + step * i;
    const angleRad = (angle * Math.PI) / 180;
    const x = Math.cos(angleRad) * dimensions.radius;
    const y = Math.sin(angleRad) * dimensions.radius;

    const card = document.createElement("article");
    card.className = "arc-gallery__card";
    if (!reducedMotion) card.classList.add("arc-gallery__card--animate");
    card.style.width = `${dimensions.cardSize}px`;
    card.style.height = `${dimensions.cardSize}px`;
    card.style.left = `calc(50% + ${x}px)`;
    card.style.bottom = `${y}px`;
    card.style.zIndex = String(count - i);
    if (!reducedMotion) card.style.animationDelay = `${i * 100}ms`;

    const inner = document.createElement("div");
    inner.className = "arc-gallery__card-inner";
    const tilt = angle / 4;
    inner.style.setProperty("--arc-rotate", `${tilt}deg`);
    inner.style.transform = `rotate(${tilt}deg)`;

    const img = document.createElement("img");
    img.src = item.src;
    img.alt = item.alt;
    img.loading = "lazy";
    img.draggable = false;
    img.addEventListener("error", () => {
      img.src = "https://placehold.co/400x400/1a1a2e/a8b4d2?text=Proyecto";
    }, { once: true });

    inner.appendChild(img);
    card.appendChild(inner);
    pivot.appendChild(card);
  });
}

function layoutArc(root, stage, pivot, images, opts) {
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const width = window.innerWidth;
  const dimensions = getDimensions(width, opts);

  stage.style.height = `${dimensions.radius * 1.2}px`;
  buildArcCards(pivot, images, dimensions, opts, reducedMotion);
  root.classList.add("is-ready");
}

export async function initArcGalleryHero() {
  const root = document.querySelector("[data-arc-gallery]");
  if (!root) return;

  const stage = root.querySelector(".arc-gallery__stage");
  const pivot = root.querySelector(".arc-gallery__pivot");
  if (!stage || !pivot) return;

  const images = await resolveArcImages();
  if (!images.length) return;

  const opts = readArcOptions(root);
  const onResize = () => layoutArc(root, stage, pivot, images, opts);

  onResize();
  window.addEventListener("resize", onResize, { passive: true });
}
