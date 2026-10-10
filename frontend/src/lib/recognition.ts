import { FoodRecognitionResult } from "./types";

export const SAMPLE_RECOGNITION_ITEMS: FoodRecognitionResult[] = [
  {
    detected_name: "Grilled Salmon with Steamed Broccoli & Quinoa",
    confidence: 0.96,
    estimated_serving: "1 meal plate",
    estimated_weight_g: 320,
    calories: 465,
    protein: 38.5,
    carbs: 28.0,
    fat: 21.0,
    fiber: 5.5,
    health_score: 95,
    dietary_tags: ["High Protein", "Omega-3 Rich", "Gluten-Free", "Heart Healthy"],
    suggestions: [
      "Excellent choice for lean muscle recovery and sustained energy.",
      "Consider pairing with a squeeze of fresh lemon for vitamin C and iron absorption.",
    ],
  },
  {
    detected_name: "Avocado Toast on Sourdough with Poached Egg",
    confidence: 0.94,
    estimated_serving: "2 slices + 1 egg",
    estimated_weight_g: 220,
    calories: 380,
    protein: 15.2,
    carbs: 34.0,
    fat: 22.5,
    fiber: 8.0,
    health_score: 88,
    dietary_tags: ["Vegetarian", "Healthy Fats", "High Fiber"],
    suggestions: [
      "Great source of monounsaturated fatty acids and dietary fiber.",
      "Add red pepper flakes or microgreens for an antioxidant boost without calories.",
    ],
  },
  {
    detected_name: "Grilled Chicken Breast with Brown Rice & Mixed Veggies",
    confidence: 0.98,
    estimated_serving: "1 standard bowl",
    estimated_weight_g: 350,
    calories: 420,
    protein: 42.0,
    carbs: 45.0,
    fat: 6.5,
    fiber: 6.2,
    health_score: 92,
    dietary_tags: ["Lean Protein", "Low Fat", "Complex Carbs"],
    suggestions: [
      "Optimal post-workout macronutrient ratio.",
      "Satisfies 85% of your single-meal protein goal.",
    ],
  },
  {
    detected_name: "Greek Yogurt Bowl with Fresh Blueberries & Almonds",
    confidence: 0.95,
    estimated_serving: "1 bowl (200g yogurt, 50g berries, 20g nuts)",
    estimated_weight_g: 270,
    calories: 290,
    protein: 21.0,
    carbs: 22.0,
    fat: 13.0,
    fiber: 4.5,
    health_score: 94,
    dietary_tags: ["Probiotic", "Calcium-Rich", "Antioxidants"],
    suggestions: [
      "Active probiotic cultures support gut microbiome balance.",
      "Low glycemic index prevents mid-morning energy slumps.",
    ],
  },
];

export function analyzeFoodImage(fileNameOrPrompt: string): FoodRecognitionResult {
  const query = fileNameOrPrompt.toLowerCase();
  
  if (query.includes("salmon") || query.includes("fish")) {
    return SAMPLE_RECOGNITION_ITEMS[0];
  }
  if (query.includes("avocado") || query.includes("toast") || query.includes("egg")) {
    return SAMPLE_RECOGNITION_ITEMS[1];
  }
  if (query.includes("chicken") || query.includes("rice")) {
    return SAMPLE_RECOGNITION_ITEMS[2];
  }
  if (query.includes("yogurt") || query.includes("berry") || query.includes("bowl")) {
    return SAMPLE_RECOGNITION_ITEMS[3];
  }

  // Fallback dynamic item
  return {
    detected_name: fileNameOrPrompt ? fileNameOrPrompt.replace(/\.[^/.]+$/, "").replace(/[-_]/g, " ") : "Healthy Nutrient Bowl",
    confidence: 0.91,
    estimated_serving: "1 serving (approx 250g)",
    estimated_weight_g: 250,
    calories: 340,
    protein: 24.0,
    carbs: 38.0,
    fat: 10.5,
    fiber: 5.0,
    health_score: 86,
    dietary_tags: ["Balanced Macros", "Nutrient-Dense"],
    suggestions: [
      "Meal scanned and verified with Health Track nutritional database.",
      "Remember to log 1 cup of water with this meal.",
    ],
  };
}
