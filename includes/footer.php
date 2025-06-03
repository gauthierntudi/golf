<footer class="main-footer">
        <div class="footer-container">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> Invitation Management System. All rights reserved.</p>
            </div>
        </div>
    </footer>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <script src="js/main.js"></script>
    <?php
    // Include specific page scripts based on current page
    $currentPage = basename($_SERVER['PHP_SELF'], '.php');
    
    $pageScripts = [
        'index' => ['js/dashboard.js', 'js/excel-import.js', 'js/email-sender.js'],
        'confirm' => ['js/confirmation.js'],
        'generate-invitation' => ['js/invitation-generator.js']
    ];
    
    if (isset($pageScripts[$currentPage])) {
        foreach ($pageScripts[$currentPage] as $script) {
            echo '<script src="' . $script . '"></script>' . PHP_EOL;
        }
    }
    ?>
</body>
</html>