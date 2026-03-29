<?php
/**
 * Template: Things to Do hub page – Klook-style v1.7
 * UAE/Dubai first · Category icons · Worldwide · Search engine
 */
if (!defined('ABSPATH')) { exit; }

$primary       = Flavor_Travel_Hub::get_primary_color();
$secondary     = Flavor_Travel_Hub::get_secondary_color();
$hero_title    = get_option('fth_things_hero_title', 'Worldwide Tours & Attractions');
$hero_subtitle = get_option('fth_things_hero_subtitle', 'Découvrez des expériences uniques dans le monde entier – présentées par Yahia Dubai.');
$hero_image    = get_option('fth_things_hero_image', 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?w=1920');

// Klook category icons map
$cat_icons = array(
    'things-to-do'        => '🗺️',
    'tours'               => '🚌',
    'attractions'         => '🎡',
    'culture'             => '🏛️',
    'food'                => '🍜',
    'adventure'           => '🏔️',
    'water-sports'        => '🏄',
    'cruises'             => '⛵',
    'shows'               => '🎭',
    'theme-parks'         => '🎢',
    'sports'              => '⚽',
    'photography'         => '📸',
    'transfer'            => '🚗',
    'wellness'            => '💆',
    'outdoor'             => '🌿',
    'museums'             => '🎨',
    'nightlife'           => '🌙',
    'desert'              => '🐪',
    'beaches'             => '🏖️',
    'skydiving'           => '🪂',
    'helicopter'          => '🚁',
    'cooking'             => '👨‍🍳',
);

// Fetch data
$all_countries  = FTH_Taxonomies::get_countries(array('hide_empty' => false));
$all_cities     = FTH_Taxonomies::get_cities(array('hide_empty' => false, 'number' => 60));
$all_categories = get_terms(array('taxonomy' => 'travel_category', 'hide_empty' => false, 'number' => 24));

// Detect UAE/Dubai first
$uae_term  = get_term_by('name', 'United Arab Emirates', 'travel_country') ?: get_term_by('name', 'UAE', 'travel_country');
$dubai_term = get_term_by('name', 'Dubai', 'travel_city');

// Reorder: UAE/Dubai first
if ($uae_term && !is_wp_error($uae_term)) {
    $sorted_countries = array($uae_term);
    foreach ($all_countries as $c) {
        if ($c->term_id !== $uae_term->term_id) $sorted_countries[] = $c;
    }
} else {
    $sorted_countries = is_array($all_countries) ? $all_countries : array();
}
if ($dubai_term && !is_wp_error($dubai_term)) {
    $sorted_cities = array($dubai_term);
    foreach ($all_cities as $c) {
        if ($c->term_id !== $dubai_term->term_id) $sorted_cities[] = $c;
    }
} else {
    $sorted_cities = is_array($all_cities) ? $all_cities : array();
}

// Search params
$sq       = isset($_GET['fth_search'])   ? sanitize_text_field(wp_unslash($_GET['fth_search']))   : '';
$s_city   = isset($_GET['fth_city'])     ? sanitize_text_field(wp_unslash($_GET['fth_city']))     : '';
$s_ctr    = isset($_GET['fth_country'])  ? sanitize_text_field(wp_unslash($_GET['fth_country']))  : '';
$s_cat    = isset($_GET['fth_category']) ? sanitize_text_field(wp_unslash($_GET['fth_category'])) : '';
$paged    = max(1, (int) get_query_var('paged'));
$is_search = ($sq !== '' || $s_city !== '' || $s_ctr !== '' || $s_cat !== '');

$activities = FTH_Search::search_activities(array(
    'keyword'  => $sq,
    'city'     => $s_city,
    'country'  => $s_ctr,
    'category' => $s_cat,
    'per_page' => 12,
    'paged'    => $paged,
));

// UAE/Dubai featured activities
$dubai_activities = null;
if (!$is_search && $dubai_term && !is_wp_error($dubai_term)) {
    $dubai_activities = FTH_Search::search_activities(array('city' => $dubai_term->slug, 'per_page' => 8, 'paged' => 1));
}

get_header();
?>
<style>
html,body,#main_wrapper,.master_header,.master_wrapper,.content_wrapper,.container,.site-content{overflow:visible!important;height:auto!important;max-height:none!important;position:static!important}
body.page .widget-area,body.page .sidebar,body.page .right_sidebar,body.page .page_header,body.page .title_container,body.page .wpestate_header_image,body.page .property_breadcrumbs{display:none!important}
.klp,.klp *{box-sizing:border-box}
.klp{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1a1a1a;background:#f5f5f5;position:relative;z-index:5}
.klp a{text-decoration:none}
/* Hero */
.klp-hero{position:relative;min-height:560px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#0a0a1a}
.klp-hero-bg{position:absolute;inset:0;background:url('<?php echo esc_url($hero_image); ?>') center/cover no-repeat;opacity:.35}
.klp-hero-overlay{position:absolute;inset:0;background:linear-gradient(180deg,rgba(5,5,20,.25) 0%,rgba(5,5,20,.82) 100%)}
.klp-hero-inner{position:relative;z-index:3;text-align:center;padding:80px 20px;width:100%;max-width:1280px;margin:0 auto}
.klp-hero-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;background:rgba(255,255,255,.15);color:#fff;font-weight:800;font-size:13px;border:1px solid rgba(255,255,255,.2);margin-bottom:18px}
.klp-hero-title{font-size:52px;font-weight:900;color:#fff!important;line-height:1.05;margin:0 0 14px;text-shadow:0 2px 10px rgba(0,0,0,.4)}
.klp-hero-sub{font-size:18px;color:rgba(255,255,255,.9);margin:0 auto 32px;max-width:760px;line-height:1.6}
/* Search box */
.klp-search{max-width:1100px;margin:0 auto;background:#fff;border-radius:20px;padding:14px;display:grid;grid-template-columns:1.8fr 1fr 1fr 1fr 160px;gap:10px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.klp-search input,.klp-search select{width:100%;height:56px;padding:0 16px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;color:#1a1a1a;background:#fff}
.klp-search input:focus,.klp-search select:focus{outline:none;border-color:<?php echo esc_attr($primary); ?>}
.klp-search button{height:56px;border:none;border-radius:12px;background:<?php echo esc_attr($primary); ?>;color:#fff;font-weight:800;font-size:15px;cursor:pointer;transition:opacity .2s}
.klp-search button:hover{opacity:.88}
/* Category icons bar */
.klp-cats-bar{background:#fff;border-bottom:1px solid #eee;overflow-x:auto;white-space:nowrap;padding:0}
.klp-cats-bar-in{max-width:1280px;margin:0 auto;padding:0 20px;display:flex;gap:0}
.klp-cat-btn{display:inline-flex;flex-direction:column;align-items:center;gap:4px;padding:14px 18px;cursor:pointer;border-bottom:3px solid transparent;font-size:12px;font-weight:700;color:#555;transition:color .15s,border-color .15s;text-decoration:none;white-space:nowrap;background:none;border-top:none;border-left:none;border-right:none}
.klp-cat-btn:hover{color:<?php echo esc_attr($primary); ?>;border-bottom-color:<?php echo esc_attr($primary); ?>}
.klp-cat-btn .cat-icon{font-size:22px}
.klp-cat-btn.active{color:<?php echo esc_attr($primary); ?>;border-bottom-color:<?php echo esc_attr($primary); ?>}
/* Sections */
.klp-section{padding:44px 0}
.klp-wrap{max-width:1280px;margin:0 auto;padding:0 20px}
.klp-section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px}
.klp-section-head h2{margin:0;font-size:26px;font-weight:800;color:#1a1a1a}
.klp-section-head a{font-size:14px;color:<?php echo esc_attr($primary); ?>;font-weight:700}
/* City cards */
.klp-cities{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px}
.klp-city-card{background:#fff;border-radius:14px;padding:16px;text-align:center;font-weight:800;color:#1a1a1a;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:box-shadow .2s,transform .2s;border:1px solid #eee}
.klp-city-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.12);transform:translateY(-2px)}
.klp-city-card .flag{font-size:26px;margin-bottom:8px}
/* Activity grid */
.klp-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}
/* Dubai featured banner */
.klp-dubai-banner{background:linear-gradient(135deg,#1e3a5f,#2989C0);border-radius:20px;padding:30px 36px;display:flex;align-items:center;justify-content:space-between;gap:20px;margin-bottom:0;flex-wrap:wrap}
.klp-dubai-banner h2{margin:0 0 8px;font-size:30px;font-weight:900;color:#fff}
.klp-dubai-banner p{margin:0;color:rgba(255,255,255,.88);font-size:15px;max-width:560px}
.klp-dubai-banner a{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:12px;background:#fff;color:<?php echo esc_attr($primary); ?>;font-weight:800;font-size:15px;white-space:nowrap;margin-top:8px}
/* Empty */
.klp-empty{background:#fff;border-radius:14px;padding:40px;text-align:center;color:#888;font-size:15px}
/* Pagination */
.klp-pag{text-align:center;margin-top:28px}
.klp-pag .page-numbers{display:inline-flex;margin:0 3px;padding:9px 14px;border-radius:8px;border:1.5px solid #e5e7eb;color:#1a1a1a;font-weight:700;font-size:14px;background:#fff}
.klp-pag .current{background:<?php echo esc_attr($primary); ?>;color:#fff;border-color:<?php echo esc_attr($primary); ?>}
/* Footer nav */
.klp-footer-nav{background:#1a1a1a;color:#fff;padding:36px 0}
.klp-footer-nav-in{max-width:1280px;margin:0 auto;padding:0 20px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:32px}
.klp-footer-nav h4{margin:0 0 14px;font-size:14px;font-weight:800;color:#fff;text-transform:uppercase;letter-spacing:.5px}
.klp-footer-nav ul{list-style:none;padding:0;margin:0;display:grid;gap:8px}
.klp-footer-nav ul li a{color:rgba(255,255,255,.7);font-size:13px;transition:color .15s}
.klp-footer-nav ul li a:hover{color:#fff}
/* Responsive */
@media(max-width:1160px){.klp-search{grid-template-columns:1fr 1fr 1fr}.klp-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.klp-cities{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:720px){.klp-hero-title{font-size:34px}.klp-hero-sub{font-size:15px}.klp-search{grid-template-columns:1fr}.klp-grid,.klp-cities{grid-template-columns:repeat(2,minmax(0,1fr))}.klp-footer-nav-in{grid-template-columns:1fr}}
</style>

<div class="klp">

  <!-- Hero -->
  <section class="klp-hero">
    <div class="klp-hero-bg"></div>
    <div class="klp-hero-overlay"></div>
    <div class="klp-hero-inner">
      <div class="klp-hero-badge">🎟️ Tours · Attractions · Activités</div>
      <h1 class="klp-hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="klp-hero-sub"><?php echo esc_html($hero_subtitle); ?></p>
      <!-- Search -->
      <form class="klp-search" method="get" action="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">
        <input type="hidden" name="fth_mode" value="activities">
        <input type="text" name="fth_search" placeholder="🔍 Que voulez-vous faire ?" value="<?php echo esc_attr($sq); ?>">
        <select name="fth_city">
          <option value="">Toutes les villes</option>
          <?php foreach ($sorted_cities as $city): ?>
          <option value="<?php echo esc_attr($city->slug); ?>" <?php selected($s_city, $city->slug); ?>><?php echo esc_html($city->name); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="fth_country">
          <option value="">Tous les pays</option>
          <?php foreach ($sorted_countries as $country): ?>
          <option value="<?php echo esc_attr($country->slug); ?>" <?php selected($s_ctr, $country->slug); ?>><?php echo esc_html($country->name); ?></option>
          <?php endforeach; ?>
        </select>
        <select name="fth_category">
          <option value="">Toutes les catégories</option>
          <?php if (!is_wp_error($all_categories)) foreach ($all_categories as $cat): ?>
          <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($s_cat, $cat->slug); ?>><?php echo esc_html($cat->name); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit">Rechercher</button>
      </form>
    </div>
  </section>

  <!-- Category icons bar -->
  <?php if (!$is_search && !is_wp_error($all_categories) && !empty($all_categories)): ?>
  <div class="klp-cats-bar">
    <div class="klp-cats-bar-in">
      <a class="klp-cat-btn active" href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">
        <span class="cat-icon">🌍</span><span>Tout</span>
      </a>
      <?php foreach (array_slice((array)$all_categories, 0, 14) as $cat):
        $icon = $cat_icons[$cat->slug] ?? '🎯';
      ?>
      <a class="klp-cat-btn" href="<?php echo esc_url(add_query_arg(array('fth_category' => $cat->slug, 'fth_mode' => 'activities'), FTH_Templates::get_hub_url('things-to-do'))); ?>">
        <span class="cat-icon"><?php echo $icon; ?></span>
        <span><?php echo esc_html($cat->name); ?></span>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($is_search): ?>
  <!-- ── Search results ──────────────────────────────────── -->
  <section class="klp-section">
    <div class="klp-wrap">
      <div class="klp-section-head">
        <h2><?php echo esc_html($activities->found_posts); ?> résultats trouvés</h2>
        <a href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">← Retour</a>
      </div>
      <div class="klp-grid">
        <?php if ($activities->have_posts()): while ($activities->have_posts()): $activities->the_post();
          echo FTH_Templates::get_activity_card(get_the_ID());
        endwhile; wp_reset_postdata();
        else: ?><div class="klp-empty">Aucun résultat pour cette recherche.</div><?php endif; ?>
      </div>
      <?php if ($activities->max_num_pages > 1): ?>
      <div class="klp-pag"><?php echo paginate_links(array('total' => (int)$activities->max_num_pages, 'current' => $paged)); ?></div>
      <?php endif; ?>
    </div>
  </section>

  <?php else: ?>
  <!-- ── Homepage view ──────────────────────────────────── -->

  <!-- UAE / Dubai featured -->
  <section class="klp-section" style="padding-bottom:0">
    <div class="klp-wrap">
      <div class="klp-dubai-banner">
        <div>
          <h2>🇦🇪 Émirats arabes unis &amp; Dubaï</h2>
          <p>Notre destination phare – des dizaines d'activités, tours et hôtels soigneusement sélectionnés avec des promotions négociées par Yahia Fadlallah.</p>
          <?php if ($dubai_term && !is_wp_error($dubai_term)): ?>
          <a href="<?php echo esc_url(get_term_link($dubai_term)); ?>">Explorer Dubaï →</a>
          <?php endif; ?>
        </div>
        <div style="font-size:80px;flex:0 0 auto">🐪</div>
      </div>
    </div>
  </section>

  <!-- Dubai featured activities -->
  <?php if ($dubai_activities && $dubai_activities->have_posts()): ?>
  <section class="klp-section" style="padding-top:24px">
    <div class="klp-wrap">
      <div class="klp-section-head">
        <h2>🔥 Activités populaires à Dubaï</h2>
        <?php if ($dubai_term && !is_wp_error($dubai_term)): ?>
        <a href="<?php echo esc_url(get_term_link($dubai_term)); ?>">Voir tout →</a>
        <?php endif; ?>
      </div>
      <div class="klp-grid">
        <?php while ($dubai_activities->have_posts()): $dubai_activities->the_post();
          echo FTH_Templates::get_activity_card(get_the_ID());
        endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- All cities -->
  <?php if (!empty($sorted_cities)): ?>
  <section class="klp-section" style="background:#fff">
    <div class="klp-wrap">
      <div class="klp-section-head"><h2>🌍 Explorer par ville</h2></div>
      <div class="klp-cities">
        <?php foreach (array_slice($sorted_cities, 0, 24) as $city):
          $hero = get_term_meta($city->term_id, 'fth_hero_image', true);
          $count = FTH_Templates::get_city_activity_count($city->term_id);
        ?>
        <a class="klp-city-card" href="<?php echo esc_url(get_term_link($city)); ?>">
          <div class="flag"><?php echo ($city->name === 'Dubai' || $city->name === 'Dubaï') ? '🇦🇪' : '📍'; ?></div>
          <div><?php echo esc_html($city->name); ?></div>
          <?php if ($count): ?><div style="font-size:11px;color:#999;margin-top:4px;font-weight:500"><?php echo $count; ?> activité<?php echo $count > 1 ? 's' : ''; ?></div><?php endif; ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- Popular worldwide picks -->
  <section class="klp-section">
    <div class="klp-wrap">
      <div class="klp-section-head"><h2>⭐ Coups de cœur dans le monde</h2></div>
      <div class="klp-grid">
        <?php if ($activities->have_posts()): while ($activities->have_posts()): $activities->the_post();
          echo FTH_Templates::get_activity_card(get_the_ID());
        endwhile; wp_reset_postdata();
        else: ?><div class="klp-empty">Importez des activités depuis l'admin pour les afficher ici.</div><?php endif; ?>
      </div>
    </div>
  </section>

  <?php endif; // end is_search ?>

  <!-- Footer navigation -->
  <footer class="klp-footer-nav">
    <div class="klp-footer-nav-in">
      <div>
        <h4>🎟️ Activités</h4>
        <ul>
          <li><a href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">Toutes les activités</a></li>
          <?php if ($dubai_term && !is_wp_error($dubai_term)): ?>
          <li><a href="<?php echo esc_url(get_term_link($dubai_term)); ?>">Activités à Dubaï</a></li>
          <?php endif; ?>
          <?php foreach (array_slice((array)$sorted_cities, 0, 6) as $city): ?>
          <li><a href="<?php echo esc_url(get_term_link($city)); ?>"><?php echo esc_html($city->name); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4>🏨 Hôtels</h4>
        <ul>
          <li><a href="<?php echo esc_url(FTH_Templates::get_hub_url('hotels')); ?>">Tous les hôtels</a></li>
          <?php foreach (array_slice((array)$sorted_cities, 0, 6) as $city): ?>
          <li><a href="<?php echo esc_url(add_query_arg(array('fth_city' => $city->slug, 'fth_mode' => 'hotels'), FTH_Templates::get_hub_url('hotels'))); ?>">Hôtels à <?php echo esc_html($city->name); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4>🌍 Destinations</h4>
        <ul>
          <?php foreach (array_slice((array)$sorted_countries, 0, 6) as $ctr): ?>
          <li><a href="<?php echo esc_url(get_term_link($ctr)); ?>"><?php echo esc_html($ctr->name); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </footer>

</div>
<?php get_footer(); ?>
