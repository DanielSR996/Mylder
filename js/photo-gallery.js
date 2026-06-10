/** Photo Gallery — port gallery.tsx (21st.dev) */
const GALLERY_PHOTOS = [
  {
    id: 1,
    order: 0,
    x: -320,
    y: 15,
    z: 50,
    direction: "left",
    src: "./assets/showcase-saggita.png",
    alt: "Saggita Residencial — desarrollo web",
  },
  {
    id: 2,
    order: 1,
    x: -160,
    y: 32,
    z: 40,
    direction: "left",
    src: "./assets/showcase-cumbre.png",
    alt: "La Cumbre Juriquilla — landing inmobiliaria",
  },
  {
    id: 3,
    order: 2,
    x: 0,
    y: 8,
    z: 30,
    direction: "right",
    src: "./assets/showcase-aduanero.png",
    alt: "Sistema Aduanero — plataforma a medida",
  },
  {
    id: 4,
    order: 3,
    x: 160,
    y: 22,
    z: 20,
    direction: "right",
    src: "./assets/showcase-wallet.png",
    alt: "App wallet financiera",
  },
  {
    id: 5,
    order: 4,
    x: 320,
    y: 44,
    z: 10,
    direction: "left",
    src: "./assets/mockup-web-renathya.png",
    alt: "Renathya Elite Women — sitio corporativo",
  },
];

function randomRotation(direction) {
  const base = 1 + Math.random() * 3;
  return direction === "left" ? -base : base;
}

function scaleOffset(value, ratio) {
  return Math.round(value * ratio);
}

function getLayoutRatio() {
  const width = window.innerWidth;
  if (width < 640) return 0.55;
  if (width < 1024) return 0.78;
  return 1;
}

function getBaseSize(ratio) {
  return Math.round(220 * ratio);
}

export function initPhotoGallery() {
  const gallery = document.querySelector("[data-photo-gallery]");
  if (!gallery) return;

  const stage = gallery.querySelector(".photo-gallery__stage");
  if (!stage) return;

  const animationDelay = Number(gallery.dataset.animationDelay) || 0.5;
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const renderPhotos = () => {
    stage.innerHTML = "";
    gallery.classList.remove("is-ready");

    const ratio = getLayoutRatio();
    const baseSize = getBaseSize(ratio);
    stage.style.width = `${baseSize}px`;
    stage.style.height = `${baseSize}px`;

    [...GALLERY_PHOTOS].reverse().forEach((photo) => {
      const el = document.createElement("article");
      el.className = "photo-gallery__photo";
      el.style.width = `${baseSize}px`;
      el.style.height = `${baseSize}px`;
      el.style.left = "0";
      el.style.top = "0";
      el.style.zIndex = String(photo.z);
      el.dataset.targetX = String(scaleOffset(photo.x, ratio));
      el.dataset.targetY = String(scaleOffset(photo.y, ratio));
      el.dataset.direction = photo.direction;
      el.dataset.z = String(photo.z);
      el.dataset.order = String(photo.order);

      const rot = randomRotation(photo.direction);
      el.dataset.rotate = String(rot);

      el.innerHTML = `
        <div class="photo-gallery__photo-inner">
          <img src="${photo.src}" alt="${photo.alt}" loading="lazy" draggable="false" />
        </div>
      `;

      const img = el.querySelector("img");
      img?.addEventListener(
        "error",
        () => {
          img.src = "https://placehold.co/440x440/1a1a2e/a8b4d2?text=Proyecto";
        },
        { once: true }
      );

      stage.appendChild(el);
      setupPhotoInteractions(el);
    });

    if (reducedMotion) {
      gallery.classList.add("is-ready");
      [...stage.querySelectorAll(".photo-gallery__photo")].forEach((photo) => {
        applyFinalTransform(photo);
      });
      return;
    }

    revealGallery(gallery, stage, animationDelay);
  };

  renderPhotos();
  window.addEventListener("resize", renderPhotos, { passive: true });
}

