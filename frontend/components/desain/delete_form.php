<?php
/**
 * Komponen Hidden Form untuk Delete
 */

function render_delete_form() {
?>
<!-- Hidden form for delete -->
<form id="deleteForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="hapus">
    <input type="hidden" name="id_desain" id="deleteDesainId">
</form>
<?php
}
?>
