/**
 * Google Maps Reviews Widget - Utilities JavaScript
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * GMRW Utilities
     */
    var GMRWUtils = {

        /**
         * Initialize all utilities
         */
        init: function() {
            this.initLazyLoading();
            this.initReadMore();
            this.initAccessibility();
            this.initAnimations();
            this.initPerformance();
        },

        /**
         * Lazy loading for images
         */
        initLazyLoading: function() {
            if ('IntersectionObserver' in window) {
                this.setupIntersectionObserver();
            } else {
                this.setupFallbackLazyLoading();
            }
        },

        setupIntersectionObserver: function() {
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var $img = $(entry.target);
                        var src = $img.attr('data-src');
                        
                        if (src) {
                            $img.attr('src', src).removeAttr('data-src');
                            $img.removeClass('gmrw-lazy');
                            observer.unobserve(entry.target);
                            
                            // Trigger custom event
                            $img.trigger('gmrw:imageLoaded');
                        }
                    }
                });
            }, {
                rootMargin: '50px 0px',
                threshold: 0.01
            });

            $('.gmrw-author-image[data-src]').each(function() {
                imageObserver.observe(this);
            });
        },

        setupFallbackLazyLoading: function() {
            var $lazyImages = $('.gmrw-author-image[data-src]');
            
            if ($lazyImages.length === 0) return;

            var checkLazyImages = function() {
                $lazyImages.each(function() {
                    var $img = $(this);
                    var rect = this.getBoundingClientRect();
                    
                    if (rect.top <= window.innerHeight + 100 && rect.bottom >= 0) {
                        var src = $img.attr('data-src');
                        if (src) {
                            $img.attr('src', src).removeAttr('data-src');
                            $img.removeClass('gmrw-lazy');
                            $img.trigger('gmrw:imageLoaded');
                        }
                    }
                });
            };

            // Check on scroll and resize
            $(window).on('scroll resize', this.debounce(checkLazyImages, 100));
            checkLazyImages();
        },

        /**
         * Read more functionality
         */
        initReadMore: function() {
            $(document).on('click', '.gmrw-read-more, .gmrw-read-more-response', function(e) {
                e.preventDefault();
                var $button = $(this);
                var $content = $button.siblings('.gmrw-review-text, .gmrw-owner-response-text');
                var $truncated = $content.find('.gmrw-truncated');
                var $full = $content.find('.gmrw-full');

                if ($truncated.is(':visible')) {
                    $truncated.slideUp(300);
                    $full.slideDown(300);
                    $button.text('Read less');
                    $button.attr('aria-expanded', 'true');
                } else {
                    $full.slideUp(300);
                    $truncated.slideDown(300);
                    $button.text('Read more');
                    $button.attr('aria-expanded', 'false');
                }
            });
        },

        /**
         * Accessibility features
         */
        initAccessibility: function() {
            // Add ARIA labels and roles
            this.addAriaAttributes();
            
            // Keyboard navigation
            this.setupKeyboardNavigation();
            
            // Focus management
            this.setupFocusManagement();
            
            // Screen reader announcements
            this.setupScreenReaderAnnouncements();
        },

        addAriaAttributes: function() {
            // Star ratings
            $('.gmrw-stars').each(function() {
                var $stars = $(this);
                var rating = $stars.find('.gmrw-star-filled').length;
                $stars.attr({
                    'role': 'img',
                    'aria-label': rating + ' out of 5 stars'
                });
            });

            // Review items
            $('.gmrw-review, .gmrw-review-card, .gmrw-review-grid-item').each(function(index) {
                var $review = $(this);
                var authorName = $review.find('.gmrw-author-name').text();
                var rating = $review.find('.gmrw-star-filled').length;
                
                $review.attr({
                    'role': 'article',
                    'aria-label': 'Review by ' + authorName + ' with ' + rating + ' stars'
                });
            });

            // Read more buttons
            $('.gmrw-read-more, .gmrw-read-more-response').each(function() {
                $(this).attr({
                    'role': 'button',
                    'aria-expanded': 'false',
                    'aria-controls': $(this).siblings('.gmrw-review-text, .gmrw-owner-response-text').attr('id') || 'review-content'
                });
            });
        },

        setupKeyboardNavigation: function() {
            // Star ratings keyboard navigation
            $('.gmrw-stars').on('keydown', function(e) {
                if (e.keyCode === 13 || e.keyCode === 32) { // Enter or Space
                    e.preventDefault();
                    $(this).click();
                }
            });

            // Review items keyboard navigation
            $('.gmrw-review, .gmrw-review-card, .gmrw-review-grid-item').on('keydown', function(e) {
                if (e.keyCode === 13 || e.keyCode === 32) { // Enter or Space
                    e.preventDefault();
                    $(this).find('.gmrw-read-more').click();
                }
            });
        },

        setupFocusManagement: function() {
            // Focus trap for modals (if any)
            $(document).on('keydown', '.gmrw-modal', function(e) {
                if (e.keyCode === 27) { // Escape
                    e.preventDefault();
                    $(this).find('.gmrw-modal-close').click();
                }
            });

            // Focus restoration
            $(document).on('gmrw:modalOpened', function() {
                $('body').addClass('gmrw-modal-open');
            });

            $(document).on('gmrw:modalClosed', function() {
                $('body').removeClass('gmrw-modal-open');
            });
        },

        setupScreenReaderAnnouncements: function() {
            // Create live region for announcements
            if ($('.gmrw-sr-announcements').length === 0) {
                $('body').append('<div class="gmrw-sr-announcements" aria-live="polite" aria-atomic="true" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;"></div>');
            }
        },

        /**
         * Animation utilities
         */
        initAnimations: function() {
            // Check for reduced motion preference
            if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                this.disableAnimations();
            }

            // Setup animation triggers
            this.setupAnimationTriggers();
        },

        disableAnimations: function() {
            $('*').css({
                'animation-duration': '0.01ms !important',
                'animation-iteration-count': '1 !important',
                'transition-duration': '0.01ms !important'
            });
        },

        setupAnimationTriggers: function() {
            // Animate elements on scroll
            if ('IntersectionObserver' in window) {
                var animationObserver = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('gmrw-animated');
                        }
                    });
                }, {
                    threshold: 0.1
                });

                $('.gmrw-review, .gmrw-review-card, .gmrw-review-grid-item').each(function() {
                    animationObserver.observe(this);
                });
            }
        },

        /**
         * Performance utilities
         */
        initPerformance: function() {
            // Debounce scroll events
            $(window).on('scroll', this.debounce(function() {
                this.handleScroll();
            }.bind(this), 16));

            // Throttle resize events
            $(window).on('resize', this.throttle(function() {
                this.handleResize();
            }.bind(this), 100));
        },

        handleScroll: function() {
            // Update scroll-based animations
            $('.gmrw-scroll-animate').each(function() {
                var $element = $(this);
                var rect = this.getBoundingClientRect();
                var scrollPercent = (window.innerHeight - rect.top) / (window.innerHeight + rect.height);
                
                if (scrollPercent > 0 && scrollPercent < 1) {
                    $element.css('transform', 'translateY(' + (scrollPercent * 20) + 'px)');
                }
            });
        },

        handleResize: function() {
            // Trigger resize event for components
            $(document).trigger('gmrw:resize');
        },

        /**
         * Utility functions
         */
        debounce: function(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        },

        throttle: function(func, limit) {
            var inThrottle;
            return function() {
                var args = arguments;
                var context = this;
                if (!inThrottle) {
                    func.apply(context, args);
                    inThrottle = true;
                    setTimeout(function() {
                        inThrottle = false;
                    }, limit);
                }
            };
        },

        /**
         * Announce to screen reader
         */
        announceToScreenReader: function(message) {
            var $announcements = $('.gmrw-sr-announcements');
            if ($announcements.length === 0) {
                $('body').append('<div class="gmrw-sr-announcements" aria-live="polite" aria-atomic="true" style="position: absolute; left: -10000px; width: 1px; height: 1px; overflow: hidden;"></div>');
                $announcements = $('.gmrw-sr-announcements');
            }

            $announcements.text(message);
            
            // Clear after a short delay
            setTimeout(function() {
                $announcements.empty();
            }, 1000);
        },

        /**
         * Format date
         */
        formatDate: function(dateString) {
            var date = new Date(dateString);
            var now = new Date();
            var diffTime = Math.abs(now - date);
            var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

            if (diffDays === 1) {
                return 'Yesterday';
            } else if (diffDays < 7) {
                return diffDays + ' days ago';
            } else if (diffDays < 30) {
                var weeks = Math.floor(diffDays / 7);
                return weeks + ' week' + (weeks > 1 ? 's' : '') + ' ago';
            } else if (diffDays < 365) {
                var months = Math.floor(diffDays / 30);
                return months + ' month' + (months > 1 ? 's' : '') + ' ago';
            } else {
                var years = Math.floor(diffDays / 365);
                return years + ' year' + (years > 1 ? 's' : '') + ' ago';
            }
        },

        /**
         * Generate star rating HTML
         */
        generateStarRating: function(rating, maxRating) {
            maxRating = maxRating || 5;
            var html = '<div class="gmrw-stars" role="img" aria-label="' + rating + ' out of ' + maxRating + ' stars">';
            
            for (var i = 1; i <= maxRating; i++) {
                if (i <= rating) {
                    html += '<span class="gmrw-star gmrw-star-filled" aria-hidden="true">★</span>';
                } else {
                    html += '<span class="gmrw-star gmrw-star-empty" aria-hidden="true">☆</span>';
                }
            }
            
            html += '</div>';
            return html;
        },

        /**
         * Truncate text
         */
        truncateText: function(text, maxLength, suffix) {
            suffix = suffix || '...';
            if (text.length <= maxLength) {
                return text;
            }
            return text.substring(0, maxLength - suffix.length) + suffix;
        },

        /**
         * Generate unique ID
         */
        generateId: function(prefix) {
            prefix = prefix || 'gmrw';
            return prefix + '-' + Math.random().toString(36).substr(2, 9);
        },

        /**
         * Check if element is in viewport
         */
        isInViewport: function(element) {
            var rect = element.getBoundingClientRect();
            return (
                rect.top >= 0 &&
                rect.left >= 0 &&
                rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
                rect.right <= (window.innerWidth || document.documentElement.clientWidth)
            );
        },

        /**
         * Get element dimensions
         */
        getElementDimensions: function(element) {
            var rect = element.getBoundingClientRect();
            return {
                width: rect.width,
                height: rect.height,
                top: rect.top,
                left: rect.left,
                bottom: rect.bottom,
                right: rect.right
            };
        }
    };

    // Initialize utilities when DOM is ready
    $(document).ready(function() {
        GMRWUtils.init();
    });

    // Make utilities available globally
    window.GMRWUtils = GMRWUtils;

    // jQuery plugin for utilities
    $.fn.gmrwUtils = function(method) {
        if (GMRWUtils[method]) {
            return GMRWUtils[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return GMRWUtils.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' does not exist on GMRWUtils');
        }
    };

})(jQuery);
