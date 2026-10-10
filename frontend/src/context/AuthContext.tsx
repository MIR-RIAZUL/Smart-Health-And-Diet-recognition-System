"use client";

import React, { createContext, useContext, useState, useEffect } from "react";
import { UserSession } from "@/lib/types";

interface AuthContextType {
  user: UserSession | null;
  loading: boolean;
  login: (email: string, role?: string) => Promise<boolean>;
  quickLogin: (role: "admin" | "dietitian" | "user") => void;
  logout: () => void;
}

const defaultUser: UserSession = {
  id: 4,
  name: "Alice Johnson",
  email: "alice@example.com",
  role: "user",
  status: "active",
};

const AuthContext = createContext<AuthContextType>({
  user: null,
  loading: true,
  login: async () => false,
  quickLogin: () => {},
  logout: () => {},
});

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<UserSession | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const hydrateSession = () => {
      let nextUser = defaultUser;

      try {
        const stored = localStorage.getItem("healthtrack_user");
        if (stored) {
          const parsed = JSON.parse(stored) as Partial<UserSession>;
          if (
            typeof parsed.id === "number" &&
            typeof parsed.name === "string" &&
            typeof parsed.email === "string" &&
            ["admin", "dietitian", "user"].includes(parsed.role ?? "") &&
            ["active", "inactive", "suspended", "pending"].includes(parsed.status ?? "")
          ) {
            nextUser = parsed as UserSession;
          }
        } else {
          localStorage.setItem("healthtrack_user", JSON.stringify(defaultUser));
        }
      } catch {
        localStorage.removeItem("healthtrack_user");
      }

      setUser(nextUser);
      setLoading(false);
    };

    const hydrationTimer = window.setTimeout(hydrateSession, 0);
    return () => window.clearTimeout(hydrationTimer);
  }, []);

  const login = async (email: string, role?: string): Promise<boolean> => {
    setLoading(true);
    let matchedUser: UserSession | null = null;
    const lower = email.toLowerCase().trim();

    if (!lower) {
      setLoading(false);
      return false;
    }

    if (role === "admin" || lower.includes("admin")) {
      matchedUser = {
        id: 1,
        name: "System Administrator",
        email: "admin@healthtrack.com",
        role: "admin",
        status: "active",
      };
    } else if (role === "dietitian" || lower.includes("sarah") || lower.includes("dietitian")) {
      matchedUser = {
        id: 2,
        name: "Dr. Sarah Miller",
        email: "sarah@healthtrack.com",
        role: "dietitian",
        status: "active",
      };
    } else if (lower.includes("james")) {
      matchedUser = {
        id: 3,
        name: "Dr. James Wilson",
        email: "james@healthtrack.com",
        role: "dietitian",
        status: "pending",
      };
    } else {
      matchedUser = {
        id: 4,
        name: "Alice Johnson",
        email: email || "alice@example.com",
        role: "user",
        status: "active",
      };
    }

    setUser(matchedUser);
    localStorage.setItem("healthtrack_user", JSON.stringify(matchedUser));
    setLoading(false);
    return true;
  };

  const quickLogin = (role: "admin" | "dietitian" | "user") => {
    let newUser: UserSession;
    if (role === "admin") {
      newUser = { id: 1, name: "System Administrator", email: "admin@healthtrack.com", role: "admin", status: "active" };
    } else if (role === "dietitian") {
      newUser = { id: 2, name: "Dr. Sarah Miller", email: "sarah@healthtrack.com", role: "dietitian", status: "active" };
    } else {
      newUser = { id: 4, name: "Alice Johnson", email: "alice@example.com", role: "user", status: "active" };
    }
    setUser(newUser);
    localStorage.setItem("healthtrack_user", JSON.stringify(newUser));
  };

  const logout = () => {
    setUser(null);
    localStorage.removeItem("healthtrack_user");
  };

  return (
    <AuthContext.Provider value={{ user, loading, login, quickLogin, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export const useAuth = () => useContext(AuthContext);
