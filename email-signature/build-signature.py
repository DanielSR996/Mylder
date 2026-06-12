"""Genera la firma como PNG + HTML con imagen hospedada."""
from __future__ import annotations

import urllib.request
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

ROOT = Path(__file__).resolve().parent
ASSETS = ROOT.parent / "assets"
FONTS_DIR = ROOT / "fonts"

BLUE = "#041e42"
GOLD = "#f3c400"
WHITE = "#ffffff"
MUTED = "#c9d4f5"
MUTED2 = "#8fa3cc"

FONT_URLS = {
    "Syne-Bold.ttf": "https://github.com/google/fonts/raw/main/ofl/syne/Syne%5Bwght%5D.ttf",
    "PlusJakartaSans-Bold.ttf": "https://github.com/google/fonts/raw/main/ofl/plusjakartasans/PlusJakartaSans%5Bwght%5D.ttf",
    "DMSans-Regular.ttf": "https://github.com/google/fonts/raw/main/ofl/dmsans/DMSans%5Bopsz%2Cwght%5D.ttf",
    "JetBrainsMono-SemiBold.ttf": "https://github.com/google/fonts/raw/main/ofl/jetbrainsmono/JetBrainsMono%5Bwght%5D.ttf",
}

LOGO_PATH = ASSETS / "logo-completo-white-sm.png"
if not LOGO_PATH.is_file():
    LOGO_PATH = ASSETS / "logo-completo-white.png"

SCALE = 2
PAD = 16 * SCALE
LOGO_W = 88 * SCALE
GAP = 18 * SCALE
LINE_W = 2 * SCALE
BORDER = 1 * SCALE
RADIUS = 8 * SCALE
HOSTED_IMG = "https://mylder.mx/assets/daniel-silva-signature.png"


def ensure_fonts() -> None:
    FONTS_DIR.mkdir(parents=True, exist_ok=True)
    for name, url in FONT_URLS.items():
        target = FONTS_DIR / name
        if target.is_file() and target.stat().st_size > 1000:
            continue
        print(f"Descargando {name}...")
        urllib.request.urlretrieve(url, target)


def load_font(file: str, size: int, weight: int | None = None, opsz: float | None = None) -> ImageFont.FreeTypeFont:
    path = FONTS_DIR / file
    font = ImageFont.truetype(str(path), size)
    try:
        if opsz is not None and weight is not None:
            font.set_variation_by_axes([opsz, float(weight)])
        elif weight is not None:
            font.set_variation_by_axes([float(weight)])
    except Exception:
        pass
    return font


def measure_text(font: ImageFont.ImageFont, text: str) -> tuple[int, int]:
    bbox = font.getbbox(text)
    return bbox[2] - bbox[0], bbox[3] - bbox[1]


def measure_spaced_text(font: ImageFont.ImageFont, text: str, tracking: int) -> tuple[int, int]:
    if not text:
        return 0, 0
    width = 0
    height = 0
    for i, ch in enumerate(text):
        cw, ch = measure_text(font, ch)
        width += cw
        height = max(height, ch)
        if i < len(text) - 1:
            width += tracking
    return width, height


def draw_text_line(
    draw: ImageDraw.ImageDraw,
    x: int,
    y: int,
    text: str,
    font: ImageFont.ImageFont,
    fill: str,
) -> int:
    bbox = font.getbbox(text)
    draw.text((x, y - bbox[1]), text, font=font, fill=fill)
    return bbox[3] - bbox[1]


