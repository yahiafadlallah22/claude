<?php
/**
 * Template: City Taxonomy – Klook-style v1.7
 */
if (!defined('ABSPATH')) { exit; }
$term = get_queried_object();
if (!$term || is_wp_error($term)) { get_header(); echo '<div style="max-width:900px;margin:60px auto;padding:0 20px;text-align:center"><h1>City not found</h1><a href="' . esc_url(home_url('/')) . '">Back</a></div>'; get_footer(); return; }

$primary     = Flavor_Travel_Hub::get_primary_color();
$secondary   = Flavor_Travel_Hub::get_secondary_color();
$hero_img    = get_term_meta($term->term_id, 'fth_hero_image', true) ?: 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1600';
$country     = null;
$country_id  = (int) get_term_meta($term->term_id, 'fth_parent_country', true);
if ($country_id) { $c = get_term($country_id, 'travel_country'); if ($c && !is_wp_error($c)) $country = $c; }
$country_name = $country ? $country->name : '';
$categories   = get_terms(array('taxonomy' => 'travel_category', 'hide_empty' => false, 'number' => 20));
$sq           = isset($_GET['fth_search'])   ? sanitize_text_field(wp_unslash($_GET['fth_search']))   : '';
$s_cat        = isset($_GET['fth_category']) ? sanitize_text_field(wp_unslash($_GET['fth_category'])) : '';
$paged        = max(1, (int) get_query_var('paged'));
$act_count    = FTH_Templates::get_city_activity_count($term->term_id);
$hot_count    = FTH_Templates::get_city_hotel_count($term->term_id);
$activities   = FTH_Search::search_activities(array('city' => $term->slug, 'keyword' => $sq, 'category' => $s_cat, 'per_page' => 12, 'paged' => $paged));
$hotels       = FTH_Search::search_hotels(array('city' => $term->slug, 'per_page' => 8, 'paged' => 1));
get_header();
?>
<style>
html,body,#main_wrapper,.master_header,.master_wrapper,.content_wrapper,.container,.site-content{overflow:visible!important;height:auto!important;max-height:none!important;position:static!important}
body.tax-travel_city .widget-area,body.tax-travel_city .sidebar,body.tax-travel_city .right_sidebar,body.tax-travel_city .page_header,body.tax-travel_city .title_container,body.tax-travel_city .wpestate_header_image,body.tax-travel_city .property_breadcrumbs{display:none!important}
.klc,.klc *{box-sizing:border-box}
.klc{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#f5f5f5;color:#1a1a1a;position:relative;z-index:5}
.klc a{text-decoration:none}
.klc-hero{position:relative;min-height:420px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#0a0a1a}
.klc-hero-bg{position:absolute;inset:0;background:url('<?php echo esc_url($hero_img); ?>') center/cover no-repeat;opacity:.36}
.klc-hero-ov{position:absolute;inset:0;background:linear-gradient(180deg,rgba(5,5,20,.2),rgba(5,5,20,.82))}
.klc-hero-in{position:relative;z-index:3;text-align:center;padding:72px 20px;width:100%;max-width:1280px;margin:0 auto}
.klc-chips{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-bottom:16px}
.klc-chip{display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:999px;background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.2);font-weight:700;font-size:12px}
.klc-title{font-size:48px;font-weight:900;color:#fff!important;line-height:1.05;margin:0 0 12px}
.klc-subtitle{margin:0 auto 28px;max-width:700px;color:rgba(255,255,255,.9);font-size:17px;line-height:1.6}
.klc-search{max-width:960px;margin:0 auto;background:#fff;border-radius:18px;padding:12px;display:grid;grid-template-columns:1.8fr 1fr 150px;gap:10px;box-shadow:0 20px 50px rgba(0,0,0,.24)}
.klc-search input,.klc-search select{width:100%;height:52px;padding:0 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;color:#1a1a1a;background:#fff}
.klc-search input:focus,.klc-search select:focus{outline:none;border-color:<?php echo esc_attr($primary); ?>}
.klc-search button{height:52px;border:none;border-radius:10px;background:<?php echo esc_attr($primary); ?>;color:#fff;font-weight:800;font-size:14px;cursor:pointer}
.klc-action-links{display:flex;gap:10px;justify-content:center;flex-wrap:wrap;margin-top:18px}
.klc-action-links a{display:inline-flex;align-items:center;gap:6px;padding:11px 20px;border-radius:10px;font-weight:700;font-size:14px}
.klc-action-links .solid{background:#fff;color:<?php echo esc_attr($primary); ?>}
.klc-action-links .ghost{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)}
.klc-section{padding:40px 0}
.klc-wrap{max-width:1280px;margin:0 auto;padding:0 20px}
.klc-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px}
.klc-head h2{margin:0;font-size:24px;font-weight:800}
.klc-head a{font-size:14px;color:<?php echo esc_attr($primary); ?>;font-weight:700}
.klc-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.klc-empty{background:#fff;border-radius:14px;padding:40px;text-align:center;color:#888;font-size:15px}
.klc-pag{text-align:center;margin-top:24px}
.klc-pag .page-numbers{display:inline-flex;margin:0 3px;padding:8px 13px;border-radius:8px;border:1.5px solid #e5e7eb;color:#1a1a1a;font-weight:700;font-size:14px;background:#fff}
.klc-pag .current{background:<?php echo esc_attr($primary); ?>;color:#fff;border-color:<?php echo esc_attr($primary); ?>}
.klc-footer-nav{background:#1a1a1a;color:#fff;padding:32px 0}
.klc-footer-nav-in{max-width:1280px;margin:0 auto;padding:0 20px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}
.klc-footer-nav h4{margin:0 0 12px;font-size:13px;font-weight:800;color:#fff;text-transform:uppercase;letter-spacing:.5px}
.klc-footer-nav ul{list-style:none;padding:0;margin:0;display:grid;gap:7px}
.klc-footer-nav ul li a{color:rgba(255,255,255,.7);font-size:13px}
.klc-footer-nav ul li a:hover{color:#fff}
@media(max-width:1100px){.klc-search{grid-template-columns:1fr 1fr}.klc-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.klc-title{font-size:32px}.klc-search{grid-template-columns:1fr}.klc-grid{grid-template-columns:1fr}.klc-footer-nav-in{grid-template-columns:1fr}}
</style>
<div class="klc">
  <section class="klc-hero">
    <div class="klc-hero-bg"></div><div class="klc-hero-ov"></div>
    <div class="klc-hero-in">
      <div class="klc-chips">
        <span class="klc-chip">📍 <?php echo esc_html($term->name); ?></span>
        <?php if ($country_name): ?><span class="klc-chip"><?php echo esc_html($country_name); ?></span><?php endif; ?>
        <?php if ($act_count): ?><span class="klc-chip"><?php echo $act_count; ?> activité<?php echo $act_count > 1 ? 's' : ''; ?></span><?php endif; ?>
        <?php if ($hot_count): ?><span class="klc-chip"><?php echo $hot_count; ?> hôtel<?php echo $hot_count > 1 ? 's' : ''; ?></span><?php endif; ?>
      </div>
      <h1 class="klc-title">Explore <?php echo esc_html($term->name); ?></h1>
      <p class="klc-subtitle">Activities, tours, attractions and hotels in <?php echo esc_html($term->name); ?>. Exclusive deals by Yahia Fadlallah.</p>
      <form class="klc-search" method="get" action="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">
        <input type="hidden" name="fth_mode" value="activities">
        <input type="hidden" name="fth_city" value="<?php echo esc_attr($term->slug); ?>">
        <input type="text" name="fth_search" placeholder="🔍 Search in <?php echo esc_attr($term->name); ?>" value="<?php echo esc_attr($sq); ?>">
        <select name="fth_category">
          <option value="">All categories</option>
          <?php if (!is_wp_error($categories)) foreach ($categories as $cat): ?>
          <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($s_cat, $cat->slug); ?>><?php echo esc_html($cat->name); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
      </form>
      <div class="klc-action-links">
        <?php if ($hot_count): ?><a class="solid" href="<?php echo esc_url(add_query_arg(array('fth_city' => $term->slug, 'fth_mode' => 'hotels'), FTH_Templates::get_hub_url('hotels'))); ?>">🏨 Hotels</a><?php endif; ?>
        <?php if ($country): ?><a class="ghost" href="<?php echo esc_url(get_term_link($country)); ?>">🌍 <?php echo esc_html($country->name); ?></a><?php endif; ?>
        <a class="ghost" href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">← Back</a>
      </div>
    </div>
  </section>

  <section class="klc-section">
    <div class="klc-wrap">
      <div class="klc-head">
        <h2>🎟️ Activities in <?php echo esc_html($term->name); ?></h2>
        <?php if ($activities->max_num_pages > 1): ?><a href="?paged=2">View more →</a><?php endif; ?>
      </div>
      <div class="klc-grid">
        <?php if ($activities->have_posts()): while ($activities->have_posts()): $activities->the_post();
          echo FTH_Templates::get_activity_card(get_the_ID());
        endwhile; wp_reset_postdata();
        else: ?><div class="klc-empty">No activities found for this city.</div><?php endif; ?>
      </div>
      <?php if ($activities->max_num_pages > 1): ?>
      <div class="klc-pag"><?php echo paginate_links(array('total' => (int)$activities->max_num_pages, 'current' => $paged)); ?></div>
      <?php endif; ?>
    </div>
  </section>

  <?php if ($hot_count): ?>
  <section class="klc-section" style="padding-top:0;background:#fff">
    <div class="klc-wrap">
      <div class="klc-head">
        <h2>🏨 Hotels in <?php echo esc_html($term->name); ?></h2>
        <a href="<?php echo esc_url(add_query_arg(array('fth_city' => $term->slug, 'fth_mode' => 'hotels'), FTH_Templates::get_hub_url('hotels'))); ?>">View all →</a>
      </div>
      <div class="klc-grid">
        <?php if ($hotels->have_posts()): while ($hotels->have_posts()): $hotels->the_post();
          echo FTH_Templates::get_hotel_card(get_the_ID());
        endwhile; wp_reset_postdata();
        else: ?><div class="klc-empty">No hotels found.</div><?php endif; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php echo FTH_Templates::render_seo_footer('activities'); ?>

  <footer class="klc-footer-nav">
    <div class="klc-footer-nav-in">
      <div>
        <h4>Activities</h4>
        <ul>
          <li><a href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">All activities</a></li>
          <li><a href="<?php echo esc_url(add_query_arg(array('fth_city' => $term->slug, 'fth_mode' => 'activities'), FTH_Templates::get_hub_url('things-to-do'))); ?>">Activities – <?php echo esc_html($term->name); ?></a></li>
        </ul>
      </div>
      <div>
        <h4>Hotels</h4>
        <ul>
          <li><a href="<?php echo esc_url(FTH_Templates::get_hub_url('hotels')); ?>">All hotels</a></li>
          <li><a href="<?php echo esc_url(add_query_arg(array('fth_city' => $term->slug, 'fth_mode' => 'hotels'), FTH_Templates::get_hub_url('hotels'))); ?>">Hotels – <?php echo esc_html($term->name); ?></a></li>
        </ul>
      </div>
      <div>
        <h4>Destinations</h4>
        <ul>
          <?php if ($country): ?><li><a href="<?php echo esc_url(get_term_link($country)); ?>"><?php echo esc_html($country->name); ?></a></li><?php endif; ?>
          <li><a href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">Worldwide</a></li>
        </ul>
      </div>
    </div>
  </footer>
</div>
<?php get_footer(); ?>
