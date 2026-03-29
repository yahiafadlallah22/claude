<?php
/**
 * Template: Single Activity – Klook-style v1.7
 * Promo Yahia Fadlallah · ACTIVATE DISCOUNT CTA · FAQ · Gallery
 */
if (!defined('ABSPATH')) { exit; }

$post_id        = get_the_ID();
$primary        = Flavor_Travel_Hub::get_primary_color();
$secondary      = Flavor_Travel_Hub::get_secondary_color();
$promo_text     = Flavor_Travel_Hub::get_promo_text();
$cta_text       = Flavor_Travel_Hub::get_cta_text();

$price          = get_post_meta($post_id, '_fth_price', true);
$orig_price     = get_post_meta($post_id, '_fth_original_price', true);
$currency       = get_post_meta($post_id, '_fth_currency', true) ?: 'USD';
$rating         = get_post_meta($post_id, '_fth_rating', true);
$review_count   = get_post_meta($post_id, '_fth_review_count', true);
$duration       = get_post_meta($post_id, '_fth_duration', true);
$meeting_point  = get_post_meta($post_id, '_fth_meeting_point', true);
$inclusions     = get_post_meta($post_id, '_fth_inclusions', true);
$exclusions     = get_post_meta($post_id, '_fth_exclusions', true);
$itinerary      = get_post_meta($post_id, '_fth_itinerary', true);
$highlights     = get_post_meta($post_id, '_fth_highlights', true);
$promo          = get_post_meta($post_id, '_fth_promo', true) ?: $promo_text;
$faq_raw        = get_post_meta($post_id, '_fth_faq', true);
$affiliate_link = get_post_meta($post_id, '_fth_affiliate_link', true);

$cities         = wp_get_post_terms($post_id, 'travel_city');
$countries      = wp_get_post_terms($post_id, 'travel_country');
$city_name      = !empty($cities)    ? $cities[0]->name    : '';
$city_link      = !empty($cities)    ? get_term_link($cities[0])    : '';
$country_name   = !empty($countries) ? $countries[0]->name : '';
$country_link   = !empty($countries) ? get_term_link($countries[0]) : '';

$sym_map = array('USD'=>'$','AED'=>'AED ','EUR'=>'€','GBP'=>'£','SAR'=>'SAR ','QAR'=>'QAR ');
$sym     = isset($sym_map[$currency]) ? $sym_map[$currency] : $currency . ' ';

// Gallery – proxy Klook CDN URLs so they display in the browser
$main_img = has_post_thumbnail($post_id)
    ? get_the_post_thumbnail_url($post_id, 'full')
    : get_post_meta($post_id, '_fth_external_image', true);
$main_img = Flavor_Travel_Hub::fth_img_url($main_img);
$gallery  = array();
$gids     = array_filter(array_map('intval', explode(',', (string) get_post_meta($post_id, '_fth_gallery', true))));
foreach ($gids as $gid) { $u = wp_get_attachment_image_url($gid, 'large'); if ($u) $gallery[] = $u; }
$gext = array_filter(array_map('trim', explode(',', (string) get_post_meta($post_id, '_fth_external_gallery', true))));
foreach ($gext as $img) {
    if ($img && !in_array($img, $gallery, true) && $img !== $main_img) {
        $gallery[] = Flavor_Travel_Hub::fth_img_url($img);
    }
}
if ($main_img) array_unshift($gallery, $main_img);
$gallery = array_values(array_unique(array_filter($gallery)));

// Discount %
$discount_pct = 0;
if ($orig_price && $price && (float)$orig_price > (float)$price) {
    $discount_pct = round((1 - (float)$price / (float)$orig_price) * 100);
}

// Related
$related = FTH_Search::search_activities(array('city' => !empty($cities) ? $cities[0]->slug : '', 'per_page' => 4, 'paged' => 1));

