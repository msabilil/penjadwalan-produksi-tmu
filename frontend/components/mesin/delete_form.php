<?php
/**
 * Komponen Delete Form untuk Mesin
 */

function render_delete_form() {
?>

<!-- Hidden Form untuk Delete -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" id="deleteIdMesin" name="id_mesin" value="">
</form>

<?php
}
?>
