<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Simulación de Tanque de Agua</title>
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f0f0f0;
      padding: 20px;
    }
    .container {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      background: white;
      padding: 20px;
      border-radius: 15px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    .input-group {
      display: flex;
      flex-direction: column;
      margin-bottom: 15px;
    }
    label {
      font-weight: bold;
    }
    input {
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 8px;
    }
    .tank {
      width: 200px;
      height: 300px;
      border: 4px solid #0077cc;
      position: relative;
      background: #fff;
      border-radius: 10px;
      margin: 0 auto;
    }
    .water {
      position: absolute;
      bottom: 0;
      width: 100%;
      background-color: #00bfff;
      border-radius: 0 0 8px 8px;
    }
    .btn {
      padding: 10px;
      background: #0077cc;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      margin: 3px;
    }
    .btn:hover {
      background: #005fa3;
    }
    canvas {
      max-width: 100%;
    }
  </style>
</head>
<body>
  <h1>Simulación de Llenado y Vaciado de Tanque</h1>
  <div class="container">
    <div>
      <div class="input-group">
        <label for="altura">Altura del tanque (m):</label>
        <input type="number" id="altura" value="3">
      </div>
      <div class="input-group">
        <label for="area">Área de base (m²):</label>
        <input type="number" id="area" value="1">
      </div>
      <div class="input-group">
        <label for="qin">Caudal de entrada (L/min):</label>
        <input type="number" id="qin" value="20">
      </div>
      <div class="input-group">
        <label for="qout">Caudal de salida (L/min):</label>
        <input type="number" id="qout" value="5">
      </div>
      <button class="btn" onclick="simular()">Simular</button>
      <button class="btn" onclick="cambiarGrafico()">Cambiar gráfico</button>
      <button class="btn" onclick="optimizarQin()">Optimizar Qin</button>
      <div style="margin-top: 20px;">
        <label><strong>Velocidad:</strong></label><br>
        <button class="btn" onclick="cambiarVelocidad(0.5)">×0.5</button>
        <button class="btn" onclick="cambiarVelocidad(1)">×1</button>
        <button class="btn" onclick="cambiarVelocidad(2)">×2</button>
        <button class="btn" onclick="cambiarVelocidad(5)">×5</button>
        <button class="btn" onclick="cambiarVelocidad(10)">×10</button>
        <button class="btn" onclick="cambiarVelocidad(20)">×20</button>
        <button class="btn" onclick="cambiarVelocidad(50)">×50</button>
      </div>
      <p id="resultado"></p>
    </div>
    <div>
        <p id="contador" style="text-align:center; margin-top:10px; font-weight: bold;"></p>
        <div class="tank">
            <div class="water" id="water" style="height: 0%;"></div>
        </div>
    </div>
  </div>

  <canvas id="graficoTiempo" style="display: block; margin-top: 30px;"></canvas>
  <canvas id="graficoDisperso" style="display: none; margin-top: 30px;"></canvas>
  
  <section style="margin-top: 40px; background: #fff; padding: 20px; border-radius: 10px;">
    <h2>📘 Modelo Matemático del Sistema</h2>
    <p><strong>1. Volumen del tanque:</strong></p>
    <p style="margin-left: 20px;">V = A × h</p>
    <p><strong>2. Caudal neto:</strong></p>
    <p style="margin-left: 20px;">dV/dt = Q<sub>in</sub> − Q<sub>out</sub></p>
    <p><strong>3. Tiempo estimado de llenado:</strong></p>
    <p style="margin-left: 20px;">t = V / (Q<sub>in</sub> − Q<sub>out</sub>)</p>
    <p><strong>4. Nivel con el tiempo:</strong></p>
    <p style="margin-left: 20px;">h(t) = [(Q<sub>in</sub> − Q<sub>out</sub>) / A] × t</p>
  </section>

  <script>
    let multiplicador = 1;
    let tiempoSimulado = 0;
    let duracionReal = 0;
    let tiempoAnterior = null;
    let animacionActiva = false;

    // NUEVO: Array para guardar los puntos simulados
    const puntosDispersos = [];

    function cambiarVelocidad(nuevaVelocidad) {
      multiplicador = nuevaVelocidad;
    }

    function simular() {
      const altura = parseFloat(document.getElementById('altura').value);
      const area = parseFloat(document.getElementById('area').value);
      const qin = parseFloat(document.getElementById('qin').value);
      const qout = parseFloat(document.getElementById('qout').value);
      const water = document.getElementById('water');
      const contador = document.getElementById('contador');

      if (altura <= 0 || area <= 0 || qin < 0 || qout < 0) {
        alert("Por favor ingresa valores válidos y positivos.");
        return;
      }

      const volumen = area * altura * 1000;
      const tasa = qin - qout;

      if (tasa <= 0) {
        document.getElementById('resultado').textContent = "El tanque nunca se llenará con estos valores.";
        return;
      }

      const tiempo = volumen / tasa;
      const tiempoEnSegundos = tiempo * 60;
      duracionReal = tiempoEnSegundos * 1000;

      document.getElementById('resultado').textContent =
        tiempoEnSegundos >= 60
          ? `Tiempo estimado de llenado: ${(tiempoEnSegundos / 60).toFixed(2)} minutos.`
          : `Tiempo estimado de llenado: ${tiempoEnSegundos.toFixed(0)} segundos.`;

      // NUEVO: Guardar datos para gráfica de dispersión
      puntosDispersos.push({
        x: qin,
        y: parseFloat(tiempo.toFixed(2)),
        qin,
        qout,
        area,
        altura
      });

      water.style.height = '0%';
      contador.textContent = 'Tiempo simulado: 0 s';
      cancelAnimationFrame(window.idAnimacion);
      tiempoSimulado = 0;
      tiempoAnterior = null;
      animacionActiva = true;
      window.idAnimacion = requestAnimationFrame(animar);

      const tiempoDatos = [];
      const nivelDatos = [];

      for (let t = 0; t <= tiempo + 1; t++) {
        tiempoDatos.push(t);
        const nivelActual = (t * tasa) / volumen * 100;
        nivelDatos.push(Math.min(nivelActual, 100));
      }

      const ctx = document.getElementById('graficoTiempo').getContext('2d');
      if (window.chartTiempo) window.chartTiempo.destroy();
      window.chartTiempo = new Chart(ctx, {
        type: 'line',
        data: {
          labels: tiempoDatos,
          datasets: [{
            label: 'Nivel de agua (%)',
            data: nivelDatos,
            borderColor: '#0077cc',
            fill: true,
            tension: 0.2
          }]
        },
        options: {
          scales: {
            x: { title: { display: true, text: 'Tiempo (min)' } },
            y: { title: { display: true, text: 'Nivel de agua (%)' }, min: 0, max: 100 }
          }
        }
      });
    }

    function animar(timestamp) {
      const water = document.getElementById('water');
      const contador = document.getElementById('contador');

      if (!tiempoAnterior) tiempoAnterior = timestamp;

      const deltaTiempo = (timestamp - tiempoAnterior) * multiplicador;
      tiempoSimulado += deltaTiempo;
      tiempoAnterior = timestamp;

      const porcentaje = Math.min((tiempoSimulado / duracionReal) * 100, 100);
      water.style.height = porcentaje + '%';
      contador.textContent = `Tiempo simulado: ${Math.floor(tiempoSimulado / 1000)} s`;

      if (porcentaje < 100 && animacionActiva) {
        requestAnimationFrame(animar);
      } else {
        animacionActiva = false;
        contador.textContent = `✅ ¡Llenado completo en ${Math.round(tiempoSimulado / 1000)} s!`;
      }
    }

    function cambiarGrafico() {
      const canvasTiempo = document.getElementById("graficoTiempo");
      const canvasDisperso = document.getElementById("graficoDisperso");

      if (canvasTiempo.style.display === "none") {
        canvasTiempo.style.display = "block";
        canvasDisperso.style.display = "none";
      } else {
        canvasTiempo.style.display = "none";
        canvasDisperso.style.display = "block";
        generarGraficoDisperso();
      }
    }

    function generarGraficoDisperso() {
      const ctx = document.getElementById('graficoDisperso').getContext('2d');
      if (window.chartDisperso) window.chartDisperso.destroy();
      window.chartDisperso = new Chart(ctx, {
        type: 'scatter',
        data: {
          datasets: [{
            label: 'Tiempo de llenado vs Q<sub>in</sub>',
            data: puntosDispersos,
            backgroundColor: '#ff5733',
            pointRadius: 6
          }]
        },
        options: {
          plugins: {
            title: {
              display: true,
              text: 'Gráfica de Dispersión: Tiempo de llenado vs Caudal de entrada'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const d = context.raw;
                  return `qin: ${d.qin} L/min, qout: ${d.qout} L/min, área: ${d.area} m², altura: ${d.altura} m, t: ${d.y} min`;
                }
              }
            }
          },
          scales: {
            x: { title: { display: true, text: 'Caudal de entrada (L/min)' } },
            y: { title: { display: true, text: 'Tiempo de llenado (min)' } }
          }
        }
      });
    }
    
    function optimizarQin() {
    const altura = parseFloat(document.getElementById('altura').value);
    const area = parseFloat(document.getElementById('area').value);
    const qout = parseFloat(document.getElementById('qout').value);

    if (altura <= 0 || area <= 0 || qout < 0) {
      alert("Por favor ingresa valores válidos.");
      return;
    }

    const volumen = area * altura * 1000; // en litros
    let qin = qout + 0.01; // el mínimo válido (para evitar división por cero)
    let mejorQin = qin;
    let mejorTiempo = volumen / (qin - qout);

    for (let i = 1; i <= 100; i++) {
      qin = qout + i;
      const tiempo = volumen / (qin - qout);
      if (tiempo < mejorTiempo) {
        mejorTiempo = tiempo;
        mejorQin = qin;
      }
    }

    const resultado = document.getElementById("resultado");
    resultado.innerHTML += `<br><strong>🔍 Qin óptimo (mínimo tiempo de llenado):</strong> ${mejorQin} L/min<br><strong>⏱ Tiempo estimado:</strong> ${(mejorTiempo).toFixed(2)} min`;
  }
  </script>
</body>
</html>
