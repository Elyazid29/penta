import type { Metadata, Viewport } from "next";
import "./globals.css";
import RegisterSW from "./register-sw";

export const metadata: Metadata = {
  title: "Penta Tri Saka | Operations",
  description: "Dashboard operasional Penta Tri Saka",
  manifest: "/manifest.webmanifest",
};

export const viewport: Viewport = {
  themeColor: "#002480",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="id" className="h-full antialiased">
      <body className="min-h-full flex flex-col"><RegisterSW />{children}</body>
    </html>
  );
}
