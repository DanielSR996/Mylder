/** Carga de página + scroll reveal (complementa Tailwind + Motion) */
export function initPage() {
  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const markLoaded = () => document.documentElement.classList.add("is-loaded");
  requestAnimationFrame(markLoaded);
  window.setTimeout(markLoaded, 1200);

  if (reducedMotion) {
    document.querySelectorAll(".reveal").forEach((el) => el.classList.add("is-visible"));
    return;
  }

  initScrollReveal();
}

async function initScrollReveal() {
  try {
    const { animate, inView, stagger } = await import("https://cdn.jsdelivr.net/npm/motion@12.37.0/+esm");

    inView(".reveal", (element) => {
      element.classList.add("is-visible");
      animate(
        element,
        { opacity: [0, 1], y: [20, 0] },
        { duration: 0.55, easing: [0.16, 1, 0.3, 1] }
      );
    }, { amount: 0.12, margin: "0px 0px -8% 0px" });

    document.querySelectorAll("[data-diff-bento], [data-process-timeline]").forEach((grid) => {
      const items = [...grid.querySelectorAll(".diff-bento-card, .process-timeline__step")];
      if (!items.length) return;
      inView(grid, () => {
        animate(items, { opacity: [0, 1], y: [16, 0] }, {
          duration: 0.5,
          delay: stagger(0.1),
          easing: "ease-out",
        });
      }, { amount: 0.2, once: true });
    });
  } catch {
    document.querySelectorAll(".reveal").forEach((el) => el.classList.add("is-visible"));
  }
}
