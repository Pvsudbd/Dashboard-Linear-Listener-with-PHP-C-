<?php
// Ambil size dari GET parameter, default 200
$size = isset($_GET['size']) ? $_GET['size'] : '200';

// Validasi size
if (!in_array($size, ['200', '1000', '10000'])) {
    $size = '200';
}

// Area ngambil size
$json = file_get_contents("http://127.0.0.1:8080/items?size=" . $size); //size itu nama dari c++

if ($json === false) {
    die("Gagal mengambil data dari API");
}

$items = json_decode($json, true); 

if (!is_array($items)) {
    die("Format JSON tidak valid");
}
// Kalau eror, berarti c++ belum dirun!

// Pengaturan pagination, jangan dihapus woy
$limit = 50;
$total_data = count($items);
$total_pages = ceil($total_data / $limit);

// Ambil halaman dari query string, default halaman 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, min($page, $total_pages)); // Pastikan halaman valid

// Hitung offset (index data untuk halaman berikutnya)
$offset = ($page - 1) * $limit;

// Ambil data sesuai halaman
$items_paginated = array_slice($items, $offset, $limit);

// Request and Response area
$search_result = null;
$keyword = "";

// Inisialisasi session untuk menyimpan hasil kompleksitas
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['complexity_results'])) {
    $_SESSION['complexity_results'] = [];
}

// Handle reset complexity data
if (isset($_GET['reset_complexity'])) {
    $_SESSION['complexity_results'] = [];
    header("Location: ?size=" . $size);
    exit;
}

