"use client";

import React, { useState } from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { Suspense } from "react";
import {
  ArrowLeft,
  Camera,
  Heart,
  Sparkles,
  Flame,
  Droplets,
  Scale,
  Apple,
  CheckCircle2,
  UtensilsCrossed,
  UserCheck,
  ShieldCheck,
  Activity,
  Plus,
  Trash2,
  Search,
  Users,
  ClipboardList,
  BarChart3,
  BookOpen,
  Database,
  Lock,
  Mail,
  User,
  Check,
  X,
  Stethoscope,
  ChevronRight,
  TrendingUp,
  AlertCircle
} from "lucide-react";
import Sidebar from "@/components/Sidebar";
import { useAuth } from "@/context/AuthContext";
import { SAMPLE_RECOGNITION_ITEMS, analyzeFoodImage } from "@/lib/recognition";
import { mockData } from "@/lib/mockData";
import { FoodRecognitionResult, MealLog } from "@/lib/types";

export default function PortalPage() {
  return (
    <Suspense
      fallback={
        <main className="flex-1 grid place-items-center p-8 text-sm text-slate-500">
          <div className="flex items-center gap-2">
            <Sparkles className="h-5 w-5 animate-spin text-emerald-600" />
            <span>Loading workspace...</span>
          </div>
        </main>
      }
    >
      <PortalRouter />
    </Suspense>
  );
}

function PortalRouter() {
  const pathname = usePathname();
  const { user, login, quickLogin } = useAuth();

  // If path is login or register, show standalone auth portal
  if (pathname === "/login" || pathname === "/register") {
    return <AuthPortal mode={pathname === "/login" ? "login" : "register"} />;
  }

  return (
    <>
      <Sidebar />
      <main className="flex-1 bg-gradient-to-br from-slate-50 via-slate-100/70 to-emerald-50/30 p-4 sm:p-8 lg:p-10 min-h-screen overflow-y-auto">
        <div className="mx-auto max-w-6xl">
          {/* Top navigation helper */}
          <div className="mb-6 flex items-center justify-between">
            <Link
              href="/"
              className="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-emerald-700 transition-colors"
            >
              <ArrowLeft className="h-4 w-4" />
              Home Overview
            </Link>
            <div className="inline-flex items-center gap-2 rounded-full border border-slate-200/80 bg-white/70 px-3 py-1 text-xs font-medium text-slate-600 shadow-2xs backdrop-blur-md">
              <span className="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
              <span>{user?.name || "Guest Preview"} ({user?.role || "user"} portal)</span>
            </div>
          </div>

          {/* Route views */}
          {pathname.includes("recognition") && <RecognitionView />}
          {pathname === "/user/dashboard" && <UserDashboardView />}
          {pathname.includes("log-meals") && <LogMealsView />}
          {pathname.includes("health-info") && <HealthInfoView />}
          {pathname.includes("guidance") && <GuidanceView />}
          {pathname.includes("reports") && <ReportsView />}
          {pathname.includes("dietitian") && <DietitianPortalView />}
          {pathname.includes("admin") && <AdminPortalView />}

          {/* Fallback for other subroutes */}
          {!pathname.includes("recognition") &&
            pathname !== "/user/dashboard" &&
            !pathname.includes("log-meals") &&
            !pathname.includes("health-info") &&
            !pathname.includes("guidance") &&
            !pathname.includes("reports") &&
            !pathname.includes("dietitian") &&
            !pathname.includes("admin") && (
              <FallbackWorkspaceView pathname={pathname} user={user} />
            )}
        </div>
      </main>
    </>
  );
}

