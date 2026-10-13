"use client";

import React from "react";
import Link from "next/link";
import { useAuth } from "@/context/AuthContext";
import {
  Heart,
  Camera,
  UserCheck,
  ShieldCheck,
  Sparkles,
  ArrowRight,
  Flame,
  Droplets,
  Scale,
  Apple,
  CheckCircle2,
  TrendingUp,
  Activity,
  Stethoscope
} from "lucide-react";

export default function HomePage() {
  const { quickLogin } = useAuth();

  return (
    <div className="flex-1 flex flex-col justify-between">
      {/* Hero Section */}
      <section className="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28">
        {/* Ambient Glow Gradients */}
        <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[400px] bg-gradient-to-tr from-emerald-300/30 via-teal-200/25 to-cyan-300/30 rounded-full blur-3xl pointer-events-none -z-10 animate-pulse-glow" />

        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="inline-flex items-center gap-2 rounded-full border border-emerald-200/80 bg-white/70 px-4 py-1.5 text-xs font-semibold text-emerald-800 mb-6 shadow-sm backdrop-blur-md">
            <Sparkles className="h-3.5 w-3.5 text-emerald-600 animate-spin" />
            <span>AI Diet Recognition &amp; Clinical Health Suite</span>
          </div>

          <h1 className="text-4xl sm:text-5xl lg:text-6xl font-black tracking-tight text-slate-900 max-w-4xl mx-auto leading-[1.12]">
            Smart Health &amp; <br className="hidden sm:inline" />
            <span className="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 bg-clip-text text-transparent">
              AI Diet Recognition System
            </span>
          </h1>

          <p className="mt-6 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
            Harness real-time computer vision food recognition, automated macronutrient analytics,
            and clinical guidance from certified registered dietitians in a frosted glassmorphic UI.
          </p>

          {/* Quick Action Button Group */}
          <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
            <Link
              href="/user/dashboard"
              onClick={() => quickLogin("user")}
              className="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-7 py-4 text-sm font-bold text-white shadow-xl shadow-emerald-600/25 hover:bg-emerald-700 hover:shadow-emerald-600/35 transition-all transform hover:-translate-y-0.5"
            >
              <Apple className="h-4 w-4" />
              Launch User Dashboard
              <ArrowRight className="h-4 w-4" />
            </Link>

            <Link
              href="/user/recognition"
              onClick={() => quickLogin("user")}
              className="inline-flex items-center gap-2 rounded-2xl bg-white/90 border border-slate-300/80 px-7 py-4 text-sm font-bold text-slate-800 shadow-sm hover:bg-white hover:border-slate-400 backdrop-blur-md transition-all transform hover:-translate-y-0.5"
            >
              <Camera className="h-4 w-4 text-emerald-600" />
              Try AI Food Scanner
            </Link>
          </div>

          {/* Metrics / Stats Ribbon */}
          <div className="mt-12 max-w-3xl mx-auto grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div className="glass-card rounded-2xl p-4 text-center">
              <div className="text-2xl font-black text-slate-900">10K+</div>
              <div className="text-xs text-slate-500 font-semibold mt-0.5">Active Users</div>
            </div>
            <div className="glass-card rounded-2xl p-4 text-center">
              <div className="text-2xl font-black text-emerald-600">98%</div>
              <div className="text-xs text-slate-500 font-semibold mt-0.5">Goal Adherence</div>
            </div>
            <div className="glass-card rounded-2xl p-4 text-center">
              <div className="text-2xl font-black text-teal-600">50+</div>
              <div className="text-xs text-slate-500 font-semibold mt-0.5">Clinical Dietitians</div>
            </div>
            <div className="glass-card rounded-2xl p-4 text-center">
              <div className="text-2xl font-black text-indigo-600">1,240+</div>
              <div className="text-xs text-slate-500 font-semibold mt-0.5">Verified Dishes</div>
            </div>
          </div>

          {/* Quick Portal Switcher Grid */}
          <div className="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6 text-left max-w-5xl mx-auto">
            {/* User Portal Card */}
            <div className="group relative rounded-3xl border border-emerald-200/70 bg-white/80 p-7 shadow-sm hover:shadow-xl transition-all hover:border-emerald-400 backdrop-blur-md">
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 mb-5 group-hover:scale-110 transition-transform shadow-xs">
                <Heart className="h-6 w-6 fill-emerald-600" />
              </div>
              <h3 className="text-lg font-bold text-slate-900">User &amp; Patient Portal</h3>
              <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                Log meals, track daily calories &amp; water, compute BMI, and follow customized dietitian guidance.
              </p>
              <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span className="text-xs font-semibold text-emerald-700">Alice Johnson</span>
                <Link
                  href="/user/dashboard"
                  onClick={() => quickLogin("user")}
                  className="rounded-xl bg-emerald-50 px-3.5 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition-colors inline-flex items-center gap-1"
                >
                  Enter Portal <ArrowRight className="h-3 w-3" />
                </Link>
              </div>
            </div>

            {/* Dietitian Portal Card */}
            <div className="group relative rounded-3xl border border-teal-200/70 bg-white/80 p-7 shadow-sm hover:shadow-xl transition-all hover:border-teal-400 backdrop-blur-md">
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-100 text-teal-700 mb-5 group-hover:scale-110 transition-transform shadow-xs">
                <UserCheck className="h-6 w-6" />
              </div>
              <h3 className="text-lg font-bold text-slate-900">Dietitian Portal</h3>
              <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                Manage assigned patients, author personalized meal plans, and prescribe dietary therapy.
              </p>
              <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span className="text-xs font-semibold text-teal-700">Dr. Sarah Miller, RD</span>
                <Link
                  href="/dietitian/dashboard"
                  onClick={() => quickLogin("dietitian")}
                  className="rounded-xl bg-teal-50 px-3.5 py-1.5 text-xs font-bold text-teal-700 hover:bg-teal-100 transition-colors inline-flex items-center gap-1"
                >
                  Enter Portal <ArrowRight className="h-3 w-3" />
                </Link>
              </div>
            </div>

            {/* Admin Console Card */}
            <div className="group relative rounded-3xl border border-indigo-200/70 bg-white/80 p-7 shadow-sm hover:shadow-xl transition-all hover:border-indigo-400 backdrop-blur-md">
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-700 mb-5 group-hover:scale-110 transition-transform shadow-xs">
                <ShieldCheck className="h-6 w-6" />
              </div>
              <h3 className="text-lg font-bold text-slate-900">Admin Console</h3>
              <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                Approve dietitian licenses, manage user privileges, update food databases, and audit reports.
              </p>
              <div className="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span className="text-xs font-semibold text-indigo-700">Administrator</span>
                <Link
                  href="/admin/dashboard"
                  onClick={() => quickLogin("admin")}
                  className="rounded-xl bg-indigo-50 px-3.5 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition-colors inline-flex items-center gap-1"
                >
                  Enter Console <ArrowRight className="h-3 w-3" />
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Feature Showcase Grid */}
      <section className="bg-white/70 backdrop-blur-md border-y border-slate-200/80 py-16">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-2xl mx-auto mb-12">
            <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900">
              Modern Clinical Nutrition Architecture
            </h2>
            <p className="mt-2 text-sm text-slate-600">
              High-performance, reactive state-driven interfaces with frosted glassmorphism aesthetics.
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="glass-card rounded-2xl p-5 hover:-translate-y-1 transition-transform">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600 mb-3">
                <Flame className="h-5 w-5" />
              </div>
              <h4 className="font-bold text-slate-900 text-sm">Calorie Target Engine</h4>
              <p className="mt-1 text-xs text-slate-500 leading-relaxed">
                Live circular progress counters showing consumed vs remaining allowance with macro breakdown.
              </p>
            </div>

            <div className="glass-card rounded-2xl p-5 hover:-translate-y-1 transition-transform">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 mb-3">
                <Droplets className="h-5 w-5" />
              </div>
              <h4 className="font-bold text-slate-900 text-sm">Hydration Tracker</h4>
              <p className="mt-1 text-xs text-slate-500 leading-relaxed">
                One-tap water logging with visual hydration fill gauge towards your 2,500ml daily target.
              </p>
            </div>

            <div className="glass-card rounded-2xl p-5 hover:-translate-y-1 transition-transform">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 mb-3">
                <Camera className="h-5 w-5" />
              </div>
              <h4 className="font-bold text-slate-900 text-sm">AI Food Recognition</h4>
              <p className="mt-1 text-xs text-slate-500 leading-relaxed">
                Computer-vision analysis estimating meal weight, calories, macro splits and health scores.
              </p>
            </div>

            <div className="glass-card rounded-2xl p-5 hover:-translate-y-1 transition-transform">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600 mb-3">
                <Scale className="h-5 w-5" />
              </div>
              <h4 className="font-bold text-slate-900 text-sm">Biometric &amp; BMI Sync</h4>
              <p className="mt-1 text-xs text-slate-500 leading-relaxed">
                Automatic BMI classification, height/weight logging, and clinical goal synchronization.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-slate-900 text-slate-400 py-8 text-xs text-center border-t border-slate-800">
        <p>© 2026 HealthTrack - Smart Health &amp; Diet Recognition System. Built with React &amp; Next.js.</p>
      </footer>
    </div>
  );
}
