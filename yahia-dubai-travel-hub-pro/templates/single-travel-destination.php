<?php
/**
 * Template: Single Destination - Klook Style
 * Uses theme header
 */

if (!defined('ABSPATH')) {
    exit;
}

$post_id = get_the_ID();
$primary_color = Flavor_Travel_Hub::get_primary_color();

$hero_image = get_post_meta($post_id, '_fth_hero_image', true);
$external_image = get_post_meta($post_id, '_fth_external_image', true);
$seo_intro = get_post_meta($post_id, '_fth_seo_intro', true);
$deeplink = get_post_meta($post_id, '_fth_deeplink', true);

$cities = wp_get_post_terms($post_id, 'travel_city');
$countries = wp_get_post_terms($post_id, 'travel_country');

$city_term = !empty($cities) ? $cities[0] : null;
$city_name = $city_term ? $city_term->name : get_the_title();
$country_name = !empty($countries) ? $countries[0]->name : '';
$country_term = !empty($countries) ? $countries[0] : null;

if (!$hero_image) {
    if ($external_image) $hero_image = $external_image;
    elseif (has_post_thumbnail($post_id)) $hero_image = get_the_post_thumbnail_url($post_id, 'full');
    else $hero_image = 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1920';
}

$activities = array();
$search_query = isset($_GET['fth_search']) ? sanitize_text_field(wp_unslash($_GET['fth_search'])) : '';
if ($city_term) $activities = FTH_Search::search_activities(array('keyword' => $search_query, 'city' => $city_term->slug, 'per_page' => 8));

$categories = FTH_Taxonomies::get_categories(array('hide_empty' => false));
$site_name = get_bloginfo('name');

$currency = get_option('fth_default_currency', 'USD');
$currency_symbols = array('USD' => '$', 'AED' => 'AED ', 'EUR' => '€', 'GBP' => '£');
$symbol = isset($currency_symbols[$currency]) ? $currency_symbols[$currency] : $currency . ' ';
$activity_count = $city_term ? FTH_Templates::get_city_activity_count($city_term->term_id) : 0;

// Use theme header
get_header();
?>

