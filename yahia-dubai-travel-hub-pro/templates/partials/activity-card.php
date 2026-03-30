<?php
/**
 * Partial: Activity Card - Klook Style
 * Used in grids throughout the plugin
 */

if (!defined('ABSPATH')) {
    exit;
}

$card_id = get_the_ID();
$card_price = get_post_meta($card_id, '_fth_price', true);
$card_rating = get_post_meta($card_id, '_fth_rating', true);
$card_reviews = get_post_meta($card_id, '_fth_review_count', true);
$card_image = get_post_meta($card_id, '_fth_external_image', true);
$card_bestseller = get_post_meta($card_id, '_fth_is_bestseller', true);
$card_duration = get_post_meta($card_id, '_fth_duration', true);
$card_cities = wp_get_post_terms($card_id, 'travel_city');
$card_city = !empty($card_cities) ? $card_cities[0]->name : '';
$card_affiliate = get_post_meta($card_id, '_fth_affiliate_link', true);

if (!$card_image && has_post_thumbnail($card_id)) {
    $card_image = get_the_post_thumbnail_url($card_id, 'medium_large');
}
if (!$card_image) {
    $card_image = 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=400';
}
// Proxy Klook CDN URLs so they display without hotlink blocks
if (class_exists('Flavor_Travel_Hub')) {
    $card_image = Flavor_Travel_Hub::fth_img_url($card_image);
}

$currency = get_option('fth_default_currency', 'USD');
$currency_symbols = array('USD' => '$', 'AED' => 'AED ', 'EUR' => '€', 'GBP' => '£', 'SAR' => 'SAR ', 'QAR' => 'QAR ');
$symbol = isset($currency_symbols[$currency]) ? $currency_symbols[$currency] : $currency . ' ';
?>

<article class="fth-card">
    <a href="<?php the_permalink(); ?>" class="fth-card-link">
        <div class="fth-card-image">
            <img src="<?php echo esc_url($card_image); ?>" alt="<?php the_title_attribute(); ?>" loading="lazy">
            <?php if ($card_bestseller === '1'): ?>
                <span class="fth-card-badge">Bestseller</span>
            <?php endif; ?>
        </div>
    </a>
    
    <div class="fth-card-content">
        <h3 class="fth-card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>
        
        <?php if ($card_rating || $card_city): ?>
        <div class="fth-card-meta">
            <?php if ($card_rating): ?>
            <div class="fth-card-rating">
                ⭐
                <strong><?php echo number_format((float)$card_rating, 1); ?></strong>
                <?php if ($card_reviews): ?>
                    <span class="fth-card-reviews">(<?php echo number_format((int)$card_reviews); ?>)</span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="fth-card-features">
            <span class="fth-card-feature">
                ✅ Instant Confirmation
            </span>
            <?php if ($card_duration): ?>
            <span class="fth-card-feature">
                ⏱ <?php echo esc_html($card_duration); ?>
            </span>
            <?php endif; ?>
        </div>
        
        <div class="fth-card-footer">
            <div>
                <div class="fth-card-price-label">From</div>
                <div class="fth-card-price">
                    <?php echo $card_price ? esc_html($symbol . number_format((float)$card_price, 2)) : 'TBD'; ?>
                </div>
            </div>
            <a href="<?php echo $card_affiliate ? esc_url($card_affiliate) : get_permalink(); ?>" 
               class="fth-card-btn" 
               <?php echo $card_affiliate ? 'target="_blank" rel="noopener noreferrer"' : ''; ?>>
                Book
            </a>
        </div>
    </div>
</article>
