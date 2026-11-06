# Informe criptográfico — Fase 1

- **Proyecto:** Tienda en línea (PHP y MySQL)
- **Estudiante:** Estefania Torres
- **Fecha:** 30/10/2026
- **Alcance:** revisión estática del repositorio, esquema SQL y comparación local con una contraseña sintética. No se consultaron bases de datos desplegadas ni se usaron credenciales reales.

## 1. Repaso aplicado

Una función hash transforma una entrada en una salida de longitud fija; no cifra la contraseña ni permite recuperarla de forma legítima. Las contraseñas deben almacenarse con una función lenta diseñada para ello, no con hashes rápidos de propósito general.

- **Salt:** valor aleatorio y único por contraseña. Evita que dos contraseñas iguales produzcan el mismo hash y dificulta el uso de tablas precalculadas. No necesita mantenerse secreto; las funciones recomendadas lo incluyen en su formato de almacenamiento.
- **Recomendados:** Argon2id cuando está disponible y configurado adecuadamente; bcrypt como alternativa ampliamente soportada. Ajustar el costo a la capacidad del servidor y verificar con la función correspondiente (`password_verify()` en PHP).
- **Desaconsejados:** MD5 y SHA-1 para contraseñas por ser demasiado rápidos y no ofrecer resistencia suficiente ante ataques de adivinación. Añadirles un salt manual no los convierte en funciones adecuadas para contraseñas.
- **Cifrado en tránsito:** HTTPS protege la comunicación entre cliente y servidor mediante TLS. No sustituye el hash seguro de contraseñas ni se puede confirmar únicamente leyendo el código de la aplicación.

## 2. Evidencia y hallazgos

