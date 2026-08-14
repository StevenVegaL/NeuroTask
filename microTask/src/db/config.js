require('dotenv').config();
const mongoose = require('mongoose');

const DB_URI = process.env.MONGO_URI || 'mongodb://mongo_db:27017/Neurotask';

const connectDB = async () => {
  try {
    await mongoose.connect(DB_URI, { autoIndex: true });
    console.log('Database connected');
  } catch (error) {
    console.error('Database connection error:', error.message);
    process.exit(1);
  }
};

module.exports = { connectDB };
