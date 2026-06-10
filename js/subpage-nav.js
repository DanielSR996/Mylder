(() => {
  const header = document.querySelector(".header");
  const mobileMenuBtn = document.querySelector("#mobileMenuBtn");
  const navLinks = [...document.querySelectorAll("#mainNav a")];
  if (!header || !mobileMenuBtn) return;

  mobileMenuBtn.addEventListener("click", () => {
    const isOpen = header.classList.toggle("menu-open");
    mobileMenuBtn.setAttribute("aria-expanded", isOpen ? "true" : "false");
  });

  navLinks.forEach((link) => {
    link.addEventListener("click", () => {
      header.classList.remove("menu-open");
      mobileMenuBtn.setAttribute("aria-expanded", "false");
    });
  });

  window.addEventListener("resize", () => {
    if (window.innerWidth > 820) {
      header.classList.remove("menu-open");
      mobileMenuBtn.setAttribute("aria-expanded", "false");
    }
  });
})();
