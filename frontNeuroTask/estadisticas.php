<?php
  session_start();
  if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
  }
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Estadísticas - NeuroTask</title>
  <link rel="stylesheet" href="estilos.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <?php include 'includes/sidebar.php'; ?>

  <main class="main-content">
    <h1>Dashboard de Usuario</h1>
    
    <div class="charts-container">
      <div class="chart-box">
        <h3>Tareas por Estado</h3>
        <canvas id="tareasEstadoChart"></canvas>
      </div>

      <div class="chart-box">
        <h3>Promedio de Tiempo de Tareas</h3>
        <canvas id="tiempoPromedioChart"></canvas>
      </div>

      <div class="chart-box">
        <h3>Distribución por Prioridad</h3>
        <canvas id="prioridadChart"></canvas>
      </div>
    </div>
  </main>

  <script>
    const usuarioId = "<?php echo $_SESSION['usuario']['_id']; ?>";

    fetch(`ajax/obtener_analisis_usuario.php?usuario_id=${usuarioId}`)
      .then(res => res.json())
      .then(data => {
        if (!data.ok) throw new Error(data.error);

        const a = data.analisis;

        // Chart 1: Tareas por Estado
        new Chart(document.getElementById("tareasEstadoChart"), {
          type: "bar",
          data: {
            labels: ["Completadas", "Pendientes", "En Progreso"],
            datasets: [{
              label: "Tareas",
              data: [a.tareas_completadas, a.tareas_pendientes, a.tareas_en_progreso_actual],
              backgroundColor: ["#4caf50", "#f44336", "#ff9800"]
            }]
          }
        });

        // Chart 2: Tiempo Promedio
        new Chart(document.getElementById("tiempoPromedioChart"), {
          type: "doughnut",
          data: {
            labels: ["Horas"],
            datasets: [{
              label: "Tiempo Promedio",
              data: [a.promedio_tiempo_horas],
              backgroundColor: ["#2196f3"]
            }]
          }
        });

        // Chart 3: Prioridad
        new Chart(document.getElementById("prioridadChart"), {
          type: "pie",
          data: {
            labels: ["Alta", "Media", "Baja"],
            datasets: [{
              label: "Prioridad",
              data: [a.tareas_pendientes_por_prioridad.alta, a.tareas_pendientes_por_prioridad.media, a.tareas_pendientes_por_prioridad.baja],
              backgroundColor: ["#e91e63", "#ffeb3b", "#8bc34a"]
            }]
          }
        });
      })
      .catch(err => console.error("Error cargando análisis:", err));
  </script>
</body>
</html>
