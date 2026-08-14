const express = require('express');
const { connectDB } = require('./db/config');
const morgan = require("morgan");
const cors = require("cors");

const app = express();

connectDB();

app.use(express.json());

//const port = process.env.PORT || 0;

const port = 3008;

app.use(express.json());
app.use(morgan("dev"));
app.use(cors());

app.use('/api/project', require('./routes/projectroute'));


app.listen(port, '0.0.0.0', () => {
  console.log("Server listening on",port);
});