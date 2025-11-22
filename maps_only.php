<?php
$apiKey = 'AIzaSyAPnGJS6r5Q2H_235Szh3bxPrQBmvckM8k'; // API Key

function checkYoutubeStatus($videoId, $apiKey) {
    $apiUrl = "https://www.googleapis.com/youtube/v3/videos?part=liveStreamingDetails&id={$videoId}&key={$apiKey}";
    $json = @file_get_contents($apiUrl);
    if (!$json) return 'off';

    $data = json_decode($json, true);
    if (!empty($data['items'])) {
        $details = $data['items'][0]['liveStreamingDetails'] ?? null;
        if ($details && isset($details['actualStartTime']) && !isset($details['actualEndTime'])) {
            return 'on';
        }
    }
    return 'off';
}

$cctv_map_data = json_decode(file_get_contents("cctv_data.json"), true);

foreach ($cctv_map_data as &$cctv) {
    $cctv['status'] = checkYoutubeStatus($cctv['video_id'], $apiKey);
}
unset($cctv);

$onCount = count(array_filter($cctv_map_data, fn($cctv) => $cctv['status'] === 'on'));
$offCount = count($cctv_map_data) - $onCount;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Map CCTV Rembang</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>
         body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }

        #map {
            width: 100%;
            height: 500px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .map-container {
            padding: 15px;
        }
        .close-btn {
            background: #e74c3c;
            color: #fff;
            border: none;
            padding: 5px 8px;
            border-radius: 4px;
            float: right;
            cursor: pointer;
        }
        .close-btn:hover {
            background: #c0392b;
        }
    </style>
</head>
<body>

<div class="map-container">
    <div id="map"></div>
</div>

<div class="info-box">
    <div class="info-item" id="btnOn">
        <h2>CCTV Aktif</h2>
        <p class="on">🟢 <?= $onCount ?></p>
    </div>
    <div class="info-item" id="btnOff">
        <h2>CCTV Tidak Aktif</h2>
        <p class="off">🔴 <?= $offCount ?></p>
    </div>
</div>

<div class="list-container" id="listContainer">
    <button class="close-btn" onclick="hideList()">Tutup</button>
    <div id="listContent"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
const cctvs = <?= json_encode($cctv_map_data) ?>;
const map = L.map('map').setView([-6.71, 111.39], 13);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
}).addTo(map);

// Kelompokkan marker
const groupedCCTVs = {};
cctvs.forEach(cctv => {
    const key = `${cctv.lat},${cctv.lng}`;
    if (!groupedCCTVs[key]) groupedCCTVs[key] = [];
    groupedCCTVs[key].push(cctv);
});

Object.entries(groupedCCTVs).forEach(([key, cctvGroup]) => {
    const [lat, lng] = key.split(',').map(Number);
    let popupContent = '';
    cctvGroup.forEach(cctv => {
        const statusIcon = cctv.status === "on" ? "🟢" : "🔴";
        const color = cctv.status === "on" ? "green" : "red";
        popupContent += `
            <strong>${cctv.nama}</strong><br>
            ${cctv.alamat}<br>
            Status: <span style="color:${color}">${statusIcon} ${cctv.status.toUpperCase()}</span><br>
            <a href="index.php?id=${cctv.id}" target="_blank" class="popup-btn">📹 Lihat CCTV</a>
            <hr style="margin:8px 0;">
        `;
    });
    popupContent = popupContent.replace(/<hr.*>$/, '');
    L.marker([lat, lng]).addTo(map).bindPopup(popupContent);
});

// ====== Fitur daftar CCTV aktif/tidak aktif ======
document.getElementById('btnOn').addEventListener('click', () => showList('on'));
document.getElementById('btnOff').addEventListener('click', () => showList('off'));

function showList(status) {
    const listContainer = document.getElementById('listContainer');
    const listContent = document.getElementById('listContent');
    const filtered = cctvs.filter(c => c.status === status);

    let html = `<h3>Daftar CCTV ${status === 'on' ? 'Aktif 🟢' : 'Tidak Aktif 🔴'}</h3><ul>`;
    filtered.forEach(cctv => {
        html += `<li><strong>${cctv.nama}</strong> – ${cctv.alamat} 
                 (<a href="index.php?id=${cctv.id}" target="_blank">Lihat</a>)</li>`;
    });
    html += '</ul>';

    listContent.innerHTML = html;
    listContainer.style.display = 'block';
}

function hideList() {
    document.getElementById('listContainer').style.display = 'none';
}
</script>

<footer>
    <p>&copy; <?= date('Y') ?> Sistem Pemantauan CCTV</p>
</footer>
</body>
</html>