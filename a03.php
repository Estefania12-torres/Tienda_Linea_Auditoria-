<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Lección A03 sobre fallos de la cadena de suministro de software y análisis de dependencias.">
    <title>A03 · Cadena de suministro de software</title>
    <link rel="stylesheet" href="css/a03.css">
</head>
<body>
    <header class="site-header">
        <a class="brand" href="index.php">Tienda en línea <span>· Seguridad</span></a>
        <a class="back-link" href="index.php">Volver a la tienda</a>
    </header>

    <main class="page">
        <section class="hero">
            <p class="eyebrow">A03 · Fallos de la cadena de suministro de software</p>
            <h1>Tu aplicación hereda los riesgos de todo lo que usa.</h1>
            <p class="intro">Una librería vulnerable puede afectar a tu aplicación aunque nadie de tu equipo haya escrito ese código.</p>
            <span class="new-badge">Nueva en 2025</span>
        </section>

        <section class="lesson-card" aria-labelledby="what-title">
            <h2 id="what-title">¿Qué es?</h2>
            <p>El software moderno se construye con librerías, paquetes y herramientas de terceros. Si una de esas piezas llega comprometida o contiene una vulnerabilidad, tu aplicación puede heredar el problema.</p>
            <div class="analogy">
                <span aria-hidden="true">💡</span>
                <p><strong>Imagina que cocinas un pastel:</strong> si la harina venía contaminada, el pastel también puede estarlo aunque hayas seguido la receta. Las dependencias son los ingredientes de tu software.</p>
            </div>
            <div class="example">
                <h3>Ejemplo técnico: Log4Shell</h3>
                <p>En 2021, CVE-2021-44228 afectó a Apache Log4j, una librería Java de registro. Las aplicaciones que incluían versiones vulnerables podían quedar expuestas sin haber escrito Log4j ellas mismas. La NVD asignó a esta vulnerabilidad una severidad CVSS crítica de 10.0.</p>
            </div>
        </section>

        <section class="lesson-card" aria-labelledby="prevention-title">
            <h2 id="prevention-title">Cómo se previene</h2>
            <ul class="prevention-list">
                <li><strong>Inventariar y escanear</strong> dependencias automáticamente con SCA, por ejemplo Trivy, npm audit u OWASP Dependency-Check.</li>
                <li><strong>Actualizar con frecuencia</strong> y fijar versiones conocidas y aprobadas.</li>
                <li><strong>Verificar la integridad</strong> de lo descargado mediante hashes, firmas y fuentes confiables.</li>
            </ul>
        </section>

        <section class="simulation" aria-labelledby="simulation-title">
            <div class="simulation-heading">
                <div>
                    <p class="eyebrow">Simulación en clase</p>
                    <h2 id="simulation-title">El escáner de dependencias</h2>
                    <p>Un SCA en miniatura sobre un archivo de dependencias de juguete.</p>
                </div>
                <div class="progress" aria-live="polite">
                    <strong id="progress-count">1/3</strong>
                    <span>pasos realizados</span>
                </div>
            </div>

            <ol class="steps" aria-label="Ruta guiada">
                <li class="step complete" id="step-inspect">
                    <span class="step-number">✓</span>
                    <div><strong>Paso 1: Inspeccionar</strong><p>Observa las cuatro dependencias. A simple vista parecen normales.</p></div>
                </li>
                <li class="step" id="step-scan">
                    <span class="step-number">2</span>
                    <div><strong>Paso 2: Ejecutar el escáner SCA</strong><p>Compara las versiones con el ejemplo de vulnerabilidad CVE-2021-44228.</p></div>
                </li>
                <li class="step" id="step-fix">
                    <span class="step-number">3</span>
                    <div><strong>Paso 3: Actualizar la dependencia</strong><p>Aplica el parche del ejercicio y vuelve a comprobar el security gate.</p></div>
                </li>
            </ol>

            <div class="scanner-layout">
                <div class="manifest-panel">
                    <div class="panel-title"><span class="file-dot"></span> package.json <span class="toy-label">ejemplo de juguete</span></div>
                    <pre id="manifest" aria-label="Dependencias de ejemplo"><code>{
  "dependencies": {
    "express": "4.18.2",
    "lodash": "4.17.21",
    "log4j": "2.14.1",
    "mysql2": "3.9.8"
  }
}</code></pre>
                    <p class="notice"><strong>Nota:</strong> Log4j es una librería Java; normalmente se declara en un archivo Maven/Gradle, no en <code>package.json</code>. Este manifiesto es deliberadamente didáctico, no una configuración real de Node.js.</p>
                </div>
                <div class="scan-panel">
                    <div class="panel-title">Resultado del análisis</div>
                    <div id="scan-result" class="scan-result" aria-live="polite">
                        <p class="empty-state">Pulsa «Escanear dependencias» para iniciar el análisis de ejemplo.</p>
                    </div>
                    <div class="scan-actions">
                        <button id="scan-button" class="button button-primary" type="button">🔍 Escanear dependencias</button>
                        <button id="fix-button" class="button button-success" type="button" hidden>Actualizar log4j a 2.17.1</button>
                    </div>
                </div>
            </div>
            <p class="simulation-disclaimer">Esta simulación usa una regla local de demostración; no consulta la NVD ni ejecuta Trivy, npm audit u otro escáner real.</p>
        </section>

        <section class="lesson-card what-you-saw">
            <h2>¿Qué acabas de ver?</h2>
            <p>Nadie del equipo escribió la librería vulnerable y aun así podía estar dentro de la aplicación: llegó por una dependencia. En un proyecto real pueden existir cientos de dependencias directas y transitivas.</p>
        </section>

        <details class="help-card">
            <summary>💡 ¿Atascado? Mostrar pista y código vulnerable vs. seguro</summary>
            <div class="help-content">
                <p><strong>Pista:</strong> compara las versiones del manifiesto y busca cuál coincide con Log4Shell (CVE-2021-44228).</p>
                <div class="code-comparison">
                    <div><h3>Vulnerable en este ejercicio</h3><code>"log4j": "2.14.1"</code></div>
                    <div><h3>Actualizada en este ejercicio</h3><code>"log4j": "2.17.1"</code></div>
                </div>
                <p>La corrección real debe actualizar el artefacto Log4j en el gestor Java correspondiente y comprobar todas las dependencias afectadas.</p>
            </div>
        </details>

        <section class="ape-card">
            <p class="eyebrow">Guía APE 04 · Fase 1 · Semana 13</p>
            <h2>Ahora en tu proyecto APE</h2>
            <ol>
                <li><strong>Hoy:</strong> abran el manifiesto de dependencias que use su aplicación (por ejemplo, <code>package.json</code>, <code>requirements.txt</code>, <code>pom.xml</code> o <code>composer.json</code>) y cuenten las dependencias.</li>
                <li><strong>Fase 4:</strong> revisen si alguna versión está desactualizada y anótenlo como hallazgo preliminar; confírmenlo con un escáner.</li>
                <li><strong>Guía 04 · Fase 1:</strong> ejecuten el análisis real con Trivy dentro de GitHub Actions y configúrenlo como security gate del pipeline.</li>
            </ol>
            <aside class="project-note">
                <strong>Observación de este repositorio:</strong> esta aplicación está implementada en PHP y MySQL. En el repositorio revisado no aparece un manifiesto de dependencias (como <code>composer.json</code> o <code>package.json</code>); por ello, esta simulación no confirma el estado real de sus dependencias. Inventaríen también los componentes de servidor y sigan la revisión con Trivy.
            </aside>
        </section>

        <section class="practice-card" aria-labelledby="practice-title">
            <p class="eyebrow">A03 · Evidencia</p>
            <h2 id="practice-title">Registro de Práctica y Evidencia</h2>
            <fieldset>
                <legend>1. Pregunta de control · Sección 7 del Manual</legend>
                <p class="question">¿Por qué una aplicación puede contener código vulnerable que ningún integrante del equipo escribió? ¿Qué herramienta revisa esa situación en la Guía 04?</p>
                <details class="answer-hint">
                    <summary>Ver pista</summary>
                    <p>Piensa en las librerías externas que usa una aplicación y en el análisis automatizado de composición de software (SCA).</p>
                </details>
                <label for="answer">Tu respuesta</label>
                <textarea id="answer" rows="4" placeholder="Escribe tu respuesta aquí. Se guarda en este navegador."></textarea>
                <p id="save-status" class="save-status" aria-live="polite">La respuesta se guarda solo en este navegador.</p>
            </fieldset>
            <fieldset>
                <legend>2. Hallazgo en mi Aplicación APE · Lista de Cotejo</legend>
                <label for="finding-status">Selecciona el estado de la revisión:</label>
                <select id="finding-status">
                    <option value="pending">⏳ Pendiente de revisar en nuestro proyecto</option>
                    <option value="found">⚠️ Sí encontramos un hallazgo / vulnerabilidad</option>
                    <option value="verified">✅ Verificado: nuestra app ya tiene el control seguro</option>
                    <option value="not-applicable">➖ No aplica en este módulo</option>
                </select>
                <p class="save-status">El estado se guarda solo en este navegador; adjunta evidencia en tu entrega.</p>
            </fieldset>
        </section>

        <footer class="page-footer">A03 · Seguridad en la cadena de suministro · <a href="index.php">Volver a la tienda</a></footer>
    </main>
    <script src="js/a03.js"></script>
</body>
</html>
