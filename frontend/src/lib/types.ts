export type UserRole = "admin" | "dietitian" | "user";

export interface UserSession {
  id: number;
  name: string;
  email: string;
  role: UserRole;
  phone?: string;
  status: "active" | "inactive" | "suspended" | "pending";
}

export interface HealthProfile {
  age: number;
  gender: "male" | "female" | "other";
  height: number; // cm
  weight: number; // kg
  activity_level: string;
  health_goal: string;
  dietary_preference: string;
  daily_calorie_target: number;
  bmi: number;
}

export interface FoodItem {
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

export interface MealLog {
  id: number;
  user_id: number;
  food_id?: number | null;
  food_name: string;
  meal_type: "Breakfast" | "Lunch" | "Dinner" | "Snack";
  quantity: number;
  calories: number;
  protein: number;
  carbohydrates: number;
  fat: number;
  meal_date: string;
  meal_time: string;
}

export interface FoodRecognitionResult {
  detected_name: string;
  confidence: number;
  estimated_serving: string;
  estimated_weight_g: number;
  calories: number;
  protein: number;
  carbs: number;
  fat: number;
  fiber: number;
  health_score: number; // 1-100
  dietary_tags: string[];
  suggestions: string[];
}