def draw_icon_mail(draw: ImageDraw.ImageDraw, x: int, y: int, size: int, color: str) -> None:
    stroke = max(1, SCALE)
    w, h = size, int(size * 0.72)
    top = y + 1
    draw.rounded_rectangle((x, top + h // 4, x + w, top + h), radius=2, outline=color, width=stroke)
    mid_x = x + w // 2
    fold_y = top + h // 4
    lip_y = top + int(h * 0.58)
    draw.line((x, fold_y, mid_x, lip_y), fill=color, width=stroke)
    draw.line((x + w, fold_y, mid_x, lip_y), fill=color, width=stroke)


def draw_icon_phone(draw: ImageDraw.ImageDraw, x: int, y: int, size: int, color: str) -> None:
    stroke = max(1, SCALE)
    w, h = int(size * 0.52), size
    left = x + (size - w) // 2
    draw.rounded_rectangle((left, y, left + w, y + h), radius=3, outline=color, width=stroke)
    draw.line((left + w // 3, y + h - 4, left + w - w // 3, y + h - 4), fill=color, width=stroke)


def draw_icon_web(draw: ImageDraw.ImageDraw, x: int, y: int, size: int, color: str) -> None:
    stroke = max(1, SCALE)
    draw.ellipse((x, y, x + size, y + size), outline=color, width=stroke)
    mid_y = y + size // 2
    draw.arc((x - 1, y + 2, x + size + 1, y + size - 2), start=0, end=180, fill=color, width=stroke)
    draw.line((x + 2, mid_y, x + size - 2, mid_y), fill=color, width=stroke)


def draw_contact_icon(draw: ImageDraw.ImageDraw, kind: str, x: int, y: int, size: int, color: str) -> None:
    if kind == "mail":
        draw_icon_mail(draw, x, y, size, color)
    elif kind == "phone":
        draw_icon_phone(draw, x, y, size, color)
    else:
        draw_icon_web(draw, x, y, size, color)


def render_signature_png() -> Image.Image:
    ensure_fonts()

    f_name = load_font("Syne-Bold.ttf", 18 * SCALE, weight=800)
    f_company = load_font("PlusJakartaSans-Bold.ttf", 14 * SCALE, weight=700)
    f_label = load_font("PlusJakartaSans-Bold.ttf", 12 * SCALE, weight=700)
    f_body = load_font("DMSans-Regular.ttf", 11 * SCALE, weight=400, opsz=9)
    f_contact = load_font("DMSans-Regular.ttf", 12 * SCALE, weight=400, opsz=9)
    f_contact_sm = load_font("DMSans-Regular.ttf", 11 * SCALE, weight=400, opsz=9)
    f_link = load_font("PlusJakartaSans-Bold.ttf", 12 * SCALE, weight=700)

    label_text = "Consultor\u00eda tecnol\u00f3gica"
    icon_size = 10 * SCALE
    icon_gap = 6 * SCALE
    icon_slot = icon_size + icon_gap

    logo = Image.open(LOGO_PATH).convert("RGBA")
    logo_ratio = logo.height / logo.width
    logo_h = int(LOGO_W * logo_ratio)
    logo = logo.resize((LOGO_W, logo_h), Image.LANCZOS)

    text_x = PAD + LOGO_W + GAP + LINE_W + GAP
    max_text_w = 0

    y = PAD
    h_name = measure_text(f_name, "Daniel Silva")[1]
    h_label = measure_text(f_label, label_text)[1]
    h_company = measure_text(f_company, "Mylder Solutions")[1]
    tagline = "Consultor\u00eda y ejecuci\u00f3n tecnol\u00f3gica para empresas en crecimiento."
    h_body = measure_text(f_body, tagline)[1]

    gap_name_label = 7 * SCALE
    gap_label_company = 8 * SCALE
    gap_company_body = 9 * SCALE
    gap_body_contacts = 14 * SCALE
    contact_row_gap = 7 * SCALE

    max_text_w = max(
        measure_text(f_name, "Daniel Silva")[0],
        measure_text(f_label, label_text)[0],
        measure_text(f_company, "Mylder Solutions")[0],
        measure_text(f_body, tagline)[0],
    )

    contact_rows = [
        ("mail", "contacto@mylder.mx", WHITE, False),
        ("phone", "+52 442 424 1707", WHITE, True),
        ("web", "mylder.mx", GOLD, False),
    ]

    for kind, text, color, has_wa in contact_rows:
        font = f_contact if color == WHITE else f_link
        row_w = icon_slot + measure_text(font, text)[0]
        if has_wa:
            row_w += measure_text(f_contact_sm, " \u00b7 WhatsApp")[0]
        max_text_w = max(max_text_w, row_w)

    content_h = (
        PAD
        + h_name + gap_name_label
        + h_label + gap_label_company
        + h_company + gap_company_body
        + h_body + gap_body_contacts
        + sum(measure_text(f_contact if c == WHITE else f_link, t)[1] + contact_row_gap for _, t, c, _ in contact_rows)
        - contact_row_gap
        + PAD
    )
    content_h = max(content_h, PAD + logo_h + PAD)
    content_w = text_x + max_text_w + PAD
    img_w = content_w + BORDER * 2
    img_h = content_h + BORDER * 2

    img = Image.new("RGBA", (img_w, img_h), (0, 0, 0, 0))
    draw = ImageDraw.Draw(img)

    inner = (BORDER, BORDER, img_w - BORDER - 1, img_h - BORDER - 1)
    draw.rounded_rectangle(inner, radius=RADIUS, fill=BLUE, outline=GOLD, width=BORDER)

    logo_x = BORDER + PAD
    logo_y = BORDER + PAD + max(0, (content_h - PAD * 2 - logo_h) // 2)
    img.paste(logo, (logo_x, logo_y), logo)

    line_x = BORDER + PAD + LOGO_W + GAP
    line_y1 = BORDER + PAD
    line_y2 = BORDER + content_h - PAD
    draw.rectangle((line_x, line_y1, line_x + LINE_W - 1, line_y2), fill=GOLD)

    y = BORDER + PAD
    y += draw_text_line(draw, text_x, y, "Daniel Silva", f_name, WHITE) + gap_name_label
    y += draw_text_line(draw, text_x, y, label_text, f_label, GOLD) + gap_label_company
    y += draw_text_line(draw, text_x, y, "Mylder Solutions", f_company, WHITE) + gap_company_body
    y += draw_text_line(draw, text_x, y, tagline, f_body, MUTED) + gap_body_contacts

    for kind, text, color, has_wa in contact_rows:
        row_font = f_contact if color == WHITE else f_link
        row_h = max(icon_size, measure_text(row_font, text)[1])
        icon_y = y - row_h // 2 - icon_size // 2 + 2
        draw_contact_icon(draw, kind, text_x, icon_y, icon_size, GOLD)
        text_x_row = text_x + icon_slot
        draw_text_line(draw, text_x_row, y, text, row_font, color)
        if has_wa:
            tw = measure_text(row_font, text)[0]
            draw_text_line(draw, text_x_row + tw, y, " \u00b7 WhatsApp", f_contact_sm, MUTED2)
        y += row_h + contact_row_gap

    return img.convert("RGB")


def write_html_files() -> None:
    html_img = f"""<!--
  FIRMA — Daniel Silva · Mylder Solutions (IMAGEN)
  Gmail: Configuración → Firma → Insertar imagen → daniel-silva-mylder-signature.png
-->

<table cellpadding="0" cellspacing="0" border="0" role="presentation">
  <tr>
    <td>
      <a href="https://mylder.mx/" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
        <img src="{HOSTED_IMG}" alt="Daniel Silva — Mylder Solutions | contacto@mylder.mx | +52 442 424 1707" width="500" style="display:block;border:0;max-width:500px;height:auto;" />
      </a>
    </td>
  </tr>
</table>"""

    preview = f"""<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Firma Daniel Silva — Mylder</title>
  <style>
    body {{
      margin: 0;
      min-height: 100vh;
      font-family: "DM Sans", "Segoe UI", Helvetica, Arial, sans-serif;
      background: #f3f5fa;
      color: #041e42;
      padding: 32px 20px 48px;
    }}
    .wrap {{ max-width: 560px; margin: 0 auto; }}
    h1 {{ font-size: 1.35rem; margin: 0 0 8px; }}
    p, li {{ font-size: 0.95rem; line-height: 1.6; }}
    .card {{
      background: #fff;
      border: 1px solid rgba(4, 30, 66, 0.12);
      border-radius: 12px;
      padding: 20px;
      margin: 20px 0;
    }}
    .sig {{
      display: inline-block;
      margin: 8px 0 16px;
      box-shadow: 0 8px 28px rgba(4, 30, 66, 0.12);
    }}
    .btn {{
      display: inline-block;
      margin: 6px 10px 6px 0;
      padding: 10px 16px;
      background: #041e42;
      color: #fff;
      text-decoration: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
    }}
    .btn-gold {{ background: #f3c400; color: #041e42; }}
    @media print {{
      body {{ background: #fff; padding: 0; }}
      .no-print {{ display: none !important; }}
      .card {{ border: none; box-shadow: none; padding: 0; }}
    }}
  </style>
</head>
<body>
  <div class="wrap">
    <div class="no-print">
      <h1>Firma electrónica — Daniel Silva</h1>
      <p>Usa la <strong>imagen PNG</strong> en Gmail u Outlook (Insertar imagen).</p>
      <div class="card">
        <ol>
          <li>Descarga la imagen con el botón de abajo.</li>
          <li>Gmail → Configuración → Firma → Insertar imagen.</li>
          <li>Opcional: enlace de la imagen a <code>https://mylder.mx/</code>.</li>
        </ol>
        <a class="btn btn-gold" href="./daniel-silva-mylder-signature.png" download="daniel-silva-mylder-signature.png">Descargar firma PNG</a>
        <a class="btn" href="#" onclick="window.print(); return false;">Imprimir / Guardar PDF</a>
      </div>
    </div>
    <div class="card">
      <p class="no-print" style="margin-top:0;"><strong>Vista previa</strong></p>
      <div class="sig">
        <img src="./daniel-silva-mylder-signature.png" alt="Firma Daniel Silva Mylder Solutions" width="500" style="display:block;max-width:100%;height:auto;" />
      </div>
    </div>
  </div>
</body>
</html>"""

    (ROOT / "daniel-silva-mylder.html").write_text(html_img, encoding="utf-8")
    (ROOT / "daniel-silva-mylder-preview.html").write_text(preview, encoding="utf-8")


def main() -> None:
    png = render_signature_png()
    out_local = ROOT / "daniel-silva-mylder-signature.png"
    out_assets = ASSETS / "daniel-silva-signature.png"
    png.save(out_local, format="PNG", optimize=True)
    png.save(out_assets, format="PNG", optimize=True)
    write_html_files()
    print(f"OK: {out_local}")
    print(f"OK: {out_assets}")


if __name__ == "__main__":
    main()
