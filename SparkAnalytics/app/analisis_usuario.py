import os
from pyspark.sql import SparkSession
from pyspark.sql.functions import col, count, avg, month, year, unix_timestamp, when, lit
from pyspark.sql import functions as F
from datetime import datetime

# Crear la sesión Spark con conector MongoDB
spark = SparkSession.builder \
    .appName("AnalisisNeuroTask") \
    .config("spark.mongodb.input.uri", os.getenv("MONGO_URI", "mongodb://mongo_db:27017/Neurotask")) \
    .config("spark.mongodb.output.uri", os.getenv("MONGO_URI", "mongodb://mongo_db:27017/Neurotask")) \
    .getOrCreate()

# Cargar colección tareas
df = spark.read.format("mongo").option("collection", "tareas").load()

# Asegurar que fechas sean timestamp
df = df.withColumn("createdAt", col("createdAt").cast("timestamp")) \
       .withColumn("updatedAt", col("updatedAt").cast("timestamp")) \
       .withColumn("fecha_limite", col("fecha_limite").cast("timestamp"))

# 1. Tareas completadas vs pendientes y en progreso
tareas_estado = df.groupBy("usuario_asignado").pivot("estado", ["Hecho", "Por hacer", "En progreso"]).count().na.fill(0)
tareas_estado = tareas_estado.withColumnRenamed("Hecho", "tareas_completadas") \
    .withColumnRenamed("Por hacer", "tareas_pendientes") \
    .withColumnRenamed("En progreso", "tareas_en_progreso")

# 2. Productividad del mes actual
current_month = datetime.now().month
current_year = datetime.now().year
df = df.withColumn("mes_completado", month("updatedAt")) \
       .withColumn("anio_completado", year("updatedAt"))
tareas_mes_actual = df.filter(
    (col("estado") == "Hecho") &
    (col("mes_completado") == current_month) &
    (col("anio_completado") == current_year)
) \
    .groupBy("usuario_asignado").agg(count("*").alias("tareas_completadas_mes"))

# 3. Tiempo promedio en horas
df = df.withColumn("tiempo_horas", 
                   (unix_timestamp("updatedAt") - unix_timestamp("createdAt")) / 3600)
promedio_tiempo = df.filter(col("estado") == "Hecho") \
    .groupBy("usuario_asignado").agg(avg("tiempo_horas").alias("promedio_tiempo_horas"))

# 4. Total asignadas y completadas
total_asignadas = df.groupBy("usuario_asignado").agg(count("*").alias("total_asignadas"))
total_completadas = df.filter(col("estado") == "Hecho") \
    .groupBy("usuario_asignado").agg(count("*").alias("total_completadas"))
carga_trabajo = total_asignadas.join(total_completadas, "usuario_asignado", "left").na.fill(0)

# 5. Carga actual: pendientes y en progreso
pendientes_en_progreso = df.filter(col("estado").isin("Por hacer", "En progreso"))
tareas_pendientes = pendientes_en_progreso.groupBy("usuario_asignado").agg(count("*").alias("tareas_activas"))
tareas_en_progreso = df.filter(col("estado") == "En progreso") \
    .groupBy("usuario_asignado").agg(count("*").alias("tareas_en_progreso"))
carga_actual = tareas_pendientes.join(tareas_en_progreso, "usuario_asignado", "left").na.fill(0)

# 6. Tareas pendientes por prioridad
tareas_prioridad = df.filter(col("estado") == "Por hacer") \
    .groupBy("usuario_asignado").pivot("prioridad", ["Alta", "Media", "Baja"]) \
    .count().na.fill(0) \
    .withColumnRenamed("Alta", "alta") \
    .withColumnRenamed("Media", "media") \
    .withColumnRenamed("Baja", "baja")

# 7. Eficiencia: completadas / asignadas
eficiencia = carga_trabajo.withColumn("eficiencia_usuario",
                                      (col("total_completadas") / col("total_asignadas")).cast("double"))

# 8. Retrasos y tareas a tiempo (solo estado "Hecho")
tareas_con_retraso = df.filter((col("estado") == "Hecho") & (col("updatedAt") > col("fecha_limite"))) \
    .groupBy("usuario_asignado").agg(count("*").alias("tareas_con_retraso"))

tareas_a_tiempo = df.filter((col("estado") == "Hecho") & (col("updatedAt") <= col("fecha_limite"))) \
    .groupBy("usuario_asignado").agg(count("*").alias("tareas_entregadas_a_tiempo"))

# 9. Carga crítica: tareas de prioridad alta / tareas activas
carga_critica = tareas_prioridad.join(tareas_pendientes, "usuario_asignado", "left") \
    .withColumn("carga_critica",
                (col("alta") / when(col("tareas_activas") == 0, lit(1)).otherwise(col("tareas_activas")))) \
    .select("usuario_asignado", "carga_critica")

# Combinar todo
df_analisis = tareas_estado.alias("te") \
    .join(tareas_mes_actual.alias("tm"), "usuario_asignado", "left") \
    .join(promedio_tiempo.alias("pt"), "usuario_asignado", "left") \
    .join(carga_trabajo.alias("ct"), "usuario_asignado", "left") \
    .join(carga_actual.alias("ca"), "usuario_asignado", "left") \
    .join(tareas_prioridad.alias("tp"), "usuario_asignado", "left") \
    .join(eficiencia.select("usuario_asignado", "eficiencia_usuario"), "usuario_asignado", "left") \
    .join(tareas_con_retraso, "usuario_asignado", "left") \
    .join(tareas_a_tiempo, "usuario_asignado", "left") \
    .join(carga_critica, "usuario_asignado", "left") \
    .select(
        col("te.usuario_asignado").alias("usuario_id"),
        col("te.tareas_completadas"),
        col("te.tareas_pendientes"),
        col("te.tareas_en_progreso"),
        col("tm.tareas_completadas_mes"),
        col("pt.promedio_tiempo_horas"),
        col("ct.total_asignadas"),
        col("ct.total_completadas"),
        col("ca.tareas_activas"),
        col("ca.tareas_en_progreso").alias("tareas_en_progreso_actual"),
        F.struct(col("tp.alta"), col("tp.media"), col("tp.baja")).alias("tareas_pendientes_por_prioridad"),
        col("eficiencia_usuario"),
        col("tareas_con_retraso"),
        col("tareas_entregadas_a_tiempo"),
        col("carga_critica")
    ).na.fill(0)

# Guardar en MongoDB
df_analisis.write.format("mongo").mode("overwrite").option("collection", "analisis_usuario").save()

spark.stop()
