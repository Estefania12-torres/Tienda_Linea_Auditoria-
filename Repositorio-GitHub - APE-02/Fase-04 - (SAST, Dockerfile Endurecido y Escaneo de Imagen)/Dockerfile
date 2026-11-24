# ==============================================================================
# Dockerfile Endurecido (Hardened Container)
# Aplicación: Tienda en Línea - Online Shopping System (PHP 8.2 + Apache)
# Guía Práctica APE Nro. 002 - Fase 4
# ==============================================================================

# ------------------------------------------------------------------------------
# BUENA PRÁCTICA 1: Imagen base oficial, versión fija y mínima (NUNCA usar :latest)
# Se utiliza Debian Bookworm slim con versión específica de PHP 8.2.20
# ------------------------------------------------------------------------------
FROM php:8.2.20-apache-bookworm AS production

# Metadatos del contenedor
LABEL maintainer="Equipo Auditoría Software Security <estefania.torres@unl.edu.ec>"
LABEL description="Contenedor seguro y endurecido para Tienda Online"
LABEL version="1.0.0"

# Evitar prompts interactivos durante la instalación
ENV DEBIAN_FRONTEND=noninteractive

# Instalar extensiones indispensables de PHP para MySQL y limpiar cachés de apt
# Reducción de superficie de ataque: no se instalan herramientas de desarrollo (gcc, g++, make)
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql gd \
    && apt-get purge -y --auto-remove libpng-dev libjpeg-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Configuración de endurecimiento para Apache y PHP:
# - Desactivar información de versiones del servidor (ServerSignature Off, ServerTokens Prod)
# - Desactivar exposición de cabecera X-Powered-By (expose_php = Off)
RUN sed -i 's/^ServerSignature .*/ServerSignature Off/' /etc/apache2/conf-available/security.conf \
    && sed -i 's/^ServerTokens .*/ServerTokens Prod/' /etc/apache2/conf-available/security.conf \
    && a2enconf security \
    && a2enmod rewrite headers \
    && echo "expose_php=Off" > /usr/local/etc/php/conf.d/security-hardening.ini \
    && echo "session.cookie_httponly=1" >> /usr/local/etc/php/conf.d/security-hardening.ini \
    && echo "session.cookie_samesite=Strict" >> /usr/local/etc/php/conf.d/security-hardening.ini \
    && echo "display_errors=Off" >> /usr/local/etc/php/conf.d/security-hardening.ini \
    && echo "log_errors=On" >> /usr/local/etc/php/conf.d/security-hardening.ini

# ------------------------------------------------------------------------------
# BUENA PRÁCTICA 3: Exposición mínima de puertos (EXPOSE)
# Modificar Apache para escuchar en el puerto no privilegiado 8080 (puerto > 1024)
# para permitir la ejecución bajo un usuario no-root sin capacidades de red especiales
# ------------------------------------------------------------------------------
RUN sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost \*:8080>/' /etc/apache2/sites-available/000-default.conf

# Configurar directorio de trabajo
WORKDIR /var/www/html

# Copiar el código fuente con permisos restringidos asignados a www-data
# (La exclusión de secretos y archivos .env se realiza obligatoriamente mediante .dockerignore)
COPY --chown=www-data:www-data . /var/www/html/

# Asegurar que ningún archivo sensible o de respaldo haya quedado en la imagen
RUN rm -rf /var/www/html/.env* \
           /var/www/html/.git* \
           /var/www/html/*.sql \
           /var/www/html/database/*.sql \
           /var/www/html/*.md \
           /var/www/html/*.docx

# Configurar permisos para directorios en los que Apache necesita escribir en tiempo de ejecución
RUN mkdir -p /var/run/apache2 /var/lock/apache2 /var/log/apache2 \
    && chown -R www-data:www-data /var/run/apache2 /var/lock/apache2 /var/log/apache2 /var/www/html

# ------------------------------------------------------------------------------
# BUENA PRÁCTICA 2: Usuario sin privilegios (PROHIBIDO correr como root)
# Se conmuta al usuario estándar no privilegiado www-data (UID 33)
# ------------------------------------------------------------------------------
USER www-data

# Exponer únicamente el puerto 8080
EXPOSE 8080

# Healthcheck para verificar el estado de la aplicación
HEALTHCHECK --interval=30s --timeout=3s --retries=3 \
  CMD curl -f http://localhost:8080/index.php || exit 1

# Comando por defecto para iniciar Apache en primer plano
CMD ["apache2-foreground"]
