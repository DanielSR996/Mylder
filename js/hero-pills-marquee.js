/** Hero pills — loop infinito (patrón clients-sparkles slider) */
const PILLS_DURATION = 22;

export function initHeroPillsMarquee() {
  const root = document.querySelector("[data-hero-pills-marquee]");
  const track = root?.querySelector("[data-hero-pills-track]");
  if (!root || !track) return;

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const originals = [...track.querySelectorAll(".hero-pills-marquee__pill:not([data-clone])")];
  if (!originals.length) return;

  const rebuild = () => {
    track.querySelectorAll("[data-clone]").forEach((node) => node.remove());
    track.classList.remove("is-animating");
    track.style.removeProperty("--slider-duration");
    track.style.removeProperty("--slider-shift");

    if (reducedMotion) return;

    for (let copy = 0; copy < 2; copy += 1) {
      originals.forEach((pill) => {
        const clone = pill.cloneNode(true);
        clone.setAttribute("data-clone", "true");
        clone.setAttribute("aria-hidden", "true");
        track.appendChild(clone);
      });
    }

    track.style.setProperty("--slider-duration", `${PILLS_DURATION}s`);
    track.style.setProperty("--slider-shift", `${100 / 3}%`);
    track.classList.add("is-animating");
  };

  rebuild();
  window.addEventListener("resize", rebuild, { passive: true });
}
