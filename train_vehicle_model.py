# === train_vehicle_model.py ===

# 1. Import Roboflow dan download dataset
from roboflow import Roboflow
rf = Roboflow(api_key="no7qFQPXW1x7YdLXrQv3")
project = rf.workspace("herdian-adi-winarno-ou2sz").project("vehicle-detection-utebm")
version = project.version(1)
dataset = version.download("yolov8", location="C:/xampp/htdocs/cctv1")

# 2. Latih model YOLOv8 pakai dataset itu
from ultralytics import YOLO

# Buat model YOLO baru (boleh pakai yang kecil dulu)
model = YOLO("yolov8n.pt")

# Jalankan training
model.train(
    data=r"C:\Users\acer\datasets\Vehicle-Detection-1\data.yaml",  # path asli dataset
    epochs=50,
    imgsz=640,
    project="runs/train",
    name="vehicles_model3"
)

# 3. Setelah selesai, model tersimpan di:
# runs/train/vehicles_model/weights/best.pt
