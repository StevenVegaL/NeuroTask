import express, { json } from 'express';
import { connectDB } from './db/config.js';
import analisisroute from './routes/analisisroute.js';
import morgan from "morgan";
import cors from "cors";

const app = express();

connectDB();

app.use(json());


const port = 3011;

app.use(json());
app.use(morgan("dev"));
app.use(cors());


app.use('/api/analisis', analisisroute);


app.listen(port, '0.0.0.0', () => {
  console.log("Server listening on",port);
});