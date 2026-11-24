# Tienda_Linea_Auditoria-

Repositorio oficial para las actividades de **Software Security** (Unidad 1) — Ciclo 8vo "A".  
**Integrantes:**
- Bradley Alexander Poma Vera
- Estefania Carolina Torres Balcazar
- Andersson Leandro Ambuludi Gomez

**Docente:** Ing. Valeria Herrera Salazar  
**Repositorio GitHub:** [https://github.com/Estefania12-torres/Tienda_Linea_Auditoria-.git](https://github.com/Estefania12-torres/Tienda_Linea_Auditoria-.git)

---

## 📌 Acceso Rápido a Entregas de la Unidad 1 (APE 01 y APE 02)

Para facilitar la navegación y evaluación sumativa de la Unidad 1, a continuación se enlazan formalmente los entregables de las guías prácticas:

| Guía / Actividad | Fase / Componente | Descripción y Entregable | Enlace Directo |
|---|---|---|---|
| **APE Nro. 001** | **Fase 2: CIA** | Matriz de activos de información y amenazas según la tríada CIA. | [📄 matriz_activos_CIA.md](./matriz_activos_CIA.md) |
| **APE Nro. 001** | **Seguridad BD** | Script de privilegios mínimos en MySQL (`app_runtime` y `app_migrator`). | [📄 hardening_db.sql](./hardening_db.sql) |
| **APE Nro. 001** | **Continuidad** | Propuesta de hashes, RPO/RTO y estrategia 3-2-1-1-0. | [Sección Hashes y Continuidad](#hashes-y-continuidad-r05) |
| **APE Nro. 002** | **Fase 01** | Criptografía aplicada: hashing (Argon2id/bcrypt), salting y gestión de secretos. | [📁 APE-02/Fase-01/README.md](./APE-02/Fase-01/README.md) |
| **APE Nro. 002** | **Fase 02** | Autenticación, análisis de tokens JWT y matriz de pruebas IDOR remediadas. | [📁 APE-02/Fase-02/README.md](./APE-02/Fase-02/README.md) |
| **APE Nro. 002** | **Fase 03** | Inyecciones SQLi y XSS: ciclo Hallazgo → Mitigación → Verificación y evidencias. | [📁 APE-02/Fase-03/README.md](./APE-02/Fase-03/README.md) |
| **APE Nro. 002** | **Fase 04** | Análisis estático SAST (Semgrep/CodeQL), Dockerfile endurecido y escaneo Trivy. | [📁 APE-02/Fase-04/README.md](./APE-02/Fase-04/README.md) |
| **APE Nro. 002** | **Contenedor** | Archivo Dockerfile endurecido con las 3 buenas prácticas obligatorias. | [🐳 APE-02/Fase-04/Dockerfile](./APE-02/Fase-04/Dockerfile) |

---

## 📁 Estructura del Repositorio APE-02

La carpeta de entrega [`/APE-02/`](./APE-02/) se encuentra organizada según el formato único exigido para el EVA:

```text
📁 Tienda_Linea_Auditoria- / APE-02 /
├── Fase-01/ (Criptografía y Hashes)
│   └── README.md
├── Fase-02/ (Análisis JWT y Pruebas IDOR)
│   └── README.md
├── Fase-03/ (Explotación y Mitigación SQLi y XSS)
│   ├── README.md
│   └── evidencias/
│       ├── sqli-comilla-antes.png
│       ├── sqli-tautologia-antes.png
│       ├── xss-reflejado-antes.png
│       ├── sqli-comilla-despues.png
│       ├── sqli-tautologia-despues.png
│       ├── sqli-parametro-400-despues.png
│       └── xss-reflejado-despues.png
└── Fase-04/ (SAST, Dockerfile Endurecido y Escaneo de Imagen)
    ├── README.md
    ├── Dockerfile
    ├── .dockerignore
    └── evidencias/
        ├── sast-semgrep-scan.png
        ├── sast-codeql-summary.png
        ├── docker-build-hardened.png
        └── docker-trivy-scan.png
```

---

## Configuración de Base de Datos

La aplicación carga la configuración desde variables de entorno. Para desarrollo local, copia `.env.example` como `.env` y completa los valores para las bases de datos `onlineshop` y `ecommerece`. El archivo `.env` está estrictamente excluido de Git mediante `.gitignore` y `.dockerignore`; no subas credenciales al repositorio.

## Cabeceras de Seguridad (Apache)

`.htaccess` agrega CSP, `X-Frame-Options`, `X-Content-Type-Options`, elimina `X-Powered-By`, no habilita CORS y envía HSTS solo en HTTPS. Apache debe tener habilitado `mod_headers` y permitir `AllowOverride FileInfo`; HSTS no protege conexiones HTTP.

El sitio aún usa scripts y estilos en línea, por eso la CSP inicial permite `'unsafe-inline'` para conservar su funcionamiento. Esto limita la protección CSP contra XSS; para una política más estricta hay que migrar esos bloques a archivos o nonces. La política también permite los dominios externos requeridos por las fuentes del sitio.

Para ocultar la versión de Apache, configura en el `httpd.conf` del servidor (no en `.htaccess`) `ServerTokens Prod` y `ServerSignature Off`, y reinicia Apache. `.user.ini` desactiva `expose_php` cuando PHP se ejecuta con CGI/FastCGI; con mod_php, configura `expose_php=Off` en `php.ini` y reinicia el servidor.

## Sesiones y Límite de Intentos de Inicio de Sesión

Las sesiones usan cookies `HttpOnly` y `SameSite=Strict`, y activan el modo estricto de PHP. `Secure` se habilita automáticamente en HTTPS; define `SESSION_SECURE_COOKIE=true` en el entorno si TLS termina en un proxy, o `false` solo para desarrollo HTTP local. Las sesiones vencen después de 15 minutos de inactividad o 8 horas de duración total. Los inicios de sesión regeneran el ID de sesión y el cierre de sesión borra sus datos.

El panel administrativo valida el rol `admin` en cada ruta y exige tokens CSRF en las operaciones POST protegidas. El registro público de administradores está deshabilitado: la cuenta inicial debe provisionarse mediante un procedimiento administrativo confiable antes de usar el panel. Los manejadores de inicio de sesión permiten cinco intentos por IP en 15 minutos; el siguiente responde HTTP 429. Los fallos y bloqueos se registran en el log de errores de PHP; el contador temporal se guarda fuera del directorio público en el directorio temporal del sistema.

`payment_success.php` no registra pedidos hasta que se configure y valide la respuesta del proveedor de pagos en el servidor. Actualmente responde con HTTP 503 para una confirmación que no pueda verificarse; no uses este callback para pagos reales.

## Roles de Base de Datos

`hardening_db.sql` crea `app_migrator` para cambios de esquema y `app_runtime` para las operaciones de datos de la aplicación en `onlineshop` y `ecommerece`. Antes de ejecutarlo, reemplaza los dos marcadores de contraseña por valores distintos y fuertes. Ejecútalo como administrador de MySQL/MariaDB; revisa ambos resultados de `SHOW GRANTS` y confirma que no existan permisos globales adicionales.

Cuando hayas creado los usuarios, actualiza `SHOP_DB_USER`, `SHOP_DB_PASSWORD`, `AUTH_DB_USER` y `AUTH_DB_PASSWORD` en tu `.env` local. El ejemplo usa `app_runtime` como usuario y no contiene contraseñas. Conserva las credenciales de `app_migrator` fuera de la aplicación web. La cuenta runtime conserva `DELETE` porque el código elimina artículos de carritos/listas y permite borrar productos/pedidos desde administración; no recibe permisos DDL como `DROP`.

## Hashes y Continuidad (R05)

Los registros nuevos usan `password_hash` de PHP (`PASSWORD_DEFAULT` o Argon2id). Tras un inicio de sesión válido, las contraseñas heredadas en texto plano o MD5 se convierten a hashes seguros; para clientes también se actualiza la fila asociada de `user_info_backup`. Las columnas del esquema `onlineshop` admiten estos hashes. Verifica que `ecommerece.register.password` admita al menos 60 caracteres antes de usar ese flujo heredado. No se puede convertir un MD5 sin que el usuario inicie sesión con su clave o la restablezca.

La propuesta de continuidad del negocio establece **RPO 24 horas** y **RTO 4 horas**. El esquema 3-2-1-1-0 requiere tres copias, dos tipos de almacenamiento, una copia fuera del sitio, una aislada/inmutable con Object Lock o WORM y restauraciones verificadas sin errores.
