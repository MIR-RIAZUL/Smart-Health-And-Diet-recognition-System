"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { Suspense } from "react";
import { ArrowLeft, Construction } from "lucide-react";
import Sidebar from "@/components/Sidebar";
import { useAuth } from "@/context/AuthContext";

function formatTitle(pathname: string): string {
  const segment = pathname.split("/").filter(Boolean).at(-1) ?? "dashboard";
  return segment
    .split("-")
    .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
    .join(" ");
}

function PortalContent() {
  const pathname = usePathname();
  const { user, loading } = useAuth();
  const title = formatTitle(pathname);

  if (loading) {
    return (
      <main className="flex-1 grid place-items-center p-8 text-sm text-slate-500">
        Loading your workspace...
      </main>
    );
  }

  return (
    <>
      <Sidebar />
      <main className="flex-1 bg-slate-50 p-6 sm:p-10">
        <div className="mx-auto max-w-5xl">
          <Link
            href="/"
            className="mb-8 inline-flex items-center gap-2 text-sm font-semibold text-slate-500 hover:text-emerald-700"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to home
          </Link>
          <section className="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
            <div className="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700">
              <Construction className="h-6 w-6" />
            </div>
            <h1 className="mt-6 text-3xl font-bold text-slate-900">{title}</h1>
            <p className="mt-3 max-w-xl text-slate-600">
              This {user?.role ?? "user"} workspace is ready for the next feature update.
              Your session is active as {user?.name ?? "a guest"}.
            </p>
          </section>
        </div>
      </main>
    </>
  );
}

export default function PortalPage() {
  return (
    <Suspense
      fallback={
        <main className="flex-1 grid place-items-center p-8 text-sm text-slate-500">
          Loading your workspace...
        </main>
      }
    >
      <PortalContent />
    </Suspense>
  );
}
