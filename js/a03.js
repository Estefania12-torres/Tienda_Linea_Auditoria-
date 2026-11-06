(function () {
    "use strict";

    var version = "2.14.1";
    var scanned = false;
    var fixed = false;
    var result = document.getElementById("scan-result");
    var manifest = document.getElementById("manifest");
    var scanButton = document.getElementById("scan-button");
    var fixButton = document.getElementById("fix-button");

    function markStep(id) {
        var step = document.getElementById(id);
        step.classList.add("complete");
        step.querySelector(".step-number").textContent = "✓";
    }

    function updateProgress() {
        var count = 1 + Number(scanned) + Number(fixed);
        document.getElementById("progress-count").textContent = count + "/3";
    }

    function renderManifest() {
        manifest.textContent = [
            "{",
            '  "dependencies": {',
            '    "express": "4.18.2",',
            '    "lodash": "4.17.21",',
            '    "log4j": "' + version + '",',
            '    "mysql2": "3.9.8"',
            "  }",
            "}"
        ].join("\n");
    }

    function renderResult() {
        var isVulnerable = version === "2.14.1";
        var dependencyRows = [
            ["express", "4.18.2"],
            ["lodash", "4.17.21"],
            ["log4j", version],
            ["mysql2", "3.9.8"]
        ];
        var rows = dependencyRows.map(function (dependency) {
            var affected = dependency[0] === "log4j" && isVulnerable;
            return '<div class="dependency-status"><span>' + dependency[0] + "@" + dependency[1] +
                '</span><span class="' + (affected ? "vulnerable" : "patched") + '">' +
                (affected ? "VULNERABLE" : "Sin alerta en esta demo") + "</span></div>";
        }).join("");

        if (isVulnerable) {
            result.innerHTML = '<div class="scan-alert"><h3>⚠ Hallazgo: log4j 2.14.1</h3>' +
                "<p>CVE-2021-44228 (Log4Shell) · severidad crítica · security gate: bloqueado.</p></div>" + rows;
            fixButton.hidden = false;
        } else {
            result.innerHTML = '<div class="scan-alert safe"><h3>✓ Sin hallazgos en esta demo</h3>' +
                "<p>La versión de Log4j del ejercicio está actualizada · security gate: aprobado.</p></div>" + rows;
            fixButton.hidden = true;
        }
    }

    scanButton.addEventListener("click", function () {
        scanned = true;
        markStep("step-scan");
        updateProgress();
        renderResult();
    });

    fixButton.addEventListener("click", function () {
        if (!scanned || fixed) {
            return;
        }

        version = "2.17.1";
        fixed = true;
        markStep("step-fix");
        updateProgress();
        renderManifest();
        renderResult();
        scanButton.textContent = "🔍 Volver a escanear";
    });

    var answer = document.getElementById("answer");
    var findingStatus = document.getElementById("finding-status");
    var saveStatus = document.getElementById("save-status");

    try {
        answer.value = localStorage.getItem("a03-answer") || "";
        findingStatus.value = localStorage.getItem("a03-finding-status") || "pending";
    } catch (error) {
        saveStatus.textContent = "El almacenamiento del navegador no está disponible; tus respuestas no se guardarán.";
    }

    answer.addEventListener("input", function () {
        try {
            localStorage.setItem("a03-answer", answer.value);
            saveStatus.textContent = "Respuesta guardada en este navegador.";
        } catch (error) {
            saveStatus.textContent = "No se pudo guardar la respuesta en este navegador.";
        }
    });

    findingStatus.addEventListener("change", function () {
        try {
            localStorage.setItem("a03-finding-status", findingStatus.value);
        } catch (error) {
            var statusMessage = findingStatus.parentElement.querySelector(".save-status");
            statusMessage.textContent = "No se pudo guardar el estado en este navegador.";
        }
    });
}());
