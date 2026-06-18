<?php
declare(strict_types=1);

/** Rutas absolutas desde la raíz del sitio (funcionan en /crm, /crm/ y subpáginas). */
const CRM_FAVICON = "/assets/favicon-mylder.png";
const CRM_LOGO_WHITE = "/assets/logo-completo-white.png";
const CRM_LOGO_WHITE_SM = "/assets/logo-completo-white-sm.png";
const CRM_BRAND_NAME = "Mylder Solutions";

function crmBrandHeadLinks(): string {
  $icon = htmlspecialchars(CRM_FAVICON, ENT_QUOTES | ENT_HTML5, "UTF-8");
  return '<link rel="icon" type="image/png" href="' . $icon . '" />' . "\n"
    . '  <link rel="apple-touch-icon" href="' . $icon . '" />';
}
