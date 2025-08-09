<?php
/**
 * Komponen Header untuk halaman Data Mesin
 */

function render_page_header_mesin($title, $description, $button_text = "Tambah Mesin", $button_onclick = "bukaFormTambah()") {
?>
<div class="page-header mb-8 p-6">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="page-header-title text-3xl font-bold mb-2"><?php echo htmlspecialchars($title); ?></h1>
            <p class="text-gray-600 text-lg"><?php echo htmlspecialchars($description); ?></p>
        </div>
        <button onclick="<?php echo $button_onclick; ?>" class="btn-primary text-white px-6 py-3 rounded-xl font-semibold inline-flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            <span><?php echo htmlspecialchars($button_text); ?></span>
        </button>
    </div>
</div>
<?php
}
?>
