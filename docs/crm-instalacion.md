# CRM Mylder — Instalación

## Credenciales MySQL (cPanel)

| Campo | Valor correcto | Notas |
|-------|----------------|-------|
| **DB_NAME** | `bnfivdwn_mylderbdA` | El campo "Anfitrión" en tu gestor de contraseñas es el **nombre de la BD**, no el host |
| **DB_USER** | `bnfivdwn_m1ld3rd0l1` | Usuario MySQL |
| **DB_PASS** | *(tu contraseña)* | No subir a git |
| **DB_HOST** | `localhost` | Desde PHP en el mismo hosting. El IP `75.102.22.162` es acceso remoto/phpMyAdmin, no uses ese IP en PHP |

## Pasos

1. **phpMyAdmin** → selecciona BD `bnfivdwn_mylderbdA` → pestaña SQL → ejecuta `sql/mylder_crm.sql`

2. Copia `api/settings.example.php` → `api/settings.php` (o añade las constantes DB_* a tu settings existente):

```php
const DB_HOST = "localhost";
const DB_NAME = "bnfivdwn_mylderbdA";
const DB_USER = "bnfivdwn_m1ld3rd0l1";
const DB_PASS = "TU_PASSWORD_REAL";
const DB_CHARSET = "utf8mb4";
```

3. Sube la carpeta `/crm/` y `/api/db.php` al hosting

4. Visita **una sola vez**: `https://mylder.mx/crm/setup-once.php` → crea el admin → **elimina ese archivo**

5. Entra en `https://mylder.mx/crm/login.php`

6. Admin: importa CSV en **Importar CSV**, crea agentes en **Usuarios**

## URL del CRM

`https://mylder.mx/crm/`
