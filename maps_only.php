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

// Load data dari JSON dengan error handling
$cctv_map_data = [];
$jsonFile = 'cctv_data.json';

if (file_exists($jsonFile)) {
    $jsonContent = file_get_contents($jsonFile);
    $cctv_map_data = json_decode($jsonContent, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error parsing JSON: " . json_last_error_msg();
        $cctv_map_data = [];
    }
} else {
    echo "File cctv_data.json tidak ditemukan!";
}

// Cek status untuk setiap CCTV
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Map CCTV Rembang</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            background: #f5f5f5;
            font-family: 'Poppins', sans-serif;
        }

        #map {
            width: 100%;
            height: 500px;
            border-radius: 0;
        }

        .map-container {
            padding: 0;
            position: relative;
        }

        /* Custom Leaflet Popup Styling */
        .leaflet-popup-content-wrapper {
            border-radius: 12px;
            padding: 0;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        }

        .leaflet-popup-content {
            margin: 0;
            padding: 15px;
            font-family: 'Poppins', sans-serif;
            min-width: 200px;
        }

        .leaflet-popup-content strong {
            display: block;
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .popup-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 12px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #ffffff !important;
            font-size: 0.9rem;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            text-decoration: none;
            transition: all 0.3s ease;
            width: 100%;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        }

        .popup-btn:hover {
            background: linear-gradient(135deg, #764ba2, #667eea);
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
            color: #ffffff !important;
        }

        .popup-status {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-top: 8px;
        }

        .popup-status.online {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .popup-status.offline {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .popup-address {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 4px;
        }

        /* Custom Marker Styling */
        .custom-marker-wrapper {
            background: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: all 0.3s ease;
        }

        .custom-marker-wrapper:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(0,0,0,0.2);
        }

        /* Leaflet Controls Styling */
        .leaflet-control-zoom a {
            border-radius: 8px !important;
            font-weight: 600;
        }

        .leaflet-control-zoom {
            border: none !important;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }

        .leaflet-control-attribution {
            background: rgba(255, 255, 255, 0.9) !important;
            border-radius: 8px 0 0 0 !important;
            padding: 4px 8px !important;
            font-size: 0.75rem !important;
        }

        /* Tooltip Style */
        .leaflet-tooltip {
            background: rgba(30, 41, 59, 0.95);
            color: white;
            border: none;
            border-radius: 8px;
            padding: 6px 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }

        .leaflet-tooltip-left::before {
            border-left-color: rgba(30, 41, 59, 0.95);
        }

        .leaflet-tooltip-right::before {
            border-right-color: rgba(30, 41, 59, 0.95);
        }

        .leaflet-tooltip-top::before {
            border-top-color: rgba(30, 41, 59, 0.95);
        }

        .leaflet-tooltip-bottom::before {
            border-bottom-color: rgba(30, 41, 59, 0.95);
        }

        /* Responsive */
        @media (max-width: 768px) {
            #map {
                height: 400px;
            }

            .leaflet-popup-content {
                padding: 12px;
                min-width: 180px;
            }

            .popup-btn {
                padding: 8px 16px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>

<div class="map-container">
    <div id="map"></div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
console.log('Initializing map...');

// CCTV Data from PHP
const cctvs = <?= json_encode($cctv_map_data) ?>;
console.log('CCTV Data:', cctvs);
console.log('Total CCTV:', cctvs.length);

// Validasi data
if (!cctvs || cctvs.length === 0) {
    console.error('No CCTV data available!');
    document.getElementById('map').innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100%; font-size: 1.2rem; color: #ef4444;"><i class="fas fa-exclamation-triangle" style="margin-right: 10px;"></i> Data CCTV tidak tersedia</div>';
} else {
    // Initialize Map
    const map = L.map('map', {
        zoomControl: true,
        attributionControl: true
    }).setView([-6.71, 111.39], 13);

    console.log('Map initialized');

    // Add Tile Layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a> contributors'
    }).addTo(map);

    console.log('Tile layer added');

    // Kelompokkan marker berdasarkan lokasi yang sama
    const groupedCCTVs = {};
    cctvs.forEach(cctv => {
        const key = `${cctv.lat},${cctv.lng}`;
        if (!groupedCCTVs[key]) groupedCCTVs[key] = [];
        groupedCCTVs[key].push(cctv);
    });

    console.log('Grouped CCTVs:', groupedCCTVs);

    // Tambahkan markers ke map
    let markerCount = 0;
    Object.entries(groupedCCTVs).forEach(([key, cctvGroup]) => {
        const [lat, lng] = key.split(',').map(Number);
        
        console.log(`Adding marker at ${lat}, ${lng} for ${cctvGroup.length} CCTV(s)`);
        
        // Buat popup content
        let popupContent = '';
        cctvGroup.forEach((cctv, index) => {
            const statusClass = cctv.status === "on" ? "online" : "offline";
            const statusIcon = cctv.status === "on" ? "🟢" : "🔴";
            const statusText = cctv.status === "on" ? "ONLINE" : "OFFLINE";
            
            popupContent += `
                <div style="padding-bottom: ${index < cctvGroup.length - 1 ? '15px' : '0'}; ${index < cctvGroup.length - 1 ? 'border-bottom: 1px solid #e2e8f0; margin-bottom: 15px;' : ''}">
                    <strong>${cctv.nama}</strong>
                    <div class="popup-address">
                        <i class="fas fa-map-marker-alt"></i> ${cctv.alamat}
                    </div>
                    <div class="popup-status ${statusClass}">
                        ${statusIcon} ${statusText}
                    </div>
                    <a href="maps_preview_admin.php?id=${cctv.id}" class="popup-btn">
                        <i class="fas fa-video"></i> Lihat CCTV
                    </a>
                </div>
            `;
        });

        // Tentukan warna marker berdasarkan status mayoritas
        const onlineCount = cctvGroup.filter(c => c.status === 'on').length;
        const markerColor = onlineCount > cctvGroup.length / 2 ? '#10b981' : '#ef4444';
        
        // Custom icon dengan Font Awesome
        const customIcon = L.divIcon({
            html: `<div class="custom-marker-wrapper" style="border: 3px solid ${markerColor};">
                <i class="fas fa-video" style="color: ${markerColor}; font-size: 18px;"></i>
            </div>`,
            className: 'custom-marker',
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });

        // Tambahkan marker
        const marker = L.marker([lat, lng], { icon: customIcon })
            .addTo(map)
            .bindPopup(popupContent, {
                maxWidth: 300,
                className: 'custom-popup'
            });
        
        markerCount++;
        console.log(`Marker ${markerCount} added successfully`);
        
        // Tambahkan tooltip on hover
        const tooltipText = cctvGroup.length > 1 
            ? `${cctvGroup.length} CCTV di lokasi ini` 
            : cctvGroup[0].nama;
        
        marker.bindTooltip(tooltipText, {
            permanent: false,
            direction: 'top',
            offset: [0, -40]
        });
    });

    console.log(`Total markers added: ${markerCount}`);

    // Fit bounds to show all markers
    if (cctvs.length > 0) {
        const bounds = L.latLngBounds(cctvs.map(c => [parseFloat(c.lat), parseFloat(c.lng)]));
        map.fitBounds(bounds, { padding: [50, 50] });
        console.log('Map bounds fitted');
    }

    // Add scale control
    L.control.scale({
        position: 'bottomleft',
        imperial: false
    }).addTo(map);

    console.log('Map initialization complete!');
}
</script>

</body>
</html>