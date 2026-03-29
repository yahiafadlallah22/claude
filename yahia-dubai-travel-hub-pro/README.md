# Flavor Travel Hub

Professional WordPress plugin for tourism search engine and landing pages with Klook affiliate integration.

## Features

- **Custom Post Types**: Destinations, Activities, Hotels
- **Custom Taxonomies**: Countries, Cities, Categories, Activity Types
- **Global Search Engine**: Search activities across all destinations
- **City-Specific Search**: Each city page has its own local search
- **SEO-Optimized**: AIO SEO compatible with proper meta tags and schema
- **Klook Integration**: Deep link support for all content
- **Modern Design**: Clean, responsive design with brand color customization
- **Pre-loaded Data**: 15 countries, 30+ cities, 18 categories, sample activities

## Installation

1. Upload `flavor-travel-hub` folder to `/wp-content/plugins/`
2. Activate the plugin in WordPress admin
3. Go to **Travel Hub** in admin menu
4. Visit **Settings > Permalinks** and click "Save Changes"
5. Main page is at `/things-to-do/`

## Configuration

### Settings (Travel Hub > Settings)
- Primary Brand Color: `#19A880` (default)
- Affiliate ID: `115387` (default)
- Booking Button Text: "Book Now"
- Items Per Page: 12
- Default Currency: USD

## Shortcodes

```
[fth_travel_hub]                          - Main travel hub page
[fth_search_form]                         - Search form only
[fth_featured_activities count="6"]       - Featured activities
[fth_featured_cities count="6"]           - Popular cities
[fth_categories]                          - Categories grid
[fth_city_activities city="dubai"]        - City-specific activities
[fth_activities_grid count="12"]          - Custom grid
```

## Pre-loaded Data

### Countries (15)
UAE, France, Morocco, Saudi Arabia, Qatar, Turkey, Thailand, Japan, Singapore, Indonesia, UK, Italy, Spain, Egypt, Malaysia

### Cities (30+)
Dubai, Abu Dhabi, Paris, Marrakech, Riyadh, Doha, Istanbul, Bangkok, Tokyo, Bali, Singapore, London, Rome, Barcelona, and more

### Categories (18)
Attractions, Theme Parks, Desert Safari, Water Activities, Museums, Observation Decks, Boat Tours, City Tours, Family Activities, Cultural Experiences, Outdoor Activities, Transfers, Dining, Adventure Tours, Shows, Day Trips, Wellness, Nightlife

## Klook Affiliate Links

Use official deep links from your Klook affiliate dashboard:
```
https://affiliate.klook.com/redirect?aid=YOUR_ID&aff_adid=XXX&k_site=https%3A%2F%2Fwww.klook.com%2F...
```

## File Structure

```
flavor-travel-hub/
├── flavor-travel-hub.php      # Main plugin file
├── includes/
│   ├── class-fth-post-types.php
│   ├── class-fth-taxonomies.php
│   ├── class-fth-meta-boxes.php
│   ├── class-fth-templates.php
│   ├── class-fth-search.php
│   ├── class-fth-seo.php
│   ├── class-fth-shortcodes.php
│   ├── class-fth-widgets.php
│   ├── class-fth-ajax.php
│   └── class-fth-seed-data.php
├── admin/
│   ├── class-fth-admin.php
│   ├── class-fth-admin-settings.php
│   ├── class-fth-admin-dashboard.php
│   └── class-fth-admin-preview.php
├── public/
│   └── class-fth-public.php
├── templates/
│   ├── single-travel-activity.php
│   ├── single-travel-destination.php
│   ├── archive-travel-activity.php
│   ├── taxonomy-travel-city.php
│   ├── taxonomy-travel-country.php
│   └── taxonomy-travel-category.php
├── assets/
│   ├── css/
│   │   ├── public.css
│   │   └── admin.css
│   └── js/
│       ├── public.js
│       └── admin.js
├── readme.txt
└── README.md
```

## Support

For issues or feature requests, contact the developer.

## License

GPL v2 or later
