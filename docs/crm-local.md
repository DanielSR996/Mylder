# Arrancar CRM en local (XAMPP)

## Arrancar (usa PHP, NO Live Server)

**Cierra Live Server** en el puerto 5501 y ejecuta:

```powershell
.\scripts\start-crm-local.ps1
```

Esto levanta el servidor PHP de XAMPP en `http://127.0.0.1:5501/`

| Qué | URL |
|-----|-----|
| CRM | http://127.0.0.1:5501/crm/ |
| Login | http://127.0.0.1:5501/crm/login.php |
| Sitio público | http://127.0.0.1:5501/index.html |

### ¿Por qué se descarga el .php?

**Live Server** (5501) solo sirve HTML/CSS/JS. No interpreta PHP.
Para probar el CRM necesitas el servidor PHP (`php -S`), no "Open with Live Server".

## Probar conexión a BD de producción

```powershell
C:\xampp\php\php.exe api/test-db.php
```

## URLs

- Test BD: ejecutar script arriba
- Setup admin (solo si usuarios = 0): http://localhost:8080/crm/setup-once.php
- Login: http://localhost:8080/crm/login.php

## Requisito en cPanel

Para conectar desde tu PC, en **cPanel → MySQL remoto** agrega tu IP pública.
Sin eso, la conexión remota fallará aunque usuario/contraseña sean correctos.

En el servidor (producción) no uses `settings.local.php`; ahí `DB_HOST` sigue siendo `localhost`.
