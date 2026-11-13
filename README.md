# Tienda_Linea_Auditoria-

## Configuracion de base de datos

La aplicacion carga la configuracion desde variables de entorno. Para desarrollo local, copia `.env.example` como `.env` y completa los valores para las bases de datos `onlineshop` y `ecommerece`. El archivo `.env` esta excluido de Git; no subas credenciales al repositorio.

## Cabeceras de seguridad (Apache)

`.htaccess` agrega CSP, `X-Frame-Options`, `X-Content-Type-Options`, elimina `X-Powered-By`, no habilita CORS y envia HSTS solo en HTTPS. Apache debe tener habilitado `mod_headers` y permitir `AllowOverride FileInfo`; HSTS no protege conexiones HTTP.

El sitio aun usa scripts y estilos en linea, por eso la CSP inicial permite `'unsafe-inline'` para conservar su funcionamiento. Esto limita la proteccion CSP contra XSS; para una politica mas estricta hay que migrar esos bloques a archivos o nonces. La politica tambien permite los dominios externos requeridos por las fuentes del sitio.

Para ocultar la version de Apache, configura en el `httpd.conf` del servidor (no en `.htaccess`) `ServerTokens Prod` y `ServerSignature Off`, y reinicia Apache. `.user.ini` desactiva `expose_php` cuando PHP se ejecuta con CGI/FastCGI; con mod_php, configura `expose_php=Off` en `php.ini` y reinicia el servidor.

## Sesiones y limite de intentos de inicio de sesion

Las sesiones usan cookies `HttpOnly` y `SameSite=Strict`, y activan el modo estricto de PHP. `Secure` se habilita automaticamente en HTTPS; define `SESSION_SECURE_COOKIE=true` en el entorno si TLS termina en un proxy, o `false` solo para desarrollo HTTP local. Las sesiones vencen despues de 15 minutos de inactividad o 8 horas de duracion total. Los inicios de sesion regeneran el ID de sesion y el cierre de sesion borra sus datos.

El panel administrativo valida el rol `admin` en cada ruta y exige tokens CSRF en las operaciones POST protegidas. El registro publico de administradores esta deshabilitado: la cuenta inicial debe provisionarse mediante un procedimiento administrativo confiable antes de usar el panel. Los manejadores de inicio de sesion permiten cinco intentos por IP en 15 minutos; el siguiente responde HTTP 429. Los fallos y bloqueos se registran en el log de errores de PHP; el contador temporal se guarda fuera del directorio publico en el directorio temporal del sistema.

`payment_success.php` no registra pedidos hasta que se configure y valide la respuesta del proveedor de pagos en el servidor. Actualmente responde con HTTP 503 para una confirmacion que no pueda verificarse; no uses este callback para pagos reales.

## Roles de base de datos

`hardening_db.sql` crea `app_migrator` para cambios de esquema y `app_runtime` para las operaciones de datos de la aplicacion en `onlineshop` y `ecommerece`. Antes de ejecutarlo, reemplaza los dos marcadores de contrasena por valores distintos y fuertes. Ejecutalo como administrador de MySQL/MariaDB; revisa ambos resultados de `SHOW GRANTS` y confirma que no existan permisos globales adicionales.

Cuando hayas creado los usuarios, actualiza `SHOP_DB_USER`, `SHOP_DB_PASSWORD`, `AUTH_DB_USER` y `AUTH_DB_PASSWORD` en tu `.env` local. El ejemplo usa `app_runtime` como usuario y no contiene contrasenas. Conserva las credenciales de `app_migrator` fuera de la aplicacion web. La cuenta runtime conserva `DELETE` porque el codigo elimina articulos de carritos/listas y permite borrar productos/pedidos desde administracion; no recibe permisos DDL como `DROP`.

## Hashes y continuidad (R05)

Los registros nuevos usan `password_hash` de PHP (bcrypt por defecto). Tras un inicio de sesion valido, las contrasenas heredadas en texto plano o MD5 se convierten a bcrypt; para clientes tambien se actualiza la fila asociada de `user_info_backup`. Las columnas del esquema `onlineshop` admiten estos hashes. Verifica que `ecommerece.register.password` admita al menos 60 caracteres antes de usar ese flujo heredado. No se puede convertir un MD5 sin que el usuario inicie sesion con su clave o la restablezca. La evidencia y el plan de recuperacion propuesto estan en [informe_R05_hashes_backups.md](./informe_R05_hashes_backups.md).

La propuesta inicial es **RPO 24 horas** y **RTO 4 horas**, pendientes de aprobacion del equipo y de una prueba real de restauracion. El esquema 3-2-1-1-0 requiere tres copias, dos tipos de almacenamiento, una copia fuera del sitio, una aislada/inmutable con Object Lock o WORM y restauraciones verificadas sin errores.