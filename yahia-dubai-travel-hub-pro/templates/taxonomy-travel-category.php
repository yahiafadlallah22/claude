<?php
/**
 * Template: Category Page - Klook Style
 * Uses theme header
 */

if (!defined('ABSPATH')) {
    exit;
}

$term = get_queried_object();
$primary_color = Flavor_Travel_Hub::get_primary_color();
$icon = get_term_meta($term->term_id, 'fth_icon', true);
$color = get_term_meta($term->term_id, 'fth_color', true) ?: $primary_color;
$hero_image = get_term_meta($term->term_id, 'fth_hero_image', true);
if (!$hero_image) $hero_image = 'https://images.unsplash.com/photo-1507608616759-54f48f0af0ee?w=1920';

$paged = get_query_var('paged') ? get_query_var('paged') : 1;
$activities = FTH_Search::search_activities(array('category' => $term->slug, 'per_page' => 12, 'paged' => $paged));
$cities = FTH_Taxonomies::get_cities(array('hide_empty' => true));

$total = new WP_Query(array('post_type' => 'travel_activity', 'posts_per_page' => -1, 'fields' => 'ids', 'tax_query' => array(array('taxonomy' => 'travel_category', 'field' => 'term_id', 'terms' => $term->term_id))));
$activity_count = $total->found_posts;

$site_name = get_bloginfo('name');

$currency = get_option('fth_default_currency', 'USD');
$currency_symbols = array('USD' => '$', 'AED' => 'AED ', 'EUR' => '€', 'GBP' => '£');
$symbol = isset($currency_symbols[$currency]) ? $currency_symbols[$currency] : $currency . ' ';
$current_city = isset($_GET['fth_city']) ? sanitize_text_field($_GET['fth_city']) : '';

