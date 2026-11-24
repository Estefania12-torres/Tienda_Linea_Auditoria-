# Fase 1 — Criptografía Aplicada: Hashing, Salting y Almacenamiento de Credenciales

## Guía Práctica APE Nro. 002 — Software Security
**Unidad 1:** R1. Diseña e implementa software bajo los principios básicos de estándares seguros.  
**Integrantes:** Bradley Alexander Poma Vera, Estefania Carolina Torres Balcazar, Andersson Leandro Ambuludi Gomez.  
**Ciclo y Paralelo:** 8vo "A"  
**Docente:** Ing. Valeria Herrera Salazar  

---

## 1. Introducción y Alcance

La Fase 1 se enfoca en auditar el almacenamiento y manejo de información sensible en la aplicación web *Online Shopping System*, evaluando el uso de funciones hash criptográficas, salting y la gestión de credenciales y secretos de base de datos.

Durante la revisión inicial del código se identificó que:
- Las credenciales de MySQL estaban escritas directamente en múltiples archivos fuente (`db.php`, `admin/admin/includes/db.php`, `admin/server/server.php`, `config.php`).
- Las contraseñas de usuarios clientes se almacenaban en texto plano sin ninguna función de resumen criptográfico en varias rutas (`register.php`, `login.php`).
- Las contraseñas de administradores se procesaban mediante la función hash obsoleta `MD5` sin salt único por usuario.
- En el panel administrativo (`manageuser.php`) se mostraban contraseñas en texto claro y la base de datos duplicaba contraseñas en tablas de respaldo mediante triggers (`user_info_backup`).

---

## 2. Matriz de Hallazgos Técnicos (Fase 1)

| ID | Elemento Auditado | Hallazgo Identificado | Nivel de Riesgo | Criterio de Observación y Remediación |
|---|---|---|---|---|
| **H-01** | `db.php` | Credenciales y parámetros de conexión a MySQL quemados directamente en el código fuente. | **Alto** | Externalizar secretos fuera del código fuente usando variables de entorno (`.env`) no versionadas. |
| **H-02** | `admin/admin/includes/db.php` | Duplicación de datos de conexión a la base de datos en módulos administrativos. | **Alto** | Centralizar la configuración de conexión en un único punto seguro y retirar credenciales duplicadas. |
| **H-03** | `admin/server/server.php` | Configuración de conexión de desarrollo con contraseña vacía y usuario `root`. | **Alto** | Separar entornos de desarrollo y producción; exigir contraseñas robustas y usuarios con privilegios mínimos. |
| **H-04** | `config.php` | Credenciales directas y configuraciones alternativas de base de datos expuestas. | **Alto** | Unificar la carga de configuración mediante variables de entorno y eliminar accesos redundantes. |
| **H-05** | `register.php`, `login.php`, `admin/admin/addsuppliers.php`, `admin/admin/edituser.php` | Contraseñas de clientes almacenadas en texto plano y comparadas directamente en login. | **Alto** | Reemplazar por funciones adaptativas modernas (`password_hash` con Argon2id / bcrypt) y `password_verify`. |
| **H-06** | `admin/server/server.php`, `login.php`, `admin/admin/profile.php` | Uso de `md5()` para procesar contraseñas de administración sin salting. | **Alto** | MD5 es vulnerable a colisiones y ataques rápidos de fuerza bruta con GPU; migrar a Argon2id. |
| **H-07** | `admin/admin/manageuser.php`, `database/onlineshop.sql` | Visualización de contraseñas en listados y duplicación mediante trigger en tabla `user_info_backup`. | **Alto** | Ocultar campos de contraseñas de vistas administrativas y eliminar triggers que copien claves legibles. |

---

## 3. Propuesta de Migración del Almacenamiento de Contraseñas

Se estableció el siguiente procedimiento para la actualización segura de contraseñas:

1. **Implementación de Argon2id / bcrypt:** Utilizar `password_hash($password, PASSWORD_ARGON2ID)` (o `PASSWORD_DEFAULT` según compatibilidad del servidor) que incorpora salting criptográfico aleatorio e internamente gestionado.
2. **Actualización de Flujo de Autenticación:** Sustituir comparaciones de cadenas directas y funciones `md5()` por `password_verify($password, $hash_almacenado)`.
3. **Mecanismo de Rehash Automático:** Emplear `password_needs_rehash()` durante inicios de sesión válidos para elevar el costo computacional progresivamente conforme evolucione el hardware.
4. **Restablecimiento Obligatorio:** Dado que los hashes MD5 y textos planos antiguos no pueden convertirse matemáticamente en hashes seguros de forma directa sin la contraseña en claro del usuario, se implementa una política de actualización en el siguiente login o reseteo obligatorio de contraseñas heredadas.

---

## 4. Gestión Segura de Secretos

Para resolver la exposición de credenciales en el código:
- Se eliminaron las credenciales hardcodeadas de `db.php` y `config.php`.
- Se adoptó la carga de variables de entorno mediante archivo `.env` local.
- Se configuró el archivo `.gitignore` para impedir la publicación accidental del archivo `.env` en GitHub.
- Se proporcionó un archivo plantilla `.env.example` documentando las variables requeridas sin contener valores reales ni contraseñas.
- Se creó el script `hardening_db.sql` estableciendo usuarios de base de datos con privilegios mínimos (`app_runtime` para DML básico y `app_migrator` para cambios de esquema).

---

## 5. Preguntas de Control (Fase 1)

### Pregunta de Control Nro. 1:
> **¿Por qué el almacenamiento de contraseñas con funciones hash rápidas como MD5 o SHA-1 resulta inseguro, incluso cuando se aplica un salt?**

**Respuesta:**  
Las funciones como MD5 y SHA-1 fueron diseñadas originalmente para verificar la integridad de archivos y transferencias de datos, priorizando la máxima velocidad de cálculo por segundo. Esa misma velocidad informática se convierte en su mayor debilidad ante el almacenamiento de contraseñas: un atacante que obtenga la base de datos puede ejecutar cientos de miles de millones de intentos por segundo utilizando clústeres de tarjetas gráficas (GPU) modernas o circuitos ASIC.  
Aunque el *salt* evita el uso de tablas arcoíris (*rainbow tables*) precalculadas, **no mitiga la velocidad masiva del ataque de fuerza bruta ni de diccionario**: el atacante simplemente calcula `MD5(candidata + salt)` para cada intento a una velocidad insostenible para contraseñas de longitud típica.

---

### Pregunta de Control Nro. 2:
> **¿Qué propiedades debe reunir un algoritmo de almacenamiento de credenciales como bcrypt o Argon2id para resistir los ataques de diccionario y de fuerza bruta modernos?**

**Respuesta:**  
Un algoritmo robusto de almacenamiento de credenciales debe reunir las siguientes propiedades:
1. **Factor de Costo Parametrizable (*Work Factor* / *Cost*):** Debe permitir incrementar deliberadamente el tiempo de cómputo requerido para calcular cada hash, logrando que el atacante requiera una fracción significativa de segundo por intento en lugar de microsegundos.
2. **Dureza de Memoria (*Memory-Hardness*):** En el caso de Argon2id (ganador de la *Password Hashing Competition*), requiere el uso intensivo de grandes cantidades de memoria RAM (KB o MB configurables), neutralizando la ventaja de paralelismo masivo de las GPUs y ASICs, que carecen de arquitecturas de memoria amplia por núcleo.
3. **Salting Automático y Criptográficamente Seguro:** Debe generar e incorporar un salt aleatorio y único por usuario de al menos 128 bits extraído de fuentes de entropía seguras del sistema operativo (`/dev/urandom`), impidiendo ataques globales cruzados.