get_header();
?>
<style>
/* ── WP Residence isolation ──────────────────────────────── */
html,body,#main_wrapper,.master_header,.master_wrapper,.content_wrapper,.container,.site-content{overflow:visible!important;height:auto!important;max-height:none!important;position:static!important}
body.single-travel_activity .widget-area,body.single-travel_activity .sidebar,body.single-travel_activity .right_sidebar,body.single-travel_activity .page_header,body.single-travel_activity .title_container,body.single-travel_activity .wpestate_header_image,body.single-travel_activity .property_breadcrumbs{display:none!important}
/* ── Base ─────────────────────────────────────────────────── */
.kl,.kl *{box-sizing:border-box}
.kl{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1a1a1a;background:#f5f5f5;position:relative;z-index:5}
.kl a{text-decoration:none;color:<?php echo esc_attr($primary); ?>}
.kl img{max-width:100%;height:auto;display:block}
/* Breadcrumb */
.kl-bc{background:#fff;border-bottom:1px solid #eee;font-size:13px;color:#666}
.kl-bc-in{max-width:1280px;margin:0 auto;padding:10px 20px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.kl-bc a{color:#666}
.kl-bc span{color:#999}
/* Layout */
.kl-main{max-width:1280px;margin:0 auto;padding:20px 20px 48px;display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:24px}
/* Left column */
.kl-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.07)}
/* Gallery */
.kl-gallery-main{position:relative;border-radius:12px;overflow:hidden;background:#e8e8e8;aspect-ratio:16/9;max-height:520px;cursor:pointer}
.kl-gallery-main img{width:100%;height:100%;object-fit:cover;transition:opacity .3s}
.kl-discount-badge{position:absolute;top:14px;left:14px;background:#e44e4e;color:#fff;font-size:13px;font-weight:800;padding:5px 10px;border-radius:6px}
.kl-thumbs{display:flex;gap:8px;overflow-x:auto;padding:10px 0 2px;scrollbar-width:thin}
.kl-thumb{flex:0 0 90px;height:64px;border-radius:8px;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:border-color .2s}
.kl-thumb:hover,.kl-thumb.active{border-color:<?php echo esc_attr($primary); ?>}
.kl-thumb img{width:100%;height:100%;object-fit:cover}
/* Title & meta */
.kl-title{margin:20px 0 14px;font-size:28px;font-weight:800;line-height:1.2;color:#1a1a1a}
.kl-meta{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 18px;padding:0;list-style:none}
.kl-meta li{display:flex;align-items:center;gap:6px;padding:7px 12px;border-radius:999px;background:#f5f5f5;font-size:13px;font-weight:600;color:#333}
/* Promo banner */
.kl-promo{display:flex;align-items:center;gap:12px;background:linear-gradient(135deg,<?php echo esc_attr($primary); ?>,<?php echo esc_attr($secondary); ?>);color:#fff;border-radius:12px;padding:14px 18px;margin:0 0 20px;font-weight:800;font-size:15px}
.kl-promo svg{flex:0 0 22px;opacity:.9}
/* Content sections */
.kl-section{background:#fff;border-radius:16px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-top:16px}
.kl-sec-title{margin:0 0 14px;font-size:18px;font-weight:800;color:#1a1a1a;display:flex;align-items:center;gap:8px}
.kl-sec-title .icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:<?php echo esc_attr($primary); ?>22;color:<?php echo esc_attr($primary); ?>;font-size:14px}
.kl-content{color:#444;line-height:1.8;font-size:15px}
.kl-list{list-style:none;padding:0;margin:0;display:grid;gap:10px}
.kl-list li{display:flex;gap:10px;align-items:flex-start;padding:10px;border-radius:8px;background:#fafafa;font-size:14px;color:#333}
.kl-list li .check{flex:0 0 20px;height:20px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;background:<?php echo esc_attr($primary); ?>22;color:<?php echo esc_attr($primary); ?>}
.kl-list li .cross{background:#fee2e2;color:#dc2626}
/* FAQ */
.kl-faq-item{border:1px solid #eee;border-radius:10px;margin-bottom:8px;overflow:hidden}
.kl-faq-q{padding:14px 16px;font-weight:700;cursor:pointer;display:flex;justify-content:space-between;align-items:center;background:#fafafa;color:#1a1a1a;font-size:14px}
.kl-faq-q:hover{background:#f0f7ff}
.kl-faq-a{padding:14px 16px;font-size:14px;color:#555;line-height:1.7;display:none;border-top:1px solid #eee;background:#fff}
.kl-faq-item.open .kl-faq-a{display:block}
.kl-faq-item.open .kl-faq-q{background:#f0f7ff}
/* Right sidebar */
.kl-sidebar{position:sticky;top:90px;height:fit-content}
.kl-book-box{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.07)}
.kl-price-from{font-size:13px;color:#666;margin-bottom:4px}
.kl-price-orig{font-size:14px;color:#999;text-decoration:line-through;margin-bottom:2px}
.kl-price-curr{font-size:36px;font-weight:900;color:<?php echo esc_attr($primary); ?>;margin-bottom:4px}
.kl-price-note{font-size:12px;color:#999;margin-bottom:18px}
.kl-cta{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:16px;border-radius:12px;background:<?php echo esc_attr($primary); ?>;color:#fff!important;font-weight:800;font-size:16px;letter-spacing:.3px;margin-bottom:16px;transition:opacity .2s;cursor:pointer;border:none}
.kl-cta:hover{opacity:.88}
.kl-trust{display:grid;gap:10px;margin-top:16px}
.kl-trust-item{display:flex;gap:10px;align-items:flex-start;font-size:13px;color:#555}
.kl-trust-icon{font-size:18px;flex:0 0 24px}
/* Related */
.kl-related-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
/* Mobile sticky CTA bar */
.kl-mobile-cta{display:none}
@media(max-width:1100px){
  .kl-main{grid-template-columns:1fr}.kl-sidebar{position:static}.kl-related-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
  .kl-mobile-cta{display:flex;position:sticky;top:0;z-index:999;width:100%;background:<?php echo esc_attr($primary); ?>;color:#fff;align-items:center;justify-content:space-between;padding:12px 16px;gap:10px;box-shadow:0 2px 12px rgba(0,0,0,.18)}
  .kl-mobile-cta-price{font-size:18px;font-weight:900;line-height:1}
  .kl-mobile-cta-price small{display:block;font-size:11px;font-weight:400;opacity:.8}
  .kl-mobile-cta-btn{flex-shrink:0;background:#fff;color:<?php echo esc_attr($primary); ?>;font-weight:800;font-size:14px;padding:10px 18px;border-radius:8px;text-decoration:none;white-space:nowrap}
  .kl-mobile-cta-btn:hover{opacity:.9}
}
@media(max-width:640px){.kl-title{font-size:22px}.kl-related-grid{grid-template-columns:1fr}}
/* Yahia promo box – mobile vs desktop visibility */
.kl-yahia-mobile{display:none}
.kl-yahia-desktop{display:block}
@media(max-width:1100px){
  .kl-yahia-mobile{display:block;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:16px;text-align:center;margin-top:16px}
  .kl-yahia-desktop{display:none}
}
</style>

<div class="kl">
  <!-- Mobile sticky CTA bar (hidden on desktop via CSS) -->
  <div class="kl-mobile-cta">
    <div class="kl-mobile-cta-price">
      <small>From</small>
      <?php if ($price): ?>
        <?php echo esc_html($sym . number_format((float)$price, 0)); ?>
      <?php else: ?>
        <span style="font-size:15px;">Check price</span>
      <?php endif; ?>
    </div>
    <a class="kl-mobile-cta-btn" href="<?php echo esc_url($affiliate_link ?: '#'); ?>" target="_blank" rel="noopener noreferrer">
      🎟 <?php echo esc_html($cta_text); ?>
    </a>
  </div>

  <!-- Breadcrumb -->
  <div class="kl-bc"><div class="kl-bc-in">
    <a href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">Things to do</a>
    <span>›</span>
    <?php if ($country_name && $country_link): ?><a href="<?php echo esc_url($country_link); ?>"><?php echo esc_html($country_name); ?></a><span>›</span><?php endif; ?>
    <?php if ($city_name && $city_link): ?><a href="<?php echo esc_url($city_link); ?>"><?php echo esc_html($city_name); ?></a><span>›</span><?php endif; ?>
    <span><?php the_title(); ?></span>
  </div></div>

  <div class="kl-main">
    <!-- ── Left ───────────────────────────────────────────── -->
    <div>
      <div class="kl-card">
        <!-- Gallery -->
        <?php if (!empty($gallery)): ?>
        <div class="kl-gallery-main" id="klGalleryMain">
          <img id="klMainImg" src="<?php echo esc_url($gallery[0]); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="eager">
          <?php if ($discount_pct > 0): ?><div class="kl-discount-badge">-<?php echo $discount_pct; ?>%</div><?php endif; ?>
        </div>
        <?php if (count($gallery) > 1): ?>
        <div class="kl-thumbs">
          <?php foreach (array_slice($gallery, 0, 8) as $i => $img): ?>
          <div class="kl-thumb <?php echo $i === 0 ? 'active' : ''; ?>" onclick="klSetImg(this,'<?php echo esc_js($img); ?>')">
            <img src="<?php echo esc_url($img); ?>" alt="" loading="lazy">
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <!-- Promo Yahia Fadlallah -->
        <div class="kl-promo">
          <svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          <span><?php echo esc_html($promo); ?></span>
        </div>

        <!-- Title + meta -->
        <h1 class="kl-title"><?php the_title(); ?></h1>
        <ul class="kl-meta">
          <?php if ($city_name || $country_name): ?>
          <li>📍 <?php echo esc_html(trim($city_name . ($country_name ? ', ' . $country_name : ''))); ?></li>
          <?php endif; ?>
          <?php if ($duration): ?><li>⏱ <?php echo esc_html($duration); ?></li><?php endif; ?>
          <?php if ($rating): ?>
          <li>⭐ <?php echo esc_html(number_format((float)$rating, 1)); ?>
            <?php if ($review_count): ?>&nbsp;· <?php echo esc_html(number_format((int)$review_count)); ?> reviews<?php endif; ?>
          </li>
          <?php endif; ?>
          <li>✅ Instant confirmation</li>
        </ul>
      </div>

      <!-- Overview -->
      <div class="kl-section">
        <h2 class="kl-sec-title"><span class="icon">ℹ</span> About this experience</h2>
        <div class="kl-content"><?php the_content(); ?></div>
        <?php if ($meeting_point): ?><p style="margin-top:14px;font-size:14px;"><strong>Meeting point:</strong> <?php echo esc_html($meeting_point); ?></p><?php endif; ?>
      </div>

      <!-- Yahia promo box – shown on mobile right after "About this experience" -->
      <div class="kl-yahia-mobile">
        <img src="https://yahiadubai.com/wp-content/uploads/2026/03/New-Project-4.png" alt="Yahia Fadlallah" style="max-width:100px;height:auto;display:block;margin:0 auto 10px;border-radius:8px;">
        <div style="font-size:14px;color:#92400e;font-weight:700;">🤝 <?php echo esc_html($promo_text); ?></div>
        <?php if ($affiliate_link): ?>
        <a href="<?php echo esc_url($affiliate_link); ?>" target="_blank" rel="noopener noreferrer" style="display:inline-block;margin-top:10px;background:<?php echo esc_attr($primary); ?>;color:#fff;font-weight:800;font-size:14px;padding:10px 22px;border-radius:8px;text-decoration:none;">🎟 <?php echo esc_html($cta_text); ?></a>
        <?php endif; ?>
      </div>

      <!-- Highlights -->
      <?php if ($highlights):
        $hl_lines = array_filter(array_map('trim', explode("\n", $highlights))); ?>
      <div class="kl-section">
        <h2 class="kl-sec-title"><span class="icon">★</span> Highlights</h2>
        <ul class="kl-list">
          <?php foreach ($hl_lines as $line): ?>
          <li><span class="check">✓</span><span><?php echo esc_html($line); ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- Itinerary -->
      <?php if ($itinerary):
        $it_lines = array_filter(array_map('trim', explode("\n", $itinerary))); ?>
      <div class="kl-section">
        <h2 class="kl-sec-title"><span class="icon">🗺</span> Itinerary</h2>
        <ul class="kl-list">
          <?php foreach ($it_lines as $i => $line): ?>
          <li><span class="check"><?php echo $i + 1; ?></span><span><?php echo esc_html($line); ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- Inclusions / Exclusions -->
      <?php if ($inclusions || $exclusions): ?>
      <div class="kl-section">
        <?php if ($inclusions):
          $inc_lines = array_filter(array_map('trim', explode("\n", $inclusions))); ?>
        <h2 class="kl-sec-title"><span class="icon">✓</span> What's included</h2>
        <ul class="kl-list" style="margin-bottom:<?php echo $exclusions ? '18px' : '0'; ?>">
          <?php foreach ($inc_lines as $line): ?>
          <li><span class="check">✓</span><span><?php echo esc_html($line); ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
        <?php if ($exclusions):
          $exc_lines = array_filter(array_map('trim', explode("\n", $exclusions))); ?>
        <h2 class="kl-sec-title" style="margin-top:18px;"><span class="icon">✕</span> Not included</h2>
        <ul class="kl-list">
          <?php foreach ($exc_lines as $line): ?>
          <li><span class="check cross">✕</span><span><?php echo esc_html($line); ?></span></li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- FAQ -->
      <?php if ($faq_raw):
        $faq_blocks = array_filter(array_map('trim', explode("\n\n", $faq_raw))); ?>
      <div class="kl-section">
        <h2 class="kl-sec-title"><span class="icon">❓</span> Frequently asked questions</h2>
        <?php foreach ($faq_blocks as $block):
          if (preg_match('/^Q:\s*(.+)/u', $block, $mq) && preg_match('/\nA:\s*(.+)/us', $block, $ma)): ?>
          <div class="kl-faq-item">
            <div class="kl-faq-q" onclick="this.parentElement.classList.toggle('open')">
              <span><?php echo esc_html($mq[1]); ?></span>
              <span class="kl-faq-arrow">▼</span>
            </div>
            <div class="kl-faq-a"><?php echo esc_html(trim($ma[1])); ?></div>
          </div>
          <?php endif; endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Related -->
      <?php if ($related->have_posts()): ?>
      <div class="kl-section">
        <h2 class="kl-sec-title"><span class="icon">✨</span> Similar experiences nearby</h2>
        <div class="kl-related-grid">
          <?php while ($related->have_posts()): $related->the_post();
            if (get_the_ID() === $post_id) continue;
            echo FTH_Templates::get_activity_card(get_the_ID());
          endwhile; wp_reset_postdata(); ?>
        </div>
      </div>
      <?php endif; ?>

      <?php echo FTH_Templates::render_seo_footer('activities'); ?>
    </div>

    <!-- ── Sidebar ─────────────────────────────────────────── -->
    <aside class="kl-sidebar">
      <div class="kl-book-box">
        <div class="kl-price-from">From</div>
        <?php if ($orig_price && (float)$orig_price > (float)$price): ?>
        <div class="kl-price-orig"><?php echo esc_html($sym . number_format((float)$orig_price, 2)); ?></div>
        <?php endif; ?>
        <?php if ($price): ?>
        <div class="kl-price-curr"><?php echo esc_html($sym . number_format((float)$price, 2)); ?></div>
        <?php else: ?>
        <div class="kl-price-curr" style="font-size:24px;">Check price</div>
        <?php endif; ?>
        <div class="kl-price-note">Per person · Instant confirmation</div>
        <a class="kl-cta" href="<?php echo esc_url($affiliate_link ?: '#'); ?>" target="_blank" rel="noopener noreferrer">
          🎟 <?php echo esc_html($cta_text); ?>
        </a>
        <div class="kl-yahia-desktop" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px;text-align:center;margin-bottom:16px;">
          <img src="https://yahiadubai.com/wp-content/uploads/2026/03/New-Project-4.png" alt="Yahia Fadlallah" style="max-width:120px;height:auto;display:block;margin:0 auto 8px;border-radius:8px;">
          <div style="font-size:13px;color:#92400e;font-weight:700;">🤝 <?php echo esc_html($promo_text); ?></div>
        </div>
        <div class="kl-trust">
          <div class="kl-trust-item"><span class="kl-trust-icon">🎟️</span><span>Tickets &amp; availability visible in real time</span></div>
          <div class="kl-trust-item"><span class="kl-trust-icon">📍</span><span>Meeting point and practical information</span></div>
          <div class="kl-trust-item"><span class="kl-trust-icon">🔒</span><span>Secure booking – refund available</span></div>
          <div class="kl-trust-item"><span class="kl-trust-icon">⚡</span><span>Instant access after payment</span></div>
        </div>
      </div>
    </aside>
  </div>
</div>

<script>
function klSetImg(thumb, src) {
  document.getElementById('klMainImg').src = src;
  document.querySelectorAll('.kl-thumb').forEach(function(t){ t.classList.remove('active'); });
  thumb.classList.add('active');
}
</script>

<?php get_footer(); ?>
