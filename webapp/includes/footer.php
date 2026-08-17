</div><!-- /container-fluid -->
<footer class="mt-5 py-3 border-top text-muted small text-center">
  <?= h(APP_NAME) ?> &mdash; <?= date("Y") ?>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_BASE_URL ?>/public/js/app.js?v=<?= @filemtime(__DIR__ . '/../public/js/app.js') ?: time() ?>"></script>
</body>
</html>
