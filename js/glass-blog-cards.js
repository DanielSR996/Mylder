/** Glass blog cards — animación de entrada (21st.dev glass-blog-card) */
export function initGlassBlogCards() {
  const cards = [...document.querySelectorAll(".glass-blog-card")];
  if (!cards.length) return;

  const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reducedMotion) {
    cards.forEach((card) => card.classList.add("is-visible"));
    return;
  }

  const reveal = async () => {
    try {
      const { animate, stagger, inView } = await import("https://cdn.jsdelivr.net/npm/motion@12.37.0/+esm");
      inView(
        ".blog-track",
        () => {
          animate(cards, { opacity: [0, 1], y: [20, 0] }, {
            duration: 0.4,
            delay: stagger(0.12),
            easing: "ease-out",
          });
          cards.forEach((card) => card.classList.add("is-visible"));
        },
        { amount: 0.25, once: true }
      );
    } catch {
      cards.forEach((card) => card.classList.add("is-visible"));
    }
  };

  reveal();
}
