import express, { json } from 'express';
import { connectDB } from './db/config.js';


import morgan from "morgan";
import cors from "cors";

const app = express();

connectDB();

app.use(json());


const port = 3009;

app.use(json());
app.use(morgan("dev"));
app.use(cors());

import userroute from './routes/userroute.js';
app.use('/api/user', userroute);


app.listen(port, '0.0.0.0', () => {
  console.log("Server listening on",port);
});