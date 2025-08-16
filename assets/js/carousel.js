/**
 * Google Maps Reviews Widget - Carousel JavaScript
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Carousel Class
     */
    var GMRWCarousel = function(element, options) {
        this.$element = $(element);
        this.options = $.extend({}, GMRWCarousel.DEFAULTS, options);
        this.init();
    };

    GMRWCarousel.DEFAULTS = {
        slidesToShow: 3,
        autoplay: true,
        autoplaySpeed: 5000,
        pauseOnHover: true,
        touchEnabled: true,
        keyboardEnabled: true,
        infinite: true,
        dots: true,
        arrows: true,
        responsive: {
            1200: { slidesToShow: 4 },
            992: { slidesToShow: 3 },
            768: { slidesToShow: 2 },
            576: { slidesToShow: 1 }
        }
    };

    GMRWCarousel.prototype = {
        init: function() {
            this.$track = this.$element.find('.gmrw-carousel-track');
            this.$slides = this.$element.find('.gmrw-carousel-slide');
            this.$prevBtn = this.$element.find('.gmrw-carousel-prev');
            this.$nextBtn = this.$element.find('.gmrw-carousel-next');
            this.$dots = this.$element.find('.gmrw-carousel-dot');
            
            this.currentSlide = 0;
            this.totalSlides = this.$slides.length;
            this.autoplayInterval = null;
            this.isAnimating = false;
            this.touchStartX = 0;
            this.touchStartY = 0;
            this.touchEndX = 0;
            this.touchEndY = 0;

            this.setupCarousel();
            this.bindEvents();
            this.updateResponsiveSettings();
            this.goToSlide(0);
            this.startAutoplay();
        },

        setupCarousel: function() {
            // Set initial slide width
            this.updateSlideWidth();
            
            // Add ARIA attributes
            this.$element.attr({
                'role': 'region',
                'aria-label': 'Reviews carousel'
            });

            this.$slides.each(function(index) {
                $(this).attr({
                    'role': 'group',
                    'aria-label': 'Review ' + (index + 1) + ' of ' + this.totalSlides
                });
            });

            // Create dots if enabled
            if (this.options.dots && this.$dots.length === 0) {
                this.createDots();
            }
        },

        createDots: function() {
            var $dotsContainer = $('<div class="gmrw-carousel-dots"></div>');
            var totalDots = Math.ceil(this.totalSlides / this.options.slidesToShow);

            for (var i = 0; i < totalDots; i++) {
                var $dot = $('<button class="gmrw-carousel-dot" type="button" aria-label="Go to slide ' + (i + 1) + '"></button>');
                $dotsContainer.append($dot);
            }

            this.$element.append($dotsContainer);
            this.$dots = this.$element.find('.gmrw-carousel-dot');
        },

        bindEvents: function() {
            var self = this;

            // Navigation buttons
            if (this.options.arrows) {
                this.$nextBtn.on('click', function(e) {
                    e.preventDefault();
                    self.nextSlide();
                });

                this.$prevBtn.on('click', function(e) {
                    e.preventDefault();
                    self.prevSlide();
                });
            }

            // Dots navigation
            this.$dots.on('click', function(e) {
                e.preventDefault();
                var index = $(this).index();
                self.goToSlide(index * self.options.slidesToShow);
            });

            // Keyboard navigation
            if (this.options.keyboardEnabled) {
                this.$element.on('keydown', function(e) {
                    if (e.keyCode === 37) { // Left arrow
                        e.preventDefault();
                        self.prevSlide();
                    } else if (e.keyCode === 39) { // Right arrow
                        e.preventDefault();
                        self.nextSlide();
                    }
                });
            }

            // Touch/swipe support
            if (this.options.touchEnabled) {
                this.$element.on('touchstart', function(e) {
                    self.handleTouchStart(e);
                });

                this.$element.on('touchmove', function(e) {
                    self.handleTouchMove(e);
                });

                this.$element.on('touchend', function(e) {
                    self.handleTouchEnd(e);
                });
            }

            // Autoplay pause on hover
            if (this.options.pauseOnHover) {
                this.$element.on('mouseenter', function() {
                    self.stopAutoplay();
                });

                this.$element.on('mouseleave', function() {
                    self.startAutoplay();
                });
            }

            // Window resize
            $(window).on('resize', function() {
                self.updateResponsiveSettings();
                self.updateSlideWidth();
                self.goToSlide(self.currentSlide);
            });

            // Visibility change (pause when tab is not active)
            $(document).on('visibilitychange', function() {
                if (document.hidden) {
                    self.stopAutoplay();
                } else {
                    self.startAutoplay();
                }
            });
        },

        updateResponsiveSettings: function() {
            var width = this.$element.width();
            var responsive = this.options.responsive;

            // Find the appropriate breakpoint
            var breakpoints = Object.keys(responsive).sort(function(a, b) {
                return parseInt(b) - parseInt(a);
            });

            for (var i = 0; i < breakpoints.length; i++) {
                if (width >= parseInt(breakpoints[i])) {
                    this.options.slidesToShow = responsive[breakpoints[i]].slidesToShow;
                    break;
                }
            }
        },

        updateSlideWidth: function() {
            var containerWidth = this.$element.width();
            var slideWidth = containerWidth / this.options.slidesToShow;
            this.$slides.css('flex-basis', slideWidth + 'px');
        },

        goToSlide: function(index) {
            if (this.isAnimating) return;

            // Handle infinite loop
            if (this.options.infinite) {
                if (index < 0) {
                    index = this.totalSlides - this.options.slidesToShow;
                } else if (index > this.totalSlides - this.options.slidesToShow) {
                    index = 0;
                }
            } else {
                index = Math.max(0, Math.min(index, this.totalSlides - this.options.slidesToShow));
            }

            this.currentSlide = index;
            this.isAnimating = true;

            var translateX = -(this.currentSlide * (100 / this.options.slidesToShow));
            this.$track.css('transform', 'translateX(' + translateX + '%)');

            // Update dots
            this.updateDots();

            // Update button states
            this.updateButtonStates();

            // Trigger custom event
            this.$element.trigger('slideChanged', [this.currentSlide]);

            // Reset animation flag
            setTimeout(function() {
                this.isAnimating = false;
            }.bind(this), 300);
        },

        nextSlide: function() {
            this.goToSlide(this.currentSlide + 1);
        },

        prevSlide: function() {
            this.goToSlide(this.currentSlide - 1);
        },

        updateDots: function() {
            this.$dots.removeClass('active');
            var activeDotIndex = Math.floor(this.currentSlide / this.options.slidesToShow);
            this.$dots.eq(activeDotIndex).addClass('active');
        },

        updateButtonStates: function() {
            var isFirst = this.currentSlide === 0;
            var isLast = this.currentSlide >= this.totalSlides - this.options.slidesToShow;

            this.$prevBtn.toggleClass('disabled', isFirst);
            this.$nextBtn.toggleClass('disabled', isLast);

            // Update ARIA attributes
            this.$prevBtn.attr('aria-disabled', isFirst);
            this.$nextBtn.attr('aria-disabled', isLast);
        },

        startAutoplay: function() {
            if (!this.options.autoplay || this.totalSlides <= this.options.slidesToShow) {
                return;
            }

            this.stopAutoplay();
            this.autoplayInterval = setInterval(function() {
                this.nextSlide();
            }.bind(this), this.options.autoplaySpeed);
        },

        stopAutoplay: function() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        },

        handleTouchStart: function(e) {
            var touch = e.originalEvent.touches[0];
            this.touchStartX = touch.clientX;
            this.touchStartY = touch.clientY;
            this.stopAutoplay();
        },

        handleTouchMove: function(e) {
            e.preventDefault();
        },

        handleTouchEnd: function(e) {
            var touch = e.originalEvent.changedTouches[0];
            this.touchEndX = touch.clientX;
            this.touchEndY = touch.clientY;

            var diffX = this.touchStartX - this.touchEndX;
            var diffY = this.touchStartY - this.touchEndY;

            // Check if it's a horizontal swipe
            if (Math.abs(diffX) > Math.abs(diffY) && Math.abs(diffX) > 50) {
                if (diffX > 0) {
                    this.nextSlide();
                } else {
                    this.prevSlide();
                }
            }

            this.startAutoplay();
        },

        destroy: function() {
            this.stopAutoplay();
            this.$element.off();
            this.$element.removeData('gmrw-carousel');
        }
    };

    // jQuery plugin
    $.fn.gmrwCarousel = function(option) {
        return this.each(function() {
            var $this = $(this);
            var data = $this.data('gmrw-carousel');
            var options = typeof option === 'object' && option;

            if (!data) {
                $this.data('gmrw-carousel', (data = new GMRWCarousel(this, options)));
            }

            if (typeof option === 'string') {
                data[option]();
            }
        });
    };

    $.fn.gmrwCarousel.Constructor = GMRWCarousel;

    // Auto-initialize carousels
    $(document).ready(function() {
        $('.gmrw-layout-carousel').each(function() {
            var $carousel = $(this);
            var options = {
                slidesToShow: parseInt($carousel.attr('data-slides')) || 3,
                autoplay: $carousel.attr('data-autoplay') === 'true',
                autoplaySpeed: parseInt($carousel.attr('data-autoplay-speed')) || 5000,
                pauseOnHover: $carousel.attr('data-pause-on-hover') !== 'false',
                touchEnabled: $carousel.attr('data-touch-enabled') !== 'false',
                keyboardEnabled: $carousel.attr('data-keyboard-enabled') !== 'false',
                infinite: $carousel.attr('data-infinite') !== 'false',
                dots: $carousel.attr('data-dots') !== 'false',
                arrows: $carousel.attr('data-arrows') !== 'false'
            };

            $carousel.gmrwCarousel(options);
        });
    });

})(jQuery);