function applyFinalTransform(photo) {
  const tx = Number(photo.dataset.targetX || 0);
  const ty = Number(photo.dataset.targetY || 0);
  const rot = Number(photo.dataset.rotate || 0);
  photo.style.transform = `translate(${tx}px, ${ty}px) rotate(${rot}deg)`;
}

async function revealGallery(gallery, stage, animationDelay) {
  const photos = [...stage.querySelectorAll(".photo-gallery__photo")];

  try {
    const { animate, inView } = await import("https://cdn.jsdelivr.net/npm/motion@12.37.0/+esm");

    inView(
      gallery,
      () => {
        setTimeout(() => {
          animate(gallery, { opacity: [0, 1] }, { duration: 0.4, easing: "ease-out" });
        }, animationDelay * 1000);

        setTimeout(() => {
          photos.forEach((photo) => {
            const tx = Number(photo.dataset.targetX || 0);
            const ty = Number(photo.dataset.targetY || 0);
            const order = Number(photo.dataset.order || 0);

            const rot = Number(photo.dataset.rotate || 0);

            animate(
              photo,
              { x: [0, tx], y: [0, ty], rotate: [0, rot], scale: 1 },
              {
                type: "spring",
                stiffness: 70,
                damping: 12,
                mass: 1,
                delay: 0.1 + order * 0.15,
              }
            );
          });
          gallery.classList.add("is-ready");
        }, (animationDelay + 0.4) * 1000);
      },
      { amount: 0.35 }
    );
  } catch {
    gallery.classList.add("is-ready");
    gallery.style.opacity = "1";
    photos.forEach(applyFinalTransform);
  }
}

function setupPhotoInteractions(photo) {
  let dragging = false;
  let startX = 0;
  let startY = 0;
  let baseX = 0;
  let baseY = 0;
  const direction = photo.dataset.direction || "left";
  const dirSign = direction === "left" ? -1 : 1;

  const readTranslate = () => {
    const m = new DOMMatrix(getComputedStyle(photo).transform);
    return { x: m.m41, y: m.m42, rot: Number(photo.dataset.rotate || 0) };
  };

  const onPointerDown = (e) => {
    dragging = true;
    photo.classList.add("is-lifted");
    photo.setPointerCapture(e.pointerId);
    startX = e.clientX;
    startY = e.clientY;
    const pos = readTranslate();
    baseX = pos.x;
    baseY = pos.y;
    photo.style.zIndex = "9999";
  };

  const onPointerMove = (e) => {
    if (!dragging) return;
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;
    const rot = Number(photo.dataset.rotate || 0);
    photo.style.transform = `translate(${baseX + dx}px, ${baseY + dy}px) rotate(${rot}deg) scale(1.1)`;
  };

  const onPointerUp = (e) => {
    if (!dragging) return;
    dragging = false;
    photo.classList.remove("is-lifted");
    photo.releasePointerCapture(e.pointerId);
    photo.style.zIndex = photo.dataset.z || "10";
    const pos = readTranslate();
    photo.style.transform = `translate(${pos.x}px, ${pos.y}px) rotate(${pos.rot}deg)`;
  };

  photo.addEventListener("pointerenter", () => {
    if (dragging) return;
    const pos = readTranslate();
    photo.style.zIndex = "9999";
    photo.style.transform = `translate(${pos.x}px, ${pos.y}px) rotate(${2 * dirSign}deg) scale(1.1)`;
  });

  photo.addEventListener("pointerleave", () => {
    if (dragging) return;
    photo.style.zIndex = photo.dataset.z || "10";
    applyFinalTransform(photo);
  });

  photo.addEventListener("pointerdown", onPointerDown);
  photo.addEventListener("pointermove", onPointerMove);
  photo.addEventListener("pointerup", onPointerUp);
  photo.addEventListener("pointercancel", onPointerUp);
}
