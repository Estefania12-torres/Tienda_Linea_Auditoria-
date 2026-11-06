# Informe R05: hashes de contrasenas y resiliencia

## Estado de implementacion

- Los formularios de alta de clientes, administradores y usuarios creados desde administracion guardan contrasenas con `password_hash` de PHP (bcrypt por defecto).
- El login verifica hashes modernos con `password_verify`.
- Para migrar cuentas antiguas, el primer login valido acepta el formato anterior (texto plano o MD5) y lo reemplaza por bcrypt. Las filas emparejadas de `user_info_backup` tambien se actualizan para clientes.
- La pantalla de cambio de clave de administrador verifica el formato existente y almacena la nueva clave como bcrypt.
- Las columnas `user_info.password`, `user_info_backup.password` y `admin_info.admin_password` del esquema incluido permiten 300 caracteres, suficiente para los hashes actuales.
- El flujo heredado `ecommerece.register` requiere que su columna `password` permita al menos 60 caracteres; confirmar su tipo antes de habilitar ese flujo con hashes.

Verificacion de formato, sin mostrar los hashes:

```sql
SELECT user_id, email,
       CASE
         WHEN password LIKE '$2y$%' THEN 'bcrypt'
         WHEN password LIKE '$argon2id$%' THEN 'argon2id'
         ELSE 'legacy: requiere login o restablecimiento'
       END AS password_status
FROM onlineshop.user_info;
```

La migracion bajo demanda no puede transformar registros antiguos que nadie haya usado. Esas cuentas deben iniciar sesion una vez o completar un restablecimiento seguro de contrasena. No copiar los hashes ni contrasenas a la evidencia.

## Cuentas de prueba para JWT/IDOR

Crear dos cuentas reales desde la aplicacion, cada una con un pedido propio. No se crearon desde este entorno porque no hay servidor PHP ni conexion MySQL disponibles.

| Cuenta | Correo creado | Registro propio (pedido) | Verificado |
|---|---|---|---|
| Usuario A | Completar despues de crear | Completar ID o confirmacion | Pendiente |
| Usuario B | Completar despues de crear | Completar ID o confirmacion | Pendiente |

Usar cuentas de prueba, no cuentas personales, y no incluir contrasenas en el informe.

## Objetivos de recuperacion propuestos

- **RPO propuesto:** 24 horas (perdida maxima de datos objetivo).
- **RTO propuesto:** 4 horas (tiempo objetivo para volver a operar).
- **Aprobacion del equipo:** Pendiente; validar que estos objetivos sean compatibles con las necesidades del proyecto.

## Estrategia 3-2-1-1-0 propuesta

1. Mantener al menos tres copias de los datos, incluida produccion.
2. Distribuirlas en dos tipos de almacenamiento distintos.
3. Mantener al menos una copia fuera del sitio principal.
4. Mantener una copia aislada o inmutable con Object Lock/WORM.
5. Probar restauraciones periodicamente y registrar cero errores de integridad.

No se declara que las copias ni una restauracion hayan sido ejecutadas; completar fechas, ubicaciones, responsable y resultado tras implementar el proceso.
