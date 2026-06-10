(() => {
  const root = document.querySelector(".serenity");
  if (!root) return;

  const mouseGlow = root.querySelector(".serenity__mouse-glow");
  const prefersReducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const animateWords = () => {
    root.querySelectorAll(".word-animate").forEach((word) => {
      const delay = Number.parseInt(word.getAttribute("data-delay") || "0", 10);
      window.setTimeout(() => {
        word.style.animation = "serenity-word-appear 0.8s ease-out forwards";
      }, delay);
    });
  };

  window.setTimeout(animateWords, 500);

  if (!prefersReducedMotion && mouseGlow) {
    const onMouseMove = (event) => {
      mouseGlow.style.left = `${event.clientX}px`;
      mouseGlow.style.top = `${event.clientY}px`;
      mouseGlow.style.opacity = "1";
    };

    const onMouseLeave = () => {
      mouseGlow.style.opacity = "0";
    };

    document.addEventListener("mousemove", onMouseMove);
    document.addEventListener("mouseleave", onMouseLeave);
  }

  if (!prefersReducedMotion) {
    document.addEventListener("click", (event) => {
      const ripple = document.createElement("div");
      ripple.className = "serenity__ripple";
      ripple.style.left = `${event.clientX}px`;
      ripple.style.top = `${event.clientY}px`;
      document.body.appendChild(ripple);
      window.setTimeout(() => ripple.remove(), 1000);
    });
  }

  root.querySelectorAll(".word-animate").forEach((word) => {
    word.addEventListener("mouseenter", () => {
      word.style.textShadow = "0 0 20px rgba(243, 196, 0, 0.35)";
    });
    word.addEventListener("mouseleave", () => {
      word.style.textShadow = "none";
    });
  });

  let scrolled = false;
  const floatingElements = [...root.querySelectorAll(".serenity__float")];

  const onScroll = () => {
    if (scrolled) return;
    scrolled = true;
    floatingElements.forEach((element, index) => {
      const delayMs =
        Number.parseFloat(element.style.animationDelay || "0") * 1000 + index * 100;
      window.setTimeout(() => {
        element.style.animationPlayState = "running";
        element.style.opacity = "";
      }, delayMs);
    });
    window.removeEventListener("scroll", onScroll);
  };

  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();
})();
