(() => {
  const originalTitle = document.title;
  const ctaMessages = [
    "🚀 Tu proyecto te espera en Mylder",
    "👀 Estamos aqui para ayudarte"
  ];

  let timer = null;
  let index = 0;

  const restoreTitle = () => {
    if (timer) {
      clearInterval(timer);
      timer = null;
    }
    document.title = originalTitle;
  };

  const startAttentionLoop = () => {
    if (timer) return;
    document.title = ctaMessages[index % ctaMessages.length];
    timer = setInterval(() => {
      index += 1;
      document.title = ctaMessages[index % ctaMessages.length];
    }, 1700);
  };

  document.addEventListener("visibilitychange", () => {
    if (document.hidden) startAttentionLoop();
    else restoreTitle();
  });

  window.addEventListener("focus", restoreTitle);
  window.addEventListener("beforeunload", restoreTitle);
})();
