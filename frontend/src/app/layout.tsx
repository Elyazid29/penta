import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Penta Tri Saka | Operations",
  description: "Dashboard operasional Penta Tri Saka",
};

export default function RootLayout({ children }: LayoutProps<"/">) {
  return (
    <html lang="id" className="h-full antialiased">
      <body className="min-h-full flex flex-col">{children}</body>
    </html>
  );
}