// Use theme header
get_header();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .fth-container *, .fth-container *::before, .fth-container *::after { box-sizing: border-box; }
    :root { --klook-primary: <?php echo esc_attr($primary_color); ?>; --klook-category: <?php echo esc_attr($color); ?>; --klook-text: #1a1a1a; --klook-text-secondary: #666; --klook-text-light: #999; --klook-bg: #fff; --klook-bg-gray: #f5f5f5; --klook-border: #e8e8e8; --klook-star: #ff9800; }
    .fth-container { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif; font-size: 14px; line-height: 1.5; color: var(--klook-text); background: var(--klook-bg); }
    .fth-container a { color: var(--klook-primary); text-decoration: none; }
    .fth-hero { position: relative; height: 350px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .fth-hero-bg { position: absolute; inset: 0; background-size: cover; background-position: center; }
    .fth-hero-overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.3) 0%, rgba(0,0,0,0.6) 100%); }
    .fth-hero-content { position: relative; z-index: 10; text-align: center; color: #fff; padding: 20px; }
    .fth-hero-icon { width: 80px; height: 80px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; font-size: 36px; color: var(--klook-category); }
    .fth-hero-title { font-size: 48px; font-weight: 800; margin-bottom: 8px; text-shadow: 0 2px 8px rgba(0,0,0,0.3); color: #fff; }
    .fth-hero-subtitle { font-size: 18px; opacity: 0.95; color: #fff; }
    .fth-search-box { background: #fff; border-radius: 8px; padding: 8px; display: flex; gap: 8px; max-width: 600px; margin: 24px auto 0; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
    .fth-search-input { flex: 1; padding: 12px 16px; border: none; font-size: 15px; background: var(--klook-bg-gray); border-radius: 6px; }
    .fth-search-input:focus { outline: none; }
    .fth-search-select { padding: 12px 16px; border: none; font-size: 14px; background: var(--klook-bg-gray); border-radius: 6px; min-width: 150px; }
    .fth-search-btn { padding: 12px 28px; background: var(--klook-primary); color: #fff; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; }
    .fth-main { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
    .fth-breadcrumb { font-size: 13px; color: var(--klook-text-secondary); margin-bottom: 32px; }
    .fth-breadcrumb a { color: var(--klook-text-secondary); }
    .fth-section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .fth-section-title { font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 12px; }
    .fth-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; }
    @media (max-width: 1024px) { .fth-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) { .fth-grid { grid-template-columns: repeat(2, 1fr); } .fth-hero-title { font-size: 32px; } }
    @media (max-width: 480px) { .fth-grid { grid-template-columns: 1fr; } }
    .fth-card { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.08); transition: transform 0.2s, box-shadow 0.2s; }
    .fth-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); }
    .fth-card-image { position: relative; aspect-ratio: 4/3; overflow: hidden; background: var(--klook-bg-gray); }
    .fth-card-image img { width: 100%; height: 100%; object-fit: cover; }
    .fth-card-content { padding: 16px; }
    .fth-card-title { font-size: 15px; font-weight: 600; margin-bottom: 8px; line-height: 1.4; }
    .fth-card-title a { color: inherit; text-decoration: none; }
    .fth-card-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; font-size: 13px; }
    .fth-card-rating i { color: var(--klook-star); }
    .fth-card-footer { display: flex; justify-content: space-between; align-items: flex-end; padding-top: 12px; border-top: 1px solid var(--klook-border); }
    .fth-card-price-from { font-size: 11px; color: var(--klook-text-light); }
    .fth-card-price { font-size: 20px; font-weight: 700; color: var(--klook-primary); }
    .fth-card-btn { padding: 8px 16px; background: var(--klook-primary); color: #fff; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; }
    .fth-no-results { text-align: center; padding: 60px 20px; }
    .fth-no-results i { font-size: 48px; color: var(--klook-border); margin-bottom: 16px; }
</style>

<!-- FTH Container -->
<div class="fth-container">

<section class="fth-hero">
    <div class="fth-hero-bg" style="background-image: url('<?php echo esc_url($hero_image); ?>');"></div>
    <div class="fth-hero-overlay"></div>
    <div class="fth-hero-content">
        <?php
        $display_icon = $icon ?: FTH_Templates::get_category_emoji($term);
        if ($display_icon):
            if (strpos($display_icon, 'fa') === 0): ?>
            <div class="fth-hero-icon"><i class="<?php echo esc_attr($display_icon); ?>"></i></div>
            <?php else: ?>
            <div class="fth-hero-icon" style="font-size:42px;line-height:1;background:transparent;"><?php echo esc_html($display_icon); ?></div>
            <?php endif;
        endif; ?>
        <h1 class="fth-hero-title"><?php echo esc_html($term->name); ?></h1>
        <p class="fth-hero-subtitle"><?php echo esc_html($activity_count); ?> tours & activities</p>
        <form class="fth-search-box" action="<?php echo home_url('/things-to-do/'); ?>" method="get">
            <input type="hidden" name="fth_category" value="<?php echo esc_attr($term->slug); ?>">
            <input type="text" name="fth_search" class="fth-search-input" placeholder="Search <?php echo esc_attr(strtolower($term->name)); ?> activities...">
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
        <a href="<?php echo home_url('/things-to-do/'); ?>">Things to Do</a> / 
        <strong><?php echo esc_html($term->name); ?></strong>
    </nav>
    
    <div class="fth-section-header">
        <h2 class="fth-section-title"><i class="fas fa-fire" style="color: var(--klook-category);"></i> <?php echo esc_html($term->name); ?> Activities</h2>
        <span><?php echo esc_html($activity_count); ?> results</span>
    </div>
    
    <?php if ($activities->have_posts()): ?>
    <div class="fth-grid">
        <?php while ($activities->have_posts()): $activities->the_post(); 
            $aid = get_the_ID();
            $price = get_post_meta($aid, '_fth_price', true);
            $rating = get_post_meta($aid, '_fth_rating', true);
            $reviews = get_post_meta($aid, '_fth_review_count', true);
            $external_img = get_post_meta($aid, '_fth_external_image', true);
            $affiliate = get_post_meta($aid, '_fth_affiliate_link', true);
            $img = has_post_thumbnail() ? get_the_post_thumbnail_url(null, 'medium_large') : ($external_img ?: 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=600');
        ?>
        <article class="fth-card">
            <div class="fth-card-image">
                <a href="<?php the_permalink(); ?>"><img src="<?php echo esc_url($img); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy"></a>
            </div>
            <div class="fth-card-content">
                <h3 class="fth-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <div class="fth-card-meta">
                    <?php if ($rating): ?><span class="fth-card-rating"><i class="fas fa-star"></i> <?php echo esc_html($rating); ?></span><?php endif; ?>
                    <?php if ($reviews): ?><span class="fth-card-reviews">(<?php echo number_format($reviews); ?>)</span><?php endif; ?>
                </div>
                <div class="fth-card-footer">
                    <div>
                        <?php if ($price): ?>
                        <div class="fth-card-price-from">From</div>
                        <div class="fth-card-price"><?php echo esc_html($symbol . number_format($price)); ?></div>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo $affiliate ?: get_permalink(); ?>" class="fth-card-btn" <?php echo $affiliate ? 'target="_blank"' : ''; ?>>Book</a>
                </div>
            </div>
        </article>
        <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php else: ?>
    <div class="fth-no-results">
        <i class="fas fa-search"></i>
        <h3>No activities found</h3>
        <p>Try a different category or check back later</p>
    </div>
    <?php endif; ?>
</main>

</div><!-- .fth-container -->

<?php get_footer(); ?>
