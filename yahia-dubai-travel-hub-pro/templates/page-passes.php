<?php
/**
 * Template: Passes & Attraction Passes hub page – Klook-style
 * Lists activities tagged with passes/attraction-pass categories.
 * Elementor and WPBakery compatible – outputs the_content() below the grid.
 */
if (!defined('ABSPATH')) { exit; }

$primary   = Flavor_Travel_Hub::get_primary_color();
$secondary = Flavor_Travel_Hub::get_secondary_color();

// Hero image – fallback to a Dubai/UAE Unsplash photo
$hero_image = get_option('fth_passes_hero_image', 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1920');

// Pass type filter from URL
$pass_type = isset($_GET['fth_pass_type']) ? sanitize_text_field(wp_unslash($_GET['fth_pass_type'])) : '';
$paged     = max(1, (int) get_query_var('paged'));

// Popular pass sub-categories with emoji icons
$pass_types = array(
    array('slug' => '',                  'label' => 'All Passes',         'icon' => '🎟️'),
    array('slug' => 'city-pass',         'label' => 'City Passes',        'icon' => '🎟️'),
    array('slug' => 'attraction-pass',   'label' => 'Attraction Passes',  'icon' => '🏙️'),
    array('slug' => 'theme-parks',       'label' => 'Theme Parks',        'icon' => '🎡'),
    array('slug' => 'hop-on-hop-off',    'label' => 'Hop-On Hop-Off',     'icon' => '🚌'),
    array('slug' => 'entertainment',     'label' => 'Entertainment',      'icon' => '🎭'),
    array('slug' => 'multi-day-pass',    'label' => 'Multi-day Passes',   'icon' => '🗺️'),
    array('slug' => 'cruises-ferries',   'label' => 'Cruises & Ferries',  'icon' => '🛳️'),
    array('slug' => 'water-parks',       'label' => 'Water Parks',        'icon' => '🏊'),
);

// Build tax_query: passes category + optional sub-type filter
$pass_category_slugs = array('passes', 'city-pass', 'attraction-pass');
if ($pass_type && !in_array($pass_type, $pass_category_slugs, true)) {
    $pass_category_slugs[] = $pass_type;
}

$tax_query = array(
    'relation' => 'OR',
    array('taxonomy' => 'travel_category', 'field' => 'slug', 'terms' => $pass_category_slugs),
);

if ($pass_type) {
    $tax_query = array(
        array('taxonomy' => 'travel_category', 'field' => 'slug', 'terms' => array($pass_type)),
    );
}

$activities_query = new WP_Query(array(
    'post_type'      => 'travel_activity',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'tax_query'      => $tax_query,
    'orderby'        => 'meta_value_num',
    'meta_key'       => '_fth_rating',
    'order'          => 'DESC',
));

$total_passes = $activities_query->found_posts;

// Also run a wide query if no passes found under specific slugs
if ($total_passes === 0 && !$pass_type) {
    $activities_query = new WP_Query(array(
        'post_type'      => 'travel_activity',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'paged'          => $paged,
        's'              => 'pass',
        'orderby'        => 'relevance',
    ));
    $total_passes = $activities_query->found_posts;
}

$passes_hub_url = FTH_Templates::get_hub_url('passes');

get_header();
?>
<style>
html,body,#main_wrapper,.master_header,.master_wrapper,.content_wrapper,.container,.site-content{overflow:visible!important;height:auto!important;max-height:none!important;position:static!important}
body.page .widget-area,body.page .sidebar,body.page .right_sidebar,body.page .page_header,body.page .title_container,body.page .wpestate_header_image,body.page .property_breadcrumbs{display:none!important}
.ftp,.ftp *{box-sizing:border-box}
.ftp{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1a1a1a;background:#f5f5f5;position:relative;z-index:5}
.ftp a{text-decoration:none}
/* Hero */
.ftp-hero{position:relative;min-height:480px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#0a0a1a}
.ftp-hero-bg{position:absolute;inset:0;background:url('<?php echo esc_url($hero_image); ?>') center/cover no-repeat;opacity:.35}
.ftp-hero-overlay{position:absolute;inset:0;background:linear-gradient(180deg,rgba(5,5,20,.25) 0%,rgba(5,5,20,.82) 100%)}
.ftp-hero-inner{position:relative;z-index:3;text-align:center;padding:80px 20px;width:100%;max-width:1280px;margin:0 auto}
.ftp-hero-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;background:rgba(255,255,255,.15);color:#fff;font-weight:800;font-size:13px;border:1px solid rgba(255,255,255,.2);margin-bottom:18px}
.ftp-hero-title{font-size:52px;font-weight:900;color:#fff!important;line-height:1.05;margin:0 0 14px;text-shadow:0 2px 10px rgba(0,0,0,.4)}
.ftp-hero-sub{font-size:18px;color:rgba(255,255,255,.9);margin:0 auto 0;max-width:760px;line-height:1.6}
/* Filter pills bar */
.ftp-filter-bar{background:#fff;border-bottom:1px solid #eee;overflow-x:auto;white-space:nowrap;padding:0}
.ftp-filter-bar-in{max-width:1280px;margin:0 auto;padding:0 20px;display:flex;gap:0}
.ftp-filter-btn{display:inline-flex;flex-direction:column;align-items:center;gap:4px;padding:14px 18px;cursor:pointer;border-bottom:3px solid transparent;font-size:12px;font-weight:700;color:#555;transition:color .15s,border-color .15s;text-decoration:none;white-space:nowrap;background:none;border-top:none;border-left:none;border-right:none}
.ftp-filter-btn:hover{color:<?php echo esc_attr($primary); ?>;border-bottom-color:<?php echo esc_attr($primary); ?>}
.ftp-filter-btn .cat-icon{font-size:22px}
.ftp-filter-btn.active{color:<?php echo esc_attr($primary); ?>;border-bottom-color:<?php echo esc_attr($primary); ?>}
/* Sections */
.ftp-section{padding:44px 0}
.ftp-wrap{max-width:1280px;margin:0 auto;padding:0 20px}
.ftp-section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px}
.ftp-section-head h2{margin:0;font-size:26px;font-weight:800;color:#1a1a1a}
.ftp-section-head a{font-size:14px;color:<?php echo esc_attr($primary); ?>;font-weight:700}
/* Activity grid */
.ftp-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}
/* Empty */
.ftp-empty{background:#fff;border-radius:14px;padding:40px;text-align:center;color:#888;font-size:15px}
/* Pagination */
.ftp-pag{text-align:center;margin-top:28px}
.ftp-pag .page-numbers{display:inline-flex;margin:0 3px;padding:9px 14px;border-radius:8px;border:1.5px solid #e5e7eb;color:#1a1a1a;font-weight:700;font-size:14px;background:#fff}
.ftp-pag .current{background:<?php echo esc_attr($primary); ?>;color:#fff;border-color:<?php echo esc_attr($primary); ?>}
/* Breadcrumb */
.ftp-breadcrumb{font-size:13px;color:#666;padding:14px 20px;max-width:1280px;margin:0 auto}
.ftp-breadcrumb a{color:#666}
.ftp-breadcrumb span{margin:0 8px;color:#999}
/* Page builder content area */
.ftp-pb-content{max-width:1280px;margin:0 auto;padding:0 20px 44px}
/* Responsive */
@media(max-width:1160px){.ftp-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:720px){.ftp-hero-title{font-size:34px}.ftp-hero-sub{font-size:15px}.ftp-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:480px){.ftp-grid{grid-template-columns:1fr}}
</style>

<div class="ftp">

  <!-- Hero -->
  <section class="ftp-hero">
    <div class="ftp-hero-bg"></div>
    <div class="ftp-hero-overlay"></div>
    <div class="ftp-hero-inner">
      <div class="ftp-hero-badge">🎟️ Passes · Tickets · Attractions</div>
      <h1 class="ftp-hero-title">Attraction Passes &amp; Tickets</h1>
      <p class="ftp-hero-sub">
        <?php if ($total_passes): ?>
          <?php echo esc_html($total_passes); ?> pass<?php echo $total_passes !== 1 ? 'es' : ''; ?> available — save more with multi-attraction passes
        <?php else: ?>
          Discover city passes, theme park tickets and multi-day attraction passes
        <?php endif; ?>
      </p>
    </div>
  </section>

  <!-- Breadcrumb -->
  <div class="ftp-breadcrumb">
    <a href="<?php echo esc_url(home_url('/')); ?>">Home</a>
    <span>/</span>
    <a href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">Things to Do</a>
    <span>/</span>
    <strong>Passes &amp; Tickets</strong>
  </div>

  <!-- Pass type filter bar -->
  <div class="ftp-filter-bar">
    <div class="ftp-filter-bar-in">
      <?php foreach ($pass_types as $pt):
        $is_active = ($pass_type === $pt['slug']);
        $href = $pt['slug'] ? add_query_arg('fth_pass_type', $pt['slug'], $passes_hub_url) : $passes_hub_url;
      ?>
      <a class="ftp-filter-btn<?php echo $is_active ? ' active' : ''; ?>"
         href="<?php echo esc_url($href); ?>">
        <span class="cat-icon"><?php echo esc_html($pt['icon']); ?></span>
        <span><?php echo esc_html($pt['label']); ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Activities grid -->
  <section class="ftp-section">
    <div class="ftp-wrap">
      <div class="ftp-section-head">
        <h2>
          <?php
          $active_type = null;
          foreach ($pass_types as $pt) {
              if ($pt['slug'] === $pass_type) { $active_type = $pt; break; }
          }
          if ($active_type && $active_type['slug']) {
              echo esc_html($active_type['icon'] . ' ' . $active_type['label']);
          } else {
              echo '🎟️ All Attraction Passes';
          }
          ?>
        </h2>
        <?php if ($pass_type): ?>
        <a href="<?php echo esc_url($passes_hub_url); ?>">← All passes</a>
        <?php endif; ?>
      </div>

      <div class="ftp-grid">
        <?php if ($activities_query->have_posts()): while ($activities_query->have_posts()): $activities_query->the_post();
          echo FTH_Templates::get_activity_card(get_the_ID());
        endwhile; wp_reset_postdata();
        else: ?>
        <div class="ftp-empty" style="grid-column:1/-1">
          No passes found yet. Import activities tagged with the <strong>passes</strong>, <strong>city-pass</strong> or <strong>attraction-pass</strong> category to display them here.
        </div>
        <?php endif; ?>
      </div>

      <?php if ($activities_query->max_num_pages > 1): ?>
      <div class="ftp-pag">
        <?php echo paginate_links(array(
            'total'   => (int) $activities_query->max_num_pages,
            'current' => $paged,
            'add_args' => $pass_type ? array('fth_pass_type' => $pass_type) : array(),
        )); ?>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Page builder content (Elementor / WPBakery) -->
  <?php if (have_posts()): while (have_posts()): the_post();
    $pb_content = get_the_content();
    if (!empty(trim($pb_content))): ?>
  <div class="ftp-pb-content">
    <?php the_content(); ?>
  </div>
  <?php endif; endwhile; endif; ?>

</div><!-- .ftp -->

<?php get_footer(); ?>