| ID | Hallazgo y evidencia revisada | Riesgo preliminar |
|---|---|---|
| C-01 | Las contraseñas de clientes se insertan sin hash en [`register.php`](./register.php#L139) y se comparan directamente en [`login.php`](./login.php#L16). El formulario de administración también crea y actualiza usuarios con el valor recibido directamente en [`admin/admin/addsuppliers.php`](./admin/admin/addsuppliers.php#L11) y [`admin/admin/edituser.php`](./admin/admin/edituser.php#L11). | **Alto** — una lectura de la base de datos o de sus copias expone las contraseñas utilizables. |
| C-02 | El registro y el inicio de sesión de administradores aplican MD5 en [`admin/server/server.php`](./admin/server/server.php#L54) y [`admin/server/server.php`](./admin/server/server.php#L84); el acceso administrativo alternativo también calcula MD5 en [`login.php`](./login.php#L80). El cambio de contraseña del administrador conserva MD5 en [`admin/admin/profile.php`](./admin/admin/profile.php#L10). El flujo alternativo de [`config.php`](./config.php#L55) también usa MD5. No se observa salt por usuario. | **Alto** — MD5 es inadecuado para el almacenamiento de contraseñas. |
| C-03 | La pantalla de gestión obtiene y muestra el campo de contraseña de clientes en [`admin/admin/manageuser.php`](./admin/admin/manageuser.php#L41); otra lista administrativa muestra todos los campos de usuario en [`admin/admin/index.php`](./admin/admin/index.php#L43). El volcado define `user_info_backup` y un trigger que copia el campo `password` desde `user_info` en [`database/onlineshop.sql`](./database/onlineshop.sql#L354) y [`database/onlineshop.sql`](./database/onlineshop.sql#L376). | **Alto** — amplía los lugares y personas que podrían acceder a contraseñas o a sus copias. |
| C-04 | Hay parámetros de conexión fijados en el código y configuraciones de desarrollo con contraseña vacía en [`db.php`](./db.php#L3), [`admin/admin/includes/db.php`](./admin/admin/includes/db.php#L3), [`admin/server/server.php`](./admin/server/server.php#L16) y [`config.php`](./config.php#L11). El flujo `config.php` apunta a una base y tabla de registro distintas, que no aparecen en `database/onlineshop.sql`; por eso ese flujo no se pudo contrastar con el volcado disponible. | **Alto si se despliega así** — los privilegios de la cuenta y los secretos deben salir del código y separarse por entorno. |
| C-05 | El esquema admite un campo de hasta 300 caracteres para la contraseña en `user_info` y `admin_info`; el volcado contiene registros de ejemplo en texto legible y un hash MD5. No se reproducen aquí valores, correos ni hashes del volcado. | **Alto** — tratar los datos de muestra y copias publicados como expuestos; reemplazarlos por datos ficticios y rotar cualquier credencial real que haya sido reutilizada. |

### Cifrado en tránsito y cabeceras

La búsqueda estática no encontró configuración de HTTPS ni cabeceras de seguridad dentro del código PHP revisado. Esto **no demuestra** que estén ausentes en el servidor: podrían configurarse en Apache, Nginx, un proxy o la plataforma de despliegue. Los enlaces HTTPS a recursos externos tampoco prueban que la tienda se sirva por HTTPS.

**Estado de verificación:** pendiente. En este entorno no hay PHP CLI ni instancia local de la aplicación disponible para ejecutar una comprobación de extremo a extremo. No se afirma que HTTPS o sus cabeceras hayan sido probados.

Al iniciar la aplicación local, registrar la URL, fecha y resultado de estas comprobaciones (sustituir la ruta por la URL real):

```powershell
curl.exe -I http://localhost/ruta/
curl.exe -I https://localhost/ruta/
```

La segunda respuesta debe proceder de una conexión TLS válida. Revisar además `Strict-Transport-Security` (solo tras confirmar HTTPS en todos los hosts cubiertos), `Content-Security-Policy`, `X-Content-Type-Options` y `Referrer-Policy`. Verificar `Secure`, `HttpOnly` y `SameSite` en las cookies de sesión mediante las herramientas de red del navegador; ocultar cookies y datos personales en las capturas.

## 3. Comparación de hashes con entrada sintética

Se usó una contraseña de demostración no asociada a ninguna cuenta. La comparación se ejecutó localmente con Python y la biblioteca Argon2 disponible; no se leyeron contraseñas del SQL ni de la aplicación.

| Prueba | Resultado |
|---|---|
| Calcular MD5 dos veces para la misma entrada | Los resultados son idénticos; el algoritmo no agrega salt automáticamente. |
| Calcular Argon2id dos veces para la misma entrada | Los resultados codificados son distintos por sus salts aleatorios. |
| Verificar la entrada contra uno de los hashes Argon2id | Verificación correcta (`True`). |
| Formato Argon2id codificado | Identifica `argon2id`; longitud observada: 97 caracteres. |

La longitud cabe en la columna de 300 caracteres ya existente. Los resultados son de una prueba sintética y no constituyen una prueba de configuración del despliegue.

Código de la prueba (requiere Python y `argon2-cffi`):

```python
import hashlib
from argon2 import PasswordHasher, Type

password = "Demo-Fase1-2026-no-real-credential"
hasher = PasswordHasher(type=Type.ID)
md5_first = hashlib.md5(password.encode()).hexdigest()
md5_second = hashlib.md5(password.encode()).hexdigest()
argon_first = hasher.hash(password)
argon_second = hasher.hash(password)

print(md5_first == md5_second)
print(argon_first != argon_second)
print(hasher.verify(argon_first, password))
print(argon_first.split("$")[1], len(argon_first))
```

## 4. Propuesta de migración y gestión de secretos

1. **Contener y preparar:** restringir el acceso al volcado y a las copias, reemplazar datos de muestra por datos ficticios y rotar credenciales reales que se hayan expuesto o reutilizado. Retirar de los paneles cualquier visualización de contraseñas y eliminar la copia redundante del campo de contraseña del trigger/tabla de respaldo.
2. **Migrar cuentas existentes:** exigir restablecimiento de contraseña para cuentas almacenadas en texto claro y para cuentas con MD5. No convertir esos valores en nuevos hashes como si fueran contraseñas confiables: un hash rápido o una contraseña expuesta no se vuelve segura por re-hashearla. Confirmar el procedimiento y las copias antes de una migración de producción.
3. **Cambiar todos los flujos:** centralizar la autenticación para clientes y administradores. En el alta y cambio de contraseña, usar `password_hash()` con Argon2id si el PHP desplegado lo soporta; de lo contrario, usar `PASSWORD_DEFAULT` (bcrypt en versiones compatibles). En el inicio de sesión, buscar la cuenta por identificador mediante consulta preparada, recuperar el hash y validarlo con `password_verify()`. Aplicar `password_needs_rehash()` al autenticar si cambian los parámetros.
4. **Cerrar caminos alternos:** revisar también `config.php`, los formularios administrativos y las herramientas de gestión para que no escriban ni devuelvan contraseñas en texto claro. No conservar una ruta de autenticación MD5 como compatibilidad permanente.
5. **Gestionar configuración:** inyectar credenciales mediante variables de entorno o un gestor de secretos; usar una cuenta MySQL exclusiva para la aplicación con privilegios mínimos; separar desarrollo, pruebas y producción; no versionar secretos y mantenerlos fuera de los respaldos no protegidos.
6. **Asegurar transporte y sesiones:** configurar TLS en el servidor o proxy, redirigir HTTP a HTTPS y configurar las cookies de sesión con `Secure`, `HttpOnly` y `SameSite` adecuado. Añadir cabeceras de seguridad en el componente que termina TLS y verificar el resultado con una instancia activa.
7. **Validar antes de desplegar:** probar altas, inicios de sesión, restablecimientos y cambios de contraseña para ambos tipos de cuenta; verificar que nunca se escribe ni presenta la contraseña original, que hashes idénticos de contraseñas iguales difieren por salt y que las conexiones de producción no aceptan credenciales de otro entorno.

## 5. Conclusión

La evidencia del repositorio muestra que las contraseñas de clientes se almacenan en texto claro y que varias rutas administrativas usan MD5; también se copian o se muestran valores del campo de contraseña. La prioridad es contener los datos existentes, forzar el restablecimiento de credenciales afectadas y cambiar todos los flujos a hashes adaptativos con verificación segura. La revisión de HTTPS, TLS, cabeceras y cookies queda pendiente de una instancia local o de despliegue disponible.

### Anexos por completar

- Adjuntar capturas de las líneas de evidencia indicadas, ocultando valores del SQL, hashes, correos y cualquier otro dato identificable.
- Registrar los resultados de `curl.exe -I` y la inspección de cookies una vez levantado el entorno HTTPS local.
