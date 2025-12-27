<?php
$json = file_get_contents("http://127.0.0.1:8080/items"); // jangan asal ganti oy

if ($json === false) {
    die("Gagal mengambil data dari API");
}

$items = json_decode($json, true);

if (!is_array($items)) {
    die("Format JSON tidak valid");
}

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

// Request + response handler
$search_result = null;
$keyword = "";

if (!empty($_POST['search'])) {
    $keyword = trim($_POST['search']);

    $payload = json_encode([
        "item" => $keyword
    ]);

    $ch = curl_init("http://127.0.0.1:8080/Search");
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
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Data Pengiriman</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>



<body class="bg-gray-100 min-h-screen p-10 ">
    
<!-- Navbar area-->
<nav class="bg-blue-50 fixed w-full z-20 top-0 start-0 border-b border-gray-300">
  <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-5">
    <a href="https://flowbite.com/" class="flex items-center space-x-3 rtl:space-x-reverse">
        <img src="https://flowbite.com/docs/images/logo.svg" class="h-7" alt="Flowbite Logo">
        <span class="self-center text-xl text-heading font-semibold whitespace-nowrap">SiAdmin</span>
    </a>
</nav>

<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow mt-10">
    <h1 class="text-2xl font-bold mb-6 text-center">Data Pengiriman Barang</h1>
<!-- Bungkus dengan flex container -->
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
                            <p>Kompleksitas Iterative: <span class="font-semibold text-blue-600"><?= number_format($search_result['iterative']['time_us']) ?> Microsecond</span></p>
                        <?php endif; ?>
                        <?php if (isset($search_result['recursive']['time_us'])): ?>
                            <p>Kompleksitas Recursive: <span class="font-semibold text-purple-600"><?= number_format($search_result['recursive']['time_us']) ?> Microsecond</span></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Searchbar (kanan) -->
    <form method="POST" class="w-96">   
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
                    Detail
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
                            <a href="?" class="mt-2 text-blue-600 hover:underline text-sm">Kembali ke semua data</a>
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
                    <td class="px-6 py-4 text-right">
                        <a href="detail.php?id=<?= $item['id'] ?>" class="font-medium text-blue-600 hover:underline">Detail</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            
        <?php endif; ?>
        </tbody>
    </table>
</div>

    <!-- Pagination -->
    <?php if ($search_result === null): ?>
    <div class="mt-6 flex justify-center items-center gap-2">
        <?php if ($page > 1): ?>
            <a href="?page=1" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                &laquo; First
            </a>
            <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                &lsaquo; Prev
            </a>
        <?php endif; ?>

        <?php
        // Tampilkan nomor halaman (max 5 halaman sekaligus)
        $start_page = max(1, $page - 2);
        $end_page = min($total_pages, $page + 2);
        
        for ($i = $start_page; $i <= $end_page; $i++):
        ?>
            <a href="?page=<?= $i ?>" 
               class="px-3 py-1 rounded <?= $i == $page ? 'bg-slate-700 text-white' : 'bg-gray-200 hover:bg-gray-300' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                Next &rsaquo;
            </a>
            <a href="?page=<?= $total_pages ?>" class="px-3 py-1 bg-gray-200 rounded hover:bg-gray-300">
                Last &raquo;
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

        <!-- Section kedua -->
<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow mt-6">
    <h2 class="text-xl font-bold mb-4">Tempat penilaian aka</h2>
    
    <div class="grid grid-cols-5 gap-4 mb-2">
        <div class="col-span-2">
            <p class="text-sm text-gray-600 font-medium">Metode Sorting</p>
        </div>
        <div class="col-span-3 col-start-3">
            <p class="text-sm text-gray-600 font-medium">Grafik Kompleksitas</p>
        </div>
    </div>
    
    <div class="grid grid-cols-5 grid-rows-5 gap-4">
        <div class="col-span-2 row-span-5 bg-gray-100 p-4 rounded">
            Bagian Kiri (2 kolom, 5 baris)
        </div>
        <div class="col-span-3 row-span-5 col-start-3 bg-gray-200 p-4 rounded">
            Bagian Kanan (3 kolom, 5 baris)
        </div>
    </div>
</div>
</body>
</html>