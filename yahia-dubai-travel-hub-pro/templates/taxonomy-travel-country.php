<?php
/**
 * Template: Country Taxonomy – Klook-style v1.7
 */
if (!defined('ABSPATH')) { exit; }
$term = get_queried_object();
if (!$term || is_wp_error($term)) { get_header(); echo '<div style="max-width:900px;margin:60px auto;padding:0 20px;text-align:center"><h1>Country not found</h1><a href="' . esc_url(home_url('/')) . '">Back</a></div>'; get_footer(); return; }

$primary     = Flavor_Travel_Hub::get_primary_color();
$flag        = FTH_Templates::get_country_flag($term);
$cities      = get_terms(array('taxonomy' => 'travel_city', 'hide_empty' => false, 'meta_query' => array(array('key' => 'fth_parent_country', 'value' => $term->term_id)), 'number' => 48, 'orderby' => 'name', 'order' => 'ASC'));
$sq          = isset($_GET['fth_search']) ? sanitize_text_field(wp_unslash($_GET['fth_search'])) : '';
$activities  = FTH_Search::search_activities(array('country' => $term->slug, 'keyword' => $sq, 'per_page' => 12, 'paged' => 1));
$hotels      = FTH_Search::search_hotels(array('country' => $term->slug, 'per_page' => 8, 'paged' => 1));
get_header();
?>
<style>
html,body,#main_wrapper,.master_header,.master_wrapper,.content_wrapper,.container,.site-content{overflow:visible!important;height:auto!important;max-height:none!important;position:static!important}
body.tax-travel_country .widget-area,body.tax-travel_country .sidebar,body.tax-travel_country .right_sidebar,body.tax-travel_country .page_header,body.tax-travel_country .title_container,body.tax-travel_country .wpestate_header_image,body.tax-travel_country .property_breadcrumbs{display:none!important}
.klco,.klco *{box-sizing:border-box}
.klco{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;background:#f5f5f5;color:#1a1a1a;position:relative;z-index:5}
.klco a{text-decoration:none}
.klco-hero{background:linear-gradient(180deg,#0f172a 0%,#1e293b 100%);padding:70px 0 44px;text-align:center}
.klco-hero .flag{font-size:72px}
.klco-hero h1{margin:14px 0 10px;font-size:48px;font-weight:900;color:#fff;line-height:1.05}
.klco-hero p{margin:0 auto 26px;max-width:700px;color:rgba(255,255,255,.9);font-size:17px;line-height:1.6}
.klco-search{max-width:720px;margin:0 auto;background:#fff;border-radius:16px;padding:12px;display:grid;grid-template-columns:1fr 140px;gap:10px;box-shadow:0 16px 48px rgba(0,0,0,.22)}
.klco-search input{width:100%;height:52px;padding:0 14px;border:1.5px solid #e5e7eb;border-radius:10px;font-size:14px;color:#1a1a1a;background:#fff}
.klco-search input:focus{outline:none;border-color:<?php echo esc_attr($primary); ?>}
.klco-search button{height:52px;border:none;border-radius:10px;background:<?php echo esc_attr($primary); ?>;color:#fff;font-weight:800;font-size:14px;cursor:pointer}
.klco-section{padding:40px 0}
.klco-wrap{max-width:1280px;margin:0 auto;padding:0 20px}
.klco-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;flex-wrap:wrap;gap:10px}
.klco-head h2{margin:0;font-size:24px;font-weight:800}
.klco-head a{font-size:14px;color:<?php echo esc_attr($primary); ?>;font-weight:700}
.klco-cities{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px}
.klco-city-card{background:#fff;border-radius:14px;padding:16px;text-align:center;font-weight:800;color:#1a1a1a;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:box-shadow .2s;border:1px solid #eee}
.klco-city-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.12)}
.klco-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.klco-empty{background:#fff;border-radius:14px;padding:40px;text-align:center;color:#888}
.klco-footer-nav{background:#1a1a1a;color:#fff;padding:32px 0}
.klco-footer-nav-in{max-width:1280px;margin:0 auto;padding:0 20px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:28px}
.klco-footer-nav h4{margin:0 0 12px;font-size:13px;font-weight:800;color:#fff;text-transform:uppercase;letter-spacing:.5px}
.klco-footer-nav ul{list-style:none;padding:0;margin:0;display:grid;gap:7px}
.klco-footer-nav ul li a{color:rgba(255,255,255,.7);font-size:13px}
.klco-footer-nav ul li a:hover{color:#fff}
@media(max-width:1100px){.klco-grid,.klco-cities{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:640px){.klco-hero h1{font-size:32px}.klco-search{grid-template-columns:1fr}.klco-grid,.klco-cities{grid-template-columns:1fr}.klco-footer-nav-in{grid-template-columns:1fr}}
.klco-faq-item{border:1px solid #e8e8e8;border-radius:10px;margin-bottom:8px;overflow:hidden}
.klco-faq-q{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;cursor:pointer;font-weight:700;font-size:14px;background:#fafafa;gap:10px}
.klco-faq-q:hover{background:#f0f7ff}
.klco-faq-arrow{flex-shrink:0;font-size:11px;transition:transform .2s}
.klco-faq-item.open .klco-faq-arrow{transform:rotate(180deg)}
.klco-faq-a{display:none;padding:14px 16px;font-size:14px;color:#444;line-height:1.7;border-top:1px solid #eee}
.klco-faq-item.open .klco-faq-a{display:block}
.klco-faq-item.open .klco-faq-q{background:#f0f7ff}
</style>
<div class="klco">
  <section class="klco-hero"><div class="klco-wrap">
    <div class="flag"><?php echo esc_html($flag); ?></div>
    <h1><?php echo esc_html($term->name); ?></h1>
    <p>Explore cities, activities and hotels in <?php echo esc_html($term->name); ?>.</p>
    <form class="klco-search" method="get" action="<?php echo esc_url(get_term_link($term)); ?>">
      <input type="hidden" name="fth_country" value="<?php echo esc_attr($term->slug); ?>">
      <input type="text" name="fth_search" placeholder="🔍 Search in <?php echo esc_attr($term->name); ?>" value="<?php echo esc_attr($sq); ?>">
      <button type="submit">Search</button>
    </form>
  </div></section>

  <?php if (!empty($cities) && !is_wp_error($cities)): ?>
  <section class="klco-section">
    <div class="klco-wrap">
      <div class="klco-head"><h2>Cities in <?php echo esc_html($term->name); ?></h2></div>
      <div class="klco-cities">
        <?php foreach ($cities as $city): ?>
        <a class="klco-city-card" href="<?php echo esc_url(get_term_link($city)); ?>">📍 <?php echo esc_html($city->name); ?></a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($activities->have_posts()): ?>
  <section class="klco-section" style="padding-top:0">
    <div class="klco-wrap">
      <div class="klco-head"><h2>🎟️ Activities in <?php echo esc_html($term->name); ?></h2></div>
      <div class="klco-grid">
        <?php while ($activities->have_posts()): $activities->the_post();
          echo FTH_Templates::get_activity_card(get_the_ID());
        endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php if ($hotels->have_posts()): ?>
  <section class="klco-section" style="padding-top:0;background:#fff">
    <div class="klco-wrap">
      <div class="klco-head"><h2>🏨 Hotels in <?php echo esc_html($term->name); ?></h2></div>
      <div class="klco-grid">
        <?php while ($hotels->have_posts()): $hotels->the_post();
          echo FTH_Templates::get_hotel_card(get_the_ID());
        endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <?php
  $country_faq_raw = get_term_meta($term->term_id, '_fth_faq', true);
  if ($country_faq_raw):
    $country_faq_blocks = array_filter(array_map('trim', explode("\n\n", $country_faq_raw)));
  ?>
  <section class="klco-section" style="background:#fff;padding-top:0">
    <div class="klco-wrap">
      <div class="klco-head"><h2>❓ FAQ – <?php echo esc_html($term->name); ?></h2></div>
      <?php foreach ($country_faq_blocks as $block):
        if (preg_match('/^Q:\s*(.+)/u', $block, $mq) && preg_match('/\nA:\s*(.+)/us', $block, $ma)): ?>
        <div class="klco-faq-item">
          <div class="klco-faq-q">
            <span><?php echo esc_html($mq[1]); ?></span>
            <span class="klco-faq-arrow">▼</span>
          </div>
          <div class="klco-faq-a"><?php echo esc_html(trim($ma[1])); ?></div>
        </div>
        <?php endif; endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php echo FTH_Templates::render_seo_footer('activities'); ?>

  <footer class="klco-footer-nav">
    <div class="klco-footer-nav-in">
      <div>
        <h4>Activities</h4>
        <ul>
          <li><a href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">All activities</a></li>
          <?php if (!empty($cities) && !is_wp_error($cities)) foreach (array_slice((array)$cities, 0, 4) as $c): ?>
          <li><a href="<?php echo esc_url(get_term_link($c)); ?>"><?php echo esc_html($c->name); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4>Hotels</h4>
        <ul>
          <li><a href="<?php echo esc_url(FTH_Templates::get_hub_url('hotels')); ?>">All hotels</a></li>
          <?php if (!empty($cities) && !is_wp_error($cities)) foreach (array_slice((array)$cities, 0, 4) as $c): ?>
          <li><a href="<?php echo esc_url(add_query_arg(array('fth_city' => $c->slug, 'fth_mode' => 'hotels'), FTH_Templates::get_hub_url('hotels'))); ?>">Hotels – <?php echo esc_html($c->name); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4>Destinations</h4>
        <ul>
          <li><a href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">Worldwide</a></li>
        </ul>
      </div>
    </div>
  </footer>
</div>
<script>
document.querySelectorAll('.klco-faq-q').forEach(function(q){q.addEventListener('click',function(){this.parentElement.classList.toggle('open');});});
</script>
<?php get_footer(); ?>