<style>
    .fth-container *, .fth-container *::before, .fth-container *::after { box-sizing: border-box; }
    :root { --klook-primary: <?php echo esc_attr($primary_color); ?>; --klook-primary-dark: <?php echo esc_attr(FTH_Public::darken_color($primary_color, 10)); ?>; --klook-text: #1a1a1a; --klook-text-secondary: #666; --klook-text-light: #999; --klook-bg: #fff; --klook-bg-gray: #f5f5f5; --klook-border: #e8e8e8; --klook-star: #ff9800; }
    .fth-container { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; font-size: 14px; line-height: 1.5; color: var(--klook-text); background: var(--klook-bg); }
    .fth-container a { color: var(--klook-primary); text-decoration: none; }
    .fth-container a:hover { text-decoration: underline; }
    .fth-hero { position: relative; height: 450px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .fth-hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; }
    .fth-hero-overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.6) 100%); }
    .fth-hero-content { position: relative; z-index: 10; text-align: center; color: #fff; padding: 20px; max-width: 900px; }
    .fth-hero-title { font-size: 52px; font-weight: 800; margin-bottom: 8px; text-shadow: 0 2px 8px rgba(0,0,0,0.3); color: #fff; }
    .fth-hero-subtitle { font-size: 20px; opacity: 0.95; margin-bottom: 32px; color: #fff; }
        .fth-search-box { background: #fff; border-radius: 8px; padding: 8px; display: flex; gap: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
        .fth-search-input { flex: 1; padding: 14px 16px; border: none; font-size: 15px; background: var(--klook-bg-gray); border-radius: 6px; }
        .fth-search-input:focus { outline: none; }
        .fth-search-btn { padding: 14px 32px; background: var(--klook-primary); color: #fff; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .fth-search-btn:hover { background: var(--klook-primary-dark); }
        .fth-main { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .fth-breadcrumb { font-size: 13px; color: var(--klook-text-secondary); margin-bottom: 32px; }
        .fth-breadcrumb a { color: var(--klook-text-secondary); }
        .fth-breadcrumb span { margin: 0 8px; color: var(--klook-text-light); }
        .fth-intro { background: var(--klook-bg-gray); border-radius: 16px; padding: 32px; margin-bottom: 48px; }
        .fth-intro h2 { font-size: 24px; margin-bottom: 16px; }
        .fth-intro p { color: var(--klook-text-secondary); line-height: 1.8; font-size: 15px; }
        .fth-section-title { font-size: 28px; font-weight: 700; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
        .fth-section-title i { color: var(--klook-primary); }
        .fth-categories-scroll { display: flex; gap: 12px; overflow-x: auto; padding-bottom: 16px; margin-bottom: 32px; }
        .fth-category-pill { display: flex; align-items: center; gap: 8px; padding: 10px 20px; background: #fff; border: 1px solid var(--klook-border); border-radius: 24px; white-space: nowrap; font-size: 14px; color: var(--klook-text); text-decoration: none; }
        .fth-category-pill:hover { border-color: var(--klook-primary); color: var(--klook-primary); text-decoration: none; }
        .fth-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
        @media (max-width: 1024px) { .fth-grid { grid-template-columns: repeat(3, 1fr); } }
        @media (max-width: 768px) { .fth-grid { grid-template-columns: repeat(2, 1fr); } .fth-hero-title { font-size: 36px; } }
        @media (max-width: 480px) { .fth-grid { grid-template-columns: 1fr; } }
        .fth-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s; }
        .fth-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
        .fth-card-image { position: relative; aspect-ratio: 4/3; overflow: hidden; background: var(--klook-bg-gray); }
        .fth-card-image img { width: 100%; height: 100%; object-fit: cover; }
        .fth-card-badge { position: absolute; top: 10px; left: 10px; background: var(--klook-primary); color: #fff; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; }
        .fth-card-content { padding: 16px; }
        .fth-card-title { font-size: 15px; font-weight: 600; margin-bottom: 8px; line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .fth-card-title a { color: inherit; text-decoration: none; }
        .fth-card-title a:hover { color: var(--klook-primary); }
        .fth-card-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 13px; }
        .fth-card-rating i { color: var(--klook-star); }
        .fth-card-reviews { color: var(--klook-text-light); }
        .fth-card-footer { display: flex; justify-content: space-between; align-items: flex-end; padding-top: 12px; border-top: 1px solid var(--klook-border); }
        .fth-card-price-label { font-size: 11px; color: var(--klook-text-light); }
        .fth-card-price { font-size: 20px; font-weight: 700; color: var(--klook-primary); }
        .fth-card-btn { padding: 8px 16px; background: var(--klook-primary); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; }
        .fth-card-btn:hover { background: var(--klook-primary-dark); color: #fff; text-decoration: none; }
        .fth-view-all { text-align: center; margin-top: 32px; }
        .fth-view-all-btn { display: inline-flex; align-items: center; gap: 8px; padding: 14px 40px; background: #fff; color: var(--klook-primary); border: 2px solid var(--klook-primary); border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none; }
        .fth-view-all-btn:hover { background: var(--klook-primary); color: #fff; text-decoration: none; }
        .fth-cta-banner { background: linear-gradient(135deg, var(--klook-primary), var(--klook-primary-dark)); border-radius: 16px; padding: 48px; text-align: center; color: #fff; margin-top: 48px; }
        .fth-cta-banner h2 { font-size: 28px; margin-bottom: 12px; }
        .fth-cta-banner p { font-size: 16px; opacity: 0.9; margin-bottom: 24px; }
        .fth-btn { display: inline-block; padding: 14px 40px; background: #fff; color: var(--klook-primary); border-radius: 8px; font-weight: 700; font-size: 16px; text-decoration: none; }
        .fth-btn:hover { text-decoration: none; opacity: 0.95; }
    </style>
    
    <!-- FTH Container -->
    <div class="fth-container">
    
    <section class="fth-hero">
        <div class="fth-hero-bg" style="background-image: url('<?php echo esc_url($hero_image); ?>');"></div>
        <div class="fth-hero-overlay"></div>
        <div class="fth-hero-content">
            <h1 class="fth-hero-title"><?php the_title(); ?></h1>
            <p class="fth-hero-subtitle"><?php if ($country_name) echo esc_html($country_name) . ' &bull; '; ?><?php echo esc_html($activity_count); ?> activities available</p>
            <form class="fth-search-box" action="<?php echo esc_url(get_permalink()); ?>" method="get">
                <?php if ($city_term): ?><input type="hidden" name="fth_city" value="<?php echo esc_attr($city_term->slug); ?>"><?php endif; ?>
                <input type="text" name="fth_search" class="fth-search-input" placeholder="Search activities in <?php the_title_attribute(); ?>...">
                <button type="submit" class="fth-search-btn">🔍 Search</button>
            </form>
        </div>
    </section>
    
    <main class="fth-main">
        <nav class="fth-breadcrumb">
            <a href="<?php echo home_url(); ?>">Home</a><span>/</span>
            <a href="<?php echo home_url('/things-to-do/'); ?>">Things to Do</a>
            <?php if ($country_term): ?><span>/</span><a href="<?php echo get_term_link($country_term); ?>"><?php echo esc_html($country_name); ?></a><?php endif; ?>
            <span>/</span><strong><?php the_title(); ?></strong>
        </nav>
        
        <?php if ($seo_intro || get_the_content()): ?>
        <div class="fth-intro">
            <h2>About <?php the_title(); ?></h2>
            <?php if ($seo_intro): ?><p><?php echo esc_html($seo_intro); ?></p><?php endif; ?>
            <?php if (get_the_content()): ?><div style="margin-top: 16px;"><?php the_content(); ?></div><?php endif; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($categories) && $city_term): ?>
        <div class="fth-categories-scroll">
            <?php foreach (array_slice($categories, 0, 10) as $cat): $icon = get_term_meta($cat->term_id, 'fth_icon', true); ?>
            <a href="<?php echo add_query_arg('fth_city', $city_term->slug, get_term_link($cat)); ?>" class="fth-category-pill"><?php echo esc_html(FTH_Templates::get_category_emoji($cat)); ?> <?php echo esc_html($cat->name); ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($activities && $activities->have_posts()): ?>
        <section>
            <h2 class="fth-section-title">🔥 Top Things to Do in <?php the_title(); ?></h2>
            <div class="fth-grid">
                <?php while ($activities->have_posts()): $activities->the_post();
                    $card_id = get_the_ID();
                    $card_price = get_post_meta($card_id, '_fth_price', true);
                    $card_rating = get_post_meta($card_id, '_fth_rating', true);
                    $card_reviews = get_post_meta($card_id, '_fth_review_count', true);
                    $card_image = get_post_meta($card_id, '_fth_external_image', true);
                    $card_bestseller = get_post_meta($card_id, '_fth_is_bestseller', true);
                    $card_affiliate = get_post_meta($card_id, '_fth_affiliate_link', true);
                    if (!$card_image && has_post_thumbnail($card_id)) $card_image = get_the_post_thumbnail_url($card_id, 'medium');
                    if (!$card_image) $card_image = 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=400';
                ?>
                <article class="fth-card">
                    <a href="<?php the_permalink(); ?>" class="fth-card-image">
                        <img src="<?php echo esc_url($card_image); ?>" alt="<?php the_title_attribute(); ?>">
                        <?php if ($card_bestseller === '1'): ?><span class="fth-card-badge">Bestseller</span><?php endif; ?>
                    </a>
                    <div class="fth-card-content">
                        <h3 class="fth-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                        <?php if ($card_rating): ?><div class="fth-card-meta"><span>⭐ <?php echo number_format($card_rating, 1); ?></span><?php if ($card_reviews): ?><span class="fth-card-reviews">(<?php echo number_format($card_reviews); ?>)</span><?php endif; ?></div><?php endif; ?>
                        <div class="fth-card-footer">
                            <div><div class="fth-card-price-label">From</div><div class="fth-card-price"><?php echo $card_price ? esc_html($symbol . number_format($card_price, 2)) : 'TBD'; ?></div></div>
                            <a href="<?php echo $card_affiliate ?: get_permalink(); ?>" class="fth-card-btn" <?php echo $card_affiliate ? 'target="_blank"' : ''; ?>>Book</a>
                        </div>
                    </div>
                </article>
                <?php endwhile; wp_reset_postdata(); ?>
            </div>
            <?php if ($city_term): ?><div class="fth-view-all"><a href="<?php echo get_term_link($city_term); ?>" class="fth-view-all-btn">View All <?php echo esc_html($activity_count); ?> Activities →</a></div><?php endif; ?>
        </section>
        <?php endif; ?>
        
        <?php if ($deeplink): ?>
        <div class="fth-cta-banner">
            <h2>Explore All <?php the_title(); ?> Experiences</h2>
            <p>Discover more tours, tickets, and activities</p>
            <a href="<?php echo esc_url($deeplink); ?>" class="fth-btn" target="_blank" rel="noopener noreferrer">View All Activities ↗</a>
        </div>
        <?php endif; ?>
    </main>
    
    </div><!-- .fth-container -->
    
    <?php echo FTH_Templates::render_seo_footer('activities'); ?>
<?php get_footer(); ?>
