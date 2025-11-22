# from flask import Flask, Response, jsonify
# from ultralytics import YOLO
# import cv2, os, json
# from datetime import datetime

# app = Flask(__name__)

# model = YOLO("yolov8n.pt")  # model YOLOv8 bawaan
# COUNT_FILE = "vehicle_count.json"
# VEHICLES = ["car", "bus", "truck", "motorbike"]

# def load_counts():
#     """Muat data dari file, reset jika tanggal berganti"""
#     today = datetime.now().strftime("%Y-%m-%d")
#     if not os.path.exists(COUNT_FILE):
#         data = {"date": today, "counts": {v: 0 for v in VEHICLES}}
#     else:
#         with open(COUNT_FILE, "r") as f:
#             data = json.load(f)
#         if data["date"] != today:
#             data = {"date": today, "counts": {v: 0 for v in VEHICLES}}
#     with open(COUNT_FILE, "w") as f:
#         json.dump(data, f)
#     return data

# def save_counts(data):
#     with open(COUNT_FILE, "w") as f:
#         json.dump(data, f)

# @app.route('/yolo_feed')
# def yolo_feed():
#     cap = cv2.VideoCapture(0)  # Ganti dengan RTSP stream CCTV jika tersedia
#     data = load_counts()

#     def gen():
#         while True:
#             success, frame = cap.read()
#             if not success:
#                 break

#             results = model(frame)
#             detected = []
#             for box in results[0].boxes:
#                 cls = results[0].names[int(box.cls)]
#                 if cls in VEHICLES:
#                     data["counts"][cls] += 1
#                     detected.append(cls)
#             save_counts(data)

#             annotated = results[0].plot()
#             _, buffer = cv2.imencode('.jpg', annotated)
#             frame_bytes = buffer.tobytes()
#             yield (b'--frame\r\n'
#                    b'Content-Type: image/jpeg\r\n\r\n' + frame_bytes + b'\r\n')

#     return Response(gen(), mimetype='multipart/x-mixed-replace; boundary=frame')

# @app.route('/vehicle_counts')
# def vehicle_counts():
#     data = load_counts()
#     return jsonify(data)

# if __name__ == '__main__':
#     print("🚦 Jalankan di browser: http://localhost:3306/yolo_feed")
#     app.run(host='0.0.0.0', port=3306)