/* =========================================================================
   1. AI FOOD RECOGNITION STUDIO VIEW
========================================================================= */
function RecognitionView() {
  const [selectedPresetIndex, setSelectedPresetIndex] = useState(0);
  const [activeResult, setActiveResult] = useState<FoodRecognitionResult>(
    SAMPLE_RECOGNITION_ITEMS[0]
  );
  const [analyzing, setAnalyzing] = useState(false);
  const [loggedNotice, setLoggedNotice] = useState<string | null>(null);

  const handleSelectPreset = (index: number) => {
    setSelectedPresetIndex(index);
    setAnalyzing(true);
    setTimeout(() => {
      setActiveResult(SAMPLE_RECOGNITION_ITEMS[index]);
      setAnalyzing(false);
    }, 350);
  };

  const handleCustomSearch = (query: string) => {
    if (!query.trim()) return;
    setAnalyzing(true);
    setTimeout(() => {
      setActiveResult(analyzeFoodImage(query));
      setAnalyzing(false);
    }, 400);
  };

  const handleLogMeal = () => {
    setLoggedNotice(`Logged "${activeResult.detected_name}" (${activeResult.calories} kcal) to today's food diary!`);
    setTimeout(() => setLoggedNotice(null), 4000);
  };

  return (
    <div className="space-y-8">
      {/* Header Banner */}
      <div className="relative overflow-hidden rounded-3xl border border-emerald-200/80 bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 p-8 text-white shadow-xl shadow-emerald-900/10">
        <div className="absolute top-0 right-0 -mr-16 -mt-16 h-64 w-64 rounded-full bg-white/10 blur-2xl pointer-events-none" />
        <div className="relative z-10 max-w-2xl">
          <div className="inline-flex items-center gap-2 rounded-full bg-white/20 px-3 py-1 text-xs font-semibold backdrop-blur-md mb-3">
            <Sparkles className="h-3.5 w-3.5" />
            <span>Computer Vision & Deep Nutrition Classifier</span>
          </div>
          <h1 className="text-3xl sm:text-4xl font-extrabold tracking-tight">
            AI Food Recognition Studio
          </h1>
          <p className="mt-2 text-sm sm:text-base text-emerald-50/90 leading-relaxed">
            Snap meal photos or select sample dishes. Our neural network computes serving weights,
            macronutrient breakdowns, and clinical health ratings instantly.
          </p>
        </div>
      </div>

      {loggedNotice && (
        <div className="rounded-2xl border border-emerald-300 bg-emerald-50/90 p-4 text-emerald-800 shadow-md backdrop-blur-md flex items-center justify-between">
          <div className="flex items-center gap-3">
            <CheckCircle2 className="h-5 w-5 text-emerald-600" />
            <span className="text-sm font-semibold">{loggedNotice}</span>
          </div>
          <Link
            href="/user/log-meals"
            className="text-xs font-bold underline hover:text-emerald-950"
          >
            View Food Log &rarr;
          </Link>
        </div>
      )}

      {/* Preset Selector Chips */}
      <div className="rounded-2xl border border-slate-200/80 bg-white/80 p-5 shadow-sm backdrop-blur-md">
        <div className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3 flex items-center gap-1.5">
          <Camera className="h-4 w-4 text-emerald-600" />
          <span>Quick Recognition Samples:</span>
        </div>
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
          {SAMPLE_RECOGNITION_ITEMS.map((item, idx) => (
            <button
              key={idx}
              onClick={() => handleSelectPreset(idx)}
              className={`rounded-xl p-3 text-left transition-all border ${
                selectedPresetIndex === idx
                  ? "bg-emerald-50 border-emerald-400 shadow-xs ring-2 ring-emerald-500/20"
                  : "bg-slate-50/70 border-slate-200/70 hover:bg-slate-100 hover:border-slate-300"
              }`}
            >
              <div className="text-xs font-bold text-slate-900 truncate">
                {item.detected_name}
              </div>
              <div className="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                <span>{item.calories} kcal</span>
                <span className="font-semibold text-emerald-600">
                  {Math.round(item.confidence * 100)}% match
                </span>
              </div>
            </button>
          ))}
        </div>

        {/* Custom text/photo search input */}
        <div className="mt-4 pt-4 border-t border-slate-100 flex flex-col sm:flex-row gap-3">
          <div className="relative flex-1">
            <Search className="absolute left-3.5 top-3 h-4 w-4 text-slate-400" />
            <input
              type="text"
              placeholder="Or enter any meal name (e.g., 'Berry Oatmeal Bowl', 'Grilled Steak with Sweet Potato')..."
              className="w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-2.5 text-xs text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-hidden focus:ring-2 focus:ring-emerald-500/20"
              onKeyDown={(e) => {
                if (e.key === "Enter") {
                  handleCustomSearch((e.target as HTMLInputElement).value);
                }
              }}
            />
          </div>
          <button
            onClick={(e) => {
              const input = (e.currentTarget.previousElementSibling?.querySelector("input") as HTMLInputElement);
              if (input) handleCustomSearch(input.value);
            }}
            className="rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-bold text-white hover:bg-slate-800 transition-colors shrink-0"
          >
            Recognize Custom Meal
          </button>
        </div>
      </div>

      {/* Main Analysis Results Card */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Left Column: Visual Card & Confidence */}
        <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md flex flex-col justify-between">
          <div>
            <div className="relative overflow-hidden rounded-2xl bg-gradient-to-tr from-slate-900 via-slate-800 to-emerald-950 p-6 text-white min-h-[220px] flex flex-col justify-between">
              <div className="flex items-center justify-between">
                <span className="rounded-full bg-emerald-500/20 border border-emerald-400/40 px-3 py-1 text-[11px] font-bold text-emerald-300">
                  AI Verified
                </span>
                <span className="text-xs font-semibold text-slate-300">
                  {activeResult.estimated_serving}
                </span>
              </div>

              <div>
                <div className="text-xs text-emerald-400 uppercase tracking-wider font-semibold">
                  Detected Food Item
                </div>
                <h3 className="mt-1 text-xl font-bold text-white leading-snug">
                  {analyzing ? "Analyzing visual features..." : activeResult.detected_name}
                </h3>
              </div>

              <div className="flex items-center justify-between text-xs text-slate-300 pt-3 border-t border-white/10">
                <span>Weight: ~{activeResult.estimated_weight_g}g</span>
                <span>Accuracy: {Math.round(activeResult.confidence * 100)}%</span>
              </div>
            </div>

            {/* Health Score Gauge */}
            <div className="mt-6 p-4 rounded-2xl bg-emerald-50/70 border border-emerald-100 flex items-center justify-between">
              <div>
                <div className="text-xs font-bold text-emerald-900">Nutritional Health Score</div>
                <div className="text-[11px] text-emerald-700">Calculated from micro & macro density</div>
              </div>
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-600 text-white font-extrabold text-lg shadow-sm">
                {activeResult.health_score}
              </div>
            </div>

            {/* Dietary Tags */}
            <div className="mt-5">
              <div className="text-xs font-semibold text-slate-500 mb-2">Dietary Badges:</div>
              <div className="flex flex-wrap gap-2">
                {activeResult.dietary_tags?.map((tag, idx) => (
                  <span
                    key={idx}
                    className="rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-1 text-[11px] font-semibold text-slate-700"
                  >
                    {tag}
                  </span>
                ))}
              </div>
            </div>
          </div>

          <button
            onClick={handleLogMeal}
            className="mt-6 w-full rounded-2xl bg-emerald-600 py-3.5 text-xs font-bold text-white shadow-lg shadow-emerald-600/25 hover:bg-emerald-700 transition-all flex items-center justify-center gap-2"
          >
            <Plus className="h-4 w-4" />
            Add to Today&apos;s Food Diary
          </button>
        </div>

        {/* Middle + Right Columns: Macronutrient Breakdown & Clinical Advice */}
        <div className="lg:col-span-2 space-y-6">
          {/* Macronutrients Grid */}
          <div className="grid grid-cols-2 sm:grid-cols-4 gap-4">
            {/* Calories */}
            <div className="rounded-2xl border border-orange-100 bg-white/90 p-5 shadow-xs">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600 mb-3">
                <Flame className="h-5 w-5" />
              </div>
              <div className="text-2xl font-black text-slate-900">
                {activeResult.calories}
              </div>
              <div className="text-xs font-semibold text-slate-500">Calories (kcal)</div>
            </div>

            {/* Protein */}
            <div className="rounded-2xl border border-emerald-100 bg-white/90 p-5 shadow-xs">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 mb-3">
                <Scale className="h-5 w-5" />
              </div>
              <div className="text-2xl font-black text-slate-900">
                {activeResult.protein}g
              </div>
              <div className="text-xs font-semibold text-slate-500">Protein</div>
            </div>

            {/* Carbs */}
            <div className="rounded-2xl border border-sky-100 bg-white/90 p-5 shadow-xs">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 mb-3">
                <Apple className="h-5 w-5" />
              </div>
              <div className="text-2xl font-black text-slate-900">
                {activeResult.carbs}g
              </div>
              <div className="text-xs font-semibold text-slate-500">Carbohydrates</div>
            </div>

            {/* Fat & Fiber */}
            <div className="rounded-2xl border border-amber-100 bg-white/90 p-5 shadow-xs">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-100 text-amber-600 mb-3">
                <Activity className="h-5 w-5" />
              </div>
              <div className="text-2xl font-black text-slate-900">
                {activeResult.fat}g
              </div>
              <div className="text-xs font-semibold text-slate-500">
                Fats ({activeResult.fiber}g fiber)
              </div>
            </div>
          </div>

          {/* Macro Progress Bars */}
          <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md">
            <h4 className="text-sm font-bold text-slate-900 mb-4">
              Macronutrient Energy Contribution:
            </h4>
            <div className="space-y-3.5">
              <div>
                <div className="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                  <span>Protein ({activeResult.protein}g)</span>
                  <span>{Math.round((activeResult.protein * 4 / activeResult.calories) * 100)}%</span>
                </div>
                <div className="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                  <div
                    className="h-full rounded-full bg-emerald-500"
                    style={{ width: `${Math.min(100, (activeResult.protein * 4 / activeResult.calories) * 100)}%` }}
                  />
                </div>
              </div>

              <div>
                <div className="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                  <span>Carbohydrates ({activeResult.carbs}g)</span>
                  <span>{Math.round((activeResult.carbs * 4 / activeResult.calories) * 100)}%</span>
                </div>
                <div className="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                  <div
                    className="h-full rounded-full bg-sky-500"
                    style={{ width: `${Math.min(100, (activeResult.carbs * 4 / activeResult.calories) * 100)}%` }}
                  />
                </div>
              </div>

              <div>
                <div className="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                  <span>Lipids / Healthy Fats ({activeResult.fat}g)</span>
                  <span>{Math.round((activeResult.fat * 9 / activeResult.calories) * 100)}%</span>
                </div>
                <div className="h-2.5 w-full rounded-full bg-slate-100 overflow-hidden">
                  <div
                    className="h-full rounded-full bg-amber-500"
                    style={{ width: `${Math.min(100, (activeResult.fat * 9 / activeResult.calories) * 100)}%` }}
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Clinical Dietitian Suggestions */}
          <div className="rounded-3xl border border-teal-200/70 bg-gradient-to-br from-teal-50/70 to-emerald-50/50 p-6 shadow-sm backdrop-blur-md">
            <div className="flex items-center gap-2 text-teal-800 font-bold text-sm mb-3">
              <Stethoscope className="h-4 w-4 text-teal-600" />
              <span>Clinical Guidance & Dietary Tips:</span>
            </div>
            <ul className="space-y-2 text-xs sm:text-sm text-slate-700">
              {activeResult.suggestions?.map((item, idx) => (
                <li key={idx} className="flex items-start gap-2">
                  <Check className="h-4 w-4 text-teal-600 shrink-0 mt-0.5" />
                  <span>{item}</span>
                </li>
              ))}
            </ul>
          </div>
        </div>
      </div>
    </div>
  );
}