if (!empty($_POST['search'])) {
    $keyword = trim($_POST['search']);
    
    // Ambil size dari POST (hidden input) atau dari GET
    $search_size = isset($_POST['size']) ? $_POST['size'] : $size;

    $payload = json_encode([
        "item" => $keyword,
        "size" => $search_size  // PENTING: kirim size ke C++
    ]);

    $ch = curl_init("http://127.0.0.1:8080/Search"); // Samain sama yang di c++, jangan diganti!
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "Content-Length: " . strlen($payload)
        ],
        CURLOPT_POSTFIELDS => $payload
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $search_result = json_decode($response, true);
    
    // Simpan hasil kompleksitas ke session jika data ditemukan
    // Bisa jalan walau cuman rekursiv doang yang keluar
    date_default_timezone_set('Asia/Jakarta'); 
    if ($search_result && 
        isset($search_result['iterative']['time_us']) && 
        isset($search_result['recursive']['time_us']) &&
        ($search_result['iterative']['result'] !== null || $search_result['recursive']['result'] !== null)) {
        
        $result_key = $search_size . "_" . time(); // Unique key berdasarkan size dan waktu
        $_SESSION['complexity_results'][$result_key] = [
            'keyword' => $keyword,
            'size' => $search_size,
            'iterative' => $search_result['iterative']['time_us'],
            'recursive' => $search_result['recursive']['time_us'],
            'timestamp' => date('H:i:s')
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pengiriman</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>



<body class="bg-gray-100 min-h-screen p-10 ">
    
<!-- Navbar area-->
<nav class="bg-blue-50 fixed w-full z-20 top-0 start-0 border-b border-gray-300">
  <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-5">
    <a href="https://www.youtube.com/watch?v=xvFZjo5PgG0" class="flex items-center space-x-3 rtl:space-x-reverse">
        <img src="https://flowbite.com/docs/images/logo.svg" class="h-7" alt="Logo Gwejh">
        <span class="self-center text-xl text-heading font-semibold whitespace-nowrap">SiAdmin</span>
    </a>
</nav>

<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow mt-10">
    
    <!-- Data Size Selector -->
    <div class="mb-6 bg-gradient-to-r from-blue-50 to-indigo-50 p-4 rounded-lg border border-blue-200">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-700 mb-1">Pilih Ukuran Data</h3>
                <p class="text-xs text-gray-600">Ntar bisa di reset kok</p>
            </div>
            <div class="flex gap-3 items-center">
                <a href="?size=200" 
                   class="px-4 py-2 rounded-lg font-medium text-sm transition <?= $size == '200' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' ?>">
                    200 Data
                </a>
                <a href="?size=1000" 
                   class="px-4 py-2 rounded-lg font-medium text-sm transition <?= $size == '1000' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' ?>">
                    1,000 Data
                </a>
                <a href="?size=10000" 
                   class="px-4 py-2 rounded-lg font-medium text-sm transition <?= $size == '10000' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50' ?>">
                    10,000 Data
                </a>
                <?php if ($size != '200'): ?>
                <a href="?" 
                   class="px-4 py-2 rounded-lg font-medium text-sm bg-gray-500 text-white hover:bg-gray-600 transition">
                    Reset Data
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <h1 class="text-2xl font-bold mb-6 text-center">Data Pengiriman Barang</h1>

<div class="flex justify-between items-center mb-6">
    
    <!-- Info data (kiri) -->
    <div class="text-gray-600 text-sm">
        <?php if ($search_result === null): ?>
            Menampilkan <?= $offset + 1 ?> - <?= min($offset + $limit, $total_data) ?> dari <?= $total_data ?> data
        <?php else: ?>
            <div class="space-y-1">
                <p class="font-medium">Hasil pencarian: "<?= htmlspecialchars($keyword) ?>"</p>
                <?php if (isset($search_result['iterative']['time_us']) || isset($search_result['recursive']['time_us'])): ?>
                    <div class="text-xs space-y-0.5">
                        <?php if (isset($search_result['iterative']['time_us'])): ?>
                            <p>Kompleksitas Iterative: <span class="font-semibold text-blue-600"><?= number_format($search_result['iterative']['time_us']) ?> μs</span></p>
                        <?php endif; ?>
                        <?php if (isset($search_result['recursive']['time_us'])): ?>
                            <p>Kompleksitas Recursive: <span class="font-semibold text-purple-600"><?= number_format($search_result['recursive']['time_us']) ?> μs</span></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Searchbar (kanan) -->
    <form method="POST" class="w-96">
        <input type="hidden" name="size" value="<?= $size ?>">
        <label for="search" class="block mb-2.5 text-sm font-medium text-gray-900 sr-only">Search</label>
        <div class="relative">
            <div class="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg class="w-4 h-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-width="2" d="m21 21-3.5-3.5M17 10a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z"/>
                </svg>
            </div>
            <input type="search" 
                   name="search" 
                   id="search" 
                   value="<?= htmlspecialchars($keyword) ?>"
                   class="block w-full p-3 ps-9 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400" 
                   placeholder="Cari item..." />
            <button type="submit" 
                    class="absolute end-1.5 top-1/2 -translate-y-1/2 text-white bg-blue-600 hover:bg-blue-700 border border-transparent focus:ring-4 focus:ring-blue-300 font-medium rounded-md text-xs px-3 py-1.5 focus:outline-none">
                Search
            </button>
        </div>
    </form>

</div>

<!-- Table area -->
    <div class="relative overflow-x-auto bg-white shadow-sm rounded-lg border border-gray-200">
    <table class="w-full text-sm text-left text-gray-600">
        <caption class="p-5 text-lg font-semibold text-left text-gray-900 bg-white">
            Data Pengiriman Barang
            <p class="mt-1.5 text-sm font-normal text-gray-500">Daftar lengkap pengiriman barang dengan informasi email, item, lokasi, dan tanggal kedatangan.</p>
        </caption>
        <thead class="text-sm text-gray-700 bg-gray-100 border-b border-t border-gray-200">
            <tr>
                <th scope="col" class="px-6 py-3 font-medium text-center">
                    ID
                </th>
                <th scope="col" class="px-6 py-3 font-medium text-center">
                    Email
                </th>
                <th scope="col" class="px-6 py-3 font-medium text-center">
                    Item
                </th>
                <th scope="col" class="px-6 py-3 font-medium text-center">
                    Ship From
                </th>
                <th scope="col" class="px-6 py-3 font-medium text-center">
                    Ship To
                </th>
                <th scope="col" class="px-6 py-3 font-medium text-center">
                    Kedatangan
                </th>
                <th scope="col" class="px-6 py-3 font-medium text-center">
                    Status
                </th>
            </tr>
        </thead>
        <tbody>
        <?php if ($search_result !== null): ?>
            
            <?php if ($search_result['iterative']['result'] === null && $search_result['recursive']['result'] === null): ?>
                <tr>
                    <td colspan="7" class="px-6 py-8 text-center">
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-600 font-medium">Data tidak ditemukan</p>
                            <p class="text-sm text-gray-500">Item "<span class="font-semibold"><?= htmlspecialchars($keyword) ?></span>" tidak ada dalam database</p>
                            <a href="?size=<?= $size ?>" class="mt-2 text-blue-600 hover:underline text-sm">Kembali ke semua data</a>
                        </div>
                    </td>
                </tr>
            <?php else: ?>
                
                <?php if ($search_result['iterative']['result'] !== null):
                    $item = $search_result['iterative']['result'];
                ?>
                <tr class="bg-blue-50 border-b border-gray-200 hover:bg-blue-100 text-center">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                        <?= htmlspecialchars($item['id'] ?? '-') ?>
                    </th>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['Email'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4 font-semibold text-blue-600">
                        <?= htmlspecialchars($item['Item']) ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['Ship_From'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['Ship_To'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['DateOfArrive'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-block px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">
                            ITERATIVE
                        </span>
                    </td>
                </tr>
                <?php endif; ?>

                <?php if ($search_result['recursive']['result'] !== null):
                    $item = $search_result['recursive']['result'];
                ?>
                <tr class="bg-purple-50 border-b border-gray-200 hover:bg-purple-100 text-center">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                        <?= htmlspecialchars($item['id'] ?? '-') ?>
                    </th>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['Email'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4 font-semibold text-purple-600">
                        <?= htmlspecialchars($item['Item']) ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['Ship_From'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['Ship_To'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['DateOfArrive'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-block px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">
                            RECURSIVE
                        </span>
                    </td>
                </tr>
                <?php endif; ?>

            <?php endif; ?>

        <?php else: ?>

            <?php
            foreach ($items_paginated as $item):
                if (!is_array($item)) continue;
            ?>
                <tr class="bg-white border-b border-gray-200 hover:bg-gray-50 text-center">
                    <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap">
                        <?= htmlspecialchars($item['id'] ?? '-') ?>
                    </th>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['Email'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['Item'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['Ship_From'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['Ship_To'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4">
                        <?= htmlspecialchars($item['DateOfArrive'] ?? '-') ?>
                    </td>
                    <td class="px-6 py-4 text-center text-gray-400">
                        -
                    </td>
                </tr>
            <?php endforeach; ?>
            
        <?php endif; ?>
        </tbody>
    </table>
</div>

    <!-- Pagination -->
    <?php if ($search_result === null && $total_pages > 1): ?>
    <div class="mt-6 flex justify-center items-center gap-2">
        <?php if ($page > 1): ?>
            <a href="?size=<?= $size ?>&page=1" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                &laquo; First
            </a>
            <a href="?size=<?= $size ?>&page=<?= $page - 1 ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                &lsaquo; Prev
            </a>
        <?php endif; ?>

        <?php
        // Tampilkan nomor halaman (max 5 halaman sekaligus)
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++):
        ?>
            <a href="?size=<?= $size ?>&page=<?= $i ?>" 
               class="px-3 py-1 rounded <?= $i == $page ? 'bg-slate-700 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?size=<?= $size ?>&page=<?= $page + 1 ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                Next &rsaquo;
            </a>
            <a href="?size=<?= $size ?>&page=<?= $total_pages ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                Last &raquo;
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

        <!-- Section kedua -->
<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow mt-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold">Analisis Kompleksitas Waktu</h2>
        <?php if (!empty($_SESSION['complexity_results'])): ?>
            <a href="?size=<?= $size ?>&reset_complexity=1" 
               class="px-4 py-2 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition">
                Hapus Semua Data
            </a>
        <?php endif; ?>
    </div>
    
    <div class="grid grid-cols-5 gap-4">
        <!-- Bagian Kiri - Riwayat Pencarian -->
        <div class="col-span-2 bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h3 class="font-semibold text-gray-700 mb-3">Riwayat Pencarian</h3>
            
            <?php if (empty($_SESSION['complexity_results'])): ?>
                <div class="text-center py-8 text-gray-400">
                    <svg class="w-16 h-16 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-sm">Belum ada data pencarian</p>
                    <p class="text-xs mt-1">Mulai cari item untuk melihat hasil</p>
                </div>
            <?php else: ?>
                <div class="space-y-2 max-h-96 overflow-y-auto">
                    <?php 
                    $reversed_results = array_reverse($_SESSION['complexity_results'], true);
                    foreach ($reversed_results as $key => $result): 
                    ?>
                        <div class="bg-white p-3 rounded border border-gray-200 text-sm">
                            <div class="flex justify-between items-start mb-2">
                                <span class="font-semibold text-gray-800 text-xs"><?= htmlspecialchars($result['keyword']) ?></span>
                                <span class="text-xs text-gray-500"><?= $result['timestamp'] ?></span>
                            </div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded text-xs font-medium">
                                    <?= number_format($result['size']) ?> data
                                </span>
                            </div>
                            <div class="text-xs space-y-1">
                                <div class="flex justify-between">
                                    <span class="text-blue-600">Iterative:</span>
                                    <span class="font-semibold"><?= number_format($result['iterative']) ?> μs</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-purple-600">Recursive:</span>
                                    <span class="font-semibold"><?= number_format($result['recursive']) ?> μs</span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Wilayah Grafik -->
        <div class="col-span-3 bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h3 class="font-semibold text-gray-700 mb-3">Grafik Perbandingan Kompleksitas</h3>
            
            <?php if (empty($_SESSION['complexity_results'])): ?>
                <div class="flex items-center justify-center h-80 text-gray-400">
                    <div class="text-center">
                        <svg class="w-20 h-20 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        <p class="font-medium">Grafik akan muncul setelah ada data</p>
                        <p class="text-sm mt-1">Lakukan pencarian untuk membuat grafik</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="bg-white p-4 rounded border border-gray-200">
                    <canvas id="complexityChart" class="max-h-80"></canvas>
                </div>
                
                <script>
                // Template Chart.Js
                const complexityData = <?= json_encode(array_values($_SESSION['complexity_results'])) ?>;
                
                const labels = complexityData.map((item, index) => {
                    return `${item.keyword.substring(0, 15)}... (${item.size})`;
                });
                
                const iterativeData = complexityData.map(item => item.iterative);
                const recursiveData = complexityData.map(item => item.recursive);
                
                const ctx = document.getElementById('complexityChart').getContext('2d');
                const chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: 'Iterative (μs)',
                                data: iterativeData,
                                backgroundColor: 'rgba(59, 130, 246, 0.7)',
                                borderColor: 'rgb(59, 130, 246)',
                                borderWidth: 2
                            },
                            {
                                label: 'Recursive (μs)',
                                data: recursiveData,
                                backgroundColor: 'rgba(168, 85, 247, 0.7)',
                                borderColor: 'rgb(168, 85, 247)',
                                borderWidth: 2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            title: {
                                display: true,
                                text: 'Perbandingan Waktu Eksekusi (Microseconds)',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                }
                            },
                            legend: {
                                display: true,
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label + ': ' + 
                                               context.parsed.y.toLocaleString() + ' μs';
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Waktu (microseconds)'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return value.toLocaleString() + ' μs';
                                    }
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Item (Ukuran Data)'
                                }
                            }
                        }
                    }
                });
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>