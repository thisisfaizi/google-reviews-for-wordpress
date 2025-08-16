/**
 * Google Maps Reviews Widget - Pagination JavaScript
 *
 * @package GoogleMapsReviewsWidget
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Pagination Class
     */
    var GMRWPagination = function(element, options) {
        this.$element = $(element);
        this.options = $.extend({}, GMRWPagination.DEFAULTS, options);
        this.init();
    };

    GMRWPagination.DEFAULTS = {
        itemsPerPage: 5,
        maxVisiblePages: 7,
        showFirstLast: true,
        showPrevNext: true,
        animationSpeed: 300,
        scrollToTop: true,
        scrollOffset: 100,
        preserveState: true,
        keyboardNavigation: true
    };

    GMRWPagination.prototype = {
        init: function() {
            this.$container = this.$element.closest('.gmrw-widget, .gmrw-shortcode');
            this.$reviewsContainer = this.$container.find('.gmrw-reviews-list, .gmrw-reviews-cards, .gmrw-reviews-grid');
            this.$reviews = this.$reviewsContainer.find('.gmrw-review, .gmrw-review-card, .gmrw-review-grid-item');
            
            this.currentPage = 1;
            this.totalItems = this.$reviews.length;
            this.totalPages = Math.ceil(this.totalItems / this.options.itemsPerPage);
            this.isAnimating = false;

            this.setupPagination();
            this.bindEvents();
            this.loadSavedState();
            this.showPage(1);
        },

        setupPagination: function() {
            // Hide pagination if only one page
            if (this.totalPages <= 1) {
                this.$element.hide();
                return;
            }

            this.$element.show();
            this.createPaginationControls();
            this.updatePaginationDisplay();

            // Add ARIA attributes
            this.$element.attr({
                'role': 'navigation',
                'aria-label': 'Reviews pagination'
            });
        },

        createPaginationControls: function() {
            // Clear existing content
            this.$element.empty();

            // Create pagination container
            var $paginationList = $('<ul class="gmrw-pagination-list"></ul>');

            // First page button
            if (this.options.showFirstLast) {
                var $firstBtn = this.createPageButton('first', '&laquo;', 'Go to first page');
                $paginationList.append($firstBtn);
            }

            // Previous button
            if (this.options.showPrevNext) {
                var $prevBtn = this.createPageButton('prev', '&lsaquo;', 'Go to previous page');
                $paginationList.append($prevBtn);
            }

            // Page numbers
            var $pageNumbers = this.createPageNumbers();
            $paginationList.append($pageNumbers);

            // Next button
            if (this.options.showPrevNext) {
                var $nextBtn = this.createPageButton('next', '&rsaquo;', 'Go to next page');
                $paginationList.append($nextBtn);
            }

            // Last page button
            if (this.options.showFirstLast) {
                var $lastBtn = this.createPageButton('last', '&raquo;', 'Go to last page');
                $paginationList.append($lastBtn);
            }

            this.$element.append($paginationList);
        },

        createPageButton: function(type, text, ariaLabel) {
            var $li = $('<li class="gmrw-pagination-item"></li>');
            var $button = $('<a href="#" class="gmrw-pagination-link gmrw-pagination-' + type + '"></a>');
            
            $button.html(text);
            $button.attr({
                'aria-label': ariaLabel,
                'data-type': type
            });

            $li.append($button);
            return $li;
        },

        createPageNumbers: function() {
            var $pageNumbers = $('<li class="gmrw-pagination-item gmrw-pagination-numbers"></li>');
            var $numbersList = $('<ul class="gmrw-page-numbers"></ul>');

            var startPage = this.getStartPage();
            var endPage = this.getEndPage();

            // Add ellipsis before if needed
            if (startPage > 1) {
                var $ellipsisBefore = $('<li class="gmrw-pagination-item gmrw-pagination-ellipsis"></li>');
                $ellipsisBefore.html('&hellip;');
                $numbersList.append($ellipsisBefore);
            }

            // Add page numbers
            for (var i = startPage; i <= endPage; i++) {
                var $pageItem = $('<li class="gmrw-pagination-item"></li>');
                var $pageLink = $('<a href="#" class="gmrw-pagination-link gmrw-pagination-number"></a>');
                
                $pageLink.text(i);
                $pageLink.attr({
                    'data-page': i,
                    'aria-label': 'Go to page ' + i
                });

                $pageItem.append($pageLink);
                $numbersList.append($pageItem);
            }

            // Add ellipsis after if needed
            if (endPage < this.totalPages) {
                var $ellipsisAfter = $('<li class="gmrw-pagination-item gmrw-pagination-ellipsis"></li>');
                $ellipsisAfter.html('&hellip;');
                $numbersList.append($ellipsisAfter);
            }

            $pageNumbers.append($numbersList);
            return $pageNumbers;
        },

        getStartPage: function() {
            var halfVisible = Math.floor(this.options.maxVisiblePages / 2);
            var startPage = this.currentPage - halfVisible;
            
            if (startPage < 1) {
                startPage = 1;
            }
            
            if (startPage + this.options.maxVisiblePages - 1 > this.totalPages) {
                startPage = this.totalPages - this.options.maxVisiblePages + 1;
            }
            
            return Math.max(1, startPage);
        },

        getEndPage: function() {
            var startPage = this.getStartPage();
            var endPage = startPage + this.options.maxVisiblePages - 1;
            
            return Math.min(endPage, this.totalPages);
        },

        bindEvents: function() {
            var self = this;

            // Page number clicks
            this.$element.on('click', '.gmrw-pagination-number', function(e) {
                e.preventDefault();
                var page = parseInt($(this).attr('data-page'));
                self.goToPage(page);
            });

            // Navigation button clicks
            this.$element.on('click', '.gmrw-pagination-link', function(e) {
                e.preventDefault();
                var type = $(this).attr('data-type');
                
                switch (type) {
                    case 'first':
                        self.goToPage(1);
                        break;
                    case 'prev':
                        self.goToPage(self.currentPage - 1);
                        break;
                    case 'next':
                        self.goToPage(self.currentPage + 1);
                        break;
                    case 'last':
                        self.goToPage(self.totalPages);
                        break;
                }
            });

            // Keyboard navigation
            if (this.options.keyboardNavigation) {
                this.$element.on('keydown', '.gmrw-pagination-link', function(e) {
                    switch (e.keyCode) {
                        case 13: // Enter
                        case 32: // Space
                            e.preventDefault();
                            $(this).click();
                            break;
                        case 37: // Left arrow
                            e.preventDefault();
                            self.goToPage(self.currentPage - 1);
                            break;
                        case 39: // Right arrow
                            e.preventDefault();
                            self.goToPage(self.currentPage + 1);
                            break;
                        case 36: // Home
                            e.preventDefault();
                            self.goToPage(1);
                            break;
                        case 35: // End
                            e.preventDefault();
                            self.goToPage(self.totalPages);
                            break;
                    }
                });
            }

            // Focus management
            this.$element.on('click', '.gmrw-pagination-link', function() {
                $(this).focus();
            });
        },

        goToPage: function(page) {
            if (this.isAnimating) return;

            // Validate page number
            page = Math.max(1, Math.min(page, this.totalPages));
            
            if (page === this.currentPage) return;

            this.currentPage = page;
            this.showPage(page);
            this.updatePaginationDisplay();
            this.saveState();
            this.announceToScreenReader('Showing page ' + page + ' of ' + this.totalPages);
        },

        showPage: function(page) {
            if (this.isAnimating) return;

            this.isAnimating = true;
            var self = this;

            // Calculate start and end indices
            var startIndex = (page - 1) * this.options.itemsPerPage;
            var endIndex = startIndex + this.options.itemsPerPage;

            // Hide all reviews
            this.$reviews.fadeOut(this.options.animationSpeed / 2, function() {
                // Show reviews for current page
                self.$reviews.slice(startIndex, endIndex).fadeIn(self.options.animationSpeed / 2, function() {
                    self.isAnimating = false;
                    
                    // Scroll to top if enabled
                    if (self.options.scrollToTop) {
                        self.scrollToTop();
                    }
                });
            });
        },

        updatePaginationDisplay: function() {
            // Update current page indicator
            this.$element.find('.gmrw-pagination-number').removeClass('gmrw-pagination-current');
            this.$element.find('[data-page="' + this.currentPage + '"]').addClass('gmrw-pagination-current');

            // Update navigation button states
            this.$element.find('.gmrw-pagination-first, .gmrw-pagination-prev').toggleClass('disabled', this.currentPage === 1);
            this.$element.find('.gmrw-pagination-next, .gmrw-pagination-last').toggleClass('disabled', this.currentPage === this.totalPages);

            // Update ARIA attributes
            this.$element.find('.gmrw-pagination-first, .gmrw-pagination-prev').attr('aria-disabled', this.currentPage === 1);
            this.$element.find('.gmrw-pagination-next, .gmrw-pagination-last').attr('aria-disabled', this.currentPage === this.totalPages);

            // Recreate page numbers if needed
            this.recreatePageNumbersIfNeeded();
        },

        recreatePageNumbersIfNeeded: function() {
            var startPage = this.getStartPage();
            var endPage = this.getEndPage();
            var currentStartPage = parseInt(this.$element.find('.gmrw-pagination-number').first().attr('data-page')) || 1;
            var currentEndPage = parseInt(this.$element.find('.gmrw-pagination-number').last().attr('data-page')) || this.totalPages;

            if (startPage !== currentStartPage || endPage !== currentEndPage) {
                var $pageNumbers = this.createPageNumbers();
                this.$element.find('.gmrw-pagination-numbers').replaceWith($pageNumbers);
            }
        },

        scrollToTop: function() {
            var targetOffset = this.$reviewsContainer.offset().top - this.options.scrollOffset;
            
            $('html, body').animate({
                scrollTop: targetOffset
            }, this.options.animationSpeed);
        },

        saveState: function() {
            if (!this.options.preserveState) return;

            var containerId = this.$container.attr('id');
            if (!containerId) return;

            var state = {
                currentPage: this.currentPage
            };

            localStorage.setItem('gmrw-pagination-' + containerId, JSON.stringify(state));
        },

        loadSavedState: function() {
            if (!this.options.preserveState) return;

            var containerId = this.$container.attr('id');
            if (!containerId) return;

            var savedState = localStorage.getItem('gmrw-pagination-' + containerId);
            if (!savedState) return;

            try {
                var state = JSON.parse(savedState);
                if (state.currentPage && state.currentPage <= this.totalPages) {
                    this.currentPage = state.currentPage;
                }
            } catch (e) {
                console.warn('Failed to load saved pagination state:', e);
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

        getCurrentPage: function() {
            return this.currentPage;
        },

        getTotalPages: function() {
            return this.totalPages;
        },

        getVisibleItems: function() {
            var startIndex = (this.currentPage - 1) * this.options.itemsPerPage;
            var endIndex = startIndex + this.options.itemsPerPage;
            return this.$reviews.slice(startIndex, endIndex);
        },

        destroy: function() {
            this.$element.off();
            this.$element.removeData('gmrw-pagination');
        }
    };

    // jQuery plugin
    $.fn.gmrwPagination = function(option) {
        return this.each(function() {
            var $this = $(this);
            var data = $this.data('gmrw-pagination');
            var options = typeof option === 'object' && option;

            if (!data) {
                $this.data('gmrw-pagination', (data = new GMRWPagination(this, options)));
            }

            if (typeof option === 'string') {
                data[option]();
            }
        });
    };

    $.fn.gmrwPagination.Constructor = GMRWPagination;

    // Auto-initialize pagination
    $(document).ready(function() {
        $('.gmrw-pagination').each(function() {
            var $pagination = $(this);
            var options = {
                itemsPerPage: parseInt($pagination.attr('data-per-page')) || 5,
                maxVisiblePages: parseInt($pagination.attr('data-max-visible')) || 7,
                showFirstLast: $pagination.attr('data-show-first-last') !== 'false',
                showPrevNext: $pagination.attr('data-show-prev-next') !== 'false',
                animationSpeed: parseInt($pagination.attr('data-animation-speed')) || 300,
                scrollToTop: $pagination.attr('data-scroll-to-top') !== 'false',
                scrollOffset: parseInt($pagination.attr('data-scroll-offset')) || 100,
                preserveState: $pagination.attr('data-preserve-state') !== 'false',
                keyboardNavigation: $pagination.attr('data-keyboard-nav') !== 'false'
            };

            $pagination.gmrwPagination(options);
        });
    });

})(jQuery);
