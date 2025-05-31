<?php
$currentFile = basename($_SERVER['PHP_SELF']);
$noStdFooterPages = ['login.php', 'register.php']; 
if (!in_array($currentFile, $noStdFooterPages)):
?>
    <footer class="bg-slate-800 text-slate-300 py-6 text-center text-sm print:hidden mt-auto">
        <div class="container mx-auto px-6">
            <p>&copy; <?php echo date("Y"); ?> TaskMaster Pro. All rights reserved.</p>
        </div>
    </footer>
<?php endif; ?>

<?php if (isset($customJs) && !empty($customJs)): ?>
    <?php foreach ($customJs as $jsFile): ?>
        <?php
        $projectRootPath = dirname(__DIR__);
        $filePathForMtime = $projectRootPath . '/' . ltrim($jsFile, '/');
        $versionTimestamp = file_exists($filePathForMtime) ? filemtime($filePathForMtime) : time();
        ?>
        <script src="<?php echo htmlspecialchars($jsFile); ?>?v=<?php echo $versionTimestamp; ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

<?php
if (basename($_SERVER['PHP_SELF']) === 'index.php'):
?>
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('testimonialSlider', (config) => ({
                autoplay: config.autoplay || false,
                autoplaySpeed: config.autoplaySpeed || 5000,
                testimonials: config.testimonials || [],
                currentSlide: 0,
                intervalId: null,
                init() {
                    console.log("Testimonial slider initialized with", this.testimonials.length, "slides.");
                    if (this.autoplay && this.testimonials.length > 1) {
                        this.startAutoplay();
                    }
                    if (this.$el) {
                        this.$el.addEventListener('mouseenter', () => this.stopAutoplay());
                        this.$el.addEventListener('mouseleave', () => {
                            if (this.autoplay && this.testimonials.length > 1) this.startAutoplay();
                        });
                    }
                },
                nextSlide() {
                    this.currentSlide = (this.currentSlide + 1) % this.testimonials.length;
                },
                prevSlide() {
                    this.currentSlide = (this.currentSlide - 1 + this.testimonials.length) % this.testimonials.length;
                },
                goToSlide(index) {
                    this.currentSlide = index;
                },
                startAutoplay() {
                    this.stopAutoplay();
                    this.intervalId = setInterval(() => {
                        this.nextSlide();
                    }, this.autoplaySpeed);
                },
                stopAutoplay() {
                    if (this.intervalId) {
                        clearInterval(this.intervalId);
                        this.intervalId = null;
                    }
                },
                destroy() {
                    this.stopAutoplay();
                }
            }));
        });
    </script>
<?php endif; ?>
</body>

</html>