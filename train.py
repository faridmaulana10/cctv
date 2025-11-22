from ultralytics import YOLO
from roboflow import Roboflow

# 1️⃣ Unduh dataset dari Roboflow
print("loading Roboflow workspace...")
rf = Roboflow(api_key="no7qFQPXW1x7YdLXrQv3")
print("loading Roboflow project...")
project = rf.workspace("herdian-adi-winarno-ou2sz").project("vehicle-detection-utebm")
version = project.version(1)
dataset = version.download("yolov8")

print(f"✅ Dataset path: {dataset.location}")

# 2️⃣ Load model dasar YOLOv8 (kecil dulu biar cepat)
model = YOLO("yolov8n.pt")

# 3️⃣ Latih model
model.train(
    data=f"{dataset.location}/data.yaml",  # lokasi dataset Roboflow
    epochs=25,                             # jumlah epoch training
    imgsz=640,                             # ukuran gambar input
    batch=10,                               # bisa kecil kalau CPU/GPU lemah
    name="vehicles_model_final",           # nama hasil model
    project="runs/train"                   # folder hasil pelatihan
)

print("✅ Training selesai! Model tersimpan di folder runs/train/vehicles_model_final")
