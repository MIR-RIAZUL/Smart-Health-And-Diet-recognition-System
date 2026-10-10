"use client";

import React from "react";
import Link from "next/link";
import { useAuth } from "@/context/AuthContext";
import { 
  Heart, 
  Bell, 
  LogOut, 
  ShieldCheck, 
  Sparkles
} from "lucide-react";

export default function Navbar() {
  const { user, quickLogin, logout } = useAuth();

  return (
    <header className="sticky top-0 z-40 w-full border-b border-slate-200/80 bg-white/80 backdrop-blur-md">
      <div className="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto">
        {/* Brand / Logo */}
        <div className="flex items-center gap-3">
          <Link href="/" className="flex items-center gap-2.5 group">
            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 text-white shadow-md shadow-emerald-500/20 group-hover:scale-105 transition-transform">
              <Heart className="h-5 w-5 fill-white" />
            </div>
            <div>
              <div className="flex items-center gap-1.5">
                <span className="font-bold text-lg tracking-tight text-slate-900">HealthTrack</span>
                <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">
                  AI React
                </span>
              </div>
              <p className="text-[11px] text-slate-500 font-medium">Smart Health & Diet System</p>
            </div>
          </Link>
        </div>

        {/* Quick Role Switcher (For Pair Programming / Demo Testing) */}
        <div className="hidden md:flex items-center gap-1.5 rounded-xl bg-slate-100/80 p-1 border border-slate-200/60 text-xs font-medium">
          <span className="px-2 py-1 text-slate-400 flex items-center gap-1">
            <Sparkles className="h-3.5 w-3.5 text-amber-500" /> Switch Role:
          </span>
          <button
            onClick={() => quickLogin("user")}
            className={`rounded-lg px-2.5 py-1 transition-all ${
              user?.role === "user"
                ? "bg-white text-emerald-700 font-semibold shadow-xs"
                : "text-slate-600 hover:text-slate-900"
            }`}
          >
            User (Alice)
          </button>
          <button
            onClick={() => quickLogin("dietitian")}
            className={`rounded-lg px-2.5 py-1 transition-all ${
              user?.role === "dietitian"
                ? "bg-white text-teal-700 font-semibold shadow-xs"
                : "text-slate-600 hover:text-slate-900"
            }`}
          >
            Dietitian (Dr. Sarah)
          </button>
          <button
            onClick={() => quickLogin("admin")}
            className={`rounded-lg px-2.5 py-1 transition-all ${
              user?.role === "admin"
                ? "bg-white text-indigo-700 font-semibold shadow-xs"
                : "text-slate-600 hover:text-slate-900"
            }`}
          >
            Admin
          </button>
        </div>

        {/* Right Action / Profile */}
        <div className="flex items-center gap-3">
          {user ? (
            <div className="flex items-center gap-3">
              <button 
                title="Notifications" 
                className="relative rounded-lg p-2 text-slate-500 hover:bg-slate-100 transition-colors"
              >
                <Bell className="h-5 w-5" />
                <span className="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-emerald-500 ring-2 ring-white"></span>
              </button>

              <div className="flex items-center gap-2.5 pl-2 border-l border-slate-200">
                <div className={`h-8 w-8 rounded-full flex items-center justify-center text-xs font-bold text-white shadow-xs ${
                  user.role === "admin" 
                    ? "bg-indigo-600" 
                    : user.role === "dietitian" 
                    ? "bg-teal-600" 
                    : "bg-emerald-600"
                }`}>
                  {user.name.split(" ").map(n => n[0]).join("").slice(0, 2)}
                </div>
                <div className="hidden sm:block text-left">
                  <div className="text-xs font-semibold text-slate-900 leading-tight">{user.name}</div>
                  <div className="text-[10px] font-medium capitalize text-slate-500 flex items-center gap-1">
                    {user.role === "admin" && <ShieldCheck className="h-3 w-3 text-indigo-500" />}
                    {user.role} portal
                  </div>
                </div>
                <button
                  onClick={logout}
                  title="Sign out"
                  className="rounded-lg p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors ml-1"
                >
                  <LogOut className="h-4 w-4" />
                </button>
              </div>
            </div>
          ) : (
            <div className="flex items-center gap-2">
              <Link
                href="/login"
                className="rounded-xl px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 transition-colors"
              >
                Sign In
              </Link>
              <Link
                href="/register"
                className="rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-xs hover:bg-emerald-700 transition-colors"
              >
                Sign Up
              </Link>
            </div>
          )}
        </div>
      </div>
    </header>
  );
}
