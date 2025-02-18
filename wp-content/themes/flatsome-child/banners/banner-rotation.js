window.onload = function() {
    var bannerContainer = document.querySelector('.banner-rotation-container');
    var banners = document.querySelectorAll('.banner-slide');

    // Only proceed with rotation if we have more than one banner
    if (banners.length > 1) {
        var currentIndex = 0;
        var interval = 10000; // 10 seconds

        function showBanner(index) {
            // Remove active class from all banners
            banners.forEach(function(banner) {
                banner.classList.remove('active');
            });

            // Calculate next banner index and add active class
            var nextIndex = index % banners.length;
            banners[nextIndex].classList.add('active');
        }

        function rotateBanners() {
            currentIndex = (currentIndex + 1) % banners.length;
            showBanner(currentIndex);
        }

        // Show first banner immediately
        showBanner(0);

        // Start rotation
        var rotationInterval = setInterval(rotateBanners, interval);

        // Add error handling
        window.addEventListener('error', function(e) {
            clearInterval(rotationInterval);
        });

    } else if (banners.length === 1) {
        // Single banner handling
        banners[0].classList.add('active');
    }

    // Add resize handler to ensure proper banner display
    window.addEventListener('resize', function() {
        var activeBanner = document.querySelector('.banner-slide.active');
    });
};