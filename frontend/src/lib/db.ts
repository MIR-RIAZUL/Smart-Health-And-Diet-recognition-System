import mysql from "mysql2/promise";

export interface UserRecord {
  id: number;
  name: string;
  email: string;
  password?: string;
  phone?: string;
  role: "admin" | "dietitian" | "user";
  status: "active" | "inactive" | "suspended" | "pending";
  created_at?: string;
}

export interface HealthProfileRecord {
  id: number;
  user_id: number;
  age: number;
  gender: "male" | "female" | "other";
  height: number;
  weight: number;
  activity_level: string;
  health_goal: string;
  dietary_preference: string;
  daily_calorie_target: number;
  bmi: number;
}

export interface FoodItemRecord {
  id: number;
  food_name: string;
  serving_size: string;
  serving_weight_g: number;
  calories: number;
  protein: number;
  carbohydrates: number;
  fat: number;
  fiber: number;
}

export interface MealLogRecord {
  id: number;
  user_id: number;
  food_id?: number | null;
  food_name?: string;
  custom_food_name?: string;
  meal_type: "Breakfast" | "Lunch" | "Dinner" | "Snack";
  quantity: number;
  calories: number;
  protein: number;
  carbohydrates: number;
  fat: number;
  meal_date: string;
  meal_time: string;
}

export interface DietitianProfileRecord {
  id: number;
  user_id: number;
  name: string;
  email: string;
  qualification: string;
  certification: string;
  experience: number;
  specialization: string;
  approval_status: "pending" | "approved" | "rejected";
  rejection_reason?: string;
}

// Re-export mockData from client-safe module
export { mockData } from "./mockData";

let pool: mysql.Pool | null = null;

export async function getDbPool(): Promise<mysql.Pool | null> {
  if (pool) return pool;

  try {
    const newPool = mysql.createPool({
      host: process.env.DB_HOST || "localhost",
      user: process.env.DB_USER || "root",
      password: process.env.DB_PASSWORD || "",
      database: process.env.DB_NAME || "smart_health_diet",
      port: Number(process.env.DB_PORT || 3306),
      waitForConnections: true,
      connectionLimit: 10,
      queueLimit: 0,
      connectTimeout: 2000,
    });

    // Test connectivity
    const connection = await newPool.getConnection();
    connection.release();
    pool = newPool;
    return pool;
  } catch (err) {
    console.warn("MySQL connection unavailable. Operating with high-fidelity local state store:", (err as Error).message);
    return null;
  }
}
