    <?php foreach ($scripts as $src): ?>
    <script src="<?= h(app_url($src)) ?>"></script>
    <?php endforeach; ?>
</body>
</html>
