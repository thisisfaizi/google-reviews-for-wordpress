/**
 * Google Maps Reviews Widget - Main JavaScript
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Google Maps Reviews Widget JavaScript
     */
    var GoogleMapsReviews = {

        /**
         * Initialize the plugin
         */
        init: function() {
            this.initCarousels();
            this.initPagination();
            this.initFiltering();
            this.initReadMore();
            this.initLazyLoading();
            this.initAccessibility();
        },

        /**
         * Initialize carousel functionality
         */
        initCarousels: function() {
            $('.gmrw-layout-carousel').each(function() {
                var $carousel = $(this);
                var $track = $carousel.find('.gmrw-carousel-track');
                var $slides = $carousel.find('.gmrw-carousel-slide');
                var $prevBtn = $carousel.find('.gmrw-carousel-prev');
                var $nextBtn = $carousel.find('.gmrw-carousel-next');
                var $dots = $carousel.find('.gmrw-carousel-dot');
                
                var currentSlide = 0;
                var slidesToShow = parseInt($carousel.attr('data-slides')) || 3;
                var autoplay = $carousel.attr('data-autoplay') === 'true';
                var autoplaySpeed = parseInt($carousel.attr('data-autoplay-speed')) || 5000;
                var autoplayInterval;

                // Calculate slide width
                function updateSlideWidth() {
                    var containerWidth = $carousel.width();
                    var slideWidth = containerWidth / slidesToShow;
                    $slides.css('flex-basis', slideWidth + 'px');
                }

                // Go to specific slide
                function goToSlide(index) {
                    if (index < 0) {
                        index = $slides.length - slidesToShow;
                    } else if (index > $slides.length - slidesToShow) {
                        index = 0;
                    }
                    
                    currentSlide = index;
                    var translateX = -(currentSlide * (100 / slidesToShow));
                    $track.css('transform', 'translateX(' + translateX + '%)');
                    
                    // Update dots
                    $dots.removeClass('active');
                    $dots.eq(currentSlide).addClass('active');
                    
                    // Update button states
                    $prevBtn.toggleClass('disabled', currentSlide === 0);
                    $nextBtn.toggleClass('disabled', currentSlide >= $slides.length - slidesToShow);
                }

                // Next slide
                function nextSlide() {
                    goToSlide(currentSlide + 1);
                }

                // Previous slide
                function prevSlide() {
                    goToSlide(currentSlide - 1);
                }

                // Start autoplay
                function startAutoplay() {
                    if (autoplay && $slides.length > slidesToShow) {
                        autoplayInterval = setInterval(nextSlide, autoplaySpeed);
                    }
                }

                // Stop autoplay
                function stopAutoplay() {
                    if (autoplayInterval) {
                        clearInterval(autoplayInterval);
                        autoplayInterval = null;
                    }
                }

                // Event listeners
                $nextBtn.on('click', function(e) {
                    e.preventDefault();
                    stopAutoplay();
                    nextSlide();
                    if (autoplay) {
                        startAutoplay();
                    }
                });

                $prevBtn.on('click', function(e) {
                    e.preventDefault();
                    stopAutoplay();
                    prevSlide();
                    if (autoplay) {
                        startAutoplay();
                    }
                });

                $dots.on('click', function(e) {
                    e.preventDefault();
                    stopAutoplay();
                    var index = $(this).index();
                    goToSlide(index);
                    if (autoplay) {
                        startAutoplay();
                    }
                });

                // Pause autoplay on hover
                $carousel.on('mouseenter', stopAutoplay);
                $carousel.on('mouseleave', startAutoplay);

                // Keyboard navigation
                $carousel.on('keydown', function(e) {
                    if (e.keyCode === 37) { // Left arrow
                        e.preventDefault();
                        prevSlide();
                    } else if (e.keyCode === 39) { // Right arrow
                        e.preventDefault();
                        nextSlide();
                    }
                });

                // Touch/swipe support
                var startX, startY, distX, distY;
                var threshold = 50;
                var restraint = 100;
                var allowedTime = 500;
                var elapsedTime;
                var startTime;

                $carousel.on('touchstart', function(e) {
                    var touch = e.originalEvent.touches[0];
                    startX = touch.pageX;
                    startY = touch.pageY;
                    startTime = new Date().getTime();
                    stopAutoplay();
                });

                $carousel.on('touchmove', function(e) {
                    e.preventDefault();
                });

                $carousel.on('touchend', function(e) {
                    var touch = e.originalEvent.changedTouches[0];
                    distX = touch.pageX - startX;
                    distY = touch.pageY - startY;
                    elapsedTime = new Date().getTime() - startTime;

                    if (elapsedTime <= allowedTime) {
                        if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint) {
                            if (distX > 0) {
                                prevSlide();
                            } else {
                                nextSlide();
                            }
                        }
                    }
                    
                    if (autoplay) {
                        startAutoplay();
                    }
                });

                // Initialize
                updateSlideWidth();
                goToSlide(0);
                startAutoplay();

                // Handle window resize
                $(window).on('resize', function() {
                    updateSlideWidth();
                    goToSlide(currentSlide);
                });
            });
        },

        /**
         * Initialize pagination functionality
         */
        initPagination: function() {
            $('.gmrw-pagination').each(function() {
                var $pagination = $(this);
                var $container = $pagination.closest('.gmrw-widget, .gmrw-shortcode');
                var $reviewsContainer = $container.find('.gmrw-reviews-list, .gmrw-reviews-cards, .gmrw-reviews-grid');
                var $reviews = $reviewsContainer.find('.gmrw-review, .gmrw-review-card, .gmrw-review-grid-item');
                var reviewsPerPage = parseInt($pagination.attr('data-per-page')) || 5;
                var currentPage = 1;
                var totalPages = Math.ceil($reviews.length / reviewsPerPage);

                // Show specific page
                function showPage(page) {
                    var start = (page - 1) * reviewsPerPage;
                    var end = start + reviewsPerPage;

                    $reviews.hide();
                    $reviews.slice(start, end).show();

                    // Update pagination
                    updatePagination(page);
                    
                    // Scroll to top of reviews
                    $('html, body').animate({
                        scrollTop: $reviewsContainer.offset().top - 100
                    }, 300);
                }

                // Update pagination controls
                function updatePagination(page) {
                    var $links = $pagination.find('a, span');
                    $links.removeClass('gmrw-pagination-current');

                    // Show/hide pagination if only one page
                    if (totalPages <= 1) {
                        $pagination.hide();
                        return;
                    }

                    $pagination.show();

                    // Update current page indicator
                    $pagination.find('[data-page="' + page + '"]').addClass('gmrw-pagination-current');

                    // Update prev/next buttons
                    $pagination.find('.gmrw-pagination-prev').toggleClass('disabled', page === 1);
                    $pagination.find('.gmrw-pagination-next').toggleClass('disabled', page === totalPages);
                }

                // Event listeners
                $pagination.on('click', 'a', function(e) {
                    e.preventDefault();
                    var page = parseInt($(this).attr('data-page'));
                    if (page && page !== currentPage) {
                        currentPage = page;
                        showPage(page);
                    }
                });

                // Initialize
                if (totalPages > 1) {
                    showPage(1);
                }
            });
        },

        /**
         * Initialize filtering functionality
         */
        initFiltering: function() {
            $('.gmrw-filters').each(function() {
                var $filters = $(this);
                var $container = $filters.closest('.gmrw-widget, .gmrw-shortcode');
                var $reviewsContainer = $container.find('.gmrw-reviews-list, .gmrw-reviews-cards, .gmrw-reviews-grid');
                var $reviews = $reviewsContainer.find('.gmrw-review, .gmrw-review-card, .gmrw-review-grid-item');
                var $ratingFilter = $filters.find('.gmrw-filter-rating');
                var $dateFilter = $filters.find('.gmrw-filter-date');
                var $sortFilter = $filters.find('.gmrw-filter-sort');

                // Apply filters
                function applyFilters() {
                    var selectedRating = $ratingFilter.val();
                    var selectedDate = $dateFilter.val();
                    var selectedSort = $sortFilter.val();

                    $reviews.each(function() {
                        var $review = $(this);
                        var rating = parseInt($review.attr('data-rating'));
                        var date = $review.attr('data-date');
                        var showReview = true;

                        // Rating filter
                        if (selectedRating && rating < parseInt(selectedRating)) {
                            showReview = false;
                        }

                        // Date filter
                        if (selectedDate && date) {
                            var reviewDate = new Date(date);
                            var filterDate = new Date();
                            
                            switch (selectedDate) {
                                case 'week':
                                    filterDate.setDate(filterDate.getDate() - 7);
                                    break;
                                case 'month':
                                    filterDate.setMonth(filterDate.getMonth() - 1);
                                    break;
                                case 'year':
                                    filterDate.setFullYear(filterDate.getFullYear() - 1);
                                    break;
                            }

                            if (reviewDate < filterDate) {
                                showReview = false;
                            }
                        }

                        $review.toggle(showReview);
                    });

                    // Sort reviews
                    sortReviews(selectedSort);

                    // Update review count
                    updateReviewCount();
                }

                // Sort reviews
                function sortReviews(sortBy) {
                    var $visibleReviews = $reviews.filter(':visible');
                    var reviewsArray = $visibleReviews.toArray();

                    reviewsArray.sort(function(a, b) {
                        var $a = $(a);
                        var $b = $(b);

                        switch (sortBy) {
                            case 'rating-high':
                                return parseInt($b.attr('data-rating')) - parseInt($a.attr('data-rating'));
                            case 'rating-low':
                                return parseInt($a.attr('data-rating')) - parseInt($b.attr('data-rating'));
                            case 'date-new':
                                return new Date($b.attr('data-date')) - new Date($a.attr('data-date'));
                            case 'date-old':
                                return new Date($a.attr('data-date')) - new Date($b.attr('data-date'));
                            default:
                                return 0;
                        }
                    });

                    $reviewsContainer.append(reviewsArray);
                }

                // Update review count
                function updateReviewCount() {
                    var visibleCount = $reviews.filter(':visible').length;
                    var totalCount = $reviews.length;
                    $filters.find('.gmrw-review-count').text(visibleCount + ' of ' + totalCount + ' reviews');
                }

                // Event listeners
                $ratingFilter.on('change', applyFilters);
                $dateFilter.on('change', applyFilters);
                $sortFilter.on('change', applyFilters);

                // Initialize
                updateReviewCount();
            });
        },

        /**
         * Initialize read more functionality
         */
        initReadMore: function() {
            $(document).on('click', '.gmrw-read-more, .gmrw-read-more-response', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $content = $button.siblings('.gmrw-review-text, .gmrw-owner-response-text');
                var $truncated = $content.find('.gmrw-truncated');
                var $full = $content.find('.gmrw-full');

                if ($truncated.is(':visible')) {
                    $truncated.hide();
                    $full.show();
                    $button.text('Read less');
                } else {
                    $truncated.show();
                    $full.hide();
                    $button.text('Read more');
                }
            });
        },

        /**
         * Initialize lazy loading for images
         */
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                var imageObserver = new IntersectionObserver(function(entries, observer) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            var $img = $(entry.target);
                            var src = $img.attr('data-src');
                            
                            if (src) {
                                $img.attr('src', src).removeAttr('data-src');
                                $img.removeClass('gmrw-lazy');
                                observer.unobserve(entry.target);
                            }
                        }
                    });
                });

                $('.gmrw-author-image[data-src]').each(function() {
                    imageObserver.observe(this);
                });
            } else {
                // Fallback for older browsers
                $('.gmrw-author-image[data-src]').each(function() {
                    var $img = $(this);
                    var src = $img.attr('data-src');
                    if (src) {
                        $img.attr('src', src).removeAttr('data-src');
                        $img.removeClass('gmrw-lazy');
                    }
                });
            }
        },

        /**
         * Initialize accessibility features
         */
        initAccessibility: function() {
            // Add ARIA labels and roles
            $('.gmrw-carousel-prev').attr({
                'aria-label': 'Previous review',
                'role': 'button'
            });

            $('.gmrw-carousel-next').attr({
                'aria-label': 'Next review',
                'role': 'button'
            });

            $('.gmrw-carousel-dot').attr({
                'role': 'button',
                'aria-label': function() {
                    return 'Go to review ' + ($(this).index() + 1);
                }
            });

            // Keyboard navigation for carousel dots
            $('.gmrw-carousel-dot').on('keydown', function(e) {
                if (e.keyCode === 13 || e.keyCode === 32) { // Enter or Space
                    e.preventDefault();
                    $(this).click();
                }
            });

            // Focus management for carousel
            $('.gmrw-layout-carousel').each(function() {
                var $carousel = $(this);
                var $slides = $carousel.find('.gmrw-carousel-slide');
                
                $slides.attr({
                    'role': 'group',
                    'aria-label': function() {
                        return 'Review ' + ($(this).index() + 1) + ' of ' + $slides.length;
                    }
                });
            });

            // Screen reader announcements
            function announceToScreenReader(message) {
                var $announcement = $('<div>', {
                    'aria-live': 'polite',
                    'aria-atomic': 'true',
                    'class': 'gmrw-sr-only'
                }).text(message);

                $('body').append($announcement);
                
                setTimeout(function() {
                    $announcement.remove();
                }, 1000);
            }

            // Announce pagination changes
            $('.gmrw-pagination').on('click', 'a', function() {
                var page = $(this).attr('data-page');
                announceToScreenReader('Showing page ' + page + ' of reviews');
            });

            // Announce filter changes
            $('.gmrw-filters select').on('change', function() {
                var filterName = $(this).find('option:selected').text();
                announceToScreenReader('Filtered by ' + filterName);
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        GoogleMapsReviews.init();
    });

    // Re-initialize on AJAX content load
    $(document).on('gmrw-content-loaded', function() {
        GoogleMapsReviews.init();
    });

})(jQuery);
