/** Modern feature grid — port modern-feature-grid.tsx (21st.dev), Mylder branding */
export function initModernFeatureGrid() {
  const section = document.querySelector("[data-modern-services]");
  const spotlight = section?.querySelector(".modern-services__spotlight");
  if (!section || !spotlight) return;

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reducedMotion) return;

  let raf = 0;
  let lastX = 0;
  let lastY = 0;
  section.addEventListener("mousemove", (event) => {
    lastX = event.clientX;
    lastY = event.clientY;
    if (raf) return;
    raf = requestAnimationFrame(() => {
      raf = 0;
      const rect = section.getBoundingClientRect();
      const x = lastX - rect.left;
      const y = lastY - rect.top;
      spotlight.style.background = `radial-gradient(600px at ${x}px ${y}px, rgba(4, 30, 66, 0.55), rgba(243, 196, 0, 0.06) 40%, transparent 80%)`;
    });
  }, { passive: true });

  section.addEventListener("mouseleave", () => {
    spotlight.style.background = "transparent";
  });
}
