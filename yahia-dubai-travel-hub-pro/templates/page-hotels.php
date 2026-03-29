<?php
/**
 * Template: Hotels hub page – Klook-style v1.7
 * UAE/Dubai first · Search engine
 */
if (!defined('ABSPATH')) { exit; }

$primary       = Flavor_Travel_Hub::get_primary_color();
$secondary     = Flavor_Travel_Hub::get_secondary_color();
$hero_title    = get_option('fth_hotels_hero_title', 'Worldwide Hotels');
$hero_subtitle = get_option('fth_hotels_hero_subtitle', 'Comparez les hôtels, équipements et tarifs – présentés par Yahia Dubai.');
$hero_image    = get_option('fth_hotels_hero_image', 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=1600');

$all_countries = FTH_Taxonomies::get_countries(array('hide_empty' => false));
$all_cities    = FTH_Taxonomies::get_cities(array('hide_empty' => false, 'number' => 60));

$uae_term   = get_term_by('name', 'United Arab Emirates', 'travel_country') ?: get_term_by('name', 'UAE', 'travel_country');
$dubai_term = get_term_by('name', 'Dubai', 'travel_city');

$sorted_countries = is_array($all_countries) ? $all_countries : array();
$sorted_cities    = is_array($all_cities)    ? $all_cities    : array();
if ($uae_term && !is_wp_error($uae_term)) {
    $sorted_countries = array_merge(array($uae_term), array_filter($sorted_countries, function($c) use ($uae_term){ return $c->term_id !== $uae_term->term_id; }));
}
if ($dubai_term && !is_wp_error($dubai_term)) {
    $sorted_cities = array_merge(array($dubai_term), array_filter($sorted_cities, function($c) use ($dubai_term){ return $c->term_id !== $dubai_term->term_id; }));
}

$sq      = isset($_GET['fth_search'])  ? sanitize_text_field(wp_unslash($_GET['fth_search']))  : '';
$s_city  = isset($_GET['fth_city'])    ? sanitize_text_field(wp_unslash($_GET['fth_city']))    : '';
$s_ctr   = isset($_GET['fth_country']) ? sanitize_text_field(wp_unslash($_GET['fth_country'])) : '';
$paged   = max(1, (int) get_query_var('paged'));
$is_search = ($sq !== '' || $s_city !== '' || $s_ctr !== '');

$hotels = FTH_Search::search_hotels(array('keyword' => $sq, 'city' => $s_city, 'country' => $s_ctr, 'per_page' => 12, 'paged' => $paged));

$dubai_hotels = null;
if (!$is_search && $dubai_term && !is_wp_error($dubai_term)) {
    $dubai_hotels = FTH_Search::search_hotels(array('city' => $dubai_term->slug, 'per_page' => 8, 'paged' => 1));
}

get_header();
?>
<style>
html,body,#main_wrapper,.master_header,.master_wrapper,.content_wrapper,.container,.site-content{overflow:visible!important;height:auto!important;max-height:none!important;position:static!important}
body.page .widget-area,body.page .sidebar,body.page .right_sidebar,body.page .page_header,body.page .title_container,body.page .wpestate_header_image,body.page .property_breadcrumbs{display:none!important}
.klph,.klph *{box-sizing:border-box}
.klph{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1a1a1a;background:#f5f5f5;position:relative;z-index:5}
.klph a{text-decoration:none}
.klph-hero{position:relative;min-height:480px;display:flex;align-items:center;justify-content:center;overflow:hidden;background:#0a0a1a}
.klph-hero-bg{position:absolute;inset:0;background:url('<?php echo esc_url($hero_image); ?>') center/cover no-repeat;opacity:.35}
.klph-hero-overlay{position:absolute;inset:0;background:linear-gradient(180deg,rgba(5,5,20,.25) 0%,rgba(5,5,20,.82) 100%)}
.klph-hero-inner{position:relative;z-index:3;text-align:center;padding:80px 20px;width:100%;max-width:1280px;margin:0 auto}
.klph-hero-badge{display:inline-flex;align-items:center;gap:8px;padding:8px 16px;border-radius:999px;background:rgba(255,255,255,.15);color:#fff;font-weight:800;font-size:13px;border:1px solid rgba(255,255,255,.2);margin-bottom:18px}
.klph-hero-title{font-size:48px;font-weight:900;color:#fff!important;line-height:1.05;margin:0 0 14px}
.klph-hero-sub{font-size:18px;color:rgba(255,255,255,.9);margin:0 auto 32px;max-width:700px;line-height:1.6}
.klph-search{max-width:960px;margin:0 auto;background:#fff;border-radius:20px;padding:14px;display:grid;grid-template-columns:1.8fr 1fr 1fr 160px;gap:10px;box-shadow:0 20px 60px rgba(0,0,0,.25)}
.klph-search input,.klph-search select{width:100%;height:56px;padding:0 16px;border:1.5px solid #e5e7eb;border-radius:12px;font-size:14px;color:#1a1a1a;background:#fff}
.klph-search input:focus,.klph-search select:focus{outline:none;border-color:<?php echo esc_attr($primary); ?>}
.klph-search button{height:56px;border:none;border-radius:12px;background:<?php echo esc_attr($primary); ?>;color:#fff;font-weight:800;font-size:15px;cursor:pointer}
.klph-section{padding:44px 0}
.klph-wrap{max-width:1280px;margin:0 auto;padding:0 20px}
.klph-section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px}
.klph-section-head h2{margin:0;font-size:26px;font-weight:800}
.klph-section-head a{font-size:14px;color:<?php echo esc_attr($primary); ?>;font-weight:700}
.klph-cities{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:14px}
.klph-city-card{background:#fff;border-radius:14px;padding:16px;text-align:center;font-weight:800;color:#1a1a1a;box-shadow:0 2px 8px rgba(0,0,0,.06);transition:box-shadow .2s,transform .2s;border:1px solid #eee}
.klph-city-card:hover{box-shadow:0 8px 24px rgba(0,0,0,.12);transform:translateY(-2px)}
.klph-city-card .flag{font-size:26px;margin-bottom:8px}
.klph-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:20px}
.klph-dubai-banner{background:linear-gradient(135deg,#1e3a5f,<?php echo esc_attr($primary); ?>);border-radius:20px;padding:30px 36px;display:flex;align-items:center;justify-content:space-between;gap:20px;flex-wrap:wrap}
.klph-dubai-banner h2{margin:0 0 8px;font-size:28px;font-weight:900;color:#fff}
.klph-dubai-banner p{margin:0;color:rgba(255,255,255,.88);font-size:15px;max-width:560px}
.klph-dubai-banner a{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border-radius:12px;background:#fff;color:<?php echo esc_attr($primary); ?>;font-weight:800;font-size:15px;white-space:nowrap;margin-top:8px}
.klph-empty{background:#fff;border-radius:14px;padding:40px;text-align:center;color:#888;font-size:15px}
.klph-pag{text-align:center;margin-top:28px}
.klph-pag .page-numbers{display:inline-flex;margin:0 3px;padding:9px 14px;border-radius:8px;border:1.5px solid #e5e7eb;color:#1a1a1a;font-weight:700;font-size:14px;background:#fff}
.klph-pag .current{background:<?php echo esc_attr($primary); ?>;color:#fff;border-color:<?php echo esc_attr($primary); ?>}
.klph-footer-nav{background:#1a1a1a;color:#fff;padding:36px 0}
.klph-footer-nav-in{max-width:1280px;margin:0 auto;padding:0 20px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:32px}
.klph-footer-nav h4{margin:0 0 14px;font-size:14px;font-weight:800;color:#fff;text-transform:uppercase;letter-spacing:.5px}
.klph-footer-nav ul{list-style:none;padding:0;margin:0;display:grid;gap:8px}
.klph-footer-nav ul li a{color:rgba(255,255,255,.7);font-size:13px;transition:color .15s}
.klph-footer-nav ul li a:hover{color:#fff}
@media(max-width:1160px){.klph-search{grid-template-columns:1fr 1fr 1fr}.klph-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.klph-cities{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:720px){.klph-hero-title{font-size:32px}.klph-search{grid-template-columns:1fr}.klph-grid,.klph-cities{grid-template-columns:repeat(2,minmax(0,1fr))}.klph-footer-nav-in{grid-template-columns:1fr}}
</style>

<div class="klph">
  <section class="klph-hero">
    <div class="klph-hero-bg"></div>
    <div class="klph-hero-overlay"></div>
    <div class="klph-hero-inner">
      <div class="klph-hero-badge">🏨 Hôtels dans le monde entier</div>
      <h1 class="klph-hero-title"><?php echo esc_html($hero_title); ?></h1>
      <p class="klph-hero-sub"><?php echo esc_html($hero_subtitle); ?></p>
      <form class="klph-search" method="get" action="<?php echo esc_url(FTH_Templates::get_hub_url('hotels')); ?>">
        <input type="hidden" name="fth_mode" value="hotels">
        <input type="text" name="fth_search" placeholder="🔍 Rechercher un hôtel…" value="<?php echo esc_attr($sq); ?>">
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
        <button type="submit">Rechercher</button>
      </form>
    </div>
  </section>

  <?php if ($is_search): ?>
  <section class="klph-section">
    <div class="klph-wrap">
      <div class="klph-section-head">
        <h2><?php echo esc_html($hotels->found_posts); ?> hôtels trouvés</h2>
        <a href="<?php echo esc_url(FTH_Templates::get_hub_url('hotels')); ?>">← Retour</a>
      </div>
      <div class="klph-grid">
        <?php if ($hotels->have_posts()): while ($hotels->have_posts()): $hotels->the_post();
          echo FTH_Templates::get_hotel_card(get_the_ID());
        endwhile; wp_reset_postdata();
        else: ?><div class="klph-empty">Aucun hôtel trouvé.</div><?php endif; ?>
      </div>
      <?php if ($hotels->max_num_pages > 1): ?>
      <div class="klph-pag"><?php echo paginate_links(array('total' => (int)$hotels->max_num_pages, 'current' => $paged)); ?></div>
      <?php endif; ?>
    </div>
  </section>

  <?php else: ?>

  <section class="klph-section" style="padding-bottom:0">
    <div class="klph-wrap">
      <div class="klph-dubai-banner">
        <div>
          <h2>🇦🇪 Hôtels aux Émirats arabes unis</h2>
          <p>Découvrez notre sélection d'hôtels à Dubaï et dans les Émirats – avec des promotions exclusives négociées par Yahia Fadlallah.</p>
          <?php if ($dubai_term && !is_wp_error($dubai_term)): ?>
          <a href="<?php echo esc_url(add_query_arg(array('fth_city' => $dubai_term->slug, 'fth_mode' => 'hotels'), FTH_Templates::get_hub_url('hotels'))); ?>">Voir les hôtels à Dubaï →</a>
          <?php endif; ?>
        </div>
        <div style="font-size:80px;flex:0 0 auto">🏙️</div>
      </div>
    </div>
  </section>

  <?php if ($dubai_hotels && $dubai_hotels->have_posts()): ?>
  <section class="klph-section" style="padding-top:24px">
    <div class="klph-wrap">
      <div class="klph-section-head">
        <h2>🔥 Hôtels populaires à Dubaï</h2>
        <?php if ($dubai_term && !is_wp_error($dubai_term)): ?>
        <a href="<?php echo esc_url(add_query_arg(array('fth_city' => $dubai_term->slug, 'fth_mode' => 'hotels'), FTH_Templates::get_hub_url('hotels'))); ?>">Voir tout →</a>
        <?php endif; ?>
      </div>
      <div class="klph-grid">
        <?php while ($dubai_hotels->have_posts()): $dubai_hotels->the_post();
          echo FTH_Templates::get_hotel_card(get_the_ID());
        endwhile; wp_reset_postdata(); ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <section class="klph-section" style="background:#fff">
    <div class="klph-wrap">
      <div class="klph-section-head"><h2>🌍 Explorer par ville</h2></div>
      <div class="klph-cities">
        <?php foreach (array_slice($sorted_cities, 0, 24) as $city): ?>
        <a class="klph-city-card" href="<?php echo esc_url(add_query_arg(array('fth_city' => $city->slug, 'fth_mode' => 'hotels'), FTH_Templates::get_hub_url('hotels'))); ?>">
          <div class="flag"><?php echo ($city->name === 'Dubai' || $city->name === 'Dubaï') ? '🇦🇪' : '📍'; ?></div>
          <div><?php echo esc_html($city->name); ?></div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="klph-section">
    <div class="klph-wrap">
      <div class="klph-section-head"><h2>⭐ Hôtels en vedette</h2></div>
      <div class="klph-grid">
        <?php if ($hotels->have_posts()): while ($hotels->have_posts()): $hotels->the_post();
          echo FTH_Templates::get_hotel_card(get_the_ID());
        endwhile; wp_reset_postdata();
        else: ?><div class="klph-empty">Importez des hôtels depuis l'admin pour les afficher ici.</div><?php endif; ?>
      </div>
    </div>
  </section>

  <?php endif; // end is_search ?>

  <footer class="klph-footer-nav">
    <div class="klph-footer-nav-in">
      <div>
        <h4>🏨 Hôtels</h4>
        <ul>
          <li><a href="<?php echo esc_url(FTH_Templates::get_hub_url('hotels')); ?>">Tous les hôtels</a></li>
          <?php foreach (array_slice($sorted_cities, 0, 6) as $city): ?>
          <li><a href="<?php echo esc_url(add_query_arg(array('fth_city' => $city->slug, 'fth_mode' => 'hotels'), FTH_Templates::get_hub_url('hotels'))); ?>">Hôtels à <?php echo esc_html($city->name); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4>🎟️ Activités</h4>
        <ul>
          <li><a href="<?php echo esc_url(FTH_Templates::get_hub_url('things-to-do')); ?>">Toutes les activités</a></li>
          <?php foreach (array_slice($sorted_cities, 0, 6) as $city): ?>
          <li><a href="<?php echo esc_url(get_term_link($city)); ?>"><?php echo esc_html($city->name); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div>
        <h4>🌍 Pays</h4>
        <ul>
          <?php foreach (array_slice($sorted_countries, 0, 6) as $ctr): ?>
          <li><a href="<?php echo esc_url(get_term_link($ctr)); ?>"><?php echo esc_html($ctr->name); ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </footer>
</div>
<?php get_footer(); ?>
