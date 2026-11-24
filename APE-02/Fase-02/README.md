# Fase 2 — Análisis de Autenticación, JWT y Pruebas IDOR

## Guía Práctica APE Nro. 002 — Software Security
**Unidad 1:** R1. Diseña e implementa software bajo los principios básicos de estándares seguros.  
**Integrantes:** Bradley Alexander Poma Vera, Estefania Carolina Torres Balcazar, Andersson Leandro Ambuludi Gomez.  
**Ciclo y Paralelo:** 8vo "A"  
**Docente:** Ing. Valeria Herrera Salazar  

---

## 1. Introducción y Alcance

La Fase 2 se centra en auditar los mecanismos de autenticación, la gestión de sesiones y el control de acceso en la aplicación web *Online Shopping System*, analizando la viabilidad de tokens JWT y ejecutando pruebas de **Referencias Directas Inseguras a Objetos (IDOR)** para prevenir el acceso o modificación no autorizada de recursos entre usuarios.

---

## 2. Análisis de Mecanismos de Autenticación y Tokens JWT

### 2.1 Diagnóstico de Autenticación en la Aplicación
Durante la revisión estática y dinámica del código se determinó que la aplicación **no utiliza JSON Web Tokens (JWT)** para la persistencia del estado del usuario, sino **sesiones tradicionales del lado del servidor** gestionadas a través del superglobal `$_SESSION` de PHP.

Por este motivo, se evaluó la robustez de las sesiones del servidor y se implementó un endurecimiento exhaustivo en `session_security.php`:
- Configuración de directivas de cookies seguras: `HttpOnly = true` (bloquea la lectura de la cookie de sesión desde JavaScript contra ataques XSS), `SameSite = Strict` (previene ataques de CSRF) y `Secure = true` en entornos HTTPS.
- Regeneración obligatoria del identificador de sesión mediante `session_regenerate_id(true)` tras un inicio de sesión exitoso para impedir ataques de fijación de sesión (*Session Fixation*).
- Caducidad de sesiones por inactividad (15 minutos) y tiempo de vida máximo absoluto (8 horas).

---

## 3. Matriz de Pruebas y Mitigación de Vulnerabilidades IDOR

Se identificaron tres superficies de riesgo crítico por falta de comprobación de propiedad de recursos (*Object-Level Access Control*):

| ID | Superficie Auditada | Vector de Prueba IDOR | Hallazgo en Revisión de Código | Riesgo e Impacto | Corrección Aplicada en el Proyecto |
|---|---|---|---|---|---|
| **IDOR-01** | `payment_success.php` | Manipulación del parámetro `cm` en la URL para consultar carritos ajenos. | El script procesaba el identificador del carrito recibido directamente por GET sin cotejar si pertenecía al usuario de la sesión activa. | Un usuario autenticado podía visualizar compras o alterar el estado de pedidos de otros clientes. | Validación estricta en servidor: se exige que el identificador pertenezca a `$_SESSION['uid']`, bloqueando peticiones no coincidentes con HTTP 403. |
| **IDOR-02** | `admin/admin/manageuser.php` | Modificación del parámetro `user_id` en una petición GET para eliminar cuentas. | El script ejecutaba el borrado mediante `GET` sin verificar si quien realizaba la solicitud poseía el rol `admin` ni verificar tokens CSRF. | Cualquier usuario o atacante con un enlace forjado podía eliminar usuarios arbitrarios del sistema. | Se transformó la operación a método `POST`, se implementó `require_admin_access()`, `require_admin_csrf_token()` y validación entera de `user_id`. |
| **IDOR-03** | `admin/admin/orders.php` | Alteración del parámetro `order_id` para eliminar registros de pedidos. | La eliminación de pedidos no validaba privilegios administrativos en el backend ni utilizaba tokens de protección contra peticiones forjadas. | Pérdida de integridad de transacciones comerciales y borrado no autorizado del historial de pedidos. | Restricción estricta mediante control de acceso por roles (RBAC) en servidor y token anti-CSRF obligatorio en la solicitud. |

---

## 4. Preguntas de Control (Fase 2)

### Pregunta de Control A:
> **¿Qué riesgos introduce un token JWT sin expiración o aceptado con el algoritmo `none`?**

**Respuesta:**  
- **Token sin expiración (`exp` ausente):** Si un atacante intercepta o extrae el token (por ejemplo mediante XSS, fuga en registros o tráfico no cifrado), el token conservará validez indefinidamente en el tiempo. La aplicación no podrá revocarlo de manera estándar sin implementar listas negras en el servidor, permitiendo al atacante suplantar la identidad de la víctima de forma permanente.
- **Aceptación del algoritmo `none`:** La especificación de JWT admite el header `{"alg": "none"}` para tokens no firmados. Si una biblioteca o servidor mal configurado confía en este algoritmo, un atacante puede tomar un token legítimo, modificar los *claims* en el cuerpo (por ejemplo, cambiar `"role": "user"` a `"role": "admin"` o `"user_id": 1`), eliminar la firma y enviarlo al servidor. El backend aceptará el token como auténtico, permitiendo una **escalada vertical y horizontal total de privilegios sin necesidad de conocer la clave secreta**.

---

### Pregunta de Control B:
> **¿En qué se diferencia un fallo de autenticación de un fallo de autorización como IDOR, y por qué los controles deben verificarse en el servidor y no en el cliente?**

**Respuesta:**  
- **Autenticación (AuthN):** Responde a la pregunta *"¿Quién eres?"*. Verifica la identidad del usuario (mediante credenciales válidas, contraseñas, factores biométricos o tokens). Un fallo de autenticación permite a un atacante acceder al sistema fingiendo ser otro usuario o saltándose el inicio de sesión (ej. SQLi en el login).
- **Autorización (AuthZ / IDOR):** Responde a la pregunta *"¿Qué tienes permitido hacer o ver?"*. Asume que el usuario ya está legítimamente autenticado, pero falla en verificar si tiene permisos legítimos para acceder, modificar o eliminar un registro u objeto específico (identificado por un ID en la URL o cuerpo).
- **Por qué los controles deben residir exclusivamente en el servidor:**  
  El entorno del cliente (navegador, JavaScript, peticiones HTTP, campos ocultos de formularios) está **completamente bajo el control del usuario**. Un usuario malintencionado puede alterar parámetros mediante herramientas de desarrollo (F12), interceptar y modificar solicitudes con proxies (Burp Suite, OWASP ZAP) o generar peticiones manuales con `curl`.  
  Cualquier validación efectuada únicamente en el cliente (como deshabilitar botones u ocultar enlaces) es meramente cosmética; **el servidor es la única entidad confiable capaz de contrastar la identidad de la sesión con los permisos sobre el objeto solicitado.**
