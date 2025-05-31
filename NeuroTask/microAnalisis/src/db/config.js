import { connect } from 'mongoose';

//const DB_URI = 'mongodb+srv://stevenl:3j57C1JA0TcyEhyN@cluster0.b8qxkgf.mongodb.net/Neurotask';
//const DB_URI = 'mongodb://localhost:27017/Neurotask';
const DB_URI = 'mongodb://mongo_db:27017/Neurotask';



export const connectDB = async () => {
    try {
        await connect(DB_URI, {
            autoIndex: true
        });
        console.log('DB Online');
    } catch (err) {
        console.error('Database connection error:', err);
        process.exit(1);
    }
};
