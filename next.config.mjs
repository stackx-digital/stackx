/** @type {import('next').NextConfig} */
const nextConfig = {
  reactStrictMode: true,
  experimental: {
    // Server Actions are enabled by default in Next 14; kept explicit for clarity.
    serverActions: {
      bodySizeLimit: "10mb", // CSV imports (M2) can be sizeable.
    },
  },
};

export default nextConfig;
