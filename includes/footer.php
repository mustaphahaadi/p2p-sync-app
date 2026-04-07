<!-- ====== Footer ====== -->
<footer class="footer-custom">
    <div class="px-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <p>&copy; <?= date('Y') ?> <strong>CampusRemind</strong> — Smart Reminder System</p>
            <p>Built for Campus Academic Activities</p>
        </div>
    </div>
</footer>

<?php if (isLoggedIn()): ?>
</div><!-- /.main-content -->
<?php endif; ?>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar toggle for mobile
    const toggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (toggle && sidebar && overlay) {
        toggle.addEventListener('click', function() {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        });
        overlay.addEventListener('click', function() {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        });
    }

    // Auto-dismiss alerts after 5 seconds
    document.querySelectorAll('.alert-dismissible').forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

</body>
</html>