/* =========================================================================
   2. USER DASHBOARD VIEW
========================================================================= */
function UserDashboardView() {
  const [waterMl, setWaterMl] = useState(1500);
  const [waterGoal] = useState(2500);

  const addWater = (amount: number) => {
    setWaterMl((prev) => Math.min(prev + amount, 4000));
  };

  return (
    <div className="space-y-8">
      {/* Welcome & Motivational Banner */}
      <div className="rounded-3xl border border-emerald-200/60 bg-gradient-to-r from-emerald-50 via-teal-50/60 to-white p-6 sm:p-8 shadow-xs">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div>
            <span className="text-xs font-bold text-emerald-700 uppercase tracking-wider">
              Health Status: Optimal Balance
            </span>
            <h1 className="mt-1 text-2xl sm:text-3xl font-extrabold text-slate-900">
              Welcome back, Alice!
            </h1>
            <p className="mt-1 text-xs sm:text-sm text-slate-600 max-w-xl">
              You are currently 430 kcal below your daily target with 85% of your protein goal achieved.
            </p>
          </div>
          <Link
            href="/user/recognition"
            className="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-xs font-bold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-700 transition-all shrink-0"
          >
            <Camera className="h-4 w-4" />
            Scan Next Meal
          </Link>
        </div>
      </div>

      {/* 4 Glassmorphism KPI Metric Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        {/* Calories Card */}
        <div className="glass-card rounded-3xl p-6 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Calories</span>
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-100 text-orange-600">
              <Flame className="h-5 w-5" />
            </div>
          </div>
          <div className="mt-4 flex items-baseline gap-2">
            <span className="text-3xl font-black text-slate-900">1,420</span>
            <span className="text-xs text-slate-400 font-semibold">/ 1,850 kcal</span>
          </div>
          <div className="mt-3 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
            <div className="h-full rounded-full bg-orange-500" style={{ width: "76%" }}></div>
          </div>
          <div className="mt-2 text-[11px] text-slate-500 font-medium">430 kcal remaining today</div>
        </div>

        {/* Water Card */}
        <div className="glass-card rounded-3xl p-6 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Hydration</span>
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-sky-100 text-sky-600">
              <Droplets className="h-5 w-5" />
            </div>
          </div>
          <div className="mt-4 flex items-baseline gap-2">
            <span className="text-3xl font-black text-slate-900">{waterMl}</span>
            <span className="text-xs text-slate-400 font-semibold">/ {waterGoal} ml</span>
          </div>
          <div className="mt-3 h-2 w-full rounded-full bg-slate-100 overflow-hidden">
            <div
              className="h-full rounded-full bg-sky-500 transition-all duration-300"
              style={{ width: `${Math.min(100, (waterMl / waterGoal) * 100)}%` }}
            ></div>
          </div>
          <div className="mt-3 flex items-center justify-between">
            <span className="text-[11px] text-slate-500 font-medium">
              {Math.round((waterMl / waterGoal) * 100)}% completed
            </span>
            <button
              onClick={() => addWater(250)}
              className="rounded-lg bg-sky-50 px-2 py-1 text-[11px] font-bold text-sky-700 hover:bg-sky-100 transition-colors"
            >
              +250ml
            </button>
          </div>
        </div>

        {/* Weight & BMI Card */}
        <div className="glass-card rounded-3xl p-6 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">BMI & Weight</span>
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600">
              <Scale className="h-5 w-5" />
            </div>
          </div>
          <div className="mt-4 flex items-baseline gap-2">
            <span className="text-3xl font-black text-slate-900">22.4</span>
            <span className="text-xs font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
              Normal
            </span>
          </div>
          <div className="mt-3 text-xs text-slate-600">
            Current: <strong>61.0 kg</strong> (Goal: 58.0 kg)
          </div>
          <div className="mt-2 text-[11px] text-slate-400 font-medium">Target Deficit: -0.4 kg / week</div>
        </div>

        {/* Assigned Dietitian Card */}
        <div className="glass-card rounded-3xl p-6 shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-bold text-slate-500 uppercase tracking-wider">Clinical Care</span>
            <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-100 text-teal-600">
              <UserCheck className="h-5 w-5" />
            </div>
          </div>
          <div className="mt-3">
            <div className="text-sm font-bold text-slate-900">Dr. Sarah Miller, RD</div>
            <div className="text-[11px] text-slate-500">M.Sc. Clinical Nutrition</div>
          </div>
          <div className="mt-3 rounded-xl bg-teal-50 p-2.5 text-[11px] text-teal-800 font-medium">
            Active Plan: Clean Eating &amp; Lean Deficit
          </div>
          <div className="mt-3">
            <Link
              href="/user/guidance"
              className="text-[11px] font-bold text-teal-700 hover:underline inline-flex items-center gap-1"
            >
              Read Recommendations &rarr;
            </Link>
          </div>
        </div>
      </div>

      {/* Today's Meals Timeline & Dietitian Guidance */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Left 2 Cols: Meals Logged Today */}
        <div className="lg:col-span-2 rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md">
          <div className="flex items-center justify-between mb-5">
            <div>
              <h3 className="text-base font-bold text-slate-900">Today&apos;s Meals Timeline</h3>
              <p className="text-xs text-slate-500">Logged meals and nutrient distributions</p>
            </div>
            <Link
              href="/user/log-meals"
              className="rounded-xl bg-slate-100 hover:bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700 transition-colors inline-flex items-center gap-1"
            >
              <Plus className="h-3.5 w-3.5" /> Log Meal
            </Link>
          </div>

          <div className="space-y-3">
            {mockData.mealLogs.map((log) => (
              <div
                key={log.id}
                className="flex items-center justify-between p-4 rounded-2xl bg-slate-50/80 border border-slate-200/60 hover:bg-white hover:shadow-xs transition-all"
              >
                <div className="flex items-center gap-3.5">
                  <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 font-bold text-xs">
                    {log.meal_type[0]}
                  </div>
                  <div>
                    <div className="text-xs font-bold text-slate-900">{log.food_name}</div>
                    <div className="text-[11px] text-slate-500">
                      {log.meal_type} • {log.meal_time} • {log.quantity} serving
                    </div>
                  </div>
                </div>

                <div className="text-right">
                  <div className="text-xs font-black text-slate-900">{log.calories} kcal</div>
                  <div className="text-[10px] text-slate-500 font-medium">
                    {log.protein}g P • {log.carbohydrates}g C • {log.fat}g F
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Right Col: Active Dietitian Guidance */}
        <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md flex flex-col justify-between">
          <div>
            <div className="flex items-center gap-2 mb-3 text-xs font-bold uppercase tracking-wider text-teal-700">
              <Stethoscope className="h-4 w-4" />
              <span>Dietitian Recommendation</span>
            </div>
            <h4 className="text-base font-bold text-slate-900">
              {mockData.recommendations[0]?.title}
            </h4>
            <p className="mt-3 text-xs sm:text-sm text-slate-600 leading-relaxed">
              &ldquo;{mockData.recommendations[0]?.recommendation}&rdquo;
            </p>
          </div>

          <div className="mt-6 pt-4 border-t border-slate-100">
            <div className="text-xs text-slate-400 font-medium">
              Prescribed by Dr. Sarah Miller on Oct 8, 2026
            </div>
            <Link
              href="/user/guidance"
              className="mt-3 block text-center rounded-xl bg-teal-600 hover:bg-teal-700 py-2.5 text-xs font-bold text-white transition-colors"
            >
              Open Full Guidance
            </Link>
          </div>
        </div>
      </div>
    </div>
  );
}

/* =========================================================================
   3. LOG MEALS VIEW
========================================================================= */
function LogMealsView() {
  const [logs, setLogs] = useState<MealLog[]>(mockData.mealLogs as unknown as MealLog[]);
  const [search, setSearch] = useState("");
  const [selectedFoodId, setSelectedFoodId] = useState<number>(mockData.foodItems[0].id);
  const [mealType, setMealType] = useState<"Breakfast" | "Lunch" | "Dinner" | "Snack">("Breakfast");
  const [servings, setServings] = useState(1);

  const selectedFood = mockData.foodItems.find((f) => f.id === selectedFoodId) || mockData.foodItems[0];

  const handleAddMeal = () => {
    const newLog: MealLog = {
      id: Date.now(),
      user_id: 4,
      food_id: selectedFood.id,
      food_name: selectedFood.food_name,
      meal_type: mealType,
      quantity: servings,
      calories: Math.round(selectedFood.calories * servings),
      protein: Number((selectedFood.protein * servings).toFixed(1)),
      carbohydrates: Number((selectedFood.carbohydrates * servings).toFixed(1)),
      fat: Number((selectedFood.fat * servings).toFixed(1)),
      meal_date: new Date().toISOString().split("T")[0],
      meal_time: new Date().toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" }),
    };

    setLogs([newLog, ...logs]);
  };

  const handleDelete = (id: number) => {
    setLogs(logs.filter((l) => l.id !== id));
  };

  const filteredFoods = mockData.foodItems.filter((f) =>
    f.food_name.toLowerCase().includes(search.toLowerCase())
  );

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-extrabold text-slate-900">Food Log &amp; Macronutrient Tracker</h1>
        <p className="mt-1 text-sm text-slate-600">
          Search our verified clinical nutritional database or add custom items to your daily intake.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Left: Food Selector Form */}
        <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md">
          <h3 className="text-base font-bold text-slate-900 mb-4">Add Food to Diary</h3>

          {/* Search bar */}
          <div className="relative mb-4">
            <Search className="absolute left-3.5 top-3 h-4 w-4 text-slate-400" />
            <input
              type="text"
              placeholder="Search verified food..."
              value={search}
              onChange={(e) => setSearch(e.target.value)}
              className="w-full rounded-xl border border-slate-200 bg-white pl-10 pr-4 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-emerald-500 focus:outline-hidden"
            />
          </div>

          {/* Food Select Dropdown */}
          <div className="mb-4">
            <label className="block text-xs font-bold text-slate-700 mb-1">Select Food Item</label>
            <select
              value={selectedFoodId}
              onChange={(e) => setSelectedFoodId(Number(e.target.value))}
              className="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs text-slate-800 focus:border-emerald-500 focus:outline-hidden"
            >
              {filteredFoods.map((f) => (
                <option key={f.id} value={f.id}>
                  {f.food_name} ({f.calories} kcal / {f.serving_size})
                </option>
              ))}
            </select>
          </div>

          {/* Meal Type */}
          <div className="mb-4">
            <label className="block text-xs font-bold text-slate-700 mb-1">Meal Category</label>
            <div className="grid grid-cols-2 gap-2">
              {(["Breakfast", "Lunch", "Dinner", "Snack"] as const).map((m) => (
                <button
                  key={m}
                  type="button"
                  onClick={() => setMealType(m)}
                  className={`rounded-xl py-2 text-xs font-semibold border ${
                    mealType === m
                      ? "bg-emerald-50 border-emerald-500 text-emerald-800"
                      : "bg-slate-50 border-slate-200 text-slate-600 hover:bg-slate-100"
                  }`}
                >
                  {m}
                </button>
              ))}
            </div>
          </div>

          {/* Servings */}
          <div className="mb-5">
            <label className="block text-xs font-bold text-slate-700 mb-1">
              Servings: {servings}x ({Math.round(selectedFood.serving_weight_g * servings)}g)
            </label>
            <input
              type="range"
              min="0.5"
              max="4"
              step="0.5"
              value={servings}
              onChange={(e) => setServings(Number(e.target.value))}
              className="w-full accent-emerald-600"
            />
          </div>

          {/* Live Nutrition Summary Box */}
          <div className="rounded-2xl bg-emerald-50/80 p-4 border border-emerald-100 mb-5">
            <div className="text-xs font-bold text-emerald-900 mb-1">Estimated Intake:</div>
            <div className="text-xl font-black text-emerald-950">
              {Math.round(selectedFood.calories * servings)} kcal
            </div>
            <div className="mt-1 text-[11px] text-emerald-800 font-medium">
              Protein: {(selectedFood.protein * servings).toFixed(1)}g • Carbs:{" "}
              {(selectedFood.carbohydrates * servings).toFixed(1)}g • Fat:{" "}
              {(selectedFood.fat * servings).toFixed(1)}g
            </div>
          </div>

          <button
            onClick={handleAddMeal}
            className="w-full rounded-2xl bg-emerald-600 py-3 text-xs font-bold text-white shadow-md shadow-emerald-600/25 hover:bg-emerald-700 transition-colors"
          >
            + Add to Food Diary
          </button>
        </div>

        {/* Right: Logged Meals Table */}
        <div className="lg:col-span-2 rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-base font-bold text-slate-900">Today&apos;s Registered Meals</h3>
            <span className="text-xs font-semibold text-slate-500">
              Total: {logs.reduce((acc, curr) => acc + curr.calories, 0)} kcal
            </span>
          </div>

          <div className="overflow-x-auto">
            <table className="w-full text-left text-xs">
              <thead className="border-b border-slate-200 bg-slate-50/80 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                <tr>
                  <th className="p-3">Meal / Time</th>
                  <th className="p-3">Food Item</th>
                  <th className="p-3">Calories</th>
                  <th className="p-3">P / C / F</th>
                  <th className="p-3 text-right">Action</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {logs.map((item) => (
                  <tr key={item.id} className="hover:bg-slate-50/50">
                    <td className="p-3 font-semibold text-slate-900">
                      <div>{item.meal_type}</div>
                      <div className="text-[10px] text-slate-400">{item.meal_time}</div>
                    </td>
                    <td className="p-3 font-medium text-slate-800">
                      {item.food_name} <span className="text-slate-400">({item.quantity}x)</span>
                    </td>
                    <td className="p-3 font-bold text-emerald-700">{item.calories} kcal</td>
                    <td className="p-3 text-slate-500">
                      {item.protein}g / {item.carbohydrates}g / {item.fat}g
                    </td>
                    <td className="p-3 text-right">
                      <button
                        onClick={() => handleDelete(item.id)}
                        className="rounded-lg p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                        title="Delete meal"
                      >
                        <Trash2 className="h-4 w-4" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
}

/* =========================================================================
   4. HEALTH INFO VIEW
========================================================================= */
function HealthInfoView() {
  const [height, setHeight] = useState(165);
  const [weight, setWeight] = useState(61);
  const [age, setAge] = useState(28);
  const [goal, setGoal] = useState("weight_loss");
  const [saved, setSaved] = useState(false);

  // Compute BMI
  const heightM = height / 100;
  const bmi = Number((weight / (heightM * heightM)).toFixed(1));

  let bmiLabel = "Normal Weight";
  let bmiColor = "text-emerald-700 bg-emerald-50 border-emerald-200";
  if (bmi < 18.5) {
    bmiLabel = "Underweight";
    bmiColor = "text-amber-700 bg-amber-50 border-amber-200";
  } else if (bmi >= 25 && bmi < 30) {
    bmiLabel = "Overweight";
    bmiColor = "text-orange-700 bg-orange-50 border-orange-200";
  } else if (bmi >= 30) {
    bmiLabel = "Obesity Range";
    bmiColor = "text-red-700 bg-red-50 border-red-200";
  }

  // Daily target formula
  const calorieTarget = Math.round(10 * weight + 6.25 * height - 5 * age + (goal === "weight_loss" ? -350 : 250));

  const handleSave = () => {
    setSaved(true);
    setTimeout(() => setSaved(false), 3000);
  };

  return (
    <div className="space-y-8 max-w-4xl mx-auto">
      <div>
        <h1 className="text-3xl font-extrabold text-slate-900">Health &amp; BMI Profile</h1>
        <p className="mt-1 text-sm text-slate-600">
          Personalized anthropometric measures and clinical energy expenditure equations.
        </p>
      </div>

      {saved && (
        <div className="rounded-2xl border border-emerald-300 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 flex items-center gap-2">
          <CheckCircle2 className="h-4 w-4 text-emerald-600" />
          <span>Health profile successfully synchronized with your clinical record!</span>
        </div>
      )}

      {/* Interactive Sliders */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md space-y-6">
          <h3 className="text-base font-bold text-slate-900">Physical Metrics</h3>

          <div>
            <div className="flex justify-between text-xs font-bold text-slate-700 mb-2">
              <span>Height: {height} cm</span>
              <span className="text-slate-400">({(height / 30.48).toFixed(1)} ft)</span>
            </div>
            <input
              type="range"
              min="140"
              max="210"
              value={height}
              onChange={(e) => setHeight(Number(e.target.value))}
              className="w-full accent-emerald-600"
            />
          </div>

          <div>
            <div className="flex justify-between text-xs font-bold text-slate-700 mb-2">
              <span>Current Weight: {weight} kg</span>
              <span className="text-slate-400">({(weight * 2.20462).toFixed(1)} lbs)</span>
            </div>
            <input
              type="range"
              min="40"
              max="150"
              value={weight}
              onChange={(e) => setWeight(Number(e.target.value))}
              className="w-full accent-emerald-600"
            />
          </div>

          <div>
            <div className="flex justify-between text-xs font-bold text-slate-700 mb-2">
              <span>Age: {age} years</span>
            </div>
            <input
              type="range"
              min="16"
              max="90"
              value={age}
              onChange={(e) => setAge(Number(e.target.value))}
              className="w-full accent-emerald-600"
            />
          </div>

          <div>
            <label className="block text-xs font-bold text-slate-700 mb-2">Health Goal</label>
            <select
              value={goal}
              onChange={(e) => setGoal(e.target.value)}
              className="w-full rounded-xl border border-slate-200 bg-white p-2.5 text-xs text-slate-800"
            >
              <option value="weight_loss">Weight Loss (Caloric Deficit -350 kcal)</option>
              <option value="maintain">Maintain Weight &amp; Tone</option>
              <option value="muscle_gain">Hypertrophy / Lean Muscle Surplus (+250 kcal)</option>
            </select>
          </div>

          <button
            onClick={handleSave}
            className="w-full rounded-2xl bg-emerald-600 py-3 text-xs font-bold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-700 transition-colors"
          >
            Save Health Profile
          </button>
        </div>

        {/* Calculated Results Card */}
        <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md flex flex-col justify-between">
          <div>
            <h3 className="text-base font-bold text-slate-900 mb-6">Computed Diagnostics</h3>

            {/* BMI Display */}
            <div className="p-5 rounded-2xl bg-slate-50 border border-slate-200/80 text-center">
              <div className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                Body Mass Index (BMI)
              </div>
              <div className="mt-2 text-4xl font-black text-slate-900">{bmi}</div>
              <div className="mt-2 inline-block">
                <span className={`px-3 py-1 rounded-full text-xs font-bold border ${bmiColor}`}>
                  {bmiLabel}
                </span>
              </div>
            </div>

            {/* Daily Energy Target */}
            <div className="mt-6 p-5 rounded-2xl bg-emerald-50/70 border border-emerald-100 text-center">
              <div className="text-xs font-semibold text-emerald-800 uppercase tracking-wider">
                Prescribed Daily Caloric Target
              </div>
              <div className="mt-2 text-3xl font-black text-emerald-950">
                {calorieTarget} <span className="text-base font-bold text-emerald-700">kcal</span>
              </div>
              <p className="mt-1 text-[11px] text-emerald-800">
                Calculated according to the Harris-Benedict Resting Metabolic Rate
              </p>
            </div>
          </div>

          <div className="mt-6 p-4 rounded-xl bg-slate-100/60 text-[11px] text-slate-500 leading-relaxed">
            Note: All nutritional recommendations are calibrated against your assigned dietitian&apos;s
            clinical supervision and target guidelines.
          </div>
        </div>
      </div>
    </div>
  );
}

/* =========================================================================
   5. GUIDANCE & DIETITIAN VIEW
========================================================================= */
function GuidanceView() {
  const plan = mockData.mealPlans[0];
  const recommendation = mockData.recommendations[0];

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-extrabold text-slate-900">Clinical Dietitian Guidance</h1>
        <p className="mt-1 text-sm text-slate-600">
          Personalized dietary therapy prescribed by your registered clinical dietitian.
        </p>
      </div>

      {/* Dietitian Header Card */}
      <div className="rounded-3xl border border-teal-200/80 bg-gradient-to-r from-teal-500 to-emerald-600 p-8 text-white shadow-xl shadow-teal-900/10">
        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
          <div className="flex items-center gap-4">
            <div className="h-16 w-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center font-bold text-2xl text-white">
              SM
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h2 className="text-2xl font-bold text-white">Dr. Sarah Miller, RD</h2>
                <span className="rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-bold">
                  Certified Dietitian
                </span>
              </div>
              <p className="text-xs text-teal-100 mt-0.5">
                M.Sc. Clinical Nutrition • Certified Sports Nutritionist (CSN)
              </p>
              <p className="text-xs text-teal-100/80 mt-1">8 Years Clinical Practice</p>
            </div>
          </div>
          <div className="rounded-2xl bg-white/10 p-3 backdrop-blur-md text-xs font-semibold">
            Status: Active Patient Assignment
          </div>
        </div>
      </div>

      {/* Clinical Notes Banner */}
      <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md">
        <h3 className="text-base font-bold text-slate-900 mb-2">
          {recommendation.title}
        </h3>
        <p className="text-sm text-slate-600 leading-relaxed">
          {recommendation.recommendation}
        </p>
      </div>

      {/* Active Meal Plan Items */}
      <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md">
        <div className="mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
          <div>
            <h3 className="text-lg font-bold text-slate-900">{plan.title}</h3>
            <p className="text-xs text-slate-500">{plan.description}</p>
          </div>
          <span className="rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-800 self-start sm:self-auto">
            {plan.goal}
          </span>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          {plan.items.map((item, idx) => (
            <div
              key={idx}
              className="rounded-2xl border border-slate-200/70 bg-slate-50/70 p-5 hover:bg-white transition-all shadow-2xs"
            >
              <div className="flex items-center justify-between mb-2">
                <span className="text-xs font-bold text-emerald-700 uppercase tracking-wider">
                  {item.meal_type}
                </span>
                <span className="text-xs font-black text-slate-900">{item.calories} kcal</span>
              </div>
              <h4 className="text-sm font-bold text-slate-900">{item.food_name}</h4>
              <p className="text-xs text-slate-500 mt-1">Portion: {item.quantity}</p>
              <div className="mt-3 text-[11px] text-teal-800 font-medium bg-teal-50/80 rounded-lg p-2 border border-teal-100/60">
                💡 Note: {item.notes}
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

/* =========================================================================
   6. REPORTS VIEW
========================================================================= */
function ReportsView() {
  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-extrabold text-slate-900">Calorie &amp; Macro Analytics</h1>
        <p className="mt-1 text-sm text-slate-600">
          Weekly consumption trends, macronutrient adherence, and health goal progress.
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-5">
        <div className="glass-card rounded-3xl p-6">
          <div className="text-xs font-bold text-slate-400 uppercase tracking-wider">
            7-Day Caloric Avg
          </div>
          <div className="mt-2 text-3xl font-black text-slate-900">1,780 kcal</div>
          <div className="mt-1 text-xs text-emerald-600 font-semibold">96% within target budget</div>
        </div>

        <div className="glass-card rounded-3xl p-6">
          <div className="text-xs font-bold text-slate-400 uppercase tracking-wider">
            Average Protein Intake
          </div>
          <div className="mt-2 text-3xl font-black text-slate-900">112g / day</div>
          <div className="mt-1 text-xs text-emerald-600 font-semibold">100% of minimum requirement</div>
        </div>

        <div className="glass-card rounded-3xl p-6">
          <div className="text-xs font-bold text-slate-400 uppercase tracking-wider">
            Hydration Consistency
          </div>
          <div className="mt-2 text-3xl font-black text-slate-900">2,350 ml</div>
          <div className="mt-1 text-xs text-sky-600 font-semibold">94% weekly hydration score</div>
        </div>
      </div>

      {/* Visual Chart Card */}
      <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-8 shadow-sm backdrop-blur-md">
        <h3 className="text-base font-bold text-slate-900 mb-6">
          Weekly Calorie Consumption vs. Goal (1,850 kcal)
        </h3>

        <div className="h-48 flex items-end justify-between gap-2 sm:gap-6 pt-6 border-b border-slate-200">
          {[
            { day: "Mon", kcal: 1820 },
            { day: "Tue", kcal: 1750 },
            { day: "Wed", kcal: 1890 },
            { day: "Thu", kcal: 1710 },
            { day: "Fri", kcal: 1800 },
            { day: "Sat", kcal: 1950 },
            { day: "Sun", kcal: 1780 },
          ].map((bar, idx) => (
            <div key={idx} className="flex-1 flex flex-col items-center gap-2 h-full justify-end">
              <div className="text-[10px] font-bold text-slate-600">{bar.kcal}</div>
              <div
                className="w-full rounded-t-xl bg-gradient-to-t from-emerald-600 to-teal-400 hover:brightness-110 transition-all cursor-pointer"
                style={{ height: `${(bar.kcal / 2200) * 100}%` }}
              ></div>
              <div className="text-xs font-bold text-slate-500 mt-2">{bar.day}</div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
}

/* =========================================================================
   7. DIETITIAN PORTAL VIEW
========================================================================= */
function DietitianPortalView() {
  const [patients] = useState(mockData.users.filter((u) => u.role === "user"));
  const [selectedPatient, setSelectedPatient] = useState(patients[0]);

  return (
    <div className="space-y-8">
      <div>
        <div className="inline-flex items-center gap-1.5 rounded-full bg-teal-100 px-3 py-1 text-xs font-semibold text-teal-800 mb-2">
          <Stethoscope className="h-3.5 w-3.5" />
          <span>Dietitian Clinical Portal</span>
        </div>
        <h1 className="text-3xl font-extrabold text-slate-900">Patient Directory &amp; Meal Plans</h1>
        <p className="mt-1 text-sm text-slate-600">
          Supervise assigned patient cohorts, author meal plans, and review dietary compliance.
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Patient Roster */}
        <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md">
          <h3 className="text-base font-bold text-slate-900 mb-4">Assigned Patients ({patients.length})</h3>
          <div className="space-y-3">
            {patients.map((p) => (
              <div
                key={p.id}
                onClick={() => setSelectedPatient(p)}
                className={`p-4 rounded-2xl border cursor-pointer transition-all ${
                  selectedPatient.id === p.id
                    ? "bg-teal-50 border-teal-300 ring-2 ring-teal-500/10 shadow-xs"
                    : "bg-slate-50/70 border-slate-200/70 hover:bg-slate-100"
                }`}
              >
                <div className="flex items-center justify-between">
                  <div className="font-bold text-xs text-slate-900">{p.name}</div>
                  <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-800">
                    Active
                  </span>
                </div>
                <div className="text-[11px] text-slate-500 mt-1">{p.email}</div>
                <div className="text-[11px] text-teal-700 font-semibold mt-2">
                  Goal: Weight Loss • 1,850 kcal/day
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Patient Detail & Guidance Composer */}
        <div className="lg:col-span-2 rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md space-y-6">
          <div className="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
              <h3 className="text-lg font-bold text-slate-900">{selectedPatient.name}</h3>
              <p className="text-xs text-slate-500">{selectedPatient.email} • {selectedPatient.phone}</p>
            </div>
            <span className="rounded-xl bg-teal-50 border border-teal-200 px-3 py-1 text-xs font-bold text-teal-800">
              Assigned Patient
            </span>
          </div>

          <div>
            <h4 className="text-xs font-bold uppercase tracking-wider text-slate-400 mb-3">
              Compose Clinical Recommendation:
            </h4>
            <textarea
              rows={4}
              defaultValue="Keep maintaining the 400 kcal deficit. Focus on spacing your protein evenly across lunch and dinner to prevent cravings."
              className="w-full rounded-2xl border border-slate-200 bg-white p-4 text-xs sm:text-sm text-slate-800 focus:border-teal-500 focus:outline-hidden focus:ring-2 focus:ring-teal-500/20"
            />
          </div>

          <div className="flex justify-end gap-3">
            <button
              onClick={() => alert("Recommendation published to patient portal!")}
              className="rounded-2xl bg-teal-600 px-6 py-2.5 text-xs font-bold text-white shadow-md shadow-teal-600/20 hover:bg-teal-700 transition-colors"
            >
              Publish Dietary Recommendation
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

/* =========================================================================
   8. ADMIN PORTAL VIEW
========================================================================= */
function AdminPortalView() {
  const [dietitians, setDietitians] = useState([
    {
      id: 3,
      name: "Dr. James Wilson",
      email: "james@healthtrack.com",
      qualification: "Ph.D. Nutritional Science",
      specialization: "Diabetes & Cardiovascular Diets",
      status: "pending",
    },
    {
      id: 2,
      name: "Dr. Sarah Miller",
      email: "sarah@healthtrack.com",
      qualification: "M.Sc. Clinical Nutrition, RD",
      specialization: "Weight Management & Sports",
      status: "approved",
    },
  ]);

  const handleApprove = (id: number) => {
    setDietitians(
      dietitians.map((d) => (d.id === id ? { ...d, status: "approved" } : d))
    );
  };

  const handleReject = (id: number) => {
    setDietitians(
      dietitians.map((d) => (d.id === id ? { ...d, status: "rejected" } : d))
    );
  };

  return (
    <div className="space-y-8">
      <div>
        <div className="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-800 mb-2">
          <ShieldCheck className="h-3.5 w-3.5" />
          <span>System Administration Hub</span>
        </div>
        <h1 className="text-3xl font-extrabold text-slate-900">Admin Control Center</h1>
        <p className="mt-1 text-sm text-slate-600">
          User access controls, certified dietitian credentials validation, and security telemetry.
        </p>
      </div>

      {/* Admin KPIs */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div className="glass-card rounded-3xl p-6">
          <div className="text-xs font-bold text-slate-400 uppercase tracking-wider">Registered Accounts</div>
          <div className="mt-2 text-3xl font-black text-slate-900">10,482</div>
          <div className="mt-1 text-xs text-indigo-600 font-semibold">+142 this week</div>
        </div>

        <div className="glass-card rounded-3xl p-6">
          <div className="text-xs font-bold text-slate-400 uppercase tracking-wider">Active Dietitians</div>
          <div className="mt-2 text-3xl font-black text-slate-900">54</div>
          <div className="mt-1 text-xs text-amber-600 font-semibold">1 Pending Review</div>
        </div>

        <div className="glass-card rounded-3xl p-6">
          <div className="text-xs font-bold text-slate-400 uppercase tracking-wider">Foods in Database</div>
          <div className="mt-2 text-3xl font-black text-slate-900">1,240</div>
          <div className="mt-1 text-xs text-emerald-600 font-semibold">100% verified macros</div>
        </div>

        <div className="glass-card rounded-3xl p-6">
          <div className="text-xs font-bold text-slate-400 uppercase tracking-wider">Platform Health</div>
          <div className="mt-2 text-3xl font-black text-emerald-600">99.9%</div>
          <div className="mt-1 text-xs text-slate-400 font-medium">All services online</div>
        </div>
      </div>

      {/* Dietitians Verification Queue */}
      <div className="rounded-3xl border border-slate-200/80 bg-white/80 p-6 shadow-sm backdrop-blur-md">
        <h3 className="text-base font-bold text-slate-900 mb-4">
          Dietitian Credential Approvals Queue
        </h3>

        <div className="overflow-x-auto">
          <table className="w-full text-left text-xs">
            <thead className="border-b border-slate-200 bg-slate-50 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <tr>
                <th className="p-3">Dietitian Name</th>
                <th className="p-3">Qualifications</th>
                <th className="p-3">Specialization</th>
                <th className="p-3">Status</th>
                <th className="p-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {dietitians.map((d) => (
                <tr key={d.id} className="hover:bg-slate-50/50">
                  <td className="p-3 font-bold text-slate-900">
                    <div>{d.name}</div>
                    <div className="text-[10px] text-slate-400">{d.email}</div>
                  </td>
                  <td className="p-3 text-slate-600">{d.qualification}</td>
                  <td className="p-3 text-slate-600">{d.specialization}</td>
                  <td className="p-3">
                    <span
                      className={`px-2.5 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                        d.status === "approved"
                          ? "bg-emerald-100 text-emerald-800"
                          : d.status === "pending"
                          ? "bg-amber-100 text-amber-800 animate-pulse"
                          : "bg-red-100 text-red-800"
                      }`}
                    >
                      {d.status}
                    </span>
                  </td>
                  <td className="p-3 text-right">
                    {d.status === "pending" ? (
                      <div className="flex items-center justify-end gap-2">
                        <button
                          onClick={() => handleApprove(d.id)}
                          className="rounded-lg bg-emerald-600 px-3 py-1.5 text-[11px] font-bold text-white hover:bg-emerald-700 shadow-2xs transition-colors"
                        >
                          Approve
                        </button>
                        <button
                          onClick={() => handleReject(d.id)}
                          className="rounded-lg bg-red-50 text-red-700 px-3 py-1.5 text-[11px] font-bold hover:bg-red-100 transition-colors"
                        >
                          Reject
                        </button>
                      </div>
                    ) : (
                      <span className="text-[11px] text-slate-400 font-semibold">Processed</span>
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

/* =========================================================================
   9. AUTH PORTAL VIEW (LOGIN & REGISTER)
========================================================================= */
function AuthPortal({ mode }: { mode: "login" | "register" }) {
  const { login, quickLogin } = useAuth();
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [role, setRole] = useState<"user" | "dietitian" | "admin">("user");
  const [submitting, setSubmitting] = useState(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitting(true);
    setErrorMsg(null);

    const success = await login(email || (role === "admin" ? "admin@healthtrack.com" : role === "dietitian" ? "sarah@healthtrack.com" : "alice@example.com"), role);
    setSubmitting(false);

    if (success) {
      window.location.href = role === "admin" ? "/admin/dashboard" : role === "dietitian" ? "/dietitian/dashboard" : "/user/dashboard";
    } else {
      setErrorMsg("Please provide a valid email and credentials.");
    }
  };

  const handleFillDemo = (targetRole: "admin" | "dietitian" | "user") => {
    if (targetRole === "admin") {
      setEmail("admin@healthtrack.com");
      setPassword("admin123");
      setRole("admin");
    } else if (targetRole === "dietitian") {
      setEmail("sarah@healthtrack.com");
      setPassword("dietitian123");
      setRole("dietitian");
    } else {
      setEmail("alice@example.com");
      setPassword("user123");
      setRole("user");
    }
  };

  return (
    <div className="flex-1 min-h-screen bg-gradient-to-br from-slate-900 via-slate-950 to-slate-900 flex items-center justify-center p-4 sm:p-6 relative overflow-hidden">
      {/* Background glow orbs */}
      <div className="absolute top-1/4 left-1/4 w-96 h-96 rounded-full bg-emerald-500/10 blur-3xl pointer-events-none" />
      <div className="absolute bottom-1/4 right-1/4 w-96 h-96 rounded-full bg-teal-500/10 blur-3xl pointer-events-none" />

      <div className="w-full max-w-md relative z-10">
        <div className="glass-card-dark rounded-3xl p-8 sm:p-10 border border-white/10 shadow-2xl">
          {/* Logo & Header */}
          <div className="text-center mb-8">
            <Link href="/" className="inline-flex items-center gap-2 mb-3 group">
              <div className="h-10 w-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center text-white shadow-lg shadow-emerald-500/30 group-hover:scale-105 transition-transform">
                <Heart className="h-5 w-5 fill-white" />
              </div>
              <span className="text-xl font-bold text-white tracking-tight">HealthTrack</span>
            </Link>
            <h2 className="text-2xl font-extrabold text-white">
              {mode === "login" ? "Welcome Back" : "Create Account"}
            </h2>
            <p className="mt-1 text-xs text-slate-400">
              {mode === "login"
                ? "Sign in to access your nutritional intelligence workspace."
                : "Join thousands achieving metabolic health with AI tracking."}
            </p>
          </div>

          {errorMsg && (
            <div className="mb-6 rounded-2xl bg-red-500/10 border border-red-500/30 p-3.5 text-xs font-semibold text-red-400 flex items-center gap-2">
              <AlertCircle className="h-4 w-4 shrink-0" />
              <span>{errorMsg}</span>
            </div>
          )}

          {/* Quick 1-Click Demo autofill chips */}
          <div className="mb-6 p-3 rounded-2xl bg-white/5 border border-white/10">
            <div className="text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-2 flex items-center justify-between">
              <span>Quick Demo Roles:</span>
              <span className="text-[10px] text-emerald-400 font-semibold">1-Click Sign-in</span>
            </div>
            <div className="grid grid-cols-3 gap-2">
              <button
                type="button"
                onClick={() => {
                  quickLogin("user");
                  window.location.href = "/user/dashboard";
                }}
                className="rounded-xl bg-white/10 hover:bg-emerald-600/30 border border-white/10 py-1.5 text-[11px] font-semibold text-emerald-300 transition-colors"
              >
                Patient
              </button>
              <button
                type="button"
                onClick={() => {
                  quickLogin("dietitian");
                  window.location.href = "/dietitian/dashboard";
                }}
                className="rounded-xl bg-white/10 hover:bg-teal-600/30 border border-white/10 py-1.5 text-[11px] font-semibold text-teal-300 transition-colors"
              >
                Dietitian
              </button>
              <button
                type="button"
                onClick={() => {
                  quickLogin("admin");
                  window.location.href = "/admin/dashboard";
                }}
                className="rounded-xl bg-white/10 hover:bg-indigo-600/30 border border-white/10 py-1.5 text-[11px] font-semibold text-indigo-300 transition-colors"
              >
                Admin
              </button>
            </div>
          </div>

          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">Email Address</label>
              <div className="relative">
                <Mail className="absolute left-3.5 top-3 h-4 w-4 text-slate-400" />
                <input
                  type="email"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  placeholder="alice@example.com"
                  className="w-full rounded-xl bg-white/5 border border-white/10 pl-10 pr-4 py-2.5 text-xs text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-hidden"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-300 mb-1">Password</label>
              <div className="relative">
                <Lock className="absolute left-3.5 top-3 h-4 w-4 text-slate-400" />
                <input
                  type="password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  placeholder="••••••••"
                  className="w-full rounded-xl bg-white/5 border border-white/10 pl-10 pr-4 py-2.5 text-xs text-white placeholder:text-slate-500 focus:border-emerald-500 focus:outline-hidden"
                />
              </div>
            </div>

            <button
              type="submit"
              disabled={submitting}
              className="w-full mt-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 py-3 text-xs font-bold text-white shadow-lg shadow-emerald-500/20 transition-all"
            >
              {submitting ? "Signing in..." : mode === "login" ? "Sign In" : "Register Account"}
            </button>
          </form>

          <div className="mt-6 text-center text-xs text-slate-400">
            {mode === "login" ? (
              <span>
                Don&apos;t have an account?{" "}
                <Link href="/register" className="text-emerald-400 font-semibold hover:underline">
                  Sign up
                </Link>
              </span>
            ) : (
              <span>
                Already registered?{" "}
                <Link href="/login" className="text-emerald-400 font-semibold hover:underline">
                  Sign in
                </Link>
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

/* =========================================================================
   10. FALLBACK WORKSPACE VIEW
========================================================================= */
function FallbackWorkspaceView({
  pathname,
  user,
}: {
  pathname: string;
  user: ReturnType<typeof useAuth>["user"];
}) {
  const segment = pathname.split("/").filter(Boolean).at(-1) ?? "workspace";
  const title = segment
    .split("-")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");

  return (
    <section className="rounded-3xl border border-slate-200 bg-white/90 p-8 shadow-sm backdrop-blur-md">
      <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
        <Sparkles className="h-6 w-6" />
      </div>
      <h1 className="mt-6 text-3xl font-bold text-slate-900">{title} Workspace</h1>
      <p className="mt-3 max-w-xl text-slate-600 text-sm leading-relaxed">
        Your active session is {user?.name || "Alice Johnson"} ({user?.role || "user"}). 
        Use the sidebar navigation to launch the AI Food Recognition scanner, today&apos;s food diary, 
        or clinical dietitian guidance.
      </p>
      <div className="mt-6 flex gap-3">
        <Link
          href="/user/recognition"
          className="rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition-colors"
        >
          Launch AI Recognition
        </Link>
        <Link
          href="/user/dashboard"
          className="rounded-xl bg-slate-100 px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-200 transition-colors"
        >
          Back to Dashboard
        </Link>
      </div>
    </section>
  );
}
