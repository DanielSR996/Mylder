/** Pausa un loop requestAnimationFrame cuando el elemento no está en viewport */
export function bindRafVisibility(element, onFrame, options = {}) {
  const { rootMargin = "80px", threshold = 0 } = options;
  let running = false;
  let frameId = 0;

  const tick = () => {
    if (!running) {
      frameId = 0;
      return;
    }
    onFrame();
    frameId = requestAnimationFrame(tick);
  };

  const setRunning = (next) => {
    if (next === running) return;
    running = next;
    if (running && !frameId) frameId = requestAnimationFrame(tick);
    else if (!running && frameId) {
      cancelAnimationFrame(frameId);
      frameId = 0;
    }
  };

  const observer = new IntersectionObserver(
    ([entry]) => setRunning(entry.isIntersecting),
    { rootMargin, threshold }
  );
  observer.observe(element);

  return () => {
    setRunning(false);
    observer.disconnect();
  };
}

/** Un solo listener de scroll con rAF (evita múltiples handlers sin throttle) */
export function onScrollRaf(callback) {
  let ticking = false;
  const handler = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      callback();
      ticking = false;
    });
  };
  window.addEventListener("scroll", handler, { passive: true });
  return () => window.removeEventListener("scroll", handler);
}
