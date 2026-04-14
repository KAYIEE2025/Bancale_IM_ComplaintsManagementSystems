<?php
// includes/footer.php  –  Closing HTML
?>
</main>

<footer class="site-footer">
    <span>ClearVoice &copy; <?= date('Y') ?></span>
    <span>Complaint &amp; Feedback Management System</span>
</footer>

<script src="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>assets/js/app.js"></script>
</body>
</html>
