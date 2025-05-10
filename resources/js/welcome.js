document.addEventListener('DOMContentLoaded', function() {
    const videoWrapper = document.querySelector('.video-wrapper');
    const iframe = videoWrapper.querySelector('iframe');
    const videoFallback = videoWrapper.querySelector('.video-fallback');

    iframe.onload = function() {
        try {
            // If we can access the iframe content, hide the fallback
            videoFallback.classList.add('hidden');
            iframe.classList.remove('hidden');
        } catch (e) {
            // If we can't access the iframe content, show the fallback
            videoFallback.classList.remove('hidden');
            iframe.classList.add('hidden');
        }
    };
});

// Testimonials Carousel
document.addEventListener('DOMContentLoaded', function() {
    const track = document.querySelector('.testimonials-track');
    const cards = document.querySelectorAll('.testimonial-card');
    const indicators = document.querySelectorAll('.scroll-indicator');
    const prevButton = document.querySelector('.nav-arrow.prev');
    const nextButton = document.querySelector('.nav-arrow.next');
    
    let currentIndex = 0;
    const cardWidth = cards[0].offsetWidth + 24; // Width + margin
    const maxIndex = cards.length - 1;

    function updateCarousel() {
        track.style.transform = `translateX(-${currentIndex * cardWidth}px)`;
        
        // Update indicators
        indicators.forEach((indicator, index) => {
            indicator.classList.toggle('active', index === currentIndex);
        });

        // Update button states
        prevButton.disabled = currentIndex === 0;
        nextButton.disabled = currentIndex === maxIndex;
    }

    function moveToIndex(index) {
        currentIndex = Math.max(0, Math.min(index, maxIndex));
        updateCarousel();
    }

    // Prevent default scroll behavior for testimonials section
    track.addEventListener('wheel', (e) => {
        e.preventDefault();
    });

    // Event Listeners
    prevButton.addEventListener('click', () => moveToIndex(currentIndex - 1));
    nextButton.addEventListener('click', () => moveToIndex(currentIndex + 1));

    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => moveToIndex(index));
    });

    // Auto-advance carousel
    let autoAdvanceInterval = setInterval(() => {
        if (currentIndex < maxIndex) {
            moveToIndex(currentIndex + 1);
        } else {
            moveToIndex(0);
        }
    }, 5000);

    // Pause auto-advance on hover
    track.addEventListener('mouseenter', () => clearInterval(autoAdvanceInterval));
    track.addEventListener('mouseleave', () => {
        autoAdvanceInterval = setInterval(() => {
            if (currentIndex < maxIndex) {
                moveToIndex(currentIndex + 1);
            } else {
                moveToIndex(0);
            }
        }, 5000);
    });

    // Handle window resize
    window.addEventListener('resize', () => {
        const newCardWidth = cards[0].offsetWidth + 24;
        if (newCardWidth !== cardWidth) {
            updateCarousel();
        }
    });

    // Initialize carousel state
    updateCarousel();
}); 