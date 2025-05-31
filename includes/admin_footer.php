<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<?php
?>
<?php
$adminCustomJs = isset($adminCustomJs) && is_array($adminCustomJs) ? $adminCustomJs : ['js/alpine_admin.js'];
?>
<?php if (!empty($adminCustomJs)): ?>
    <?php foreach ($adminCustomJs as $jsFile): ?>
        <?php
        $projectRootPath = dirname(__DIR__); 
        $filePathForMtime = $projectRootPath . '/' . ltrim($jsFile, '/');
        $versionTimestamp = file_exists($filePathForMtime) ? filemtime($filePathForMtime) : time();
        ?>
        <script src="<?php echo htmlspecialchars($jsFile); ?>?v=<?php echo $versionTimestamp; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>

</html>