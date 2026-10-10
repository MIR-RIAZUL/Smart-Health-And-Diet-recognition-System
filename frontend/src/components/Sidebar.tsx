"use client";

import React from "react";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import {
  LayoutDashboard,
  Camera,
  UtensilsCrossed,
  Activity,
  HeartPulse,
  BarChart3,
  Users,
  ClipboardList,
  FileText,
  BookOpen,
  UserCheck,
  Database,
  Sparkles
} from "lucide-react";

interface NavigationLink {
  href: string;
  label: string;
  icon: typeof LayoutDashboard;
  badge?: string;
}

export default function Sidebar() {
  const pathname = usePathname();
  const { user } = useAuth();
  const role = user?.role || "user";

  const userLinks: NavigationLink[] = [
    { href: "/user/dashboard", label: "Dashboard Overview", icon: LayoutDashboard },
    { href: "/user/recognition", label: "AI Food Recognition", icon: Camera, badge: "AI" },
    { href: "/user/log-meals", label: "Log Meals", icon: UtensilsCrossed },
    { href: "/user/health-info", label: "Health & BMI Profile", icon: HeartPulse },
    { href: "/user/guidance", label: "Dietitian Guidance", icon: Activity },
    { href: "/user/reports", label: "Calorie & Macro Reports", icon: BarChart3 },
  ];

  const dietitianLinks: NavigationLink[] = [
    { href: "/dietitian/dashboard", label: "Dietitian Dashboard", icon: LayoutDashboard },
    { href: "/dietitian/patients", label: "Patient Directory", icon: Users },
    { href: "/dietitian/meal-plans", label: "Meal Plan Builder", icon: ClipboardList },
    { href: "/dietitian/recommendations", label: "Nutritional Advice", icon: FileText },
    { href: "/dietitian/guides", label: "Nutritional Guides", icon: BookOpen },
  ];

  const adminLinks: NavigationLink[] = [
    { href: "/admin/dashboard", label: "Admin Hub", icon: LayoutDashboard },
    { href: "/admin/users", label: "Manage Users", icon: Users },
    { href: "/admin/dietitians", label: "Approve Dietitians", icon: UserCheck, badge: "1 Pending" },
    { href: "/admin/foods", label: "Food Database", icon: Database },
    { href: "/admin/reports", label: "System Reports", icon: BarChart3 },
  ];

  const currentLinks =
    role === "admin" ? adminLinks : role === "dietitian" ? dietitianLinks : userLinks;

  const accentColor =
    role === "admin"
      ? "text-indigo-600 bg-indigo-50 border-indigo-600"
      : role === "dietitian"
      ? "text-teal-600 bg-teal-50 border-teal-600"
      : "text-emerald-600 bg-emerald-50 border-emerald-600";

  return (
    <aside className="w-64 shrink-0 hidden lg:block bg-white border-r border-slate-200/80 min-h-[calc(100vh-4rem)] p-4 flex flex-col justify-between">
      <div>
        <div className="mb-4 px-3 py-2 rounded-xl bg-slate-50 border border-slate-200/60">
          <div className="text-[11px] font-semibold tracking-wider uppercase text-slate-400">
            Current Workspace
          </div>
          <div className="text-sm font-bold text-slate-800 capitalize flex items-center justify-between">
            <span>{role} Portal</span>
            {role === "user" && <span className="h-2 w-2 rounded-full bg-emerald-500"></span>}
            {role === "dietitian" && <span className="h-2 w-2 rounded-full bg-teal-500"></span>}
            {role === "admin" && <span className="h-2 w-2 rounded-full bg-indigo-500"></span>}
          </div>
        </div>

        <nav className="space-y-1">
          {currentLinks.map((item) => {
            const Icon = item.icon;
            const isActive = pathname === item.href;
            return (
              <Link
                key={item.href}
                href={item.href}
                className={`flex items-center justify-between px-3.5 py-2.5 rounded-xl text-xs font-semibold transition-all group ${
                  isActive
                    ? `${accentColor} shadow-2xs font-bold`
                    : "text-slate-600 hover:text-slate-900 hover:bg-slate-100/70"
                }`}
              >
                <div className="flex items-center gap-3">
                  <Icon
                    className={`h-4 w-4 transition-colors ${
                      isActive
                        ? role === "admin"
                          ? "text-indigo-600"
                          : role === "dietitian"
                          ? "text-teal-600"
                          : "text-emerald-600"
                        : "text-slate-400 group-hover:text-slate-600"
                    }`}
                  />
                  <span>{item.label}</span>
                </div>
                {item.badge && (
                  <span className="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">
                    {item.badge}
                  </span>
                )}
              </Link>
            );
          })}
        </nav>
      </div>

      <div className="pt-4 border-t border-slate-100">
        <div className="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-800 p-3.5 text-white shadow-lg shadow-slate-900/10">
          <div className="flex items-center gap-2 mb-1.5 text-xs font-semibold text-emerald-400">
            <Sparkles className="h-4 w-4" /> AI Diet Assistant
          </div>
          <p className="text-[11px] text-slate-300 leading-relaxed mb-3">
            Snap meal photos to automatically count calories & monitor macro goals.
          </p>
          <Link
            href="/user/recognition"
            className="block text-center rounded-xl bg-white/10 hover:bg-white/20 py-1.5 text-xs font-semibold text-white transition-colors"
          >
            Launch Scanner
          </Link>
        </div>
      </div>
    </aside>
  );
}
