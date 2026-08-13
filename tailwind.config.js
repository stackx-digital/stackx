import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/**
 * STACKx cockpit theme (§6). Deep ink-slate base, lifted panels, hairline
 * borders. Trader-desk feel — not generic dark-SaaS. Semantic score colors
 * (scale/winner/cut/neutral) live here so the score meter and action badges
 * share one source of truth.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
  darkMode: ["class"],
  content: [
    "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
    "./storage/framework/views/*.php",
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.tsx",
    "./resources/js/**/*.ts",
  ],
  theme: {
    container: {
      center: true,
      padding: "1.5rem",
      screens: { "2xl": "1440px" },
    },
    extend: {
      colors: {
        // Surfaces
        ink: "#0E141B", // base background
        panel: "#171F2A", // lifted panels/cards
        hairline: "#26313F", // borders
        // Semantic accents
        amber: "#FFB020", // primary / scale-signal
        winner: "#34D399", // green / winner
        cut: "#F76B6B", // red / cut
        neutral: "#5B9BD5", // muted blue / neutral
        muted: {
          DEFAULT: "#1B2430",
          foreground: "#8A97A6",
        },
      },
      fontFamily: {
        // Space Grotesk (display/UI), Inter (body), JetBrains Mono (numbers).
        display: ["Space Grotesk", ...defaultTheme.fontFamily.sans],
        sans: ["Inter", ...defaultTheme.fontFamily.sans],
        mono: ["JetBrains Mono", ...defaultTheme.fontFamily.mono],
      },
      borderRadius: {
        lg: "0.5rem",
        md: "calc(0.5rem - 2px)",
        sm: "calc(0.5rem - 4px)",
      },
    },
  },
  plugins: [forms],
};
