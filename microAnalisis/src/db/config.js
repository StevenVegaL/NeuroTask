import 'dotenv/config';
import { connect } from 'mongoose';

const DB_URI = process.env.MONGO_URI || 'mongodb://mongo_db:27017/Neurotask';

export const connectDB = async () => {
  try {
    await connect(DB_URI, { autoIndex: true });
    console.log('Database connected');
  } catch (error) {
    console.error('Database connection error:', error.message);
    process.exit(1);
  }
};
