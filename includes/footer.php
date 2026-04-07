<!-- ====== Footer ====== -->
<footer class="footer-custom">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start">
                <p>&copy; <?= date('Y') ?> <strong>CampusRemind</strong> — Smart Reminder System</p>
            </div>
            <div class="col-md-6 text-center text-md-end">
                <p>Built for Campus Academic Activities</p>
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Navbar scroll effect -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const nav = document.getElementById('mainNav');
    if (nav) {
        window.addEventListener('scroll', function() {
            nav.classList.toggle('scrolled', window.scrollY > 10);
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
