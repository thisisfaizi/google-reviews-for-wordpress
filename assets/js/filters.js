/**
 * Google Maps Reviews Widget - Filters JavaScript
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Filters Class
     */
    var GMRWFilters = function(element, options) {
        this.$element = $(element);
        this.options = $.extend({}, GMRWFilters.DEFAULTS, options);
        this.init();
    };

    GMRWFilters.DEFAULTS = {
        ratingFilter: true,
        dateFilter: true,
        sortFilter: true,
        showCount: true,
        animationSpeed: 300,
        preserveState: true,
        debounceDelay: 300
    };

    GMRWFilters.prototype = {
        init: function() {
            this.$container = this.$element.closest('.gmrw-widget, .gmrw-shortcode');
            this.$reviewsContainer = this.$container.find('.gmrw-reviews-list, .gmrw-reviews-cards, .gmrw-reviews-grid');
            this.$reviews = this.$reviewsContainer.find('.gmrw-review, .gmrw-review-card, .gmrw-review-grid-item');
            
            this.$ratingFilter = this.$element.find('.gmrw-filter-rating');
            this.$dateFilter = this.$element.find('.gmrw-filter-date');
            this.$sortFilter = this.$element.find('.gmrw-filter-sort');
            this.$countDisplay = this.$element.find('.gmrw-review-count');
            
            this.filters = {
                rating: null,
                date: null,
                sort: null
            };
            
            this.debounceTimer = null;
            this.isAnimating = false;

            this.setupFilters();
            this.bindEvents();
            this.updateReviewCount();
            this.loadSavedState();
        },

        setupFilters: function() {
            // Create filter elements if they don't exist
            if (this.options.ratingFilter && this.$ratingFilter.length === 0) {
                this.createRatingFilter();
            }

            if (this.options.dateFilter && this.$dateFilter.length === 0) {
                this.createDateFilter();
            }

            if (this.options.sortFilter && this.$sortFilter.length === 0) {
                this.createSortFilter();
            }

            if (this.options.showCount && this.$countDisplay.length === 0) {
                this.createCountDisplay();
            }

            // Add ARIA attributes
            this.$element.attr({
                'role': 'group',
                'aria-label': 'Review filters'
            });

            this.$ratingFilter.attr({
                'aria-label': 'Filter by rating'
            });

            this.$dateFilter.attr({
                'aria-label': 'Filter by date'
            });

            this.$sortFilter.attr({
                'aria-label': 'Sort reviews'
            });
        },

        createRatingFilter: function() {
            var $filterGroup = $('<div class="gmrw-filter-group"></div>');
            var $label = $('<label for="gmrw-rating-filter">Rating:</label>');
            var $select = $('<select class="gmrw-filter-rating" id="gmrw-rating-filter"></select>');
            
            $select.append('<option value="">All ratings</option>');
            $select.append('<option value="5">5 stars</option>');
            $select.append('<option value="4">4+ stars</option>');
            $select.append('<option value="3">3+ stars</option>');
            $select.append('<option value="2">2+ stars</option>');
            $select.append('<option value="1">1+ stars</option>');

            $filterGroup.append($label, $select);
            this.$element.append($filterGroup);
            this.$ratingFilter = $select;
        },

        createDateFilter: function() {
            var $filterGroup = $('<div class="gmrw-filter-group"></div>');
            var $label = $('<label for="gmrw-date-filter">Date:</label>');
            var $select = $('<select class="gmrw-filter-date" id="gmrw-date-filter"></select>');
            
            $select.append('<option value="">All time</option>');
            $select.append('<option value="week">Last week</option>');
            $select.append('<option value="month">Last month</option>');
            $select.append('<option value="year">Last year</option>');

            $filterGroup.append($label, $select);
            this.$element.append($filterGroup);
            this.$dateFilter = $select;
        },

        createSortFilter: function() {
            var $filterGroup = $('<div class="gmrw-filter-group"></div>');
            var $label = $('<label for="gmrw-sort-filter">Sort by:</label>');
            var $select = $('<select class="gmrw-filter-sort" id="gmrw-sort-filter"></select>');
            
            $select.append('<option value="date-new">Newest first</option>');
            $select.append('<option value="date-old">Oldest first</option>');
            $select.append('<option value="rating-high">Highest rating</option>');
            $select.append('<option value="rating-low">Lowest rating</option>');

            $filterGroup.append($label, $select);
            this.$element.append($filterGroup);
            this.$sortFilter = $select;
        },

        createCountDisplay: function() {
            var $countGroup = $('<div class="gmrw-filter-group gmrw-count-group"></div>');
            var $count = $('<span class="gmrw-review-count"></span>');
            
            $countGroup.append($count);
            this.$element.append($countGroup);
            this.$countDisplay = $count;
        },

        bindEvents: function() {
            var self = this;

            // Filter change events
            this.$ratingFilter.on('change', function() {
                self.filters.rating = $(this).val();
                self.debouncedApplyFilters();
            });

            this.$dateFilter.on('change', function() {
                self.filters.date = $(this).val();
                self.debouncedApplyFilters();
            });

            this.$sortFilter.on('change', function() {
                self.filters.sort = $(this).val();
                self.debouncedApplyFilters();
            });

            // Clear filters button
            this.$element.on('click', '.gmrw-clear-filters', function(e) {
                e.preventDefault();
                self.clearFilters();
            });

            // Keyboard navigation
            this.$element.on('keydown', 'select', function(e) {
                if (e.keyCode === 13) { // Enter
                    e.preventDefault();
                    self.applyFilters();
                }
            });
        },

        debouncedApplyFilters: function() {
            var self = this;
            
            if (this.debounceTimer) {
                clearTimeout(this.debounceTimer);
            }

            this.debounceTimer = setTimeout(function() {
                self.applyFilters();
            }, this.options.debounceDelay);
        },

        applyFilters: function() {
            if (this.isAnimating) return;

            this.isAnimating = true;
            var self = this;

            // Show loading state
            this.$reviewsContainer.addClass('gmrw-filtering');

            // Apply filters with animation
            setTimeout(function() {
                self.filterReviews();
                self.sortReviews();
                self.updateReviewCount();
                self.saveState();
                
                self.$reviewsContainer.removeClass('gmrw-filtering');
                self.isAnimating = false;
                
                // Trigger custom event
                self.$element.trigger('filtersApplied', [self.filters]);
            }, this.options.animationSpeed);
        },

        filterReviews: function() {
            var self = this;

            this.$reviews.each(function() {
                var $review = $(this);
                var rating = parseInt($review.attr('data-rating'));
                var date = $review.attr('data-date');
                var showReview = true;

                // Rating filter
                if (self.filters.rating && rating < parseInt(self.filters.rating)) {
                    showReview = false;
                }

                // Date filter
                if (self.filters.date && date) {
                    var reviewDate = new Date(date);
                    var filterDate = new Date();
                    
                    switch (self.filters.date) {
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

                // Animate show/hide
                if (showReview) {
                    $review.fadeIn(self.options.animationSpeed);
                } else {
                    $review.fadeOut(self.options.animationSpeed);
                }
            });
        },

        sortReviews: function() {
            if (!this.filters.sort) return;

            var $visibleReviews = this.$reviews.filter(':visible');
            var reviewsArray = $visibleReviews.toArray();
            var self = this;

            reviewsArray.sort(function(a, b) {
                var $a = $(a);
                var $b = $(b);

                switch (self.filters.sort) {
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

            // Animate reordering
            this.$reviewsContainer.append(reviewsArray);
            this.$reviews = this.$reviewsContainer.find('.gmrw-review, .gmrw-review-card, .gmrw-review-grid-item');
        },

        updateReviewCount: function() {
            if (!this.options.showCount) return;

            var visibleCount = this.$reviews.filter(':visible').length;
            var totalCount = this.$reviews.length;
            
            this.$countDisplay.text(visibleCount + ' of ' + totalCount + ' reviews');

            // Update ARIA live region
            this.announceToScreenReader(visibleCount + ' reviews shown');
        },

        clearFilters: function() {
            this.filters = {
                rating: null,
                date: null,
                sort: null
            };

            this.$ratingFilter.val('');
            this.$dateFilter.val('');
            this.$sortFilter.val('date-new');

            this.applyFilters();
            this.announceToScreenReader('All filters cleared');
        },

        saveState: function() {
            if (!this.options.preserveState) return;

            var state = {
                rating: this.filters.rating,
                date: this.filters.date,
                sort: this.filters.sort
            };

            localStorage.setItem('gmrw-filters-' + this.$container.attr('id'), JSON.stringify(state));
        },

        loadSavedState: function() {
            if (!this.options.preserveState) return;

            var containerId = this.$container.attr('id');
            if (!containerId) return;

            var savedState = localStorage.getItem('gmrw-filters-' + containerId);
            if (!savedState) return;

            try {
                var state = JSON.parse(savedState);
                
                if (state.rating) {
                    this.$ratingFilter.val(state.rating);
                    this.filters.rating = state.rating;
                }
                
                if (state.date) {
                    this.$dateFilter.val(state.date);
                    this.filters.date = state.date;
                }
                
                if (state.sort) {
                    this.$sortFilter.val(state.sort);
                    this.filters.sort = state.sort;
                }

                this.applyFilters();
            } catch (e) {
                console.warn('Failed to load saved filter state:', e);
            }
        },

        announceToScreenReader: function(message) {
            var $announcement = $('<div>', {
                'aria-live': 'polite',
                'aria-atomic': 'true',
                'class': 'gmrw-sr-only'
            }).text(message);

            $('body').append($announcement);
            
            setTimeout(function() {
                $announcement.remove();
            }, 1000);
        },

        getActiveFilters: function() {
            return this.filters;
        },

        getVisibleReviews: function() {
            return this.$reviews.filter(':visible');
        },

        destroy: function() {
            this.$element.off();
            this.$element.removeData('gmrw-filters');
        }
    };

    // jQuery plugin
    $.fn.gmrwFilters = function(option) {
        return this.each(function() {
            var $this = $(this);
            var data = $this.data('gmrw-filters');
            var options = typeof option === 'object' && option;

            if (!data) {
                $this.data('gmrw-filters', (data = new GMRWFilters(this, options)));
            }

            if (typeof option === 'string') {
                data[option]();
            }
        });
    };

    $.fn.gmrwFilters.Constructor = GMRWFilters;

    // Auto-initialize filters
    $(document).ready(function() {
        $('.gmrw-filters').each(function() {
            var $filters = $(this);
            var options = {
                ratingFilter: $filters.attr('data-rating-filter') !== 'false',
                dateFilter: $filters.attr('data-date-filter') !== 'false',
                sortFilter: $filters.attr('data-sort-filter') !== 'false',
                showCount: $filters.attr('data-show-count') !== 'false',
                preserveState: $filters.attr('data-preserve-state') !== 'false',
                animationSpeed: parseInt($filters.attr('data-animation-speed')) || 300,
                debounceDelay: parseInt($filters.attr('data-debounce-delay')) || 300
            };

            $filters.gmrwFilters(options);
        });
    });

})(jQuery);
