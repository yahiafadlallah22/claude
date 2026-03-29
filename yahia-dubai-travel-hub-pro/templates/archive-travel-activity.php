<?php
/**
 * Template: Archive Activities - Klook Style
 * Uses theme header
 */

if (!defined('ABSPATH')) {
    exit;
}

$primary_color = Flavor_Travel_Hub::get_primary_color();
$site_name = get_bloginfo('name');

// Get filters
$cities = FTH_Taxonomies::get_cities(array('hide_empty' => true));
$categories = FTH_Taxonomies::get_categories(array('hide_empty' => true));
$types = FTH_Taxonomies::get_types(array('hide_empty' => true));

// Get current filters
$current_city = isset($_GET['fth_city']) ? sanitize_text_field($_GET['fth_city']) : '';
$current_category = isset($_GET['fth_category']) ? sanitize_text_field($_GET['fth_category']) : '';
$current_search = isset($_GET['fth_search']) ? sanitize_text_field($_GET['fth_search']) : '';

$paged = get_query_var('paged') ? get_query_var('paged') : 1;

// Currency
$currency = get_option('fth_default_currency', 'USD');
$currency_symbols = array('USD' => '$', 'AED' => 'AED ', 'EUR' => '€', 'GBP' => '£');
$symbol = isset($currency_symbols[$currency]) ? $currency_symbols[$currency] : $currency . ' ';

