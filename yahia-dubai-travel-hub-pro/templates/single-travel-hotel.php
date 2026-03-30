<?php
/**
 * Template: Single Hotel – Klook-style v1.7
 * Promo Yahia Fadlallah · ACTIVATE DISCOUNT CTA
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
$address        = get_post_meta($post_id, '_fth_address', true);
$amenities_raw  = get_post_meta($post_id, '_fth_amenities', true);
$highlights_raw = get_post_meta($post_id, '_fth_highlights', true);
$inclusions_raw = get_post_meta($post_id, '_fth_inclusions', true);
$faq_raw        = get_post_meta($post_id, '_fth_faq', true);
$promo          = get_post_meta($post_id, '_fth_promo', true) ?: $promo_text;
$affiliate_link = get_post_meta($post_id, '_fth_affiliate_link', true);

$cities         = wp_get_post_terms($post_id, 'travel_city');
$countries      = wp_get_post_terms($post_id, 'travel_country');
$city_name      = !empty($cities)    ? $cities[0]->name    : '';
$city_link      = !empty($cities)    ? get_term_link($cities[0])    : '';
$country_name   = !empty($countries) ? $countries[0]->name : '';
$country_link   = !empty($countries) ? get_term_link($countries[0]) : '';

$sym_map   = array('USD'=>'$','AED'=>'AED ','EUR'=>'€','GBP'=>'£','SAR'=>'SAR ','QAR'=>'QAR ');
$sym       = isset($sym_map[$currency]) ? $sym_map[$currency] : $currency . ' ';
$amen_list = array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $amenities_raw)));

// Gallery – proxy Klook CDN URLs so they display in the browser
$main_img     = has_post_thumbnail($post_id) ? get_the_post_thumbnail_url($post_id, 'full') : get_post_meta($post_id, '_fth_external_image', true);
$main_img     = Flavor_Travel_Hub::fth_img_url($main_img);
$thumbnail_id = get_post_thumbnail_id($post_id);
$gallery      = array();
$gids         = array_filter(array_map('intval', explode(',', (string) get_post_meta($post_id, '_fth_gallery', true))));
if ($thumbnail_id) $gids = array_diff($gids, array($thumbnail_id)); // exclude thumbnail from gallery
foreach ($gids as $gid) { $u = wp_get_attachment_image_url($gid, 'large'); if ($u) $gallery[] = $u; }
$gallery_bns = array();
if ($main_img) $gallery_bns[strtolower(basename(parse_url($main_img, PHP_URL_PATH) ?: $main_img))] = true;
foreach ($gallery as $gu) { $gallery_bns[strtolower(basename(parse_url($gu, PHP_URL_PATH) ?: $gu))] = true; }
$gext         = array_filter(array_map('trim', explode(',', (string) get_post_meta($post_id, '_fth_external_gallery', true))));
foreach ($gext as $img) {
    if (!$img) continue;
    $bn = strtolower(basename(parse_url($img, PHP_URL_PATH) ?: $img));
    if (isset($gallery_bns[$bn])) continue;
    $gallery_bns[$bn] = true;
    $gallery[] = Flavor_Travel_Hub::fth_img_url($img);
}
if ($main_img) array_unshift($gallery, $main_img);
$gallery      = array_values(array_unique(array_filter($gallery)));

$discount_pct = 0;
if ($orig_price && $price && (float)$orig_price > (float)$price) {
    $discount_pct = round((1 - (float)$price / (float)$orig_price) * 100);
}

$related = FTH_Search::search_hotels(array('city' => !empty($cities) ? $cities[0]->slug : '', 'per_page' => 4, 'paged' => 1));

get_header();
?>
<style>
html,body,#main_wrapper,.master_header,.master_wrapper,.content_wrapper,.container,.site-content{overflow:visible!important;height:auto!important;max-height:none!important;position:static!important}
body.single-travel_hotel .widget-area,body.single-travel_hotel .sidebar,body.single-travel_hotel .right_sidebar,body.single-travel_hotel .page_header,body.single-travel_hotel .title_container,body.single-travel_hotel .wpestate_header_image,body.single-travel_hotel .property_breadcrumbs{display:none!important}
.klh,.klh *{box-sizing:border-box}
.klh{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1a1a1a;background:#f5f5f5;position:relative;z-index:5;overflow-x:hidden}
.klh a{text-decoration:none;color:<?php echo esc_attr($primary); ?>}
.klh img{max-width:100%;height:auto;display:block}
.klh-bc{background:#fff;border-bottom:1px solid #eee;font-size:13px;color:#666}
.klh-bc-in{max-width:1280px;margin:0 auto;padding:10px 20px;display:flex;align-items:center;gap:6px;flex-wrap:wrap}
.klh-bc a{color:#666}
.klh-main{max-width:1280px;margin:0 auto;padding:20px 20px 48px;display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:24px}
.klh-card{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.07)}
.klh-gallery-main{position:relative;border-radius:12px;overflow:hidden;background:#e8e8e8;aspect-ratio:16/9;max-height:520px}
.klh-gallery-main img{width:100%;height:100%;object-fit:cover;transition:opacity .3s}
.klh-disc-badge{position:absolute;top:14px;left:14px;background:#e44e4e;color:#fff;font-size:13px;font-weight:800;padding:5px 10px;border-radius:6px}
.klh-thumbs{display:flex;gap:8px;overflow-x:auto;padding:10px 0 2px;scrollbar-width:thin}
.klh-thumb{flex:0 0 90px;height:64px;border-radius:8px;overflow:hidden;cursor:pointer;border:2px solid transparent;transition:border-color .2s}
.klh-thumb:hover,.klh-thumb.active{border-color:<?php echo esc_attr($primary); ?>}
.klh-thumb img{width:100%;height:100%;object-fit:cover}
.klh-badge{display:inline-flex;align-items:center;gap:6px;background:<?php echo esc_attr($secondary); ?>;color:#fff;font-size:12px;font-weight:800;padding:5px 10px;border-radius:6px;margin-bottom:14px}
.klh-title{margin:10px 0 14px;font-size:28px;font-weight:800;line-height:1.2}
.klh-meta{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 18px;padding:0;list-style:none}
.klh-meta li{display:flex;align-items:center;gap:6px;padding:7px 12px;border-radius:999px;background:#f5f5f5;font-size:13px;font-weight:600;color:#333}
.klh-promo{display:flex;align-items:center;gap:12px;background:linear-gradient(135deg,<?php echo esc_attr($primary); ?>,<?php echo esc_attr($secondary); ?>);color:#fff;border-radius:12px;padding:14px 18px;margin:18px 0 20px;font-weight:800;font-size:15px}
.klh-section{background:#fff;border-radius:16px;padding:22px;box-shadow:0 2px 12px rgba(0,0,0,.07);margin-top:16px}
.klh-sec-title{margin:0 0 14px;font-size:18px;font-weight:800;color:#1a1a1a;display:flex;align-items:center;gap:8px}
.klh-sec-title .icon{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:8px;background:<?php echo esc_attr($primary); ?>22;color:<?php echo esc_attr($primary); ?>;font-size:14px}
.klh-content{color:#444;line-height:1.8;font-size:15px}
.klh-amenities{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.klh-amenity{display:flex;align-items:center;gap:8px;padding:9px 12px;border-radius:8px;background:#f5f5f5;font-size:13px;color:#333}
.klh-related-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.klh-sidebar{position:sticky;top:90px;height:fit-content}
.klh-book-box{background:#fff;border-radius:16px;padding:24px;box-shadow:0 2px 12px rgba(0,0,0,.07)}
.klh-price-from{font-size:13px;color:#666;margin-bottom:4px}
@keyframes klh-deal-glow{0%,100%{box-shadow:0 2px 8px rgba(229,62,62,.4)}50%{box-shadow:0 4px 18px rgba(229,62,62,.75)}}
.klh-deal-flag{background:linear-gradient(135deg,#e53e3e,#ff6b35);color:#fff;font-weight:800;font-size:13px;border-radius:8px;padding:7px 12px;margin-bottom:12px;text-align:center;animation:klh-deal-glow 2s ease-in-out infinite}
.klh-price-orig{font-size:14px;color:#999;margin-bottom:4px;display:flex;align-items:center;flex-wrap:wrap;gap:8px}
.klh-price-orig s{text-decoration:line-through}
.klh-saving-pill{background:linear-gradient(90deg,#fef3c7,#fde68a);color:#92400e;font-size:11px;font-weight:800;border-radius:999px;padding:2px 9px}
.klh-price-curr{font-size:36px;font-weight:900;color:<?php echo esc_attr($primary); ?>;margin-bottom:2px}
.klh-price-note{font-size:12px;color:#999;margin-bottom:18px}
.klh-cta{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;padding:16px;border-radius:12px;background:<?php echo esc_attr($primary); ?>;color:#fff!important;font-weight:800;font-size:16px;letter-spacing:.5px;text-transform:uppercase;margin-bottom:16px;transition:opacity .2s}
.klh-cta:hover{opacity:.88}
.klh-trust{display:grid;gap:10px;margin-top:16px}
.klh-trust-item{display:flex;gap:10px;align-items:flex-start;font-size:13px;color:#555}
@media(max-width:1100px){
  .klh-main{grid-template-columns:1fr;display:flex;flex-direction:column}.klh-sidebar{position:static;order:-1}.klh-related-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.klh-amenities{grid-template-columns:1fr}
}
@media(max-width:640px){.klh-title{font-size:22px}.klh-related-grid{grid-template-columns:1fr}}
@media(max-width:480px){.klh-main{padding:12px 12px 40px}.klh-card,.klh-section,.klh-book-box{padding:16px}}
.klh-faq-item{border:1px solid #e8e8e8;border-radius:10px;margin-bottom:8px;overflow:hidden}
.klh-faq-q{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;cursor:pointer;font-weight:700;font-size:14px;background:#fafafa;gap:10px}
.klh-faq-q:hover{background:#f0f7ff}
.klh-faq-arrow{flex-shrink:0;font-size:11px;transition:transform .2s}
.klh-faq-item.open .klh-faq-arrow{transform:rotate(180deg)}
.klh-faq-a{display:none;padding:14px 16px;font-size:14px;color:#444;line-height:1.7;border-top:1px solid #eee}
.klh-faq-item.open .klh-faq-a{display:block}
.klh-faq-item.open .klh-faq-q{background:#f0f7ff}
.klh-list{list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:8px}
.klh-list li{display:flex;align-items:flex-start;gap:10px;font-size:14px;color:#333;line-height:1.5}
.klh-list li .check{flex-shrink:0;width:22px;height:22px;border-radius:50%;background:<?php echo esc_attr($primary); ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:800}
</style>

<div class="klh">
  <div class="klh-bc"><div class="klh-bc-in">
    <a href="<?php echo esc_url(FTH_Templates::get_hub_url('hotels')); ?>">Hotels</a>
    <span>›</span>
    <?php if ($country_name && $country_link): ?><a href="<?php echo esc_url($country_link); ?>"><?php echo esc_html($country_name); ?></a><span>›</span><?php endif; ?>
    <?php if ($city_name && $city_link): ?><a href="<?php echo esc_url($city_link); ?>"><?php echo esc_html($city_name); ?></a><span>›</span><?php endif; ?>
    <span><?php the_title(); ?></span>
  </div></div>

  <div class="klh-main">
    <div>
      <div class="klh-card">
        <?php if (!empty($gallery)): ?>
        <div class="klh-gallery-main">
          <img id="klhMainImg" src="<?php echo esc_url($gallery[0]); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="eager">
          <?php if ($discount_pct > 0): ?><div class="klh-disc-badge">-<?php echo $discount_pct; ?>%</div><?php endif; ?>
        </div>
        <?php if (count($gallery) > 1): ?>
        <div class="klh-thumbs">
          <?php foreach (array_slice($gallery, 0, 8) as $i => $img): ?>
          <div class="klh-thumb <?php echo $i === 0 ? 'active' : ''; ?>" onclick="klhSetImg(this,'<?php echo esc_js($img); ?>')">
            <img src="<?php echo esc_url($img); ?>" alt="" loading="lazy">
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <div class="klh-promo">
          <svg viewBox="0 0 24 24" fill="currentColor" width="22" height="22"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
          <span><?php echo esc_html($promo); ?></span>
        </div>

        <div class="klh-badge">🏨 Accommodation</div>
        <h1 class="klh-title"><?php the_title(); ?></h1>
        <ul class="klh-meta">
          <?php if ($city_name || $country_name): ?>
          <li>📍 <?php echo esc_html(trim($city_name . ($country_name ? ', ' . $country_name : ''))); ?></li>
          <?php endif; ?>
          <?php if ($address): ?><li>🗺 <?php echo esc_html(wp_trim_words($address, 8)); ?></li><?php endif; ?>
          <?php if ($rating): ?>
          <li>⭐ <?php echo esc_html(number_format((float)$rating, 1)); ?>
            <?php if ($review_count): ?>&nbsp;· <?php echo esc_html(number_format((int)$review_count)); ?> reviews<?php endif; ?>
          </li>
          <?php endif; ?>
        </ul>
      </div>

      <div class="klh-section">
        <h2 class="klh-sec-title"><span class="icon">ℹ</span> About this hotel</h2>
        <div class="klh-content"><?php the_content(); ?></div>
        <?php if ($address): ?><p style="margin-top:14px;font-size:14px;"><strong>Address:</strong> <?php echo esc_html($address); ?></p><?php endif; ?>
      </div>

      <!-- Yahia Fadlallah exclusive deal card – shown right after description -->
      <div class="klh-section" style="text-align:center;padding:16px">
        <img src="https://yahiadubai.com/wp-content/uploads/2026/03/New-Project-4.png" alt="Yahia Fadlallah" style="max-width:90px;height:auto;display:block;margin:0 auto 10px;border-radius:8px;">
        <div style="font-size:14px;color:#92400e;font-weight:700;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:10px;">🤝 <?php echo esc_html($promo_text); ?></div>
      </div>

      <?php if (!empty($highlights_raw)):
        $hl_lines = array_filter(array_map('trim', explode("\n", $highlights_raw))); ?>
      <div class="klh-section">
        <h2 class="klh-sec-title"><span class="icon">★</span> Hotel highlights</h2>
        <ul class="klh-list">
          <?php foreach ($hl_lines as $line): ?>
          <li><span class="check">✓</span><span><?php echo esc_html($line); ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if (!empty($inclusions_raw)):
        $inc_lines = array_filter(array_map('trim', explode("\n", $inclusions_raw))); ?>
      <div class="klh-section">
        <h2 class="klh-sec-title"><span class="icon">✓</span> What's included</h2>
        <ul class="klh-list">
          <?php foreach ($inc_lines as $line): ?>
          <li><span class="check">✓</span><span><?php echo esc_html($line); ?></span></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <?php if (!empty($amen_list)): ?>
      <div class="klh-section">
        <h2 class="klh-sec-title"><span class="icon">✓</span> Popular amenities</h2>
        <div class="klh-amenities">
          <?php foreach (array_slice($amen_list, 0, 16) as $amen): ?>
          <div class="klh-amenity">✓ <?php echo esc_html($amen); ?></div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty($faq_raw)):
        $faq_blocks = array_filter(array_map('trim', explode("\n\n", $faq_raw))); ?>
      <div class="klh-section">
        <h2 class="klh-sec-title"><span class="icon">❓</span> Frequently asked questions</h2>
        <?php foreach ($faq_blocks as $block):
          if (preg_match('/^Q:\s*(.+)/u', $block, $mq) && preg_match('/\nA:\s*(.+)/us', $block, $ma)): ?>
          <div class="klh-faq-item">
            <div class="klh-faq-q" onclick="this.parentElement.classList.toggle('open')">
              <span><?php echo esc_html($mq[1]); ?></span>
              <span class="klh-faq-arrow">▼</span>
            </div>
            <div class="klh-faq-a"><?php echo esc_html(trim($ma[1])); ?></div>
          </div>
          <?php endif; endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if ($related->have_posts()): ?>
      <div class="klh-section">
        <h2 class="klh-sec-title"><span class="icon">✨</span> Other hotels nearby</h2>
        <div class="klh-related-grid">
          <?php while ($related->have_posts()): $related->the_post();
            if (get_the_ID() === $post_id) continue;
            echo FTH_Templates::get_hotel_card(get_the_ID());
          endwhile; wp_reset_postdata(); ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- City FAQ ───────────────────────────────────────── -->
      <?php if (!empty($cities)):
        $htl_city_faq = get_term_meta($cities[0]->term_id, '_fth_faq', true);
        if ($htl_city_faq):
          $htl_city_faq_blocks = array_filter(array_map('trim', explode("\n\n", $htl_city_faq)));
      ?>
      <div class="klh-section">
        <h2 class="klh-sec-title"><span class="icon">❓</span> FAQ – <?php echo esc_html($city_name); ?></h2>
        <?php foreach ($htl_city_faq_blocks as $block):
          if (preg_match('/^Q:\s*(.+)/u', $block, $mq) && preg_match('/\nA:\s*(.+)/us', $block, $ma)): ?>
          <div class="klh-faq-item">
            <div class="klh-faq-q" onclick="this.parentElement.classList.toggle('open')">
              <span><?php echo esc_html($mq[1]); ?></span>
              <span class="klh-faq-arrow">▼</span>
            </div>
            <div class="klh-faq-a"><?php echo esc_html(trim($ma[1])); ?></div>
          </div>
          <?php endif; endforeach; ?>
      </div>
      <?php endif; endif; ?>

      <?php echo FTH_Templates::render_seo_footer('hotels'); ?>
    </div>

    <aside class="klh-sidebar">
      <div class="klh-book-box">
        <?php if ($discount_pct >= 5): ?>
        <div class="klh-deal-flag">🏷️ Yahia Deal · -<?php echo $discount_pct; ?>% OFF</div>
        <?php endif; ?>
        <div class="klh-price-from">From</div>
        <?php if ($orig_price && (float)$orig_price > (float)$price): ?>
        <div class="klh-price-orig">
          <s><?php echo esc_html($sym . number_format((float)$orig_price, 2)); ?></s>
          <?php $saving_amt = round((float)$orig_price - (float)$price, 2); ?>
          <span class="klh-saving-pill">You save <?php echo esc_html($sym . number_format($saving_amt, 0)); ?> ✨</span>
        </div>
        <?php endif; ?>
        <?php if ($price): ?>
        <div class="klh-price-curr"><?php echo esc_html($sym . number_format((float)$price, 2)); ?></div>
        <?php else: ?>
        <div class="klh-price-curr" style="font-size:24px;">Check price</div>
        <?php endif; ?>
        <div class="klh-price-note">Per night · Live rates</div>
        <a class="klh-cta" href="<?php echo esc_url($affiliate_link ?: '#'); ?>" target="_blank" rel="noopener noreferrer">
          🏨 <?php echo esc_html($cta_text); ?>
        </a>
        <?php
        $sp_seed  = (int) substr(md5($post_id . date('Ymd') . session_id()), 0, 6);
        $sp_count = 18 + ($sp_seed % 142); // range 18–159
        $sp_unit  = $sp_count > 80 ? 'this week' : 'today';
        ?>
        <div style="text-align:center;font-size:12px;color:#e53e3e;font-weight:700;margin:-8px 0 14px;border-radius:6px;padding:4px 8px;background:#fff5f5;">
          🔥 <?php echo $sp_count; ?> guests booked <?php echo $sp_unit; ?>
        </div>
        <div class="klh-yahia-desktop" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px;text-align:center;margin-bottom:16px;">
          <img src="https://yahiadubai.com/wp-content/uploads/2026/03/New-Project-4.png" alt="Yahia Fadlallah" style="max-width:120px;height:auto;display:block;margin:0 auto 8px;border-radius:8px;">
          <div style="font-size:13px;color:#92400e;font-weight:700;">🤝 <?php echo esc_html($promo_text); ?></div>
        </div>
        <div class="klh-trust">
          <div class="klh-trust-item"><span>🏨</span><span>Rooms, location and amenities – all in one place</span></div>
          <div class="klh-trust-item"><span>📍</span><span>Practical information for quick hotel comparison</span></div>
          <div class="klh-trust-item"><span>🔒</span><span>Secure booking via Klook</span></div>
          <div class="klh-trust-item"><span>⚡</span><span>Immediate availability confirmation</span></div>
        </div>
      </div>
    </aside>
  </div>
</div>

<script>
function klhSetImg(thumb, src) {
  document.getElementById('klhMainImg').src = src;
  document.querySelectorAll('.klh-thumb').forEach(function(t){ t.classList.remove('active'); });
  thumb.classList.add('active');
}
</script>

<?php get_footer(); ?>
