=== Flavor Travel Hub ===
Contributors: flavor
Tags: travel, tourism, klook, activities, tours, booking, affiliate
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional tourism search engine and landing pages with Klook affiliate integration. Completely separated from WP Residence theme.

== Description ==

Flavor Travel Hub is a comprehensive WordPress plugin that creates a complete tourism section on your website, featuring:

* **Custom Post Types**: Destinations, Activities, Hotels
* **Custom Taxonomies**: Countries, Cities, Categories, Activity Types
* **Global Search Engine**: Search activities across all destinations
* **City-Specific Search**: Each city page has its own local search
* **SEO-Optimized Pages**: AIO SEO compatible with proper meta tags and schema
* **Klook Affiliate Integration**: Deep link support for all content
* **Modern Responsive Design**: Clean, professional design with your brand color
* **Pre-loaded Data**: Countries, cities, categories, and sample activities included

= Key Features =

* **Standalone Architecture**: Completely separated from WP Residence theme
* **No Theme Conflicts**: Uses its own templates and styling
* **English Only**: All frontend text in English
* **Brand Customization**: Set your primary color (#19A880 default)
* **Visual Admin**: Dashboard with stats, preview cards, and easy management
* **Shortcodes**: Multiple shortcodes for flexible content display
* **Widgets**: Featured cities, activities, categories, and search widgets

= Shortcodes =

* `[fth_travel_hub]` - Main travel hub page
* `[fth_search_form]` - Search form only
* `[fth_featured_activities count="6"]` - Featured activities grid
* `[fth_featured_cities count="6"]` - Popular cities grid
* `[fth_categories]` - Categories grid
* `[fth_city_activities city="dubai"]` - Activities for a specific city
* `[fth_activities_grid count="12" city="" category=""]` - Custom activities grid

= Pre-loaded Data =

The plugin comes pre-loaded with:

**15 Countries** including UAE, France, Morocco, Saudi Arabia, Japan, Thailand, Singapore, and more.

**30+ Cities** including Dubai, Paris, Tokyo, Bali, Singapore, Istanbul, London, Rome, Barcelona, and more.

**18 Categories** including Attractions, Theme Parks, Desert Safari, Water Activities, Museums, and more.

**8 Activity Types** including Ticket, Tour, Experience, Pass, Transport, and more.

**12+ Sample Activities** with real descriptions, ratings, and images.

== Installation ==

1. Upload the `flavor-travel-hub` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Travel Hub** in your admin menu
4. Visit **Settings** to configure your affiliate ID and brand color
5. Go to **Settings > Permalinks** and click "Save Changes" to flush rewrite rules
6. The main page is automatically created at `/things-to-do/`

== Configuration ==

= Settings =

Navigate to **Travel Hub > Settings** to configure:

* **Primary Brand Color**: Your brand color for buttons and accents
* **Affiliate ID**: Your Klook affiliate ID (default: 115387)
* **Booking Button Text**: Text for CTA buttons (default: "Book Now")
* **Items Per Page**: Number of activities per page
* **Search Placeholder**: Placeholder text for search forms
* **Default Currency**: USD, AED, EUR, GBP, SAR, QAR

= Adding Content =

**Adding a City:**
1. Go to **Travel Hub > Cities**
2. Add the city name and description
3. Select the parent country
4. Add the hero image URL
5. Optionally add the Klook destination deep link

**Adding an Activity:**
1. Go to **Travel Hub > Activities > Add New**
2. Fill in the title, description, and excerpt
3. Set the featured image
4. Select City, Country, Category, and Type
5. Add pricing, rating, and duration
6. Paste the Klook affiliate deep link
7. Add highlights, inclusions, and exclusions

== Klook Affiliate Links ==

The plugin supports official Klook affiliate deep links. Example format:

`https://affiliate.klook.com/redirect?aid=115387&aff_adid=1237857&k_site=https%3A%2F%2Fwww.klook.com%2Fdestination%2Fc78-dubai%2F1-things-to-do%2F`

**Important:** Never let the plugin generate fake URLs. Always use official deep links from your Klook affiliate dashboard.

== AIO SEO Compatibility ==

The plugin is fully compatible with All in One SEO (AIO SEO):

* Custom post types are registered for AIO SEO
* SEO titles and meta descriptions are auto-generated
* Open Graph and Twitter cards are supported
* Schema markup for TouristAttraction is added
* Breadcrumbs are enhanced for travel content

== Frequently Asked Questions ==

= Does this work with WP Residence? =

Yes! The plugin is designed to be completely separate from WP Residence. It uses its own custom post types and taxonomies that don't conflict with property listings.

= Does it require the Klook API? =

No. The plugin works with affiliate deep links only. It doesn't scrape or import data from Klook. You manually add activities with your affiliate links.

= Can I change the brand color? =

Yes. Go to **Travel Hub > Settings** and set your Primary Brand Color. Default is #19A880.

= How do I add the search to my homepage? =

Use the shortcode `[fth_search_form]` or add the "Flavor Travel - Search" widget.

== Changelog ==

= 1.0.0 =
* Initial release
* Custom post types: Destinations, Activities, Hotels
* Custom taxonomies: Countries, Cities, Categories, Types
* Global and city-specific search engines
* SEO-optimized templates
* Klook affiliate deep link support
* Pre-loaded seed data
* Admin dashboard with statistics
* Shortcodes and widgets
* AIO SEO compatibility
* Responsive design with brand customization

== Upgrade Notice ==

= 1.0.0 =
Initial release of Flavor Travel Hub.
