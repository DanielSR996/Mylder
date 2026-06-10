/** Features section — port feature.tsx (21st.dev), vanilla + Motion CDN */
export function initFeaturesSection() {
  const root = document.querySelector(".features-section");
  if (!root) return;

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  initDigitizeViz(root, reducedMotion);
  initBizFirstFlow(root, reducedMotion);
  initIntegralHub(root, reducedMotion);
  if (!reducedMotion) initCardHover(root);
}

function initDigitizeViz(root, reducedMotion) {
  const viz = root.querySelector("[data-digitize]");
  if (!viz) return;

  const cells = [...viz.querySelectorAll(".features-digitize__cells span")];
  if (reducedMotion || !cells.length) return;

  let tick = 0;
  setInterval(() => {
    tick += 1;
    cells.forEach((cell, i) => {
      const on = (tick + i) % 3 === 0 || (tick + i * 2) % 5 === 0;
      cell.classList.toggle("is-lit", on);
    });
  }, 420);
}

function initBizFirstFlow(root, reducedMotion) {
  const flow = root.querySelector("[data-biz-first]");
  if (!flow) return;

  const steps = [...flow.querySelectorAll("[data-biz-step]")];
  const fill = flow.querySelector("[data-biz-connector-fill]");
  if (!steps.length) return;

  const fills = ["0%", "50%", "100%"];
  let index = 0;

  const update = (animateFill) => {
    steps.forEach((step, i) => {
      step.classList.toggle("is-active", i === index);
      step.classList.toggle("is-done", i < index);
    });

    const height = fills[index];
    if (!fill) return;

    if (animateFill && !reducedMotion) {
      import("https://cdn.jsdelivr.net/npm/motion@12.37.0/+esm")
        .then(({ animate }) => animate(fill, { height }, { duration: 0.6, easing: [0.16, 1, 0.3, 1] }))
        .catch(() => { fill.style.height = height; });
    } else {
      fill.style.height = height;
    }
  };

  update(false);
  if (reducedMotion) return;

  setInterval(() => {
    index = (index + 1) % steps.length;
    update(true);
  }, 2400);
}

function initIntegralHub(root, reducedMotion) {
  const hub = root.querySelector("[data-integral-hub]");
  if (!hub) return;

  const nodes = [...hub.querySelectorAll("[data-hub-node]")];
  if (!nodes.length) return;

  let index = 0;

  const update = () => {
    nodes.forEach((node, i) => node.classList.toggle("is-active", i === index));
  };

  update();
  if (reducedMotion) return;

  setInterval(() => {
    index = (index + 1) % nodes.length;
    update();
  }, 2000);

  import("https://cdn.jsdelivr.net/npm/motion@12.37.0/+esm")
    .then(({ animate }) => {
      const ring = hub.querySelector(".features-hub__ring");
      if (ring) {
        animate(ring, { rotate: 360 }, { duration: 18, repeat: Infinity, easing: "linear" });
      }
    })
    .catch(() => {});
}

function initCardHover(root) {
  root.querySelectorAll("[data-features-card]").forEach((card) => {
    card.addEventListener("pointerenter", () => {
      import("https://cdn.jsdelivr.net/npm/motion@12.37.0/+esm")
        .then(({ animate }) => animate(card, { scale: 0.98 }, { duration: 0.2 }))
        .catch(() => {});
    });
    card.addEventListener("pointerleave", () => {
      import("https://cdn.jsdelivr.net/npm/motion@12.37.0/+esm")
        .then(({ animate }) => animate(card, { scale: 1 }, { duration: 0.2 }))
        .catch(() => {});
    });
  });
}
