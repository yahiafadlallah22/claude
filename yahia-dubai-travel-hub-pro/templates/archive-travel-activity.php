<?php
/**
 * Template: Archive Activities – Klook-style v1.9
 * Fully isolated, working search/filters, lux cards
 */
if (!defined('ABSPATH')) { exit; }

$primary    = Flavor_Travel_Hub::get_primary_color();
$secondary  = Flavor_Travel_Hub::get_secondary_color();
$hero_title = get_option('fth_things_hero_title', 'Worldwide Tours & Attractions');
$hero_img   = get_option('fth_things_hero_image', 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1920');

$all_cities     = FTH_Taxonomies::get_cities(array('hide_empty' => false, 'number' => 200));
$all_categories = get_terms(array('taxonomy' => 'travel_category', 'hide_empty' => false, 'number' => 30));

$sq     = isset($_GET['fth_search'])   ? sanitize_text_field(wp_unslash($_GET['fth_search']))   : '';
$s_city = isset($_GET['fth_city'])     ? sanitize_text_field(wp_unslash($_GET['fth_city']))     : '';
$s_cat  = isset($_GET['fth_category']) ? sanitize_text_field(wp_unslash($_GET['fth_category'])) : '';
$paged  = max(1, (int) get_query_var('paged'));

$activities = FTH_Search::search_activities(array(
    'keyword'  => $sq,
    'city'     => $s_city,
    'category' => $s_cat,
    'per_page' => 12,
    'paged'    => $paged,
));

get_header();
?>
<style>
html,body,#main_wrapper,.master_header,.master_wrapper,.content_wrapper,.container,.site-content{overflow:visible!important;height:auto!important;max-height:none!important;position:static!important}
body.post-type-archive-travel_activity .widget-area,body.post-type-archive-travel_activity .sidebar,body.post-type-archive-travel_activity .right_sidebar,body.post-type-archive-travel_activity .page_header,body.post-type-archive-travel_activity .title_container,body.post-type-archive-travel_activity .wpestate_header_image,body.post-type-archive-travel_activity .property_breadcrumbs{display:none!important}
.kla,.kla *{box-sizing:border-box}
.kla{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1a1a1a;background:#f5f5f5;position:relative;z-index:5}
.kla a{text-decoration:none}
.kla-hero{position:relative;min-height:320px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#0a0a1a}
.kla-hero-bg{position:absolute;inset:0;background:url('<?php echo esc_url($hero_img); ?>') center/cover no-repeat;opacity:.35}
.kla-hero-ov{position:absolute;inset:0;background:linear-gradient(180deg,rgba(5,5,20,.2),rgba(5,5,20,.82))}
.kla-hero-in{position:relative;z-index:3;text-align:center;padding:60px 20px;width:100%;max-width:1280px;margin:0 auto}
.kla-hero-in h1{font-size:42px;font-weight:900;color:#fff;margin:0 0 10px}
.kla-search{max-width:960px;margin:0 auto;background:#fff;border-radius:18px;padding:12px;display:grid;grid-template-columns:1.8fr 1fr 1fr 140px;gap:10px;box-shadow:0 20px 50px rgba(0,0,0,.24)}
.kla-search input,.kla-search select{width:100%;height:52px;padding:0 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;color:#1a1a1a;background:#fff}
.kla-search input:focus,.kla-search select:focus{outline:none;border-color:<?php echo esc_attr($primary); ?>}
.kla-search button{height:52px;border:none;border-radius:10px;background:<?php echo esc_attr($primary); ?>;color:#fff;font-weight:800;font-size:14px;cursor:pointer}
.kla-section{padding:40px 0}
.kla-wrap{max-width:1280px;margin:0 auto;padding:0 20px}
.kla-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px}
.kla-head h2{margin:0;font-size:24px;font-weight:800}
.kla-head a{font-size:14px;color:<?php echo esc_attr($primary); ?>;font-weight:700}
.kla-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}
.kla-empty{background:#fff;border-radius:14px;padding:60px 40px;text-align:center;color:#888;font-size:15px}
.kla-pag{text-align:center;margin-top:28px}
.kla-pag .page-numbers{display:inline-flex;margin:0 3px;padding:9px 14px;border-radius:8px;border:1.5px solid #e5e7eb;color:#1a1a1a;font-weight:700;font-size:14px;background:#fff}
.kla-pag .current{background:<?php echo esc_attr($primary); ?>;color:#fff;border-color:<?php echo esc_attr($primary); ?>}
.kla-active-filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
.kla-filter-tag{display:inline-flex;align-items:center;gap:6px;padding:6px 12px;border-radius:999px;background:<?php echo esc_attr($primary); ?>22;color:<?php echo esc_attr($primary); ?>;font-size:13px;font-weight:700}
@media(max-width:1100px){.kla-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.kla-search{grid-template-columns:1fr 1fr}}
@media(max-width:640px){.kla-hero-in h1{font-size:28px}.kla-search{grid-template-columns:1fr}.kla-grid{grid-template-columns:1fr}}
</style>

<div class="kla">
  <section class="kla-hero">
    <div class="kla-hero-bg"></div><div class="kla-hero-ov"></div>
    <div class="kla-hero-in">
      <h1>🎟️ <?php echo esc_html($hero_title); ?></h1>
      <form class="kla-search" method="get" action="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">
        <input type="hidden" name="fth_mode" value="activities">
        <input type="text" name="fth_search" placeholder="🔍 Search activities, tours..." value="<?php echo esc_attr($sq); ?>">
        <select name="fth_city">
          <option value="">All cities</option>
          <?php if (!is_wp_error($all_cities)) foreach ($all_cities as $city): ?>
          <option value="<?php echo esc_attr($city->slug); ?>" <?php selected($s_city, $city->slug); ?>><?php echo esc_html($city->name); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="fth_category">
          <option value="">All categories</option>
          <?php if (!is_wp_error($all_categories)) foreach ($all_categories as $cat): ?>
          <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($s_cat, $cat->slug); ?>><?php echo esc_html($cat->name); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Search</button>
      </form>
    </div>
  </section>

  <section class="kla-section">
    <div class="kla-wrap">
      <div class="kla-head">
        <h2>
          <?php if ($sq || $s_city || $s_cat): ?>
            <?php echo esc_html($activities->found_posts); ?> results
          <?php else: ?>
            All activities
          <?php endif; ?>
        </h2>
        <?php if ($sq || $s_city || $s_cat): ?>
        <a href="<?php echo esc_url(get_post_type_archive_link('travel_activity')); ?>">← Reset filters</a>
        <?php endif; ?>
      </div>

      <?php if ($sq || $s_city || $s_cat): ?>
      <div class="kla-active-filters">
        <?php if ($sq): ?><span class="kla-filter-tag">🔍 <?php echo esc_html($sq); ?></span><?php endif; ?>
        <?php if ($s_city): ?><span class="kla-filter-tag">📍 <?php echo esc_html($s_city); ?></span><?php endif; ?>
        <?php if ($s_cat): ?><span class="kla-filter-tag">🏷 <?php echo esc_html($s_cat); ?></span><?php endif; ?>
      </div>
      <?php endif; ?>

      <div class="kla-grid">
        <?php if ($activities->have_posts()): while ($activities->have_posts()): $activities->the_post();
          echo FTH_Templates::get_activity_card(get_the_ID());
        endwhile; wp_reset_postdata();
        else: ?>
        <div class="kla-empty" style="grid-column:1/-1">
          <div style="font-size:48px;margin-bottom:16px">🔍</div>
          <strong>No activities found</strong><br><br>
          <a href="<?php echo esc_url(get_post_type_archive_link('travel_activity')); ?>" style="color:<?php echo esc_attr($primary); ?>">View all activities</a>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($activities->max_num_pages > 1): ?>
      <div class="kla-pag"><?php echo paginate_links(array('total' => (int)$activities->max_num_pages, 'current' => $paged)); ?></div>
      <?php endif; ?>
    </div>
  </section>

  <?php echo FTH_Templates::render_seo_footer('activities'); ?>
</div>

<?php get_footer(); ?>
