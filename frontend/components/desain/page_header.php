<?php
/**
 * Komponen Header untuk halaman Data Desain
 */

function render_page_header($title, $description, $button_text = "Tambah Desain", $button_onclick = "showTambahModal()") {
?>
<div class="mb-8">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($title); ?></h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($description); ?></p>
        </div>
        <button onclick="<?php echo $button_onclick; ?>" class="btn-primary text-white px-6 py-3 rounded-xl flex items-center space-x-3 shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span class="font-medium"><?php echo htmlspecialchars($button_text); ?></span>
        </button>
    </div>
</div>
<?php
}
?>
