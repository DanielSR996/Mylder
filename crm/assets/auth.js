document.querySelectorAll(".crm-password-toggle").forEach((btn) => {
  btn.addEventListener("click", () => {
    const id = btn.getAttribute("data-target");
    const input = id ? document.getElementById(id) : btn.previousElementSibling;
    if (!input) return;
    const show = input.type === "password";
    input.type = show ? "text" : "password";
    btn.setAttribute("aria-label", show ? "Ocultar contraseña" : "Mostrar contraseña");
    btn.classList.toggle("is-visible", show);
  });
});
