import cv2
import yt_dlp
from ultralytics import YOLO
from datetime import datetime
import json
import os

# === Inisialisasi model YOLO ===
model = YOLO("yolov8n.pt")

# === Label kendaraan yang ingin dihitung ===
VEHICLE_LABELS = ["car", "motorbike", "bus", "truck"]

# === File hasil ===
OUTPUT_FILE = "vehicle_accuracy.json"


def get_youtube_stream_url(video_id):
    """Ambil direct video URL dari YouTube agar bisa dibuka oleh OpenCV."""
    ydl_opts = {'quiet': True, 'format': 'bestvideo[height>=480]+bestaudio/best'}
    url = f"https://www.youtube.com/watch?v={video_id}"
    try:
        with yt_dlp.YoutubeDL(ydl_opts) as ydl:
            info = ydl.extract_info(url, download=False)
            return info["url"]
    except Exception as e:
        print(f"⚠️ Gagal ambil URL stream: {e}")
        return None


def detect_vehicles_accuracy(video_id, cctv_name):
    print(f"\n▶️ Memproses CCTV: {cctv_name}")
    direct_url = get_youtube_stream_url(video_id)
    if not direct_url:
        return None

    cap = cv2.VideoCapture(direct_url)
    if not cap.isOpened():
        print("⚠️ Tidak bisa buka stream.")
        return None

    print("  - Mulai deteksi dan penghitungan akurasi...")

    acc_data = {lbl: [] for lbl in VEHICLE_LABELS}
    frame_count = 0

    while True:
        ret, frame = cap.read()
        if not ret:
            break
        frame_count += 1

        results = model(frame, verbose=False)
        for result in results:
            boxes = result.boxes
            names = result.names

            for box in boxes:
                cls_id = int(box.cls.item())
                conf = float(box.conf.item())
                label = names[cls_id]

                # Normalisasi label motor
                if label == "motorcycle":
                    label = "motorbike"

                # Hanya ambil label kendaraan
                if label in VEHICLE_LABELS:
                    # Threshold khusus motor karena ukurannya kecil
                    min_conf = 0.25 if label == "motorbike" else 0.4
                    if conf >= min_conf:
                        acc_data[label].append(conf)

                        # Gambar bounding box
                        x1, y1, x2, y2 = map(int, box.xyxy[0])
                        cv2.rectangle(frame, (x1, y1), (x2, y2), (0, 255, 0), 2)
                        cv2.putText(frame, f"{label} {conf:.2f}",
                                    (x1, y1 - 10),
                                    cv2.FONT_HERSHEY_SIMPLEX,
                                    0.6, (0, 255, 0), 2)

        # Tampilkan hasil
        avg_text = " | ".join([
            f"{lbl}: {sum(acc_data[lbl])/len(acc_data[lbl]):.2f}" if len(acc_data[lbl]) > 0 else f"{lbl}: 0.00"
            for lbl in VEHICLE_LABELS
        ])
        cv2.putText(frame, avg_text, (20, 30),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2)
        cv2.imshow("Akurasi Deteksi Kendaraan", frame)

        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

        if frame_count > 250:
            break

    cap.release()
    cv2.destroyAllWindows()

    # Hitung rata-rata akurasi tiap kendaraan
    avg_accuracy = {
        lbl: round(sum(acc_data[lbl]) / len(acc_data[lbl]), 3) if len(acc_data[lbl]) > 0 else 0.0
        for lbl in VEHICLE_LABELS
    }

    print(f"✅ Akurasi rata-rata: {avg_accuracy}")

    # Simpan hasil ke JSON
    today = datetime.now().strftime("%Y-%m-%d")
    data = {}

    if os.path.exists(OUTPUT_FILE):
        with open(OUTPUT_FILE, "r") as f:
            try:
                data = json.load(f)
            except:
                data = {}

    if today not in data:
        data[today] = {}

    data[today][cctv_name] = avg_accuracy

    with open(OUTPUT_FILE, "w") as f:
        json.dump(data, f, indent=4)

    print(f"📁 Hasil akurasi tersimpan ke {OUTPUT_FILE}")
    return avg_accuracy


# === Contoh penggunaan langsung ===
if __name__ == "__main__":
    CCTV_LIST = [
        {"name": "CCTV 1 - Celangapan", "video_id": "3sTPp40WU_w"},
    ]

    for cctv in CCTV_LIST:
        detect_vehicles_accuracy(cctv["video_id"], cctv["name"])