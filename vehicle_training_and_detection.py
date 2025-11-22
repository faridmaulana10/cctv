"""
====================================================
🔹 YOLOv8 Vehicle Detection (Train + Detection Script)
====================================================

Fungsi:
1️⃣ Mengunduh dataset dari Roboflow (otomatis)
2️⃣ Melatih model YOLOv8 custom (mobil, motor, bus, truk, dll)
3️⃣ Menyimpan model hasil training ke folder /runs/train/
4️⃣ Menggunakan model hasil training untuk deteksi kendaraan real-time
   dari stream YouTube (via yt_dlp + OpenCV + YOLOv8 tracking)
"""

# ==============================
# === IMPORT LIBRARY UTAMA ===
# ==============================
import os
import json
import cv2
import time
from datetime import datetime
from ultralytics import YOLO
from roboflow import Roboflow
import yt_dlp

# ==============================
# === 1. DOWNLOAD DATASET ===
# ==============================
print("\n🚀 Mengunduh dataset dari Roboflow...")

rf = Roboflow(api_key="no7qFQPXW1x7YdLXrQv3")  # Ganti dengan API key kamu
project = rf.workspace("herdian-adi-winarno-ou2sz").project("vehicle-detection-utebm")
version = project.version(1)
dataset = version.download("yolov8")

# Dataset biasanya tersimpan di folder seperti:
DATASET_PATH = os.path.join(os.getcwd(), dataset.location)
print(f"✅ Dataset siap di: {DATASET_PATH}")

# ==============================
# === 2. TRAIN YOLOV8 MODEL ===
# ==============================
print("\n⚙️ Melatih model YOLOv8 dengan dataset kendaraan...")

model = YOLO("yolov8n.pt")  # Bisa diganti yolov8s.pt jika GPU tersedia
model.train(
    data=f"{DATASET_PATH}/data.yaml",
    epochs=50,
    imgsz=640,
    name="vehicles_model_final",
    project="runs/train"
)

# Setelah training selesai, hasilnya akan ada di:
TRAINED_MODEL_PATH = "runs/train/vehicles_model_final/weights/best.pt"
print(f"✅ Model hasil training tersimpan di: {TRAINED_MODEL_PATH}")

# ==============================
# === 3. DETEKSI KENDARAAN ===
# ==============================

# Label kendaraan yang ingin dihitung
VEHICLE_LABELS = ["car", "motorcycle", "bus", "truck"]
OUTPUT_FILE = "vehicle_count.json"

# Gunakan model hasil training
model = YOLO(TRAINED_MODEL_PATH)

def get_youtube_stream_url(video_id):
    """Ambil direct URL dari YouTube agar bisa diproses oleh OpenCV."""
    ydl_opts = {'quiet': True, 'format': 'bestvideo[height>=720]+bestaudio/best'}
    url = f"https://www.youtube.com/watch?v={video_id}"

    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
            return info["url"]
    except Exception as e:
        print(f"⚠️ Gagal mengambil URL stream YouTube: {e}")
        return None


def detect_vehicles_from_stream(video_id, cctv_name):
    """Deteksi kendaraan real-time dari stream YouTube."""
    print(f"\n▶️ Memproses CCTV: {cctv_name}")
    print("  - Mencari stream YouTube...")

    direct_url = get_youtube_stream_url(video_id)
    if not direct_url:
        print(f"⚠️ Tidak bisa mendapatkan URL langsung untuk {cctv_name}.")
        return

    print("  - Dapat URL stream, mencoba buka dengan OpenCV...")
    cap = cv2.VideoCapture(direct_url)
    if not cap.isOpened():
        print(f"⚠️ Tidak dapat membuka stream untuk {cctv_name}.")
        return

    counts = {lbl: 0 for lbl in VEHICLE_LABELS}
    seen_ids = set()  # untuk menghindari hitung ganda kendaraan
    frame_count = 0

    print("  - Mendeteksi kendaraan (Tekan 'Q' untuk keluar)...")
    while True:
        ret, frame = cap.read()
        if not ret:
            print("⚠️ Stream terputus atau selesai.")
            break
        frame_count += 1

        # Gunakan mode tracking agar 1 kendaraan tidak dihitung dobel
        results = model.track(frame, persist=True, verbose=False)
        for r in results:
            if not hasattr(r, "boxes"):
                continue
            for box in r.boxes:
                cls = int(box.cls)
                conf = float(box.conf)
                label = model.names[cls]
                track_id = int(box.id) if box.id is not None else None

                if label in VEHICLE_LABELS and conf > 0.4 and track_id is not None:
                    if track_id not in seen_ids:
                        seen_ids.add(track_id)
                        counts[label] += 1

                    # Gambar bounding box
                    x1, y1, x2, y2 = map(int, box.xyxy[0])
                    cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 0), 2)
                    cv2.putText(frame, f"{label} {conf:.2f}",
                                (x1, y1 - 10),
                                cv2.FONT_HERSHEY_SIMPLEX, 0.6,
                                (0, 255, 0), 2)

        # Tampilkan hasil
        count_text = " | ".join([f"{lbl}: {counts[lbl]}" for lbl in VEHICLE_LABELS])
        cv2.putText(frame, f"{cctv_name} - {count_text}",
                    (20, 30),
                    cv2.FONT_HERSHEY_SIMPLEX,
                    0.8, (0, 0, 255), 2)
        cv2.imshow("Deteksi Kendaraan CCTV", frame)

        if cv2.waitKey(1) & 0xFF == ord('q'):
            break
        if frame_count > 500:  # batasi jumlah frame untuk testing
            break

    cap.release()
    cv2.destroyAllWindows()

    # Simpan hasil ke JSON
    today = datetime.now().strftime("%Y-%m-%d")
    if os.path.exists(OUTPUT_FILE):
        with open(OUTPUT_FILE, "r") as f:
            try:
                data = json.load(f)
            except:
                data = {}
    else:
        data = {}

    if today not in data:
        data[today] = {}

    data[today][cctv_name] = counts

    with open(OUTPUT_FILE, "w") as f:
        json.dump(data, f, indent=4)

    print(f"✅ Hasil deteksi tersimpan di {OUTPUT_FILE}")
    print(f"📊 Total hitungan: {counts}")


# ==============================
# === 4. JALANKAN DETEKSI ===
# ==============================
if __name__ == "__main__":
    CCTV_LIST = [
        {"name": "CCTV 1 - Celangapan", "video_id": "3sTPp40WU_w"},  # Ganti dengan ID CCTV YouTube kamu
    ]

    for cctv in CCTV_LIST:
        detect_vehicles_from_stream(cctv["video_id"], cctv["name"])