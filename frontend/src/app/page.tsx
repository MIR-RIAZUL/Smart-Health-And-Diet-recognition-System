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
  Apple
} from "lucide-react";

export default function HomePage() {
  const { quickLogin } = useAuth();

  return (
    <div className="flex-1 flex flex-col justify-between">
      {/* Hero Section */}
      <section className="relative overflow-hidden pt-12 pb-20 lg:pt-20 lg:pb-28">
        {/* Glow Gradients */}
        <div className="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] bg-gradient-to-tr from-emerald-200/40 via-teal-100/30 to-sky-200/40 rounded-full blur-3xl pointer-events-none -z-10" />

        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
          <div className="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50/80 px-3.5 py-1.5 text-xs font-semibold text-emerald-800 mb-6 shadow-2xs backdrop-blur-xs">
            <Sparkles className="h-3.5 w-3.5 text-emerald-600" />
            <span>Next.js & React Full-Stack Transformation</span>
          </div>

          <h1 className="text-4xl sm:text-5xl lg:text-6xl font-extrabold tracking-tight text-slate-900 max-w-4xl mx-auto leading-[1.15]">
            Smart Health & <br className="hidden sm:inline" />
            <span className="bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 bg-clip-text text-transparent">
              AI Diet Recognition System
            </span>
          </h1>

          <p className="mt-6 text-base sm:text-lg text-slate-600 max-w-2xl mx-auto leading-relaxed">
            Harness real-time food recognition, automated macronutrient analytics, 
            and clinical guidance from certified dietitians. Rebuilt with high-performance React components.
          </p>

          {/* Quick Action Button Group */}
          <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
            <Link
              href="/user/dashboard"
              onClick={() => quickLogin("user")}
              className="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-6 py-3.5 text-sm font-bold text-white shadow-lg shadow-emerald-600/25 hover:bg-emerald-700 hover:shadow-emerald-600/35 transition-all transform hover:-translate-y-0.5"
            >
              <Apple className="h-4 w-4" />
              Launch User Dashboard
              <ArrowRight className="h-4 w-4" />
            </Link>

            <Link
              href="/user/recognition"
              onClick={() => quickLogin("user")}
              className="inline-flex items-center gap-2 rounded-2xl bg-white border border-slate-300 px-6 py-3.5 text-sm font-bold text-slate-800 shadow-xs hover:bg-slate-50 hover:border-slate-400 transition-all"
            >
              <Camera className="h-4 w-4 text-emerald-600" />
              Try AI Food Scanner
            </Link>
          </div>

          {/* Quick Portal Switcher Grid */}
          <div className="mt-16 grid grid-cols-1 md:grid-cols-3 gap-6 text-left max-w-4xl mx-auto">
            {/* User Portal Card */}
            <div className="group relative rounded-3xl border border-emerald-100 bg-white/90 p-6 shadow-sm hover:shadow-xl transition-all hover:border-emerald-300">
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 mb-4 group-hover:scale-110 transition-transform">
                <Heart className="h-6 w-6 fill-emerald-600" />
              </div>
              <h3 className="text-lg font-bold text-slate-900">User Portal</h3>
              <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                Log meals, track daily calories & water, compute BMI, and follow customized dietitian guidance.
              </p>
              <div className="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span className="text-xs font-semibold text-emerald-700">Alice Johnson</span>
                <Link
                  href="/user/dashboard"
                  onClick={() => quickLogin("user")}
                  className="rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition-colors inline-flex items-center gap-1"
                >
                  Enter <ArrowRight className="h-3 w-3" />
                </Link>
              </div>
            </div>

            {/* Dietitian Portal Card */}
            <div className="group relative rounded-3xl border border-teal-100 bg-white/90 p-6 shadow-sm hover:shadow-xl transition-all hover:border-teal-300">
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-teal-100 text-teal-700 mb-4 group-hover:scale-110 transition-transform">
                <UserCheck className="h-6 w-6" />
              </div>
              <h3 className="text-lg font-bold text-slate-900">Dietitian Portal</h3>
              <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                Manage assigned patients, author personalized meal plans, and provide dietary recommendations.
              </p>
              <div className="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span className="text-xs font-semibold text-teal-700">Dr. Sarah Miller</span>
                <Link
                  href="/dietitian/dashboard"
                  onClick={() => quickLogin("dietitian")}
                  className="rounded-xl bg-teal-50 px-3 py-1.5 text-xs font-bold text-teal-700 hover:bg-teal-100 transition-colors inline-flex items-center gap-1"
                >
                  Enter <ArrowRight className="h-3 w-3" />
                </Link>
              </div>
            </div>

            {/* Admin Console Card */}
            <div className="group relative rounded-3xl border border-indigo-100 bg-white/90 p-6 shadow-sm hover:shadow-xl transition-all hover:border-indigo-300">
              <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-100 text-indigo-700 mb-4 group-hover:scale-110 transition-transform">
                <ShieldCheck className="h-6 w-6" />
              </div>
              <h3 className="text-lg font-bold text-slate-900">Admin Console</h3>
              <p className="mt-2 text-xs text-slate-500 leading-relaxed">
                Approve dietitian licenses, manage user privileges, update food databases, and generate reports.
              </p>
              <div className="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                <span className="text-xs font-semibold text-indigo-700">Administrator</span>
                <Link
                  href="/admin/dashboard"
                  onClick={() => quickLogin("admin")}
                  className="rounded-xl bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700 hover:bg-indigo-100 transition-colors inline-flex items-center gap-1"
                >
                  Enter <ArrowRight className="h-3 w-3" />
                </Link>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* Feature Showcase Grid */}
      <section className="bg-white border-y border-slate-200/80 py-16">
        <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="text-center max-w-2xl mx-auto mb-12">
            <h2 className="text-2xl sm:text-3xl font-extrabold text-slate-900">
              Core Capabilities in React
            </h2>
            <p className="mt-2 text-sm text-slate-600">
              Migrated from legacy PHP templates to high-velocity, reactive state-driven interfaces.
            </p>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
            <div className="rounded-2xl border border-slate-200/80 p-5 bg-slate-50/50 hover:bg-slate-50 transition-colors">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-600 mb-3">
                <Flame className="h-5 w-5" />
              </div>
              <h4 className="font-bold text-slate-900 text-sm">Calorie Target Engine</h4>
              <p className="mt-1 text-xs text-slate-500 leading-relaxed">
                Live circular progress counters showing consumed vs remaining allowance with macro breakdown.
              </p>
            </div>

            <div className="rounded-2xl border border-slate-200/80 p-5 bg-slate-50/50 hover:bg-slate-50 transition-colors">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-100 text-sky-600 mb-3">
                <Droplets className="h-5 w-5" />
              </div>
              <h4 className="font-bold text-slate-900 text-sm">Hydration Tracker</h4>
              <p className="mt-1 text-xs text-slate-500 leading-relaxed">
                One-tap water logging with visual hydration fill gauge towards your 2,500ml daily target.
              </p>
            </div>

            <div className="rounded-2xl border border-slate-200/80 p-5 bg-slate-50/50 hover:bg-slate-50 transition-colors">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-600 mb-3">
                <Camera className="h-5 w-5" />
              </div>
              <h4 className="font-bold text-slate-900 text-sm">AI Food Recognition</h4>
              <p className="mt-1 text-xs text-slate-500 leading-relaxed">
                Computer-vision analysis estimating meal weight, calories, macro splits and health scores.
              </p>
            </div>

            <div className="rounded-2xl border border-slate-200/80 p-5 bg-slate-50/50 hover:bg-slate-50 transition-colors">
              <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-600 mb-3">
                <Scale className="h-5 w-5" />
              </div>
              <h4 className="font-bold text-slate-900 text-sm">Biometric & BMI Sync</h4>
              <p className="mt-1 text-xs text-slate-500 leading-relaxed">
                Automatic BMI classification, height/weight logging, and clinical goal synchronization.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="bg-slate-900 text-slate-400 py-8 text-xs text-center border-t border-slate-800">
        <p>© 2026 HealthTrack - Smart Health & Diet Recognition System. Built with React & Next.js.</p>
      </footer>
    </div>
  );
}
