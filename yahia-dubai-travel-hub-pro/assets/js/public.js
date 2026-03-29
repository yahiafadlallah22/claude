/**
 * Flavor Travel Hub - Public JavaScript
 * Klook-style Search Engine with Autocomplete
 */

(function($) {
    'use strict';

    // Initialize on document ready
    $(document).ready(function() {
        FTH.init();
    });

    // Main object
    var FTH = {
        
        searchTimeout: null,
        
        init: function() {
            this.bindEvents();
            this.initSearch();
            this.initFilters();
            this.initLoadMore();
            this.initGallery();
            this.initAutocomplete();
        },
        
        bindEvents: function() {
            // Search form submit
            $(document).on('submit', '.fth-search-form', this.handleSearch);
            
            // Filter change
            $(document).on('change', '.fth-filter-select', this.handleFilterChange);
            
            // Load more click
            $(document).on('click', '.fth-load-more-btn', this.handleLoadMore);
            
            // Gallery thumbnail click
            $(document).on('click', '.fth-activity-thumb', this.handleGalleryClick);
            
            // Close autocomplete on click outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.fth-search-field, .fth-search-input-group').length) {
                    $('.fth-autocomplete').removeClass('active');
                }
            });
        },
        
        /**
         * Initialize Klook-style Autocomplete Search
         */
        initAutocomplete: function() {
            var self = this;
            var $inputs = $('.fth-search-input, #fth-search-input');
            
            $inputs.each(function() {
                var $input = $(this);
                var $wrapper = $input.closest('.fth-search-field, .fth-search-input-group');
                var $autocomplete = $wrapper.find('.fth-autocomplete');
                
                // Create autocomplete dropdown if not exists
                if (!$autocomplete.length) {
                    $autocomplete = $('<div class="fth-autocomplete" id="fth-autocomplete-' + Math.random().toString(36).substr(2, 9) + '">' +
                        '<div class="fth-autocomplete-section fth-autocomplete-cities"><div class="fth-autocomplete-title">Popular Destinations</div></div>' +
                        '<div class="fth-autocomplete-section fth-autocomplete-activities" style="display:none;"><div class="fth-autocomplete-title">Activities</div></div>' +
                        '<div class="fth-autocomplete-section fth-autocomplete-categories"><div class="fth-autocomplete-title">Categories</div></div>' +
                    '</div>');
                    $wrapper.append($autocomplete);
                }
                
                // Input events
                $input.on('input', function() {
                    var query = $(this).val().trim();
                    
                    clearTimeout(self.searchTimeout);
                    
                    if (query.length >= 2) {
                        self.searchTimeout = setTimeout(function() {
                            self.fetchSuggestions(query, $autocomplete);
                        }, 300);
                    } else if (query.length === 0) {
                        $autocomplete.removeClass('active');
                    }
                });
                
                $input.on('focus', function() {
                    if ($(this).val().length >= 2) {
                        $autocomplete.addClass('active');
                    }
                });
                
                // Keyboard navigation
                $input.on('keydown', function(e) {
                    var $items = $autocomplete.find('.fth-autocomplete-item');
                    var $focused = $items.filter('.focused');
                    var index = $items.index($focused);
                    
                    if (e.keyCode === 40) { // Down
                        e.preventDefault();
                        index = (index + 1) % $items.length;
                        $items.removeClass('focused');
                        $items.eq(index).addClass('focused');
                    } else if (e.keyCode === 38) { // Up
                        e.preventDefault();
                        index = (index - 1 + $items.length) % $items.length;
                        $items.removeClass('focused');
                        $items.eq(index).addClass('focused');
                    } else if (e.keyCode === 13 && $focused.length) { // Enter
                        e.preventDefault();
                        window.location.href = $focused.attr('href');
                    } else if (e.keyCode === 27) { // Escape
                        $autocomplete.removeClass('active');
                    }
                });
            });
        },
        
        /**
         * Fetch search suggestions via AJAX
         */
        fetchSuggestions: function(query, $autocomplete) {
            var self = this;
            
            $.ajax({
                url: fthPublic.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fth_search_suggestions',
                    query: query
                },
                success: function(response) {
                    self.renderSuggestions(response, $autocomplete);
                },
                error: function() {
                    console.log('FTH: Search suggestions error');
                }
            });
        },
        
        /**
         * Render autocomplete suggestions - Klook Style
         */
        renderSuggestions: function(data, $autocomplete) {
            var html = '';
            var hasResults = false;
            
            // Activities Section
            var $activities = $autocomplete.find('.fth-autocomplete-activities');
            if (data.activities && data.activities.length > 0) {
                html = '<div class="fth-autocomplete-title">Activities</div>';
                data.activities.forEach(function(item) {
                    var imgHtml = item.image 
                        ? '<img src="' + item.image + '" alt="">' 
                        : '<i class="fas fa-ticket"></i>';
                    html += '<a href="' + item.url + '" class="fth-autocomplete-item">' +
                        '<div class="fth-autocomplete-icon">' + imgHtml + '</div>' +
                        '<div class="fth-autocomplete-info">' +
                            '<h4>' + item.title + '</h4>' +
                            '<span>' + (item.city || 'Activity') + (item.price ? ' • From $' + item.price : '') + '</span>' +
                        '</div>' +
                    '</a>';
                });
                $activities.html(html).show();
                hasResults = true;
            } else {
                $activities.hide();
            }
            
            // Cities Section
            var $cities = $autocomplete.find('.fth-autocomplete-cities');
            if (data.cities && data.cities.length > 0) {
                html = '<div class="fth-autocomplete-title">Destinations</div>';
                data.cities.forEach(function(item) {
                    html += '<a href="' + item.url + '" class="fth-autocomplete-item">' +
                        '<div class="fth-autocomplete-icon"><i class="fas fa-map-marker-alt"></i></div>' +
                        '<div class="fth-autocomplete-info">' +
                            '<h4>' + item.name + '</h4>' +
                            '<span>' + item.count + ' activities</span>' +
                        '</div>' +
                    '</a>';
                });
                $cities.html(html).show();
                hasResults = true;
            } else {
                $cities.hide();
            }
            
            // Categories Section
            var $categories = $autocomplete.find('.fth-autocomplete-categories');
            if (data.categories && data.categories.length > 0) {
                html = '<div class="fth-autocomplete-title">Categories</div>';
                data.categories.forEach(function(item) {
                    var icon = item.icon ? '<i class="fa ' + item.icon + '"></i>' : '<i class="fas fa-tag"></i>';
                    html += '<a href="' + item.url + '" class="fth-autocomplete-item">' +
                        '<div class="fth-autocomplete-icon">' + icon + '</div>' +
                        '<div class="fth-autocomplete-info">' +
                            '<h4>' + item.name + '</h4>' +
                            '<span>Category</span>' +
                        '</div>' +
                    '</a>';
                });
                $categories.html(html).show();
                hasResults = true;
            } else {
                $categories.hide();
            }
            
            if (hasResults) {
                $autocomplete.addClass('active');
            } else {
                $autocomplete.removeClass('active');
            }
        },
        
        initSearch: function() {
            // Basic search initialization - autocomplete now handles main functionality
        },
        
        initFilters: function() {
            var $filters = $('.fth-filters');
            
            if (!$filters.length) return;
            
            // Store initial state
            $filters.data('initial', {
                city: $filters.find('[name="fth_city"]').val(),
                category: $filters.find('[name="fth_category"]').val(),
                type: $filters.find('[name="fth_type"]').val(),
                sort: $filters.find('[name="fth_sort"]').val()
            });
        },
        
        initLoadMore: function() {
            var $loadMore = $('.fth-load-more');
            
            if (!$loadMore.length) return;
            
            $loadMore.data('page', 1);
        },
        
        initGallery: function() {
            var $gallery = $('.fth-activity-gallery');
            
            if (!$gallery.length) return;
            
            // Set first thumbnail as active
            $gallery.find('.fth-activity-thumb').first().addClass('active');
        },
        
        handleSearch: function(e) {
            // Let form submit naturally, or prevent and use AJAX
            // For now, allow natural form submission
        },
        
        handleFilterChange: function(e) {
            var $this = $(this);
            var $form = $this.closest('.fth-filters');
            var $grid = $('.fth-activities-grid');
            var $wrapper = $grid.closest('.fth-hub-section, .fth-shortcode-section');
            
            var city = $form.find('[name="fth_city"]').val() || '';
            var category = $form.find('[name="fth_category"]').val() || '';
            var type = $form.find('[name="fth_type"]').val() || '';
            var sort = $form.find('[name="fth_sort"]').val() || 'date';
            
            // Show loading state
            $grid.addClass('fth-loading').css('opacity', 0.5);
            
            $.ajax({
                url: fthPublic.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fth_filter_activities',
                    nonce: fthPublic.nonce,
                    city: city,
                    category: category,
                    type: type,
                    sort: sort
                },
                success: function(response) {
                    if (response.success) {
                        $grid.html(response.data.html);
                        
                        // Update count if displayed
                        var $count = $wrapper.find('.fth-results-count');
                        if ($count.length) {
                            $count.text(response.data.found + ' activities found');
                        }
                    }
                },
                complete: function() {
                    $grid.removeClass('fth-loading').css('opacity', 1);
                }
            });
        },
        
        handleLoadMore: function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var $wrapper = $btn.closest('.fth-load-more');
            var $grid = $wrapper.siblings('.fth-activities-grid');
            
            var currentPage = $wrapper.data('page') || 1;
            var nextPage = currentPage + 1;
            var city = $wrapper.data('city') || '';
            var category = $wrapper.data('category') || '';
            
            // Disable button and show loading
            $btn.prop('disabled', true).text('Loading...');
            
            $.ajax({
                url: fthPublic.ajaxurl,
                type: 'POST',
                data: {
                    action: 'fth_load_more_activities',
                    nonce: fthPublic.nonce,
                    page: nextPage,
                    city: city,
                    category: category
                },
                success: function(response) {
                    if (response.success) {
                        $grid.append(response.data.html);
                        $wrapper.data('page', nextPage);
                        
                        if (!response.data.has_more) {
                            $wrapper.hide();
                        }
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Load More');
                }
            });
        },
        
        handleGalleryClick: function(e) {
            e.preventDefault();
            
            var $thumb = $(this);
            var $gallery = $thumb.closest('.fth-activity-gallery');
            var $mainImage = $gallery.find('.fth-activity-main-image');
            
            // Update active state
            $gallery.find('.fth-activity-thumb').removeClass('active');
            $thumb.addClass('active');
            
            // Update main image
            $mainImage.attr('src', $thumb.data('full') || $thumb.attr('src'));
        }
    };

    // Expose to global scope
    window.FTH = FTH;

})(jQuery);