// Use theme header
get_header();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?php echo FTH_PLUGIN_URL; ?>assets/css/public.css">
<style>
    .fth-container *, .fth-container *::before, .fth-container *::after { box-sizing: border-box; }
    
    :root {
        --klook-primary: <?php echo esc_attr($primary_color); ?>;
        --klook-primary-dark: <?php echo esc_attr(FTH_Public::darken_color($primary_color, 10)); ?>;
        --klook-text: #1a1a1a;
        --klook-text-secondary: #666;
        --klook-text-light: #999;
        --klook-bg: #fff;
        --klook-bg-gray: #f5f5f5;
        --klook-border: #e8e8e8;
        --klook-star: #ff9800;
        --klook-success: #00a651;
    }
    
    .fth-container {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
        font-size: 14px;
        line-height: 1.5;
        color: var(--klook-text);
        background: var(--klook-bg);
    }
    
    .fth-container a { color: var(--klook-primary); text-decoration: none; }
    
    .fth-hero {
        position: relative;
        height: 350px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #1a1a2e, #16213e);
        overflow: hidden;
    }
    
    .fth-hero-bg {
        position: absolute;
        inset: 0;
        background: url('https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1920') center/cover;
        opacity: 0.4;
    }
    
    .fth-hero-content {
        position: relative;
        z-index: 10;
        text-align: center;
        color: #fff;
        padding: 20px;
    }
    
    .fth-hero-title {
        font-size: 42px;
        font-weight: 800;
        margin-bottom: 12px;
        color: #fff;
        text-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }
    
    .fth-hero-subtitle {
        font-size: 18px;
        opacity: 0.9;
        margin-bottom: 32px;
        color: #fff;
    }
    
    .fth-search-box {
        background: #fff;
        border-radius: 8px;
            padding: 8px;
            display: flex;
            gap: 8px;
            max-width: 700px;
            margin: 0 auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        
        .fth-search-input {
            flex: 1;
            padding: 12px 16px;
            border: none;
            font-size: 15px;
            background: var(--klook-bg-gray);
            border-radius: 6px;
        }
        
        .fth-search-select {
            padding: 12px 16px;
            border: none;
            font-size: 14px;
            background: var(--klook-bg-gray);
            border-radius: 6px;
            min-width: 140px;
        }
        
        .fth-search-btn {
            padding: 12px 28px;
            background: var(--klook-primary);
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }
        
        .fth-search-btn:hover {
            background: var(--klook-primary-dark);
        }
        
        .fth-main {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .fth-breadcrumb {
            font-size: 13px;
            color: var(--klook-text-secondary);
            margin-bottom: 24px;
        }
        
        .fth-filters-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .fth-filter-select {
            padding: 10px 16px;
            border: 1px solid var(--klook-border);
            border-radius: 8px;
            font-size: 14px;
            background: #fff;
            min-width: 150px;
        }
        
        .fth-results-info {
            margin-left: auto;
            color: var(--klook-text-secondary);
        }
        
        .fth-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
        }
        
        @media (max-width: 1024px) { .fth-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .fth-grid { grid-template-columns: repeat(2, 1fr); } .fth-hero-title { font-size: 28px; } }
        @media (max-width: 480px) { .fth-grid { grid-template-columns: 1fr; } }
        
        .fth-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .fth-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.12);
        }
        
        .fth-card-image {
            position: relative;
            aspect-ratio: 4/3;
            overflow: hidden;
            background: var(--klook-bg-gray);
        }
        
        .fth-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .fth-card-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: var(--klook-primary);
            color: #fff;
            padding: 4px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .fth-card-content { padding: 16px; }
        
        .fth-card-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 8px;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .fth-card-title a { color: inherit; }
        
        .fth-card-meta {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 13px;
        }
        
        .fth-card-rating i { color: var(--klook-star); }
        
        .fth-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 12px;
            border-top: 1px solid var(--klook-border);
        }
        
        .fth-card-price-label { font-size: 11px; color: var(--klook-text-light); }
        .fth-card-price { font-size: 20px; font-weight: 700; color: var(--klook-primary); }
        
        .fth-card-btn {
            padding: 8px 16px;
            background: var(--klook-primary);
            color: #fff;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }
        
        .fth-pagination {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-top: 40px;
        }
        
        .fth-pagination a, .fth-pagination span {
            padding: 10px 16px;
            border: 1px solid var(--klook-border);
            border-radius: 6px;
            color: var(--klook-text);
        }
        
        .fth-pagination .current {
            background: var(--klook-primary);
            color: #fff;
            border-color: var(--klook-primary);
        }
        
        .fth-no-results {
            text-align: center;
            padding: 80px 20px;
            color: var(--klook-text-secondary);
        }
        
        .fth-footer {
            background: #1a1a1a;
            color: #fff;
            padding: 48px 0 24px;
            margin-top: 48px;
        }
        
        .fth-footer-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .fth-footer-bottom {
            border-top: 1px solid #333;
            padding-top: 24px;
            text-align: center;
            color: #666;
        }
    </style>
    
    <!-- FTH Container -->
    <div class="fth-container">
    
    <section class="fth-hero">
        <div class="fth-hero-bg"></div>
        <div class="fth-hero-content">
            <h1 class="fth-hero-title">Things to Do</h1>
            <p class="fth-hero-subtitle">Discover amazing tours, attractions, and experiences worldwide</p>
            
            <form class="fth-search-box" method="get">
                <input type="text" name="fth_search" class="fth-search-input" placeholder="Search activities..." value="<?php echo esc_attr($current_search); ?>">
                <select name="fth_city" class="fth-search-select">
                    <option value="">All Cities</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?php echo esc_attr($city->slug); ?>" <?php selected($current_city, $city->slug); ?>><?php echo esc_html($city->name); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="fth-search-btn"><i class="fas fa-search"></i> Search</button>
            </form>
        </div>
    </section>
    
    <main class="fth-main">
        <nav class="fth-breadcrumb">
            <a href="<?php echo home_url(); ?>">Home</a> / 
            <strong>Things to Do</strong>
        </nav>
        
        <div class="fth-filters-bar">
            <select class="fth-filter-select" name="fth_category" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($current_category, $cat->slug); ?>><?php echo esc_html($cat->name); ?></option>
                <?php endforeach; ?>
            </select>
            
            <select class="fth-filter-select" name="sort">
                <option value="date">Newest</option>
                <option value="rating">Highest Rated</option>
                <option value="price_low">Price: Low to High</option>
            </select>
            
            <span class="fth-results-info">
                <?php global $wp_query; echo esc_html($wp_query->found_posts); ?> activities found
            </span>
        </div>
        
        <?php if (have_posts()): ?>
        <div class="fth-grid">
            <?php while (have_posts()): the_post();
                $card_id = get_the_ID();
                $card_price = get_post_meta($card_id, '_fth_price', true);
                $card_rating = get_post_meta($card_id, '_fth_rating', true);
                $card_reviews = get_post_meta($card_id, '_fth_review_count', true);
                $card_image = get_post_meta($card_id, '_fth_external_image', true);
                $card_bestseller = get_post_meta($card_id, '_fth_is_bestseller', true);
                $card_affiliate = get_post_meta($card_id, '_fth_affiliate_link', true);
                
                if (!$card_image && has_post_thumbnail($card_id)) {
                    $card_image = get_the_post_thumbnail_url($card_id, 'medium');
                }
                if (!$card_image) {
                    $card_image = 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=400';
                }
            ?>
            <article class="fth-card">
                <a href="<?php the_permalink(); ?>" class="fth-card-image">
                    <img src="<?php echo esc_url($card_image); ?>" alt="<?php the_title_attribute(); ?>">
                    <?php if ($card_bestseller === '1'): ?>
                        <span class="fth-card-badge">Bestseller</span>
                    <?php endif; ?>
                </a>
                <div class="fth-card-content">
                    <h3 class="fth-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                    <?php if ($card_rating): ?>
                    <div class="fth-card-meta">
                        <span class="fth-card-rating"><i class="fas fa-star"></i> <?php echo number_format($card_rating, 1); ?></span>
                        <?php if ($card_reviews): ?><span>(<?php echo number_format($card_reviews); ?>)</span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="fth-card-footer">
                        <div>
                            <div class="fth-card-price-label">From</div>
                            <div class="fth-card-price"><?php echo $card_price ? esc_html($symbol . number_format($card_price, 2)) : 'TBD'; ?></div>
                        </div>
                        <a href="<?php echo $card_affiliate ?: get_permalink(); ?>" class="fth-card-btn" <?php echo $card_affiliate ? 'target="_blank"' : ''; ?>>Book</a>
                    </div>
                </div>
            </article>
            <?php endwhile; ?>
        </div>
        
        <?php 
        $total_pages = $wp_query->max_num_pages;
        if ($total_pages > 1):
        ?>
        <div class="fth-pagination">
            <?php echo paginate_links(array(
                'total' => $total_pages,
                'current' => $paged,
                'prev_text' => '&larr;',
                'next_text' => '&rarr;',
            )); ?>
        </div>
        <?php endif; ?>
        
        <?php else: ?>
        <div class="fth-no-results">
            <i class="fas fa-search" style="font-size: 48px; margin-bottom: 16px;"></i>
            <h3>No activities found</h3>
            <p>Try adjusting your search or filters</p>
        </div>
        <?php endif; ?>
    </main>
    
    </div><!-- .fth-container -->
    
    <?php get_footer(); ?>
