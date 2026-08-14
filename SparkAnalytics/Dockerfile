FROM bitnami/spark:3.3

COPY ./app /app
WORKDIR /app

CMD ["/opt/bitnami/spark/bin/spark-submit", "--packages", "org.mongodb.spark:mongo-spark-connector_2.12:3.0.1", "analisis_usuario.py"]
