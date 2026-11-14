const DEFAULT_MAX_WIDTH = 1600;
const DEFAULT_QUALITY = 0.75;

function loadImage(file: File): Promise<HTMLImageElement> {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const image = new Image();
    image.onload = () => {
      URL.revokeObjectURL(url);
      resolve(image);
    };
    image.onerror = () => {
      URL.revokeObjectURL(url);
      reject(new Error("Gagal membaca gambar."));
    };
    image.src = url;
  });
}

/**
 * Kompres foto di HP sebelum upload supaya hemat kuota.
 * Gambar dikecilkan ke maxWidth lalu di-encode JPEG.
 * Bila gagal atau hasilnya lebih besar, file asli dikembalikan.
 */
export async function compressImage(
  file: File,
  maxWidth: number = DEFAULT_MAX_WIDTH,
  quality: number = DEFAULT_QUALITY,
): Promise<File> {
  if (!file.type.startsWith("image/")) {
    return file;
  }

  try {
    const image = await loadImage(file);
    const scale = Math.min(1, maxWidth / Math.max(image.naturalWidth, 1));
    const width = Math.max(1, Math.round(image.naturalWidth * scale));
    const height = Math.max(1, Math.round(image.naturalHeight * scale));

    const canvas = document.createElement("canvas");
    canvas.width = width;
    canvas.height = height;
    const context = canvas.getContext("2d");

    if (!context) {
      return file;
    }

    context.fillStyle = "#ffffff";
    context.fillRect(0, 0, width, height);
    context.drawImage(image, 0, 0, width, height);

    const blob = await new Promise<Blob | null>((resolve) => {
      canvas.toBlob(resolve, "image/jpeg", quality);
    });

    if (!blob || blob.size >= file.size) {
      return file;
    }

    const name = file.name.replace(/\.[^.]+$/, "") || "foto";

    return new File([blob], `${name}.jpg`, {
      type: "image/jpeg",
      lastModified: Date.now(),
    });
  } catch {
    return file;
  }
}
