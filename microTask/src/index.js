const express = require('express');
const { connectDB } = require('./db/config');
const morgan = require("morgan");
const cors = require("cors");

const app = express();

connectDB();

app.use(express.json());

//const port = process.env.PORT || 0;

const port = 3007;

app.use(express.json());
app.use(morgan("dev"));
app.use(cors());

app.use('/api/task', require('./routes/taskroute'));



//const server = app.listen(port, () => {
//  console.log(`Microservicio de usuarios escuchando en el puerto ${server.address().port}`);
//});


app.listen(port, '0.0.0.0', () => {
  console.log("Server listening on",port);
});