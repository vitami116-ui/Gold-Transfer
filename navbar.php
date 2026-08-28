<head>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<?php
// navbar.php

// Define menu items
$navItems = [
    ["name" => "Reporting Page", "url" => "index.php"],
    ["name" => "Gold System", "url" => "http://gms.milano"],
    ["name" => "Add Card", "url" => "/config"],
    ["name" => "Super Admin", "url" => "manage.php"],
     ["name" => "Rae Report", "url" => "/rae"]
];

// Optional: Highlight the current page dynamically
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="bg-gray-800 p-4">
    <div class="max-w-7xl mx-auto px-2 sm:px-6 lg:px-8">
        <div class="relative flex items-center justify-between h-16">
            <div class="absolute inset-y-0 left-0 flex items-center sm:hidden">
                <button type="button" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white" aria-expanded="false">
                    <span class="sr-only">Open main menu</span>
                    <!-- Mobile menu button -->
                    <svg class="block h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                
            </div>
            <div class="flex items-center justify-start sm:justify-start flex-1 sm:flex-none">
                <a href="index.php" class="text-white text-xl font-semibold">Milano GMS</a>
            </div>
            <div class="hidden sm:block sm:ml-6">
                <div class="flex space-x-4">
                    <?php foreach ($navItems as $item): ?>
                        <a href="<?= $item['url']; ?>" 
                           class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium <?= ($currentPage == basename($item['url'])) ? 'bg-gray-900 text-white' : ''; ?>">
                            <?= $item['name']; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <button type="button" onclick="openCommand()" class="btn btn-dark shadow-sm d-flex align-items-center justify-content-center" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border: 1px solid #334155;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="text-amber-500" viewBox="0 0 16 16">
            <path d="M6 12.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5ZM3 8.062C3 6.76 4.235 5.765 5.53 5.886a26.58 26.58 0 0 0 4.94 0C11.765 5.765 13 6.76 13 8.062v1.157a.933.933 0 0 1-.765.935c-.845.147-1.34.344-2.235.617a.5.5 0 0 1-.606-.482v-.301c0-.211-.18-.363-.39-.33a21.822 21.822 0 0 0-2.008 0c-.211-.033-.39.119-.39.33v.3c0 .252-.19.472-.444.512-1.07.17-1.558.315-2.4.522a.933.933 0 0 1-1.157-.92V8.062Z"/>
        </svg>
    </button>
    </div>
</nav>
