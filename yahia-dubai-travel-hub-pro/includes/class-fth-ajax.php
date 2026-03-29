<?php
/**
 * AJAX Handlers
 */

if (!defined('ABSPATH')) {
    exit;
}

class FTH_Ajax {
    
    /**
     * Initialize AJAX handlers
     */
    public static function init() {
        // Public AJAX
        add_action('wp_ajax_fth_search_activities', array(__CLASS__, 'search_activities'));
        add_action('wp_ajax_nopriv_fth_search_activities', array(__CLASS__, 'search_activities'));
        
        add_action('wp_ajax_fth_load_more_activities', array(__CLASS__, 'load_more_activities'));
        add_action('wp_ajax_nopriv_fth_load_more_activities', array(__CLASS__, 'load_more_activities'));
        
        add_action('wp_ajax_fth_filter_activities', array(__CLASS__, 'filter_activities'));
        add_action('wp_ajax_nopriv_fth_filter_activities', array(__CLASS__, 'filter_activities'));
        
        add_action('wp_ajax_fth_get_cities_by_country', array(__CLASS__, 'get_cities_by_country'));
        add_action('wp_ajax_nopriv_fth_get_cities_by_country', array(__CLASS__, 'get_cities_by_country'));
        
        // Search suggestions (autocomplete)
        add_action('wp_ajax_fth_search_suggestions', array(__CLASS__, 'search_suggestions'));
        add_action('wp_ajax_nopriv_fth_search_suggestions', array(__CLASS__, 'search_suggestions'));
        
        // Admin AJAX
        add_action('wp_ajax_fth_admin_preview_activity', array(__CLASS__, 'admin_preview_activity'));
        add_action('wp_ajax_fth_admin_bulk_action', array(__CLASS__, 'admin_bulk_action'));
        
        // Klook Scraper AJAX
        add_action('wp_ajax_fth_scrape_klook', array(__CLASS__, 'scrape_klook'));
        
        // Klook City Scraper AJAX
        add_action('wp_ajax_fth_scrape_klook_city', array(__CLASS__, 'scrape_klook_city'));
        
        // Klook Destination Scraper AJAX
        add_action('wp_ajax_fth_scrape_klook_destination', array(__CLASS__, 'scrape_klook_destination'));
        
        // Import and Publish (main feature)
        add_action('wp_ajax_fth_import_and_publish', array(__CLASS__, 'import_and_publish'));
        add_action('wp_ajax_fth_import_bulk_city', array(__CLASS__, 'import_bulk_city'));
        add_action('wp_ajax_fth_import_bulk_hotels', array(__CLASS__, 'import_bulk_hotels'));
        add_action('wp_ajax_fth_import_bulk_urls', array(__CLASS__, 'import_bulk_urls'));
        add_action('wp_ajax_fth_import_bulk_country', array(__CLASS__, 'import_bulk_country'));
    }
    


private static function begin_import_request() {
    if (function_exists('ignore_user_abort')) {
        @ignore_user_abort(true);
    }
    @set_time_limit(300);
    @ini_set('display_errors', '0');
}

private static function send_json_success_clean($data = array()) {
    while (ob_get_level() > 0) { @ob_end_clean(); }
    wp_send_json_success($data);
}

private static function send_json_error_clean($message, $extra = array()) {
    while (ob_get_level() > 0) { @ob_end_clean(); }
    wp_send_json_error(array_merge(array('message' => $message), $extra));
}

private static function get_scraperapi_key() {
    $key = get_option('fth_scraperapi_key', '');
    if (empty($key)) {
        $key = 'ecdd48490f38ad039aace84101208f7a';
    }
    return trim($key);
}

private static function remote_get($url, $args = array()) {
    static $ua_index = 0;
    $user_agents = array(
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:125.0) Gecko/20100101 Firefox/125.0',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
    );
    $ua      = $user_agents[$ua_index % count($user_agents)];
    $ua_index++;
    $timeout     = isset($args['timeout']) ? (int) $args['timeout'] : 60;
    $extra_hdrs  = isset($args['headers']) ? (array) $args['headers'] : array();

    if (function_exists('curl_init')) {
        // Persistent cookie file — keeps Cloudflare session tokens across requests
        $cookie_file = sys_get_temp_dir() . '/fth_klook_session.txt';

        // Complete Chrome 124 header set — closest match to a real browser
        $headers = array_merge(array(
            'User-Agent'                => $ua,
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
            'Accept-Language'           => 'en-US,en;q=0.9',
            'Accept-Encoding'           => 'gzip, deflate, br',
            'Cache-Control'             => 'max-age=0',
            'Connection'                => 'keep-alive',
            'sec-ch-ua'                 => '"Google Chrome";v="124", "Chromium";v="124", "Not-A.Brand";v="99"',
            'sec-ch-ua-mobile'          => '?0',
            'sec-ch-ua-platform'        => '"Windows"',
            'Sec-Fetch-Dest'            => 'document',
            'Sec-Fetch-Mode'            => 'navigate',
            'Sec-Fetch-Site'            => 'none',
            'Sec-Fetch-User'            => '?1',
            'Upgrade-Insecure-Requests' => '1',
        ), $extra_hdrs);

        $curl_headers = array();
        foreach ($headers as $k => $v) {
            $curl_headers[] = $k . ': ' . $v;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => '', // auto-decompress gzip/deflate/br
            CURLOPT_HTTPHEADER     => $curl_headers,
            CURLOPT_COOKIEJAR      => $cookie_file,  // persist cookies between requests
            CURLOPT_COOKIEFILE     => $cookie_file,
            CURLOPT_HTTP_VERSION   => defined('CURL_HTTP_VERSION_2_0') ? CURL_HTTP_VERSION_2_0 : CURL_HTTP_VERSION_1_1,
        ));

        $body      = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err       = curl_error($ch);
        curl_close($ch);

        if ($err || !$body) {
            return new WP_Error('curl_error', $err ?: 'Empty response from ' . $url);
        }

        return array(
            'body'     => $body,
            'response' => array('code' => $http_code, 'message' => ''),
            'headers'  => array(),
            'cookies'  => array(),
        );
    }

    // Fallback: wp_remote_get
    return wp_remote_get($url, array(
        'timeout'     => $timeout,
        'redirection' => 5,
        'user-agent'  => $ua,
        'headers'     => array_merge(array(
            'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
            'Accept-Language'           => 'en-US,en;q=0.9',
            'Accept-Encoding'           => 'gzip, deflate',
            'Cache-Control'             => 'max-age=0',
            'sec-ch-ua'                 => '"Google Chrome";v="124", "Chromium";v="124", "Not-A.Brand";v="99"',
            'sec-ch-ua-mobile'          => '?0',
            'Sec-Fetch-Dest'            => 'document',
            'Sec-Fetch-Mode'            => 'navigate',
            'Sec-Fetch-Site'            => 'none',
            'Sec-Fetch-User'            => '?1',
            'Upgrade-Insecure-Requests' => '1',
        ), $extra_hdrs),
    ));
}

private static function build_affiliate_redirect($url) {
    $affiliate_id = Flavor_Travel_Hub::get_affiliate_id();
    return 'https://affiliate.klook.com/redirect?aid=' . rawurlencode($affiliate_id) . '&aff_adid=1238080&k_site=' . rawurlencode($url);
}

/**
 * Fetch a Klook page HTML with multiple bypass strategies for Cloudflare.
 *
 * Tries (in order):
 *  1. Direct cURL with Chrome 124 headers (cookie-persisted session)
 *  2. Same URL without /en-US/ locale prefix
 *  3. Google Web Cache — Googlebot is whitelisted by Cloudflare
 *  4. Wayback Machine latest snapshot (archive.org)
 *
 * Returns the first response body that contains __NEXT_DATA__.
 * If none have __NEXT_DATA__, returns whatever body was obtained (for og: tag parsing).
 *
 * @param  string $url   Klook URL (already normalised to /en-US/)
 * @return array  { body: string, url: string, source: string }
 */
private static function fetch_klook_html($url) {
    $result    = array('body' => '', 'url' => $url, 'source' => 'none');
    $best_body = '';

    // ── 1. Direct cURL with Chrome 124 headers ────────────────────────
    $r = self::remote_get($url, array('timeout' => 45));
    if (!is_wp_error($r)) {
        $b = wp_remote_retrieve_body($r);
        if (!empty($b) && strpos($b, '__NEXT_DATA__') !== false) {
            return array('body' => $b, 'url' => $url, 'source' => 'direct');
        }
        if (!empty($b)) $best_body = $b;
    }

    // ── 2. Without /en-US/ locale ────────────────────────────────────
    if (strpos($url, '/en-US/') !== false) {
        $url_nl = preg_replace('#/en-US/#', '/', $url);
        $r2 = self::remote_get($url_nl, array('timeout' => 45));
        if (!is_wp_error($r2)) {
            $b2 = wp_remote_retrieve_body($r2);
            if (!empty($b2) && strpos($b2, '__NEXT_DATA__') !== false) {
                return array('body' => $b2, 'url' => $url_nl, 'source' => 'direct_noloc');
            }
        }
    }

    // ── 3. Wayback Machine CDX API → precise timestamp → raw if_ fetch ─
    // NOTE: Google Web Cache was permanently shut down in February 2024.
    // We use the CDX API instead: it returns the exact snapshot timestamp,
    // then we fetch the raw HTML using the "if_" modifier which strips the
    // WB toolbar so __NEXT_DATA__ is preserved intact.
    $url_bare = preg_replace('#^https?://#', '', rtrim($url, '/'));
    $cdx_q    = http_build_query(array(
        'url'    => $url_bare,
        'output' => 'json',
        'fl'     => 'timestamp',
        'limit'  => 1,
        'from'   => date('Ymd', strtotime('-1 year')),
        'to'     => date('Ymd'),
        'filter' => 'statuscode:200',
    ));
    $cdx_r = self::remote_get('https://web.archive.org/cdx/search/cdx?' . $cdx_q, array('timeout' => 25));
    if (!is_wp_error($cdx_r)) {
        $rows = json_decode(wp_remote_retrieve_body($cdx_r), true);
        // Format: [["timestamp"], ["20241215120000"]]  (header row + data row)
        if (is_array($rows) && count($rows) >= 2 && isset($rows[1][0])) {
            $ts     = $rows[1][0];
            // if_ = serve raw archived HTML without WB toolbar/banner
            $wb_url = 'https://web.archive.org/web/' . $ts . 'if_/' . $url;
            $wb_r   = self::remote_get($wb_url, array('timeout' => 60));
            if (!is_wp_error($wb_r)) {
                $wb_b = wp_remote_retrieve_body($wb_r);
                if (!empty($wb_b) && strpos($wb_b, '__NEXT_DATA__') !== false) {
                    return array('body' => $wb_b, 'url' => $url, 'source' => 'wayback_cdx');
                }
                if (!empty($wb_b) && empty($best_body)) $best_body = $wb_b;
            }
        }
    }

    // ── 4. Wayback Machine availability API (simpler fallback) ────────
    $avail_r = self::remote_get('https://archive.org/wayback/available?url=' . rawurlencode($url_bare), array('timeout' => 20));
    if (!is_wp_error($avail_r)) {
        $avail = json_decode(wp_remote_retrieve_body($avail_r), true);
        $snap  = isset($avail['archived_snapshots']['closest']['url']) ? $avail['archived_snapshots']['closest']['url'] : '';
        if (!empty($snap)) {
            // Convert standard WB URL to if_ (raw) version
            $snap_raw = preg_replace('#/web/(\d+)/#', '/web/${1}if_/', $snap);
            $snap_r   = self::remote_get($snap_raw, array('timeout' => 60));
            if (!is_wp_error($snap_r)) {
                $snap_b = wp_remote_retrieve_body($snap_r);
                if (!empty($snap_b) && strpos($snap_b, '__NEXT_DATA__') !== false) {
                    return array('body' => $snap_b, 'url' => $url, 'source' => 'wayback_avail');
                }
                if (!empty($snap_b) && empty($best_body)) $best_body = $snap_b;
            }
        }
    }

    $result['body'] = $best_body;
    return $result;
}

/**
 * Discover activity or hotel URLs for a city directly from the Wayback Machine CDX index.
 * This bypasses Cloudflare entirely — no live Klook page is fetched.
 *
 * @param string $type      'activity' or 'hotel'
 * @param string $city_slug City slug, e.g. 'dubai'
 * @param int    $limit     Max URLs to return
 * @return string[]  Klook URLs (normalised to /en-US/)
 */
private static function discover_klook_urls_via_cdx($type, $city_slug, $limit = 100) {
    if (empty($city_slug)) return array();

    if ($type === 'hotel') {
        // Hotel URLs always contain the city slug: /en-US/hotels/{city}/hotel/{id}-{slug}/
        $pattern  = 'www.klook.com/*/hotels/' . $city_slug . '/hotel/*';
        $match_type = 'prefix'; // won't work with * in middle; use domain match
        // Use domain + path prefix trick via filter
        $q = http_build_query(array(
            'url'        => 'www.klook.com/en-US/hotels/' . $city_slug . '/hotel/',
            'output'     => 'json',
            'fl'         => 'original',
            'filter'     => 'statuscode:200',
            'collapse'   => 'urlkey',
            'limit'      => min($limit + 50, 300),
            'matchType'  => 'prefix',
            'from'       => date('Ymd', strtotime('-2 years')),
        ));
    } else {
        // Activity URLs: /en-US/activity/{id}-{slug-containing-city}/
        // Use CDX with city slug filter on the original URL
        $q = http_build_query(array(
            'url'        => 'www.klook.com/en-US/activity/',
            'output'     => 'json',
            'fl'         => 'original',
            'collapse'   => 'urlkey',
            'limit'      => min($limit * 6, 600),
            'matchType'  => 'prefix',
            'from'       => date('Ymd', strtotime('-2 years')),
        ));
        // append double filter (CDX allows repeated filter params)
        $q .= '&filter=statuscode:200&filter=original:.*' . rawurlencode($city_slug) . '.*';
    }

    $r = self::remote_get('https://web.archive.org/cdx/search/cdx?' . $q, array('timeout' => 35));
    if (is_wp_error($r)) return array();

    $rows = json_decode(wp_remote_retrieve_body($r), true);
    if (!is_array($rows) || count($rows) < 2) return array();

    $urls = array();
    foreach (array_slice($rows, 1) as $row) { // skip header row
        $u = isset($row[0]) ? $row[0] : '';
        if (!$u) continue;
        if (!preg_match('#^https?://#', $u)) $u = 'https://' . $u;
        // Must be a single activity/hotel page, not a listing
        if ($type === 'activity' && !preg_match('#/activity/\d+-#', $u)) continue;
        if ($type === 'hotel'    && !preg_match('#/hotel/\d+-#', $u))    continue;
        // For activities: confirm city slug present
        if ($type === 'activity' && stripos($u, $city_slug) === false) continue;
        // Normalise to /en-US/
        $u = preg_replace('#(klook\.com)/[a-z]{2}[-_][A-Za-z]{2,4}/#', '$1/en-US/', $u);
        if (!in_array($u, $urls, true)) {
            $urls[] = $u;
        }
        if (count($urls) >= $limit) break;
    }
    return $urls;
}



private static function array_find_first($data, $keys) {
    if (!is_array($data)) {
        return '';
    }
    foreach ($keys as $key) {
        if (isset($data[$key]) && $data[$key] !== '' && $data[$key] !== null) {
            return $data[$key];
        }
    }
    foreach ($data as $value) {
        if (is_array($value)) {
            $found = self::array_find_first($value, $keys);
            if ($found !== '' && $found !== null) {
                return $found;
            }
        }
    }
    return '';
}

private static function array_collect_values($data, $keys, &$results = array()) {
    if (!is_array($data)) {
        return $results;
    }
    foreach ($data as $key => $value) {
        if (in_array($key, $keys, true)) {
            if (is_string($value) && $value !== '') {
                $results[] = $value;
            } elseif (is_array($value)) {
                foreach ($value as $sub) {
                    if (is_string($sub) && $sub !== '') {
                        $results[] = $sub;
                    } elseif (is_array($sub)) {
                        $maybe = self::array_find_first($sub, array('url', 'src', 'imageUrl', 'coverImageUrl', 'originalUrl'));
                        if (is_string($maybe) && $maybe !== '') {
                            $results[] = $maybe;
                        }
                    }
                }
            }
        }
        if (is_array($value)) {
            self::array_collect_values($value, $keys, $results);
        }
    }
    return $results;
}

private static function normalize_text_block($value) {
    if (is_array($value)) {
        $parts = array();
        array_walk_recursive($value, function($item) use (&$parts) {
            if (is_scalar($item)) {
                $item = trim(wp_strip_all_tags((string) $item));
                if ($item !== '') {
                    $parts[] = $item;
                }
            }
        });
        $value = implode("\n", array_unique($parts));
    }
    $value = html_entity_decode((string) $value, ENT_QUOTES, 'UTF-8');
    $value = preg_replace('/\s+/', ' ', $value);
    return trim($value);
}

private static function html_paragraphs($value) {
    $text = self::normalize_text_block($value);
    if ($text === '') {
        return '';
    }
    return '<p>' . esc_html($text) . '</p>';
}

private static function bullet_lines($value, $limit = 8) {
    $text = self::normalize_text_block($value);
    if ($text === '') {
        return '';
    }
    $parts = preg_split('/\s*[•\-\|]\s*|\r\n|\n|;/u', $text);
    $parts = array_values(array_filter(array_map('trim', $parts)));
    if (empty($parts)) {
        return '';
    }
    $parts = array_slice(array_unique($parts), 0, $limit);
    return implode("\n", $parts);
}


private static function is_valid_content_image_url($url) {
    $url = trim((string) $url);
    if ($url === '' || stripos($url, 'data:image') === 0) {
        return false;
    }
    if (!preg_match('#^https?://#i', $url)) {
        return false;
    }
    $blocked = array('logo', 'icon', 'avatar', 'sprite', 'placeholder', 'favicon', 'apple-touch-icon', 'app-store', 'google-play');
    foreach ($blocked as $word) {
        if (stripos($url, $word) !== false) {
            return false;
        }
    }
    // Always accept Klook CDN URLs — their image URLs often lack a file extension
    if (stripos($url, 'res.klook.com') !== false) {
        return true;
    }
    return (bool) preg_match('/\.(jpe?g|png|webp)(\?.*)?$/i', $url);
}

private static function clean_image_urls($urls, $limit = 5) {
    $clean = array();
    foreach ((array) $urls as $url) {
        $url = esc_url_raw(html_entity_decode((string) $url, ENT_QUOTES, 'UTF-8'));
        if (!$url || !self::is_valid_content_image_url($url)) {
            continue;
        }
        if (!in_array($url, $clean, true)) {
            $clean[] = $url;
        }
        if (count($clean) >= $limit) {
            break;
        }
    }
    return $clean;
}

private static function normalize_klook_link($link) {
    $link = html_entity_decode((string) $link, ENT_QUOTES, 'UTF-8');
    $link = str_replace('\/', '/', $link);
    $link = trim($link, " \"'");
    if ($link === '') {
        return '';
    }
    if (strpos($link, 'http') !== 0) {
        if (strpos($link, '/') !== 0) {
            $link = '/' . $link;
        }
        $link = 'https://www.klook.com' . $link;
    }
    $link = preg_replace('/([?#]).*$/', '', $link);
    return esc_url_raw($link);
}

private static function clean_klook_branding_text($text) {
    $text = html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\s*[-|–—]\s*Klook.*$/iu', '', $text);
    $text = str_ireplace(array('Klook exclusive', 'on Klook', 'with Klook', 'via Klook', 'Klook'), '', $text);
    // After stripping "Klook", clean orphaned domain fragments like ".com", ".fr", ".de"
    $text = preg_replace('/^\s*\.(?:com|net|org|fr|de|cn|hk|tw|sg|my|id|th|vn|ae|sa|eg|uk|au|nz)\s*$/iu', '', $text);
    $text = preg_replace('/\b\d{4}\s+Updated\s+prices?.*$/iu', '', $text);
    $text = preg_replace('/\b(Updated prices?|Deals?|Reviews?|Book now)\b.*$/iu', '', $text);
    $text = preg_replace('/\s+/', ' ', trim($text));
    return trim($text);
}

private static function title_from_url_slug($url) {
    $path = parse_url((string) $url, PHP_URL_PATH);
    if (!$path) {
        return '';
    }
    $path = trim($path, '/');
    $parts = explode('/', $path);
    $slug = end($parts);
    $slug = preg_replace('/^\d+-/', '', (string) $slug);
    $slug = str_replace('-', ' ', $slug);
    $slug = preg_replace('/\s+/', ' ', trim($slug));
    return $slug ? ucwords($slug) : '';
}

private static function normalize_front_title($title, $url = '') {
    $title = self::clean_klook_branding_text($title);
    // Guard: discard if empty, too short, or contains no alphanumeric characters
    // (e.g. ")", ">", single punctuation marks coming from broken JSON extraction)
    if ($title !== '' && (mb_strlen(trim($title)) < 3 || !preg_match('/[a-zA-Z0-9\x{00C0}-\x{024F}]/u', $title))) {
        $title = '';
    }
    if ($title !== '') {
        $parts = preg_split('/\s*[|–—]\s*/u', $title);
        if (!empty($parts[0]) && mb_strlen(trim($parts[0])) >= 3) {
            $title = trim($parts[0]);
        }
    }
    if ($title !== '' && preg_match('/^[a-z0-9\-]+$/i', $title)) {
        $title = '';
    }
    // Reject domain fragments like ".com", ".fr" that appear after stripping Klook branding
    if ($title !== '' && preg_match('/^\s*\.?(?:com|net|org|fr|de|cn|hk|tw|sg|my|id|th|vn|ae|sa|eg|uk|au|nz)\s*$/iu', $title)) {
        $title = '';
    }
    // Reject generic Klook page titles (bot-blocked or homepage response)
    if ($title !== '' && preg_match('/^(?:book\s+experiences|travel\s+experiences|things\s+to\s+do|book\s+tours?|attractions?)$/iu', trim($title))) {
        $title = '';
    }
    if ($title === '') {
        $title = self::title_from_url_slug($url);
    }
    $title = preg_replace('/\b(Hotel Deal|Best Price|Updated Prices?|Deals?)\b/iu', '', $title);
    $title = preg_replace('/\s+/', ' ', trim($title));
    if (mb_strlen($title) > 90) {
        $title = trim(mb_substr($title, 0, 90));
        $title = preg_replace('/\s+\S*$/u', '', $title);
    }
    return trim($title);
}

private static function maybe_hotel_price($value) {
    $price = self::normalize_price_amount($value, 1);
    if ($price === '') return '';
    $num = (float) $price;
    // Accept any price >= $5 for hotels
    return ($num >= 5) ? $price : '';
}


private static function image_compare_key($url) {
    $url = preg_replace('/\?.*$/', '', (string) $url);
    return strtolower(trim($url));
}

private static function normalize_price_amount($value, $minimum = 1) {
    if (is_array($value) || is_object($value)) {
        // Try to extract value from a price-object: {amount, value, formatValue, ...}
        $obj = is_object($value) ? (array) $value : $value;
        foreach (array('amount', 'value', 'formatValue', 'sellPrice', 'fromPrice', 'price') as $k) {
            if (isset($obj[$k]) && $obj[$k] !== '' && $obj[$k] !== null) {
                $candidate = self::normalize_price_amount($obj[$k], $minimum);
                if ($candidate !== '') return $candidate;
            }
        }
        return '';
    }
    $raw = trim((string) $value);
    // Strip currency symbols + whitespace but keep digits and dot
    $clean = preg_replace('/[^0-9.]/', '', $raw);
    if ($clean === '') return '';
    $number = (float) $clean;
    if ($number <= 0) return '';
    // Heuristic: Klook sometimes stores prices as cents (e.g. 4000 = $40.00).
    // If the stripped value > 9999 and has no decimal part, divide by 100.
    if ($number > 9999 && floor($number) === $number) {
        $divided = $number / 100;
        if ($divided >= $minimum && $divided < 10000) {
            $number = $divided;
        }
    }
    if ($number < $minimum) return '';
    return number_format($number, 2, '.', '');
}

private static function extract_hotel_listing_items_from_html($body) {
    $items = array();
    if (preg_match('/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/si', $body, $match)) {
        $json = json_decode(html_entity_decode(trim($match[1]), ENT_QUOTES, 'UTF-8'), true);
        if (is_array($json)) {
            $stack = array($json);
            while ($stack) {
                $node = array_pop($stack);
                if (!is_array($node)) {
                    continue;
                }
                $url = '';
                foreach ($node as $value) {
                    if (is_string($value) && strpos($value, '/hotels/detail/') !== false) {
                        $url = self::normalize_klook_link($value);
                        break;
                    }
                }
                if ($url) {
                    $title = self::normalize_front_title(self::array_find_first($node, array('hotelName','seoTitle','name','title')), $url);
                    $price = self::maybe_hotel_price(self::array_find_first($node, array('price','minPrice','salePrice','displayPrice','fromPrice','discountPrice','lowestPrice')));
                    $original = self::normalize_price_amount(self::array_find_first($node, array('originalPrice','marketPrice','strikePrice','retailPrice')), 10);
                    $rating = preg_replace('/[^0-9.]/', '', (string) self::array_find_first($node, array('rating','reviewScore','score')));
                    $review_count = preg_replace('/[^0-9]/', '', (string) self::array_find_first($node, array('reviewCount','reviewsCount','commentCount')));
                    $images = self::clean_image_urls(self::array_collect_values($node, array('image','imageUrl','coverImageUrl','originalUrl')), 6);
                    $items[$url] = array(
                        'title' => preg_replace('/\s*[-|–—]\s*Klook.*$/iu', '', $title),
                        'price' => $price,
                        'original_price' => $original,
                        'rating' => $rating,
                        'review_count' => $review_count,
                        'image' => !empty($images) ? $images[0] : '',
                        'images' => $images,
                    );
                }
                foreach ($node as $value) {
                    if (is_array($value)) {
                        $stack[] = $value;
                    }
                }
            }
        }
    }
    if (empty($items) && preg_match_all('#(https://www\.klook\.com/[^"\'\s<>]*/hotels?/detail/[^"\'\s<>]+)#i', $body, $matches, PREG_OFFSET_CAPTURE)) {
        foreach ($matches[1] as $m) {
            $url = self::normalize_klook_link($m[0]);
            $offset = (int) $m[1];
            $chunk = substr($body, max(0, $offset - 200), 1800);
            $price = '';
            if (preg_match('/(?:From|from)\s*(?:US\$|USD|AED|EUR|GBP|SAR|QAR|\$|€|£)\s*([0-9]+(?:\.[0-9]{1,2})?)/i', $chunk, $pm)) {
                $price = self::normalize_price_amount($pm[1], 10);
            }
            $title = '';
            if (preg_match('/(?:title|name)[\"\']?\s*[:=]\s*[\"\']([^\"\']{6,120})[\"\']/i', $chunk, $tm)) {
                $title = self::normalize_front_title($tm[1], $url);
            }
            $items[$url] = array('price' => self::maybe_hotel_price($price), 'title' => $title);
        }
    }
    return $items;
}

private static function extract_activity_links_from_html($body) {
    $links = array();
    $patterns = array(
        '#href=["\'](https://www\.klook\.com/[^"\']*/activity/\d+[^"\']*)["\']#i',
        '#href=["\'](/[^"\']*/activity/\d+[^"\']*)["\']#i',
        '#https?://www\.klook\.com/[^"\'\s<>]*/activity/\d+[^"\'\s<>]*#i',
        '#/[^"\'\s<>]*/activity/\d+[^"\'\s<>]*#i',
        '#"url"\s*:\s*"(\/[^"\n]*?/activity/\d+[^"\n]*)"#i',
        '#"canonicalUrl"\s*:\s*"(\/[^"\n]*?/activity/\d+[^"\n]*)"#i',
        '#https?:\\/\\/www\\.klook\\.com\\/[^"\s]*?activity\\/\\d+[^"\s]*#i'
    );
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $body, $m)) {
            foreach (($m[1] ?? $m[0]) as $found) {
                $found = self::normalize_klook_link($found);
                if ($found && strpos($found, '/activity/') !== false) {
                    $links[] = $found;
                }
            }
        }
    }
    if (preg_match('/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/si', $body, $match)) {
        $json = json_decode(html_entity_decode(trim($match[1]), ENT_QUOTES, 'UTF-8'), true);
        if (is_array($json)) {
            $stack = array($json);
            while ($stack) {
                $node = array_pop($stack);
                if (is_array($node)) {
                    foreach ($node as $value) {
                        if (is_string($value) && preg_match('#/activity/\d+#i', $value)) {
                            $value = self::normalize_klook_link($value);
                            if ($value) {
                                $links[] = $value;
                            }
                        } elseif (is_array($value)) {
                            $stack[] = $value;
                        }
                    }
                }
            }
        }
    }
    return array_values(array_unique(array_filter($links)));
}

private static function extract_hotel_links_from_html($body) {
    $links = array();
    $patterns = array(
        '#href=["\'](https://www\.klook\.com/[^"\']*/hotels?/detail/[^"\']*)["\']#i',
        '#href=["\'](/[^"\']*/hotels?/detail/[^"\']*)["\']#i',
        '#https?://www\.klook\.com/[^"\'\s<>]*/hotels?/detail/[^"\'\s<>]*#i',
        '#/[^"\'\s<>]*/hotels?/detail/[^"\'\s<>]*#i',
        '#"url"\s*:\s*"(\/[^"\n]*?/hotels?/detail/[^"\n]*)"#i',
        '#https?:\\/\\/www\\.klook\\.com\\/[^"\s]*?hotels?\\/detail\\/[^"\s]*#i'
    );
    foreach ($patterns as $pattern) {
        if (preg_match_all($pattern, $body, $m)) {
            foreach (($m[1] ?? $m[0]) as $found) {
                $found = self::normalize_klook_link($found);
                if ($found && strpos($found, '/detail/') !== false) {
                    $links[] = $found;
                }
            }
        }
    }
    return array_values(array_unique(array_filter($links)));
}

/**
 * Try to sideload an image from the FTH proxy disk cache.
 * The proxy populates the cache whenever a visitor views a page with a proxied image.
 * This avoids both Cloudflare blocks and HTTP loopback requests during AJAX imports.
 *
 * @param string $image_url  Original Klook CDN URL (the cache key is md5 of this URL)
 * @param int    $post_id
 * @return int|WP_Error  Attachment ID on success, WP_Error otherwise.
 */
private static function sideload_from_proxy_cache($image_url, $post_id) {
    $upload_dir = wp_upload_dir();
    $cache_dir  = $upload_dir['basedir'] . '/fth-img-cache';
    $cache_key  = md5($image_url);
    $cache_img  = $cache_dir . '/' . $cache_key . '.img';
    $cache_meta = $cache_dir . '/' . $cache_key . '.meta';

    if (!file_exists($cache_img) || !file_exists($cache_meta)) {
        // Not yet cached — populate the cache by fetching inline (same logic as proxy handler)
        // This way the next visit will hit the cache AND we get the image now.
        if (!function_exists('curl_init')) {
            return new WP_Error('no_cache', 'No proxy cache entry and cURL unavailable');
        }
        $ch = curl_init($image_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => array(
                'Referer: https://www.klook.com/',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
                'sec-ch-ua: "Chromium";v="124", "Google Chrome";v="124"',
                'sec-ch-ua-mobile: ?0',
                'sec-fetch-dest: image',
                'sec-fetch-mode: no-cors',
                'sec-fetch-site: same-site',
            ),
        ));
        $img_data  = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $raw_ct    = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if (!$img_data || $http_code !== 200 || ($raw_ct && strpos($raw_ct, 'text/html') !== false)) {
            return new WP_Error('fetch_failed', 'Could not fetch image for cache: HTTP ' . $http_code);
        }
        $ct = $raw_ct ? strtok(trim($raw_ct), ';') : 'image/jpeg';
        if (!is_dir($cache_dir)) {
            wp_mkdir_p($cache_dir);
            @file_put_contents($cache_dir . '/.htaccess', "Options -Indexes\n");
        }
        @file_put_contents($cache_img, $img_data);
        @file_put_contents($cache_meta, json_encode(array('ct' => $ct, 'url' => $image_url, 'ts' => time())));
    }

    if (!file_exists($cache_img)) {
        return new WP_Error('cache_write_failed', 'Could not write proxy cache');
    }

    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
    $body = file_get_contents($cache_img);
    if (empty($body)) {
        return new WP_Error('empty_cache', 'Empty proxy cache file');
    }

    $meta         = file_exists($cache_meta) ? json_decode(file_get_contents($cache_meta), true) : array();
    $content_type = isset($meta['ct']) ? $meta['ct'] : 'image/jpeg';

    if (!function_exists('wp_handle_sideload')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }
    $tmpfname = wp_tempnam($image_url);
    if (!$tmpfname) {
        return new WP_Error('tmp_failed', 'Could not create temp file');
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    file_put_contents($tmpfname, $body);

    $ext_map  = array('image/jpeg'=>'jpg','image/jpg'=>'jpg','image/png'=>'png','image/gif'=>'gif','image/webp'=>'webp','image/avif'=>'avif');
    $ext      = isset($ext_map[$content_type]) ? $ext_map[$content_type] : 'jpg';
    $basename = sanitize_file_name(basename(strtok($image_url, '?')));
    if (!preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $basename)) {
        $basename .= '.' . $ext;
    }
    $file     = array('name' => $basename, 'type' => $content_type, 'tmp_name' => $tmpfname, 'error' => 0, 'size' => strlen($body));
    $sideload = wp_handle_sideload($file, array('test_form' => false));
    if (isset($sideload['error'])) {
        @unlink($tmpfname);
        return new WP_Error('sideload_failed', $sideload['error']);
    }
    $att_id = wp_insert_attachment(
        array('post_mime_type' => $sideload['type'], 'post_title' => sanitize_text_field(pathinfo($sideload['file'], PATHINFO_FILENAME)), 'post_content' => '', 'post_status' => 'inherit'),
        $sideload['file'], $post_id
    );
    if (is_wp_error($att_id)) {
        return $att_id;
    }
    wp_update_attachment_metadata($att_id, wp_generate_attachment_metadata($att_id, $sideload['file']));
    return $att_id;
}

/**
 * Download a remote image and attach it to a post.
 * Uses a Klook Referer header so res.klook.com CDN allows the download.
 * Falls back to storing the URL as external meta if download fails.
 */
private static function sideload_image_with_referer($image_url, $post_id, $referer = 'https://www.klook.com') {
    if (!function_exists('wp_handle_sideload')) {
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
    }

    // Download the file via cURL with spoofed browser Referer so Klook CDN allows it
    $body         = false;
    $content_type = 'image/jpeg';
    if (function_exists('curl_init')) {
        $ch = curl_init($image_url);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 5,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_HTTPHEADER     => array(
                'Referer: ' . $referer,
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
            ),
        ));
        $body      = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $raw_ct    = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        if (!$body || $http_code !== 200) {
            return new WP_Error('download_failed', 'Could not download image (HTTP ' . $http_code . '): ' . $image_url);
        }
        if ($raw_ct) {
            $content_type = strtok(trim($raw_ct), ';');
        }
        // If Cloudflare returned an HTML challenge page instead of an image, bail
        if ($raw_ct && strpos($raw_ct, 'text/html') !== false) {
            return new WP_Error('blocked', 'Cloudflare blocked image download: ' . $image_url);
        }
    } else {
        // Fallback: wp_remote_get
        $response = wp_remote_get($image_url, array(
            'timeout' => 30,
            'headers' => array(
                'Referer'         => $referer,
                'User-Agent'      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept'          => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ),
        ));
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return new WP_Error('download_failed', 'Could not download image: ' . $image_url);
        }
        $body = wp_remote_retrieve_body($response);
        $raw_ct = wp_remote_retrieve_header($response, 'content-type');
        if ($raw_ct) {
            $content_type = strtok(trim($raw_ct), ';');
        }
    }
    if (empty($body)) {
        return new WP_Error('empty_body', 'Empty image response: ' . $image_url);
    }

    // Write to a temp file
    $tmpfname = wp_tempnam($image_url);
    if (!$tmpfname) {
        return new WP_Error('tmp_failed', 'Could not create temp file');
    }
    // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
    file_put_contents($tmpfname, $body);

    // Map MIME to extension
    $ext_map = array(
        'image/jpeg' => 'jpg', 'image/jpg' => 'jpg',
        'image/png'  => 'png', 'image/gif' => 'gif',
        'image/webp' => 'webp', 'image/avif' => 'avif',
    );
    $ext = isset($ext_map[$content_type]) ? $ext_map[$content_type] : 'jpg';

    // Build file data array for wp_handle_sideload
    $basename = sanitize_file_name(basename(strtok($image_url, '?')));
    if (!preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/i', $basename)) {
        $basename .= '.' . $ext;
    }
    $file = array(
        'name'     => $basename,
        'type'     => $content_type ?: 'image/jpeg',
        'tmp_name' => $tmpfname,
        'error'    => 0,
        'size'     => strlen($body),
    );
    $overrides = array('test_form' => false);
    $sideload  = wp_handle_sideload($file, $overrides);

    if (isset($sideload['error'])) {
        @unlink($tmpfname);
        return new WP_Error('sideload_failed', $sideload['error']);
    }

    $attachment = array(
        'post_mime_type' => $sideload['type'],
        'post_title'     => sanitize_text_field(pathinfo($sideload['file'], PATHINFO_FILENAME)),
        'post_content'   => '',
        'post_status'    => 'inherit',
    );
    $attachment_id = wp_insert_attachment($attachment, $sideload['file'], $post_id);
    if (is_wp_error($attachment_id)) {
        return $attachment_id;
    }
    $metadata = wp_generate_attachment_metadata($attachment_id, $sideload['file']);
    wp_update_attachment_metadata($attachment_id, $metadata);

    return $attachment_id;
}

private static function import_post_images($post_id, $main_image, $gallery = array()) {
    $main_image = self::clean_image_urls(array($main_image), 1);
    $main_image = !empty($main_image) ? $main_image[0] : '';
    $gallery    = self::clean_image_urls($gallery, 8);
    if (empty($main_image) && empty($gallery)) {
        return;
    }

    $gallery_attachment_ids = array();
    $all_attachment_ids     = array();
    $main_key               = self::image_compare_key($main_image);

    if (!empty($main_image)) {
        // Always store the raw URL first so the card displays even if sideload fails
        update_post_meta($post_id, '_fth_external_image', esc_url_raw($main_image));

        // Attempt 1: direct cURL download with Klook Referer (works for res.klook.com CDN)
        $attachment_id = self::sideload_image_with_referer($main_image, $post_id);

        // Attempt 2: read from proxy disk cache (populated when visitors browse pages)
        // or fetch inline with the same Chrome headers the proxy uses — no HTTP loopback.
        if (is_wp_error($attachment_id)) {
            $attachment_id = self::sideload_from_proxy_cache($main_image, $post_id);
        }

        // Attempt 3: HTTP proxy URL (last resort — requires WP loopback, may deadlock)
        if (is_wp_error($attachment_id) && class_exists('Flavor_Travel_Hub')) {
            $proxy_url = Flavor_Travel_Hub::fth_img_url($main_image);
            if ($proxy_url && $proxy_url !== $main_image) {
                $attachment_id = self::sideload_image_with_referer($proxy_url, $post_id, home_url());
            }
        }

        if (!is_wp_error($attachment_id) && $attachment_id) {
            $attachment_id = (int) $attachment_id;
            set_post_thumbnail($post_id, $attachment_id);
            update_post_meta($post_id, '_fth_external_image', wp_get_attachment_url($attachment_id));
            $all_attachment_ids[] = $attachment_id;
        }
        // else: external URL is already saved above, templates display via proxy
    }

    $gallery_urls = array();
    foreach ((array) $gallery as $img) {
        if (!$img || self::image_compare_key($img) === $main_key) {
            continue;
        }
        $gallery_urls[] = esc_url_raw($img);
        if (count($gallery_urls) >= 6) {
            break;
        }
    }
    if (!empty($gallery_urls)) {
        foreach ($gallery_urls as $img) {
            $gallery_id = self::sideload_image_with_referer($img, $post_id);
            if (!is_wp_error($gallery_id) && $gallery_id) {
                $gallery_id = (int) $gallery_id;
                if (!in_array($gallery_id, $gallery_attachment_ids, true)) {
                    $gallery_attachment_ids[] = $gallery_id;
                    $all_attachment_ids[]     = $gallery_id;
                }
            }
        }
        update_post_meta($post_id, '_fth_external_gallery', implode(',', $gallery_urls));
        if (!empty($gallery_attachment_ids)) {
            update_post_meta($post_id, '_fth_gallery', implode(',', $gallery_attachment_ids));
        }
    }
    if (!empty($all_attachment_ids)) {
        update_post_meta($post_id, '_fth_imported_attachment_ids', implode(',', array_unique($all_attachment_ids)));
    }
}

public static function import_bulk_city() {
    self::begin_import_request();
    try {
        if (!check_ajax_referer('fth_import_publish', 'nonce', false)) {
            self::send_json_error_clean('Security check failed');
        }
        if (!current_user_can('edit_posts')) {
            self::send_json_error_clean('Unauthorized');
        }

        $url      = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        $city     = isset($_POST['city']) ? intval($_POST['city']) : 0;
        $country  = isset($_POST['country']) ? intval($_POST['country']) : 0;
        $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
        $limit    = isset($_POST['limit']) ? max(1, min(240, intval($_POST['limit']))) : 72;

        if (empty($url)) {
            self::send_json_error_clean('Please enter a destination URL');
        }

        $real_url = self::extract_real_klook_url($url);
        // Normalise to /en-US/ to reduce locale-based Cloudflare variance
        if (strpos($real_url, 'klook.com') !== false) {
            $real_url = preg_replace('#(https?://(?:www\.)?klook\.com)/[a-z]{2}[-_][A-Za-z]{2,4}/#', '$1/en-US/', $real_url);
            if (!preg_match('#klook\.com/en-US/#', $real_url)) {
                $real_url = preg_replace('#(https?://(?:www\.)?klook\.com)/#', '$1/en-US/', $real_url);
            }
        }
        $candidate_urls = array($real_url);
        // Strip locale for second try
        $no_locale = preg_replace('#/en-US/#', '/', $real_url);
        if ($no_locale !== $real_url) $candidate_urls[] = $no_locale;
        if (strpos($real_url, '/destination/') !== false) {
            $candidate_urls[] = trailingslashit($real_url) . '1-things-to-do/';
            $candidate_urls[] = trailingslashit($real_url) . '2-tours/';
        }
        $expanded = array();
        foreach (array_unique($candidate_urls) as $candidate_url) {
            $expanded[] = $candidate_url;
            for ($page = 2; $page <= 6; $page++) {
                $expanded[] = add_query_arg('page', $page, $candidate_url);
            }
        }

        $links = array();
        $notes = array();
        foreach (array_unique($expanded) as $candidate_url) {
            // Use full fallback chain: direct → no-locale → WB CDX → WB availability
            $fetch = self::fetch_klook_html($candidate_url);
            $body  = $fetch['body'];
            if (empty($body)) {
                $notes[] = 'Could not fetch: ' . $candidate_url;
                continue;
            }
            if (strpos($body, '__NEXT_DATA__') === false && strpos($body, '/activity/') === false) {
                $notes[] = 'No content (' . $fetch['source'] . '): ' . $candidate_url;
                continue;
            }
            if ($fetch['source'] !== 'direct') {
                $notes[] = 'Fetched via ' . $fetch['source'] . ': ' . $candidate_url;
            }
            $found = self::extract_activity_links_from_html($body);
            if (!empty($found)) {
                $links = array_merge($links, $found);
            }
            if (count($links) >= $limit) break;
        }

        // CDX fallback: if listing pages gave nothing, discover activity URLs directly
        // from the Wayback Machine archive index (no live Klook page required).
        if (empty($links)) {
            $city_slug_cdx = '';
            if (preg_match('#/destination/c\d+-([a-z0-9-]+)#i', $real_url, $csm)) {
                $city_slug_cdx = $csm[1];
            } elseif ($city) {
                $ct = get_term($city, 'travel_city');
                if ($ct && !is_wp_error($ct)) $city_slug_cdx = $ct->slug;
            }
            if (!empty($city_slug_cdx)) {
                $cdx_urls = self::discover_klook_urls_via_cdx('activity', $city_slug_cdx, $limit);
                if (!empty($cdx_urls)) {
                    $links = $cdx_urls;
                    $notes[] = 'Wayback CDX: found ' . count($cdx_urls) . ' activity URLs for "' . $city_slug_cdx . '"';
                }
            }
        }

        $links = array_values(array_unique($links));
        if (empty($links)) {
            self::send_json_error_clean('No activity links found. ' . implode(' | ', array_slice($notes, 0, 5)) . ' Tip: Try a URL like https://www.klook.com/en-US/destination/c78-dubai/');
        }
        $links = array_slice($links, 0, $limit);

        $imported = 0;
        $checked  = 0;
        $errors   = array();
        $start    = time();
        foreach ($links as $activity_url) {
            // Stop after 240 seconds to avoid PHP timeout
            if ((time() - $start) > 240) {
                $errors[] = 'Time limit reached – ' . (count($links) - $checked) . ' URLs not processed yet. Run again to continue.';
                break;
            }
            $checked++;
            $result = self::import_activity($activity_url, array(
                'city'          => $city,
                'country'       => $country,
                'category'      => $category,
                'publish'       => 1,
                'is_featured'   => 0,
                'is_bestseller' => 0,
            ), self::build_affiliate_redirect($activity_url));
            if (!empty($result['success'])) {
                $imported++;
            } else {
                $errors[] = !empty($result['message']) ? $result['message'] : 'Unknown import error';
            }
        }

        $message = 'Imported ' . $imported . ' activities out of ' . $checked . ' links found.';
        if (!empty($errors)) {
            $message .= ' Notes: ' . implode(' | ', array_slice($errors, 0, 3));
        }
        self::send_json_success_clean(array(
            'imported' => $imported,
            'checked'  => $checked,
            'message'  => $message,
        ));
    } catch (Throwable $e) {
        self::send_json_error_clean('Import failed: ' . $e->getMessage());
    }
}

    /**
     * Import from Klook and Publish automatically

    /**
     * Import from Klook and Publish automatically
     */
    public static function import_and_publish() {
        self::begin_import_request();
        try {
            if (!check_ajax_referer('fth_import_publish', 'nonce', false)) {
                self::send_json_error_clean('Security check failed');
            }
            if (!current_user_can('edit_posts')) {
                self::send_json_error_clean('Unauthorized');
            }
            $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
            $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
            if (empty($url)) {
                self::send_json_error_clean('Please enter a URL');
            }
            $real_url = self::extract_real_klook_url($url);
            $original_deeplink = $url;
            if ($type === 'activity') {
                if (strpos($real_url, '/hotels/') !== false) {
                    self::send_json_error_clean('This looks like a hotel URL. Use the hotel importer below instead.');
                }
                $result = self::import_activity($real_url, $_POST, $original_deeplink);
            } elseif ($type === 'city') {
                $result = self::import_city($real_url, $_POST, $original_deeplink);
            } elseif ($type === 'hotel') {
                $result = self::import_hotel($real_url, $_POST, $original_deeplink);
            } else {
                self::send_json_error_clean('Invalid import type');
            }
            if (!empty($result['success'])) {
                self::send_json_success_clean($result['data']);
            }
            self::send_json_error_clean(!empty($result['message']) ? $result['message'] : 'Import failed');
        } catch (Throwable $e) {
            self::send_json_error_clean('Import failed: ' . $e->getMessage());
        }
    }

    /**
     * Extract real Klook URL from affiliate deeplink
    /**
     * Extract real Klook URL from affiliate deeplink
     * Input: https://affiliate.klook.com/redirect?aid=115387&k_site=https%3A%2F%2Fwww.klook.com%2Factivity%2F30333-...
     * Output: https://www.klook.com/activity/30333-...
     */
    private static function extract_real_klook_url($url) {
        // If it's already a direct Klook URL, return as is
        // Match both plain and locale-prefixed paths (e.g. /en-US/activity/, /fr-FR/activity/)
        if (preg_match('#www\.klook\.com(?:/[a-z]{2}[-_][A-Za-z]{2,4})?/(?:activity|destination|city|hotels)/#i', $url)) {
            return $url;
        }
        
        // If it's an affiliate redirect URL, extract the k_site parameter
        if (strpos($url, 'affiliate.klook.com/redirect') !== false) {
            $parsed = parse_url($url);
            if (isset($parsed['query'])) {
                parse_str($parsed['query'], $params);
                if (isset($params['k_site'])) {
                    return urldecode($params['k_site']);
                }
            }
        }
        
        // Try to find klook.com URL in the string
        if (preg_match('/(https?:\/\/(?:www\.)?klook\.com\/[^\s&"\']+)/i', urldecode($url), $match)) {
            return $match[1];
        }
        
        return $url;
    }
    
    /**
     * Import activity from Klook
     */
    private static function import_activity($url, $params, $original_deeplink = '') {
        // Normalize Klook URL to English locale to prevent French/other-language content
        if (strpos($url, 'klook.com') !== false) {
            // Remove any existing locale prefix (e.g. /fr-FR/, /zh-HK/, /ar-SA/)
            $url = preg_replace('#(https?://(?:www\.)?klook\.com)/[a-z]{2}[-_][A-Za-z]{2,4}/#', '$1/en-US/', $url);
            // If no locale was present, inject /en-US/ after the domain
            if (!preg_match('#klook\.com/en-US/#', $url)) {
                $url = preg_replace('#(https?://(?:www\.)?klook\.com)/#', '$1/en-US/', $url);
            }
        }

        // Fetch with multi-strategy fallback: direct → no-locale → Google Cache → Wayback Machine
        $fetch = self::fetch_klook_html($url);
        $body  = $fetch['body'];
        $url   = $fetch['url'];

        if (empty($body)) {
            return array('success' => false, 'message' => 'Failed to fetch activity page (all strategies failed): ' . $url);
        }

        // Parse data
        $activity_id = '';
        if (preg_match('/\/activity\/(\d+)-/', $url, $match)) {
            $activity_id = $match[1];
        }

        $data = self::parse_klook_html($body, $url, $activity_id);
        
        if (empty($data['title'])) {
            return array('success' => false, 'message' => 'Could not extract activity data. URL: ' . $url);
        }
        
        // Use original deeplink if provided, otherwise build one
        if (!empty($original_deeplink) && strpos($original_deeplink, 'affiliate.klook.com') !== false) {
            $data['affiliate_link'] = $original_deeplink;
        }

        // Manual image URL override — admin can supply an image when auto-extraction fails
        $manual_img = isset($params['manual_image_url']) ? esc_url_raw(trim($params['manual_image_url'])) : '';
        if (!empty($manual_img) && preg_match('#^https?://#i', $manual_img)) {
            $data['image']  = $manual_img;
            $data['images'] = array_merge(array($manual_img), $data['images'] ?: array());
        }

        // Create or update post
        $post_status = isset($params['publish']) && $params['publish'] ? 'publish' : 'draft';
        $existing_post_id = 0;
        if (!empty($data['activity_id'])) {
            $existing_posts = get_posts(array(
                'post_type'      => 'travel_activity',
                'posts_per_page' => 1,
                'post_status'    => 'any',
                'meta_query'     => array(array(
                    'key'   => '_fth_klook_activity_id',
                    'value' => $data['activity_id'],
                )),
                'fields'         => 'ids',
            ));
            if (!empty($existing_posts)) {
                $existing_post_id = (int) $existing_posts[0];
            }
        }
        
        $post_data = array(
            'post_title'   => self::normalize_front_title($data['title'], $url),
            'post_content' => $data['description'] ?: '<p>Discover this amazing experience and book online for instant confirmation.</p>',
            'post_excerpt' => $data['excerpt'] ?: wp_trim_words(strip_tags($data['description']), 30),
            'post_status'  => $post_status,
            'post_type'    => 'travel_activity',
            'post_author'  => get_current_user_id(),
        );
        
        if ($existing_post_id) {
            $post_data['ID'] = $existing_post_id;
            $post_id = wp_update_post($post_data, true);
        } else {
            $post_id = wp_insert_post($post_data, true);
        }
        
        if (is_wp_error($post_id)) {
            return array('success' => false, 'message' => 'Failed to create activity');
        }
        
        // Ensure price is normalised (handles cents format & minimums)
        if (!empty($data['price'])) {
            $data['price'] = self::normalize_price_amount($data['price'], 1);
        }
        if (!empty($data['original_price'])) {
            $data['original_price'] = self::normalize_price_amount($data['original_price'], 1);
        }
        // Discard original_price if it is not actually higher than selling price
        if (!empty($data['original_price']) && !empty($data['price']) && (float) $data['original_price'] <= (float) $data['price']) {
            $data['original_price'] = '';
        }
        // Auto-set promo text if Klook did not provide one
        if (empty($data['promo'])) {
            $data['promo'] = Flavor_Travel_Hub::get_promo_text();
        }

        // Save meta
        if (!empty($data['price']))          update_post_meta($post_id, '_fth_price', $data['price']);
        if (!empty($data['original_price'])) update_post_meta($post_id, '_fth_original_price', $data['original_price']);
        update_post_meta($post_id, '_fth_currency', !empty($data['currency']) ? $data['currency'] : 'USD');
        if (!empty($data['rating']))         update_post_meta($post_id, '_fth_rating', $data['rating']);
        if (!empty($data['review_count']))   update_post_meta($post_id, '_fth_review_count', $data['review_count']);
        if (!empty($data['duration']))       update_post_meta($post_id, '_fth_duration', $data['duration']);
        if (!empty($data['itinerary']))      update_post_meta($post_id, '_fth_itinerary', $data['itinerary']);
        if (!empty($data['promo']))          update_post_meta($post_id, '_fth_promo', $data['promo']);
        if (!empty($data['highlights']))     update_post_meta($post_id, '_fth_highlights', $data['highlights']);
        if (!empty($data['inclusions']))     update_post_meta($post_id, '_fth_inclusions', $data['inclusions']);
        if (!empty($data['exclusions']))     update_post_meta($post_id, '_fth_exclusions', $data['exclusions']);
        if (!empty($data['faq']))            update_post_meta($post_id, '_fth_faq', $data['faq']);
        if (!empty($data['image']))          update_post_meta($post_id, '_fth_external_image', $data['image']);
        if (!empty($data['affiliate_link'])) update_post_meta($post_id, '_fth_affiliate_link', $data['affiliate_link']);
        if (!empty($data['activity_id']))    update_post_meta($post_id, '_fth_klook_activity_id', $data['activity_id']);
        
        // Import featured image and save gallery
        self::import_post_images($post_id, !empty($data['image']) ? $data['image'] : '', !empty($data['images']) && is_array($data['images']) ? $data['images'] : array());
        
        // Featured/Bestseller
        if (isset($params['is_featured']) && $params['is_featured']) {
            update_post_meta($post_id, '_fth_is_featured', '1');
        }
        if (isset($params['is_bestseller']) && $params['is_bestseller']) {
            update_post_meta($post_id, '_fth_is_bestseller', '1');
        }
        
        // Taxonomies
        if (!empty($params['city'])) {
            wp_set_object_terms($post_id, intval($params['city']), 'travel_city');
            // Auto-generate hero image for city if missing
            $city_id = intval($params['city']);
            if (!get_term_meta($city_id, 'fth_hero_image', true) && class_exists('Flavor_Travel_Hub')) {
                $city_t = get_term($city_id, 'travel_city');
                if ($city_t && !is_wp_error($city_t)) {
                    Flavor_Travel_Hub::generate_taxonomy_image($city_t->name, $city_id, 'travel_city');
                }
            }
        }
        if (!empty($params['country'])) {
            wp_set_object_terms($post_id, intval($params['country']), 'travel_country');
            // Auto-generate hero image for country if missing
            $country_id = intval($params['country']);
            if (!get_term_meta($country_id, 'fth_hero_image', true) && class_exists('Flavor_Travel_Hub')) {
                $country_t = get_term($country_id, 'travel_country');
                if ($country_t && !is_wp_error($country_t)) {
                    Flavor_Travel_Hub::generate_taxonomy_image($country_t->name, $country_id, 'travel_country');
                }
            }
        }
        if (!empty($params['category'])) {
            wp_set_object_terms($post_id, intval($params['category']), 'travel_category');
        }

        // Generate SEO (AIO SEO)
        self::generate_activity_seo_meta($post_id, get_post($post_id));
        if (class_exists('FTH_AIOSEO_Integration')) { FTH_AIOSEO_Integration::auto_fill_activity_seo($post_id, get_post($post_id)); }
        update_option('fth_needs_flush', true);
        
        return array(
            'success' => true,
            'data'    => array(
                'post_id'  => $post_id,
                'edit_url' => get_edit_post_link($post_id, 'raw'),
                'view_url' => get_permalink($post_id),
            ),
        );
    }
    
    /**
     * Import city from Klook
     */
    private static function import_city($url, $params, $original_deeplink = '') {
        // Fetch the real Klook page
        $response = self::remote_get($url, array(
            'timeout'     => 45,
            'redirection' => 5,
            'user-agent'  => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            'headers'     => array(
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
            ),
        ));
        
        if (is_wp_error($response)) {
            return array('success' => false, 'message' => 'Failed to fetch URL: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return array('success' => false, 'message' => 'Empty response from Klook');
        }
        
        $data = self::parse_klook_city_html($body, $url);
        
        // Use original deeplink
        if (!empty($original_deeplink)) {
            $data['deeplink'] = $original_deeplink;
        }
        
        if (empty($data['name'])) {
            return array('success' => false, 'message' => 'Could not extract city data. URL: ' . $url);
        }
        
        // Check if city exists — update if so, create if not
        $existing    = get_term_by('name', $data['name'], 'travel_city');
        $was_existing = false;
        if ($existing) {
            $term_id      = $existing->term_id;
            $was_existing = true;
            if (!empty($data['description'])) {
                wp_update_term($term_id, 'travel_city', array('description' => $data['description']));
            }
        } else {
            $result = wp_insert_term($data['name'], 'travel_city', array(
                'description' => $data['description'] ?: '',
            ));
            if (is_wp_error($result)) {
                return array('success' => false, 'message' => 'Failed to create city: ' . $result->get_error_message());
            }
            $term_id = $result['term_id'];
        }
        
        // Save meta
        if (!empty($data['hero_image'])) {
            update_term_meta($term_id, 'fth_hero_image', $data['hero_image']);
        }
        if (!empty($data['deeplink'])) {
            update_term_meta($term_id, 'fth_deeplink', $data['deeplink']);
        }
        if (!empty($params['country'])) {
            update_term_meta($term_id, 'fth_parent_country', intval($params['country']));
        }
        // Store Klook destination URL and ID for country-wide import
        update_term_meta($term_id, 'fth_klook_url', esc_url_raw($url));
        if (preg_match('#/destination/c(\d+)-#i', $url, $dm)) {
            update_term_meta($term_id, 'fth_klook_dest_id', $dm[1]);
        }
        
        // Auto-generate featured image if none exists yet
        $existing_hero = get_term_meta($term_id, 'fth_hero_image', true);
        if (empty($existing_hero) && class_exists('Flavor_Travel_Hub')) {
            Flavor_Travel_Hub::generate_taxonomy_image($data['name'], $term_id, 'travel_city');
        }

        // Generate SEO
        self::generate_city_seo_meta($term_id);
        if (class_exists('FTH_AIOSEO_Integration')) { FTH_AIOSEO_Integration::auto_fill_city_seo($term_id); }

        $term = get_term($term_id, 'travel_city');
        
        return array(
            'success' => true,
            'data'    => array(
                'term_id'  => $term_id,
                'edit_url' => get_edit_term_link($term_id, 'travel_city'),
                'view_url' => get_term_link($term),
                'updated'  => $was_existing,
            ),
        );
    }

    /**
     * Bulk import from a list of manually provided Klook URLs (activities or hotels)
     */
    public static function import_bulk_urls() {
        self::begin_import_request();
        try {
            if (!check_ajax_referer('fth_import_publish', 'nonce', false)) {
                self::send_json_error_clean('Security check failed');
            }
            if (!current_user_can('edit_posts')) {
                self::send_json_error_clean('Unauthorized');
            }
            $raw_urls = isset($_POST['urls']) ? sanitize_textarea_field($_POST['urls']) : '';
            $type     = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'activity';
            $city     = isset($_POST['city']) ? intval($_POST['city']) : 0;
            $country  = isset($_POST['country']) ? intval($_POST['country']) : 0;
            $category = isset($_POST['category']) ? intval($_POST['category']) : 0;

            $lines = array_filter(array_map('trim', preg_split('/[\r\n]+/', $raw_urls)));
            if (empty($lines)) {
                self::send_json_error_clean('No URLs provided');
            }
            $imported  = 0;
            $skipped   = 0;
            $errors    = array();
            $results   = array();
            foreach (array_slice($lines, 0, 50) as $raw) {
                $real = self::extract_real_klook_url($raw);
                if (!$real || strpos($real, 'klook.com') === false) {
                    $errors[] = 'Invalid URL: ' . esc_url($raw);
                    continue;
                }
                $is_hotel    = strpos($real, '/hotels/') !== false || strpos($real, '/hotel/') !== false;
                $import_type = ($type === 'hotel' || $is_hotel) ? 'hotel' : 'activity';
                $params = array('city'=>$city,'country'=>$country,'category'=>$category,'publish'=>1,'is_featured'=>0,'is_bestseller'=>0);
                $result = ($import_type === 'hotel')
                    ? self::import_hotel($real, $params, self::build_affiliate_redirect($real))
                    : self::import_activity($real, $params, self::build_affiliate_redirect($real));
                if (!empty($result['success'])) {
                    $imported++;
                    $results[] = array('url'=>$raw,'edit_url'=>$result['data']['edit_url'],'view_url'=>$result['data']['view_url']);
                } else {
                    $msg = !empty($result['message']) ? $result['message'] : 'Unknown error';
                    if (strpos($msg, 'already') !== false) { $skipped++; } else { $errors[] = $msg; }
                }
            }
            $message = 'Imported ' . $imported . ' items.';
            if ($skipped) $message .= ' ' . $skipped . ' skipped (already exist).';
            if (!empty($errors)) $message .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5));
            self::send_json_success_clean(array('imported'=>$imported,'skipped'=>$skipped,'message'=>$message,'results'=>array_slice($results,0,20)));
        } catch (Throwable $e) {
            self::send_json_error_clean('Batch import failed: ' . $e->getMessage());
        }
    }

    /**
     * Bulk import hotels from a city hotels URL
     */
    public static function import_bulk_hotels() {
        self::begin_import_request();
        try {
            if (!check_ajax_referer('fth_import_publish', 'nonce', false)) {
                self::send_json_error_clean('Security check failed');
            }
            if (!current_user_can('edit_posts')) {
                self::send_json_error_clean('Unauthorized');
            }
            $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
            $city = isset($_POST['city']) ? intval($_POST['city']) : 0;
            $country = isset($_POST['country']) ? intval($_POST['country']) : 0;
            $limit = isset($_POST['limit']) ? max(1, min(180, intval($_POST['limit']))) : 24;
            if (!$url) {
                self::send_json_error_clean('Please enter a hotels URL');
            }
            $start = time();
            $real_url = self::extract_real_klook_url($url);
            $candidate_urls = array($real_url);
            for ($page = 2; $page <= 8; $page++) {
                $candidate_urls[] = add_query_arg('page', $page, $real_url);
            }
            $links = array();
            $listing_map = array();
            $notes = array();
            foreach (array_unique($candidate_urls) as $candidate_url) {
                $fetch = self::fetch_klook_html($candidate_url);
                $body  = $fetch['body'];
                if (!$body) {
                    $notes[] = 'Could not fetch: ' . $candidate_url;
                    continue;
                }
                if ($fetch['source'] !== 'direct') {
                    $notes[] = 'Fetched via ' . $fetch['source'] . ': ' . $candidate_url;
                }
                $links       = array_merge($links, self::extract_hotel_links_from_html($body));
                $listing_map = array_merge($listing_map, self::extract_hotel_listing_items_from_html($body));
            }

            // CDX fallback: discover hotel URLs from WB archive index if listing pages failed
            if (empty($links)) {
                $city_slug_cdx = '';
                if (preg_match('#/hotels/([a-z0-9-]+)/#i', $real_url, $csm)) {
                    $city_slug_cdx = $csm[1];
                } elseif ($city) {
                    $ct = get_term($city, 'travel_city');
                    if ($ct && !is_wp_error($ct)) $city_slug_cdx = $ct->slug;
                }
                if (!empty($city_slug_cdx)) {
                    $cdx_urls = self::discover_klook_urls_via_cdx('hotel', $city_slug_cdx, $limit);
                    if (!empty($cdx_urls)) {
                        $links = $cdx_urls;
                        $notes[] = 'Wayback CDX: found ' . count($cdx_urls) . ' hotel URLs for "' . $city_slug_cdx . '"';
                    }
                }
            }

            $links = array_values(array_unique($links));
            if (empty($links)) {
                self::send_json_error_clean('No hotel links found. ' . implode(' | ', array_slice($notes, 0, 5)));
            }
            $links = array_slice($links, 0, $limit);
            $imported = 0; $checked = 0; $errors = array(); $stopped_early = false;
            foreach ($links as $hotel_url) {
                if ((time() - $start) > 240) {
                    $stopped_early = true;
                    break;
                }
                $checked++;
                $fallback = isset($listing_map[$hotel_url]) ? $listing_map[$hotel_url] : array();
                $result = self::import_hotel($hotel_url, array('city'=>$city,'country'=>$country,'publish'=>1,'listing_fallback'=>$fallback), self::build_affiliate_redirect($hotel_url));
                if (!empty($result['success'])) { $imported++; } else { $errors[] = !empty($result['message']) ? $result['message'] : 'Unknown import error'; }
            }
            $message = 'Imported ' . $imported . ' hotels out of ' . $checked . ' links checked.';
            if ($stopped_early) {
                $message .= ' The run stopped early to avoid a server timeout. Run the same import again to continue.';
            }
            if (!empty($errors)) { $message .= ' Some skipped: ' . implode(' | ', array_slice($errors, 0, 5)); }
            self::send_json_success_clean(array('imported'=>$imported,'checked'=>$checked,'message'=>$message));
        } catch (Throwable $e) {
            self::send_json_error_clean('Hotel import failed: ' . $e->getMessage());
        }
    }

    /**
     * Import hotel from Klook
    /**
     * Import hotel from Klook
     */
    private static function import_hotel($url, $params, $original_deeplink = '') {
        // Fetch with multi-strategy fallback: direct → no-locale → Google Cache → Wayback Machine
        $fetch = self::fetch_klook_html($url);
        $body  = $fetch['body'];
        if (empty($body)) {
            return array('success'=>false,'message'=>'Failed to fetch hotel page (all strategies failed): ' . $url);
        }
        $data = self::parse_klook_hotel_html($body, $url);
        $fallback = (!empty($params['listing_fallback']) && is_array($params['listing_fallback'])) ? $params['listing_fallback'] : array();
        foreach (array('title','rating','review_count','image') as $field) {
            if (empty($data[$field]) && !empty($fallback[$field])) {
                $data[$field] = $fallback[$field];
            }
        }
        if (!empty($fallback['price'])) {
            $data['price'] = self::maybe_hotel_price($fallback['price']);
        } else {
            $data['price'] = self::maybe_hotel_price($data['price']);
        }
        if (!empty($fallback['original_price']) && empty($data['original_price'])) {
            $data['original_price'] = self::maybe_hotel_price($fallback['original_price']);
        } else {
            $data['original_price'] = self::maybe_hotel_price($data['original_price']);
        }
        if (empty($data['images']) && !empty($fallback['images']) && is_array($fallback['images'])) {
            $data['images'] = $fallback['images'];
        }
        if (empty($data['title'])) {
            return array('success'=>false,'message'=>'Could not extract hotel data. URL: ' . $url);
        }
        if (!empty($original_deeplink) && strpos($original_deeplink, 'affiliate.klook.com') !== false) {
            $data['affiliate_link'] = $original_deeplink;
        }
        // Manual image URL override for hotels
        $manual_img = isset($params['manual_image_url']) ? esc_url_raw(trim($params['manual_image_url'])) : '';
        if (!empty($manual_img) && preg_match('#^https?://#i', $manual_img)) {
            $data['image']  = $manual_img;
            $data['images'] = array_merge(array($manual_img), $data['images'] ?: array());
        }
        $existing_post_id = 0;
        if (!empty($data['hotel_id'])) {
            $existing_posts = get_posts(array('post_type'=>'travel_hotel','posts_per_page'=>1,'post_status'=>'any','meta_query'=>array(array('key'=>'_fth_klook_hotel_id','value'=>$data['hotel_id'])),'fields'=>'ids'));
            if (!empty($existing_posts)) $existing_post_id = (int) $existing_posts[0];
        }
        $post_data = array(
            'post_title' => self::normalize_front_title($data['title'], $url),
            'post_content' => $data['description'] ?: '<p>Comfortable stay with useful room, facility and location details.</p>',
            'post_excerpt' => $data['excerpt'] ?: wp_trim_words(strip_tags($data['description']), 30),
            'post_status' => (!empty($params['publish']) ? 'publish' : 'draft'),
            'post_type' => 'travel_hotel',
            'post_author' => get_current_user_id(),
        );
        if ($existing_post_id) { $post_data['ID'] = $existing_post_id; $post_id = wp_update_post($post_data, true); } else { $post_id = wp_insert_post($post_data, true); }
        if (is_wp_error($post_id)) {
            return array('success'=>false,'message'=>'Failed to create hotel');
        }
        // Auto-set promo text for hotels
        if (empty($data['promo'])) {
            $data['promo'] = Flavor_Travel_Hub::get_promo_text();
        }
        update_post_meta($post_id, '_fth_promo', $data['promo']);
        update_post_meta($post_id, '_fth_currency', !empty($data['currency']) ? $data['currency'] : 'USD');
        foreach (array('price','original_price','rating','review_count','address','amenities','image','affiliate_link') as $field) {
            if (isset($data[$field]) && $data[$field] !== '') update_post_meta($post_id, '_fth_' . $field, $data[$field]);
        }
        // Save hotel_id with consistent key used by duplicate detection
        if (!empty($data['hotel_id'])) update_post_meta($post_id, '_fth_klook_hotel_id', $data['hotel_id']);
        // Save hotel details fields
        if (!empty($data['highlights'])) update_post_meta($post_id, '_fth_highlights', $data['highlights']);
        if (!empty($data['inclusions'])) update_post_meta($post_id, '_fth_inclusions', $data['inclusions']);
        if (!empty($data['faq']))        update_post_meta($post_id, '_fth_faq', $data['faq']);
        self::import_post_images($post_id, !empty($data['image']) ? $data['image'] : '', !empty($data['images']) && is_array($data['images']) ? $data['images'] : array());
        if (!empty($params['city'])) {
            wp_set_object_terms($post_id, intval($params['city']), 'travel_city');
            $city_id = intval($params['city']);
            if (!get_term_meta($city_id, 'fth_hero_image', true) && class_exists('Flavor_Travel_Hub')) {
                $city_t = get_term($city_id, 'travel_city');
                if ($city_t && !is_wp_error($city_t)) { Flavor_Travel_Hub::generate_taxonomy_image($city_t->name, $city_id, 'travel_city'); }
            }
        }
        if (!empty($params['country'])) {
            wp_set_object_terms($post_id, intval($params['country']), 'travel_country');
            $country_id = intval($params['country']);
            if (!get_term_meta($country_id, 'fth_hero_image', true) && class_exists('Flavor_Travel_Hub')) {
                $country_t = get_term($country_id, 'travel_country');
                if ($country_t && !is_wp_error($country_t)) { Flavor_Travel_Hub::generate_taxonomy_image($country_t->name, $country_id, 'travel_country'); }
            }
        }
        self::generate_hotel_seo_meta($post_id, get_post($post_id));
        if (class_exists('FTH_AIOSEO_Integration')) { FTH_AIOSEO_Integration::auto_fill_hotel_seo($post_id, get_post($post_id)); }
        update_option('fth_needs_flush', true);
        return array('success'=>true,'data'=>array('post_id'=>$post_id,'edit_url'=>get_edit_post_link($post_id, 'raw'),'view_url'=>get_permalink($post_id)));
    }

    /**
     * Parse Klook hotel HTML
     */
    private static function parse_klook_hotel_html($html, $url) {
        $data = array(
            'title'          => '',
            'description'    => '',
            'excerpt'        => '',
            'price'          => '',
            'original_price' => '',
            'currency'       => 'USD',
            'rating'         => '',
            'review_count'   => '',
            'address'        => '',
            'amenities'      => '',
            'highlights'     => '',
            'inclusions'     => '',
            'exclusions'     => '',
            'faq'            => '',
            'image'          => '',
            'images'         => array(),
            'affiliate_link' => self::build_affiliate_redirect($url),
            'hotel_id'       => '',
        );
        if (preg_match('/(?:detail|hotel)[^0-9]*(\d{3,})/i', $url, $m)) {
            $data['hotel_id'] = $m[1];
        }
        if (preg_match_all('/<script[^>]+type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $jsonld_matches)) {
            foreach ($jsonld_matches[1] as $jsonld_raw) {
                $jsonld = json_decode(html_entity_decode(trim($jsonld_raw), ENT_QUOTES, 'UTF-8'), true);
                if (!is_array($jsonld)) {
                    continue;
                }
                $graphs = isset($jsonld['@graph']) && is_array($jsonld['@graph']) ? $jsonld['@graph'] : array($jsonld);
                foreach ($graphs as $node) {
                    if (!is_array($node)) continue;
                    $type = isset($node['@type']) ? (array) $node['@type'] : array();
                    $type_string = strtolower(implode(' ', $type));
                    if (strpos($type_string, 'hotel') !== false || strpos($type_string, 'lodgingbusiness') !== false) {
                        if (empty($data['title']) && !empty($node['name'])) $data['title'] = self::normalize_text_block($node['name']);
                        if (empty($data['description']) && !empty($node['description'])) {
                            $desc_text = self::normalize_text_block($node['description']);
                            $data['description'] = '<p>' . esc_html($desc_text) . '</p>';
                            $data['excerpt'] = wp_trim_words($desc_text, 34, '...');
                        }
                        if (empty($data['image']) && !empty($node['image'])) {
                            $images = is_array($node['image']) ? $node['image'] : array($node['image']);
                            $images = self::clean_image_urls($images, 6);
                            if ($images) {
                                $data['images'] = array_values(array_unique(array_merge($data['images'], $images)));
                                $data['image'] = $images[0];
                            }
                        }
                        if (empty($data['rating']) && !empty($node['aggregateRating']['ratingValue'])) {
                            $data['rating'] = preg_replace('/[^0-9.]/', '', (string) $node['aggregateRating']['ratingValue']);
                        }
                        if (empty($data['review_count']) && !empty($node['aggregateRating']['reviewCount'])) {
                            $data['review_count'] = preg_replace('/[^0-9]/', '', (string) $node['aggregateRating']['reviewCount']);
                        }
                        if (empty($data['price']) && !empty($node['offers']['price'])) {
                            $data['price'] = self::normalize_price_amount($node['offers']['price'], 10);
                        }
                        if (empty($data['currency']) && !empty($node['offers']['priceCurrency'])) {
                            $data['currency'] = preg_replace('/[^A-Z]/', '', strtoupper((string) $node['offers']['priceCurrency']));
                        }
                    }
                }
            }
        }
        if (preg_match('/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/si', $html, $m)) {
            $next = json_decode(html_entity_decode(trim($m[1]), ENT_QUOTES, 'UTF-8'), true);
            if (is_array($next)) {
                $props = isset($next['props']['pageProps']) ? $next['props']['pageProps'] : $next;
                $title = self::normalize_front_title(self::array_find_first($props, array('seoTitle','hotelName','name','title','hotel_title')), $url);
                if ($title !== '') {
                    $data['title'] = preg_replace('/\s*[-|–—]\s*Klook.*$/iu', '', $title);
                }
                $desc = self::array_find_first($props, array('description','seoDescription','summary','about','hotelDescription','hotelIntro'));
                if (!empty($desc)) {
                    $desc_text = self::normalize_text_block($desc);
                    if ($desc_text !== '') {
                        $data['description'] = self::html_paragraphs($desc);
                        $data['excerpt'] = wp_trim_words($desc_text, 34, '...');
                    }
                }
                $price = self::array_find_first($props, array('price','minPrice','salePrice','displayPrice','fromPrice','discountPrice'));
                if ($price !== '') {
                    $data['price'] = self::maybe_hotel_price($price);
                }
                $original = self::array_find_first($props, array('originalPrice','marketPrice','strikePrice','retailPrice'));
                if ($original !== '') {
                    $data['original_price'] = self::maybe_hotel_price($original);
                }
                $rating = self::array_find_first($props, array('rating','reviewScore','score'));
                if ($rating !== '') {
                    $data['rating'] = preg_replace('/[^0-9.]/', '', (string) $rating);
                }
                $reviews = self::array_find_first($props, array('reviewCount','reviewsCount','commentCount'));
                if ($reviews !== '') {
                    $data['review_count'] = preg_replace('/[^0-9]/', '', (string) $reviews);
                }
                $addr = self::array_find_first($props, array('address','fullAddress','hotelAddress'));
                if ($addr !== '') {
                    $data['address'] = self::normalize_text_block($addr);
                }
                $amen = self::array_find_first($props, array('amenities','facilities','facilityList','popularFacilities'));
                if ($amen !== '') {
                    $data['amenities'] = self::bullet_lines($amen, 16);
                }
                $images = self::array_collect_values($props, array('image','imageUrl','coverImageUrl','originalUrl'));
                $clean = self::clean_image_urls($images, 10);
                if (!empty($clean)) {
                    $data['images'] = array_values(array_unique(array_merge($data['images'], $clean)));
                    if (empty($data['image'])) {
                        $data['image'] = $data['images'][0];
                    }
                }
                // Hotel highlights/facilities
                if (empty($data['highlights'])) {
                    $hl = self::bullet_lines(self::array_find_first($props, array('highlights','keyHighlights','propertyHighlights','hotelHighlights')), 10);
                    if ($hl !== '') $data['highlights'] = $hl;
                }
                // Hotel inclusions (what's included in the rate)
                if (empty($data['inclusions'])) {
                    $inc = self::bullet_lines(self::array_find_first($props, array('inclusions','included','packageInclusions','rateInclusions')), 10);
                    if ($inc !== '') $data['inclusions'] = $inc;
                }
                // Hotel FAQ
                if (empty($data['faq'])) {
                    $faq_candidate = self::array_find_first($props, array('faq','faqs','faqList','questionAnswer','qAndA','hotelFaq'));
                    if (!empty($faq_candidate) && is_array($faq_candidate)) {
                        $faq_lines = array();
                        foreach (array_slice($faq_candidate, 0, 6) as $item) {
                            $q = self::normalize_text_block(is_array($item) ? self::array_find_first($item, array('question','title','q')) : '');
                            $a = self::normalize_text_block(is_array($item) ? self::array_find_first($item, array('answer','content','a')) : '');
                            if ($q && $a) $faq_lines[] = 'Q: ' . $q . "\nA: " . $a;
                        }
                        if ($faq_lines) $data['faq'] = implode("\n\n", $faq_lines);
                    }
                }
            }
        }
        if (empty($data['title']) && preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $data['title'] = self::normalize_front_title(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'), $url);
        }
        if (empty($data['title']) && preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
            $data['title'] = self::normalize_front_title(wp_strip_all_tags($m[1]), $url);
        }
        if (empty($data['title']) && preg_match('/<title>(.*?)<\/title>/si', $html, $m)) {
            $data['title'] = self::normalize_front_title(wp_strip_all_tags($m[1]), $url);
        }
        if (empty($data['description']) && preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $desc_text = trim(html_entity_decode($m[1], ENT_QUOTES, 'UTF-8'));
            $data['description'] = '<p>' . esc_html($desc_text) . '</p>';
            $data['excerpt'] = wp_trim_words($desc_text, 34, '...');
        }
        if (empty($data['image']) && preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
            $data['image'] = trim($m[1]);
        }
        $imgs = array();
        if (preg_match_all('/["\']?(https?:\/\/res\.klook\.com\/[^"\'<>\s]+\.(?:jpg|jpeg|png|webp))(?:[^"\'<>\s]*)["\']?/i', $html, $m)) {
            foreach ($m[1] as $img) {
                $imgs[] = str_replace('\/', '/', $img);
            }
        }
        $imgs = self::clean_image_urls(array_merge($data['images'], $imgs), 10);
        if (!empty($imgs)) {
            $data['images'] = $imgs;
            if (empty($data['image'])) {
                $data['image'] = $imgs[0];
            }
        }
        if (empty($data['price']) && preg_match('/(?:From|from|rates from|starting from)\s*(?:US\$|USD|AED|EUR|GBP|SAR|QAR|\$|€|£)\s*([0-9]+(?:\.[0-9]{1,2})?)/i', $html, $m)) {
            $data['price'] = self::maybe_hotel_price($m[1]);
        }
        if (empty($data['price']) && preg_match('/([A-Z]{3}|US\$|USD|AED|EUR|GBP|SAR|QAR|\$|€|£)\s*([0-9]+(?:\.[0-9]{1,2})?)\s*(?:per night|\/night|night)/i', $html, $m)) {
            $data['price'] = self::maybe_hotel_price($m[2]);
        }
        if (empty($data['rating']) && preg_match('/([0-9]\.[0-9])\s*(?:\/\s*5)?\s*(?:Excellent|Very Good|Great|reviews?)/i', $html, $m)) {
            $data['rating'] = $m[1];
        }
        if (empty($data['review_count']) && preg_match('/([0-9,]+)\s*reviews/i', $html, $m)) {
            $data['review_count'] = preg_replace('/[^0-9]/', '', $m[1]);
        }
        $data['price'] = self::maybe_hotel_price($data['price']);
        $data['original_price'] = self::maybe_hotel_price($data['original_price']);
        if (!empty($data['original_price']) && !empty($data['price']) && (float) $data['original_price'] <= (float) $data['price']) {
            $data['original_price'] = '';
        }
        if (empty($data['title'])) {
            $data['title'] = self::title_from_url_slug($url);
        }
        if (!empty($data['description'])) {
            $plain_desc = self::clean_klook_branding_text(wp_strip_all_tags($data['description']));
            if ($plain_desc !== '') {
                $data['description'] = '<p>' . esc_html($plain_desc) . '</p>';
                if (empty($data['excerpt'])) {
                    $data['excerpt'] = wp_trim_words($plain_desc, 34, '...');
                }
            }
        }
        return $data;
    }

    /**
     * Generate AIO SEO meta for hotel
     */
    private static function generate_hotel_seo_meta($post_id, $post) {
        $title = $post->post_title;
        $cities = wp_get_post_terms($post_id, 'travel_city');
        $countries = wp_get_post_terms($post_id, 'travel_country');
        $city_name = !empty($cities) ? $cities[0]->name : '';
        $country_name = !empty($countries) ? $countries[0]->name : '';
        $seo_title = trim($title . ($city_name ? ' - ' . $city_name : '') . ($country_name ? ' - ' . $country_name : ''));
        $seo_description = $post->post_excerpt ?: ('Book ' . $title . ($city_name ? ' in ' . $city_name : '') . '. View rooms, location details, facilities and current hotel information.');
        update_post_meta($post_id, '_aioseo_title', $seo_title);
        update_post_meta($post_id, '_aioseo_description', $seo_description);
        update_post_meta($post_id, '_aioseo_og_title', $seo_title);
        update_post_meta($post_id, '_aioseo_og_description', $seo_description);
        $keywords = array_values(array_filter(array_unique(array(
            strtolower(trim($title)),
            $city_name ? strtolower(trim($city_name . ' hotels')) : '',
            $country_name ? strtolower(trim($country_name . ' hotels')) : 'hotel deals',
            strtolower(trim($title . ' rooms')),
        ))));
        $focus = !empty($keywords) ? array_shift($keywords) : strtolower(trim($title));
        update_post_meta($post_id, '_aioseo_keyphrases', wp_json_encode(array(
            'focus' => array('keyphrase' => $focus),
            'additional' => array_map(function($kw){ return array('keyphrase' => $kw); }, array_slice($keywords, 0, 3))
        )));
    }

    /**
     * Generate AIO SEO meta for activity
     */
    private static function generate_activity_seo_meta($post_id, $post) {
        $title = $post->post_title;
        $cities = wp_get_post_terms($post_id, 'travel_city');
        $city_name = !empty($cities) ? $cities[0]->name : '';
        
        $seo_title = $title;
        if ($city_name) {
            $seo_title .= ' in ' . $city_name;
        }
        $seo_title .= ' | Tickets';
        
        $seo_description = $post->post_excerpt ?: wp_trim_words(strip_tags($post->post_content), 25);
        if (empty($seo_description)) {
            $seo_description = 'Discover ' . $title . ($city_name ? ' in ' . $city_name : '') . '. Tickets, timings, highlights and key details in one clean page.';
        }
        
        // Save to AIO SEO meta keys
        update_post_meta($post_id, '_aioseo_title', $seo_title);
        update_post_meta($post_id, '_aioseo_description', $seo_description);
        update_post_meta($post_id, '_aioseo_og_title', $seo_title);
        update_post_meta($post_id, '_aioseo_og_description', $seo_description);
        update_post_meta($post_id, '_aioseo_twitter_title', $seo_title);
        update_post_meta($post_id, '_aioseo_twitter_description', $seo_description);
        
        $focus_keyphrase = strtolower($title);
        if ($city_name) {
            $focus_keyphrase = strtolower($title . ' ' . $city_name);
        }
        $additional = array();
        if ($city_name) {
            $additional[] = array('keyphrase' => strtolower($city_name . ' attractions'));
            $additional[] = array('keyphrase' => strtolower($city_name . ' tours'));
            $additional[] = array('keyphrase' => strtolower($title . ' tickets'));
        } else {
            $additional[] = array('keyphrase' => strtolower($title . ' tickets'));
            $additional[] = array('keyphrase' => strtolower($title . ' booking'));
            $additional[] = array('keyphrase' => strtolower($title . ' attraction'));
        }
        update_post_meta($post_id, '_aioseo_keyphrases', json_encode(array(
            'focus' => array('keyphrase' => $focus_keyphrase),
            'additional' => $additional
        )));
    }
    
    /**
     * Generate AIO SEO meta for city
     */
    private static function generate_city_seo_meta($term_id) {
        $term = get_term($term_id, 'travel_city');
        if (!$term || is_wp_error($term)) return;
        
        $country_id = get_term_meta($term_id, 'fth_parent_country', true);
        $country_name = '';
        if ($country_id) {
            $country = get_term($country_id, 'travel_country');
            if ($country && !is_wp_error($country)) {
                $country_name = $country->name;
            }
        }
        
        $seo_title = 'Things to Do in ' . $term->name;
        if ($country_name) {
            $seo_title .= ', ' . $country_name;
        }
        $seo_title .= ' | Tours & Activities';
        
        $seo_description = 'Discover the best things to do in ' . $term->name . '. ';
        if ($country_name) {
            $seo_description .= 'Top attractions in ' . $country_name . '. ';
        }
        $seo_description .= 'Book tours, activities, tickets & experiences. Best prices guaranteed.';
        
        update_term_meta($term_id, '_aioseo_title', $seo_title);
        update_term_meta($term_id, '_aioseo_description', $seo_description);
        update_term_meta($term_id, '_aioseo_og_title', $seo_title);
        update_term_meta($term_id, '_aioseo_og_description', $seo_description);
        update_term_meta($term_id, '_aioseo_focus_keyphrase', 'things to do in ' . strtolower($term->name));
    }
    
    /**
     * Scrape data from Klook URL and auto-fill fields
     */
    public static function scrape_klook() {
        // Security check
        if (!check_ajax_referer('fth_scrape_klook', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url) || strpos($url, 'klook.com') === false) {
            wp_send_json_error(array('message' => 'Invalid Klook URL'));
        }
        
        // Normalize URL
        $url = self::normalize_klook_url($url);
        
        // Extract activity ID from URL
        $activity_id = self::extract_klook_activity_id($url);
        
        // Fetch the page
        $response = self::remote_get($url, array(
            'timeout'    => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers'    => array(
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ),
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Failed to fetch URL: ' . $response->get_error_message()));
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            wp_send_json_error(array('message' => 'Empty response from Klook'));
        }
        
        // Parse the HTML
        $data = self::parse_klook_html($body, $url, $activity_id);
        
        if (empty($data['title'])) {
            wp_send_json_error(array('message' => 'Could not extract activity data. The page structure may have changed.'));
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Normalize Klook URL
     */
    private static function normalize_klook_url($url) {
        // Ensure HTTPS
        $url = preg_replace('/^http:/', 'https:', $url);
        
        // Remove trailing slash inconsistency
        $url = rtrim($url, '/') . '/';
        
        return $url;
    }
    
    /**
     * Extract Klook activity ID from URL
     */
    private static function extract_klook_activity_id($url) {
        // Pattern: /activity/12345-activity-name/
        if (preg_match('/\/activity\/(\d+)-/', $url, $matches)) {
            return $matches[1];
        }
        return '';
    }
    
    /**
     * Parse Klook HTML to extract activity data
     */
    private static function parse_klook_html($html, $url, $activity_id) {
        $data = array(
            'title'          => '',
            'description'    => '',
            'excerpt'        => '',
            'price'          => '',
            'original_price' => '',
            'currency'       => 'USD',
            'rating'         => '',
            'review_count'   => '',
            'duration'       => '',
            'highlights'     => '',
            'inclusions'     => '',
            'exclusions'     => '',
            'meeting_point'  => '',
            'itinerary'      => '',
            'promo'          => '',
            'image'          => '',
            'images'         => array(),
            'affiliate_link' => '',
            'activity_id'    => $activity_id,
        );
        
        // Build affiliate link
        $affiliate_id = Flavor_Travel_Hub::get_affiliate_id();
        $data['affiliate_link'] = self::build_affiliate_redirect($url);
        

// METHOD 0: Try __NEXT_DATA__ first (best source on modern Klook pages)
if (preg_match('/<script[^>]*id=["\']__NEXT_DATA__["\'][^>]*>(.*?)<\/script>/si', $html, $next_match)) {
    $next_json = json_decode(html_entity_decode(trim($next_match[1]), ENT_QUOTES, 'UTF-8'), true);
    if (is_array($next_json)) {
        $next_props = isset($next_json['props']['pageProps']) ? $next_json['props']['pageProps'] : $next_json;

        if (empty($data['title'])) {
            $title_candidate = self::array_find_first($next_props, array('seoTitle', 'title', 'name', 'activityName', 'productName'));
            $title_candidate = self::normalize_text_block($title_candidate);
            if ($title_candidate !== '') {
                $data['title'] = $title_candidate;
            }
        }

        $desc_candidate = self::array_find_first($next_props, array('description', 'seoDescription', 'introduction', 'details', 'content', 'activityIntro', 'packageDescription'));
        if ((empty($data['description']) || strlen(wp_strip_all_tags($data['description'])) < 120) && !empty($desc_candidate)) {
            $desc_text = self::normalize_text_block($desc_candidate);
            if ($desc_text !== '') {
                $data['description'] = self::html_paragraphs($desc_candidate);
                $data['excerpt'] = wp_trim_words($desc_text, 34, '...');
            }
        }

        if (empty($data['highlights'])) {
            $highlights_text = self::bullet_lines(self::array_find_first($next_props, array('highlights', 'activityHighlights', 'keyHighlights', 'highlightsText')), 10);
            if ($highlights_text !== '') {
                $data['highlights'] = $highlights_text;
            }
        }

        if (empty($data['inclusions'])) {
            $inc_text = self::bullet_lines(self::array_find_first($next_props, array('inclusions', 'included', 'packageInclusions', 'whatIsIncluded')), 12);
            if ($inc_text !== '') {
                $data['inclusions'] = $inc_text;
            }
        }

        if (empty($data['exclusions'])) {
            $exc_text = self::bullet_lines(self::array_find_first($next_props, array('exclusions', 'excluded', 'whatIsExcluded')), 12);
            if ($exc_text !== '') {
                $data['exclusions'] = $exc_text;
            }
        }

        if (empty($data['meeting_point'])) {
            $meeting_text = self::normalize_text_block(self::array_find_first($next_props, array('meetingPoint', 'meetingLocation', 'address', 'locationName')));
            if ($meeting_text !== '') {
                $data['meeting_point'] = $meeting_text;
            }
        }

        if (empty($data['itinerary'])) {
            $it_text = self::bullet_lines(self::array_find_first($next_props, array('itinerary', 'itineraries', 'schedule', 'packages', 'packageInfo')), 10);
            if ($it_text !== '') {
                $data['itinerary'] = $it_text;
            }
        }

        if (empty($data['promo'])) {
            $promo_text = self::normalize_text_block(self::array_find_first($next_props, array('discount', 'promo', 'badge', 'discountLabel', 'promotionTag', 'saleTag', 'savePrice')));
            if ($promo_text !== '') {
                $data['promo'] = $promo_text;
            }
        }

        // ── Price extraction (v1.7 improved) ──────────────────────────
        if (empty($data['price'])) {
            $price_candidate = self::array_find_first($next_props, array(
                'sellPrice','fromPrice','minSellingPrice','salePrice','discountPrice','lowestPrice','price'
            ));
            // If we got a price-object, drill into it and extract currency at the same time
            if (is_array($price_candidate)) {
                $currency_from_obj = self::array_find_first($price_candidate, array('currency','currencyCode'));
                if ($currency_from_obj && empty($data['currency'])) {
                    $data['currency'] = strtoupper(preg_replace('/[^A-Z]/', '', (string) $currency_from_obj));
                }
                $price_candidate = self::array_find_first($price_candidate, array('amount','value','formatValue','displayPrice'));
            }
            if ($price_candidate !== '' && $price_candidate !== null) {
                $p = self::normalize_price_amount($price_candidate, 1);
                if ($p !== '') $data['price'] = $p;
            }
        }
        // Currency fallback from dedicated keys
        if (empty($data['currency'])) {
            $cur = self::array_find_first($next_props, array('currency','currencyCode','priceCurrency'));
            if ($cur) $data['currency'] = strtoupper(preg_replace('/[^A-Z]/', '', (string) $cur));
        }

        if (empty($data['original_price'])) {
            $orig_candidate = self::array_find_first($next_props, array('originalPrice','marketPrice','strikePrice','retailPrice','crossedPrice'));
            if (is_array($orig_candidate)) {
                $orig_candidate = self::array_find_first($orig_candidate, array('amount','value','formatValue','displayPrice'));
            }
            if ($orig_candidate !== '' && $orig_candidate !== null) {
                $op = self::normalize_price_amount($orig_candidate, 1);
                if ($op !== '') $data['original_price'] = $op;
            }
        }
        // Extract FAQ if available
        if (empty($data['faq'])) {
            $faq_candidate = self::array_find_first($next_props, array('faq','faqs','faqList','questionAnswer','qAndA'));
            if (!empty($faq_candidate)) {
                $faq_lines = array();
                if (is_array($faq_candidate)) {
                    foreach (array_slice((array) $faq_candidate, 0, 6) as $item) {
                        $q = self::normalize_text_block(is_array($item) ? self::array_find_first($item, array('question','title','q')) : '');
                        $a = self::normalize_text_block(is_array($item) ? self::array_find_first($item, array('answer','content','a')) : '');
                        if ($q && $a) $faq_lines[] = 'Q: ' . $q . "\nA: " . $a;
                    }
                }
                if (!empty($faq_lines)) $data['faq'] = implode("\n\n", $faq_lines);
            }
        }

        if (empty($data['rating'])) {
            $rating_candidate = self::array_find_first($next_props, array('rating', 'ratingValue', 'score', 'star'));
            if (is_string($rating_candidate) || is_numeric($rating_candidate)) {
                $rating_val = (float) preg_replace('/[^\d\.]/', '', (string) $rating_candidate);
                if ($rating_val > 0 && $rating_val <= 5) {
                    $data['rating'] = $rating_val;
                }
            }
        }

        if (empty($data['review_count'])) {
            $reviews_candidate = self::array_find_first($next_props, array('reviewCount', 'reviews', 'commentCount', 'participantCount', 'totalReviews'));
            if (is_string($reviews_candidate) || is_numeric($reviews_candidate)) {
                $reviews_val = (int) preg_replace('/[^\d]/', '', (string) $reviews_candidate);
                if ($reviews_val > 0) {
                    $data['review_count'] = $reviews_val;
                }
            }
        }

        if (empty($data['duration'])) {
            $duration_text = self::normalize_text_block(self::array_find_first($next_props, array('duration', 'activityDuration', 'packageDuration', 'serviceDuration')));
            if ($duration_text !== '') {
                $data['duration'] = $duration_text;
            }
        }

        if (empty($data['image'])) {
            $image_candidate = self::array_find_first($next_props, array('coverImageUrl', 'imageUrl', 'image', 'heroImageUrl', 'shareImage'));
            if (is_string($image_candidate) && $image_candidate !== '') {
                $data['image'] = $image_candidate;
            } elseif (is_array($image_candidate)) {
                $possible = self::array_find_first($image_candidate, array('url', 'src', 'originalUrl'));
                if (is_string($possible) && $possible !== '') {
                    $data['image'] = $possible;
                }
            }
        }

        $next_images = self::array_collect_values($next_props, array('coverImageUrl', 'imageUrl', 'image', 'gallery', 'images', 'heroImageUrl'));
        if (!empty($next_images)) {
            $filtered_next = array();
            foreach ($next_images as $img) {
                if (is_string($img) && preg_match('#https?://#i', $img) && (stripos($img, 'klook') !== false || preg_match('/\.(jpg|jpeg|png|webp)(\?|$)/i', $img))) {
                    $filtered_next[] = $img;
                }
            }
            if (!empty($filtered_next)) {
                $data['images'] = array_values(array_unique(array_merge($data['images'], $filtered_next)));
                if (empty($data['image'])) {
                    $data['image'] = $data['images'][0];
                }
            }
        }
    }
}

// METHOD 1: Try JSON-LD data (most reliable when present)
        preg_match_all('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', $html, $json_matches);
        
        if (!empty($json_matches[1])) {
            foreach ($json_matches[1] as $json_str) {
                $json_data = json_decode(trim($json_str), true);
                if (!$json_data) continue;
                
                // Handle array of JSON-LD objects
                $items = isset($json_data[0]) ? $json_data : array($json_data);
                
                foreach ($items as $item) {
                    $type = isset($item['@type']) ? $item['@type'] : '';
                    
                    if (in_array($type, array('Product', 'TouristAttraction', 'Event', 'LocalBusiness', 'Thing'))) {
                        if (isset($item['name']) && empty($data['title'])) {
                            $data['title'] = html_entity_decode($item['name'], ENT_QUOTES, 'UTF-8');
                        }
                        if (isset($item['description']) && empty($data['description'])) {
                            $desc = html_entity_decode($item['description'], ENT_QUOTES, 'UTF-8');
                            $data['description'] = '<p>' . $desc . '</p>';
                            $data['excerpt'] = wp_trim_words($desc, 30, '...');
                        }
                        if (isset($item['image']) && empty($data['image'])) {
                            $data['image'] = is_array($item['image']) ? $item['image'][0] : $item['image'];
                        }
                        if (isset($item['offers'])) {
                            $offers = isset($item['offers'][0]) ? $item['offers'][0] : $item['offers'];
                            if (isset($offers['price'])) {
                                $data['price'] = floatval($offers['price']);
                            }
                            if (isset($offers['priceCurrency'])) {
                                $data['currency'] = $offers['priceCurrency'];
                            }
                        }
                        if (isset($item['aggregateRating'])) {
                            if (isset($item['aggregateRating']['ratingValue'])) {
                                $data['rating'] = floatval($item['aggregateRating']['ratingValue']);
                            }
                            if (isset($item['aggregateRating']['reviewCount'])) {
                                $data['review_count'] = intval($item['aggregateRating']['reviewCount']);
                            }
                        }
                    }
                }
            }
        }
        
        // METHOD 2: Extract from Open Graph meta tags
        if (empty($data['title'])) {
            if (preg_match('/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']+)["\']/i', $html, $match)) {
                $data['title'] = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
            } elseif (preg_match('/<meta\s+content=["\']([^"\']+)["\']\s+property=["\']og:title["\']/i', $html, $match)) {
                $data['title'] = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
            }
            // Clean Klook suffix
            if ($data['title']) {
                $data['title'] = preg_replace('/\s*[-|–—]\s*Klook.*$/iu', '', $data['title']);
            }
        }
        
        if (empty($data['image'])) {
            if (preg_match('/<meta\s+property=["\']og:image["\']\s+content=["\']([^"\']+)["\']/i', $html, $match)) {
                $data['image'] = $match[1];
            } elseif (preg_match('/<meta\s+content=["\']([^"\']+)["\']\s+property=["\']og:image["\']/i', $html, $match)) {
                $data['image'] = $match[1];
            }
        }
        
        if (empty($data['description'])) {
            if (preg_match('/<meta\s+property=["\']og:description["\']\s+content=["\']([^"\']+)["\']/i', $html, $match)) {
                $desc = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
                $data['description'] = '<p>' . $desc . '</p>';
                $data['excerpt'] = wp_trim_words($desc, 30, '...');
            } elseif (preg_match('/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i', $html, $match)) {
                $desc = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
                $data['description'] = '<p>' . $desc . '</p>';
                $data['excerpt'] = wp_trim_words($desc, 30, '...');
            }
        }
        
        // METHOD 3: Extract from title tag
        if (empty($data['title'])) {
            if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $match)) {
                $data['title'] = html_entity_decode(trim($match[1]), ENT_QUOTES, 'UTF-8');
                $data['title'] = preg_replace('/\s*[-|–—]\s*Klook.*$/iu', '', $data['title']);
            }
        }
        
        // METHOD 4: Extract from URL as last resort
        if (empty($data['title'])) {
            // Pattern: /activity/12345-activity-name-here/
            if (preg_match('/\/activity\/\d+-([^\/\?]+)/', $url, $match)) {
                $slug = str_replace(array('-', '_'), ' ', $match[1]);
                $data['title'] = ucwords($slug);
            }
        }
        
        // METHOD 5: Try to extract price patterns
        if (empty($data['price'])) {
            // Try various price patterns
            $price_patterns = array(
                '/["\'](displayPrice|price)["\']:\s*["\']?\$?(\d+(?:\.\d{2})?)/i',
                '/"price":\s*(\d+(?:\.\d{2})?)/i',
                '/From\s*\$(\d+(?:\.\d{2})?)/i',
                '/USD\s*(\d+(?:\.\d{2})?)/i',
                '/AED\s*(\d+(?:\.\d{2})?)/i',
                '/€\s*(\d+(?:\.\d{2})?)/i',
            );
            
            foreach ($price_patterns as $pattern) {
                if (preg_match($pattern, $html, $match)) {
                    $price_val = isset($match[2]) ? $match[2] : $match[1];
                    $data['price'] = floatval(str_replace(',', '', $price_val));
                    if ($data['price'] > 0) break;
                }
            }
        }
        
        // METHOD 6: Extract rating
        if (empty($data['rating'])) {
            $rating_patterns = array(
                '/"ratingValue":\s*["\']?(\d+\.?\d*)/i',
                '/(\d\.\d)\s*(?:\/\s*5|out of 5|stars?)/i',
                '/rating["\']?:\s*["\']?(\d+\.?\d*)/i',
            );
            
            foreach ($rating_patterns as $pattern) {
                if (preg_match($pattern, $html, $match)) {
                    $data['rating'] = floatval($match[1]);
                    if ($data['rating'] > 0 && $data['rating'] <= 5) break;
                }
            }
        }
        
        // METHOD 7: Extract review count
        if (empty($data['review_count'])) {
            $review_patterns = array(
                '/"reviewCount":\s*["\']?(\d+)/i',
                '/(\d{1,3}(?:,\d{3})*)\s*(?:reviews?|ratings?|avis)/i',
                '/\((\d{1,3}(?:,\d{3})*)\)/i',
            );
            
            foreach ($review_patterns as $pattern) {
                if (preg_match($pattern, $html, $match)) {
                    $data['review_count'] = intval(str_replace(',', '', $match[1]));
                    if ($data['review_count'] > 0) break;
                }
            }
        }
        
        // METHOD 7B: Build a richer fallback description if still too short
        if (empty($data['description']) || strlen(wp_strip_all_tags($data['description'])) < 120) {
            $body_text = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($html)));
            if (strlen($body_text) > 180) {
                $snippet = mb_substr($body_text, 0, 900);
                $data['description'] = '<p>' . esc_html($snippet) . '...</p>';
                if (empty($data['excerpt'])) {
                    $data['excerpt'] = wp_trim_words($snippet, 34, '...');
                }
            }
        }

        // METHOD 8: Try to find any large image from Klook CDN
        if (empty($data['image'])) {
            if (preg_match('/["\']?(https?:\/\/res\.klook\.com\/[^"\'<>\s]+\.(?:jpg|jpeg|png|webp))["\']?/i', $html, $match)) {
                $data['image'] = $match[1];
            }
        }
        
        // METHOD 9: Duration patterns
        if (empty($data['duration'])) {
            $duration_patterns = array(
                '/duration["\']?\s*:\s*["\']?([^"\'<,]+hours?)/i',
                '/(\d+(?:\s*-\s*\d+)?\s*hours?)/i',
                '/(Full\s*day|Half\s*day)/i',
            );
            
            foreach ($duration_patterns as $pattern) {
                if (preg_match($pattern, $html, $match)) {
                    $data['duration'] = trim($match[1]);
                    if (!empty($data['duration'])) break;
                }
            }
        }
        
        // METHOD 10: Extract MULTIPLE IMAGES from Klook CDN (for gallery)
        $all_images = array();
        
        // Try to find images in JSON data
        if (preg_match_all('/"image(?:Url)?"\s*:\s*"(https?:\/\/res\.klook\.com\/[^"]+)"/i', $html, $img_matches)) {
            $all_images = array_merge($all_images, $img_matches[1]);
        }
        
        // Try og:image alternates
        if (preg_match_all('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $img_matches)) {
            $all_images = array_merge($all_images, $img_matches[1]);
        }
        
        // Find all Klook CDN images (with or without file extension)
        if (preg_match_all('/["\']?(https?:\/\/res\.klook\.com\/image\/upload\/[^"\'<>\s]+\.(?:jpg|jpeg|png|webp))["\']?/i', $html, $img_matches)) {
            $all_images = array_merge($all_images, $img_matches[1]);
        }
        // Klook CDN URLs without extension (common in __NEXT_DATA__ JSON)
        if (preg_match_all('/"(https?:\/\/res\.klook\.com\/image\/upload\/[^"\'<>\s]{10,})"/i', $html, $img_matches)) {
            $all_images = array_merge($all_images, $img_matches[1]);
        }

        // Also check for other image patterns
        if (preg_match_all('/["\']?(https?:\/\/res\.klook\.com\/[^"\'<>\s]+\.(?:jpg|jpeg|png|webp))["\']?/i', $html, $img_matches)) {
            $all_images = array_merge($all_images, $img_matches[1]);
        }
        
        // Clean and deduplicate
        $all_images = array_unique(array_filter($all_images, function($img) {
            return !empty($img) && strlen($img) > 20;
        }));
        
        if (!empty($all_images)) {
            $data['images'] = array_values($all_images);
            // Set main image if not already set
            if (empty($data['image']) && !empty($data['images'][0])) {
                $data['image'] = $data['images'][0];
            }
        }
        
        if (empty($data['description'])) {
            $data['description'] = '<p>Discover this experience and book online for instant confirmation.</p>';
        }

        if (!empty($data['highlights']) && strpos($data['description'], 'Highlights') === false) {
            $highlight_lines = array_filter(array_map('trim', explode("\n", $data['highlights'])));
            if (!empty($highlight_lines)) {
                $data['description'] .= '<h3>Highlights</h3><ul>';
                foreach (array_slice($highlight_lines, 0, 8) as $line) {
                    $data['description'] .= '<li>' . esc_html($line) . '</li>';
                }
                $data['description'] .= '</ul>';
            }
        }

        if (!empty($data['itinerary']) && strpos($data['description'], 'Itinerary') === false) {
            $itinerary_lines = array_filter(array_map('trim', explode("\n", $data['itinerary'])));
            if (!empty($itinerary_lines)) {
                $data['description'] .= '<h3>Itinerary</h3><ul>';
                foreach (array_slice($itinerary_lines, 0, 8) as $line) {
                    $data['description'] .= '<li>' . esc_html($line) . '</li>';
                }
                $data['description'] .= '</ul>';
            }
        }

        return $data;
    }
    
    /**
     * Scrape Klook city/destination page
     */
    public static function scrape_klook_city() {
        // Security check
        if (!check_ajax_referer('fth_scrape_klook_city', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('manage_categories')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url) || strpos($url, 'klook.com') === false) {
            wp_send_json_error(array('message' => 'Invalid Klook URL'));
        }
        
        // Fetch the page
        $response = self::remote_get($url, array(
            'timeout'    => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers'    => array(
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ),
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Failed to fetch URL'));
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            wp_send_json_error(array('message' => 'Empty response'));
        }
        
        $data = self::parse_klook_city_html($body, $url);
        
        if (empty($data['name'])) {
            wp_send_json_error(array('message' => 'Could not extract city data'));
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Parse Klook city/destination HTML
     */
    private static function parse_klook_city_html($html, $url) {
        $data = array(
            'name'        => '',
            'description' => '',
            'hero_image'  => '',
            'deeplink'    => '',
        );
        
        // Build affiliate link
        $affiliate_id = Flavor_Travel_Hub::get_affiliate_id();
        $data['deeplink'] = 'https://affiliate.klook.com/redirect?aid=' . $affiliate_id . '&aff_adid=&k_site=&url=' . urlencode($url);
        
        // Extract city name from URL or title
        // Pattern: /city/123-city-name/ or /destination/city-name/
        if (preg_match('/\/city\/\d+-([^\/]+)/', $url, $match)) {
            $data['name'] = ucwords(str_replace('-', ' ', $match[1]));
        } elseif (preg_match('/\/destination\/([^\/]+)/', $url, $match)) {
            $data['name'] = ucwords(str_replace('-', ' ', $match[1]));
        }
        
        // Try og:title
        if (preg_match('/<meta property="og:title" content="([^"]+)"/', $html, $match)) {
            $title = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
            // Clean Klook suffix
            $title = preg_replace('/\s*[-|]\s*Klook.*$/i', '', $title);
            $title = preg_replace('/Things to Do in\s*/i', '', $title);
            if (!empty(trim($title))) {
                $data['name'] = trim($title);
            }
        }
        
        // Try h1 tag
        if (empty($data['name']) && preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $match)) {
            $data['name'] = trim(html_entity_decode($match[1], ENT_QUOTES, 'UTF-8'));
        }
        
        // Extract description
        if (preg_match('/<meta property="og:description" content="([^"]+)"/', $html, $match)) {
            $data['description'] = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
        } elseif (preg_match('/<meta name="description" content="([^"]+)"/', $html, $match)) {
            $data['description'] = html_entity_decode($match[1], ENT_QUOTES, 'UTF-8');
        }
        
        // Extract hero image
        if (preg_match('/<meta property="og:image" content="([^"]+)"/', $html, $match)) {
            $data['hero_image'] = $match[1];
        } elseif (preg_match('/src="(https:\/\/res\.klook\.com\/[^"]+(?:hero|banner|city)[^"]*\.(jpg|jpeg|png|webp))"/i', $html, $match)) {
            $data['hero_image'] = $match[1];
        } elseif (preg_match('/src="(https:\/\/res\.klook\.com\/[^"]+\.(jpg|jpeg|png|webp))"/i', $html, $match)) {
            $data['hero_image'] = $match[1];
        }
        
        return $data;
    }
    
    /**
     * Scrape Klook destination for post type
     */
    public static function scrape_klook_destination() {
        // Security check
        if (!check_ajax_referer('fth_scrape_klook_destination', 'nonce', false)) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
        }
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url) || strpos($url, 'klook.com') === false) {
            wp_send_json_error(array('message' => 'Invalid Klook URL'));
        }
        
        // Fetch the page
        $response = self::remote_get($url, array(
            'timeout'    => 30,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'headers'    => array(
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            ),
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => 'Failed to fetch URL'));
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            wp_send_json_error(array('message' => 'Empty response'));
        }
        
        // Reuse city parser - same structure
        $data = self::parse_klook_city_html($body, $url);
        
        // Additional fields for destinations
        $data['title'] = $data['name'];
        $data['affiliate_link'] = $data['deeplink'];
        
        if (empty($data['title'])) {
            wp_send_json_error(array('message' => 'Could not extract destination data'));
        }
        
        wp_send_json_success($data);
    }
    
    /**
     * Search suggestions for autocomplete - Klook style
     */
    public static function search_suggestions() {
        $query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
        
        if (strlen($query) < 2) {
            wp_send_json(array('activities' => array(), 'cities' => array(), 'categories' => array()));
        }
        
        $results = array(
            'activities' => array(),
            'cities'     => array(),
            'categories' => array(),
        );
        
        // Search activities
        $activities = new WP_Query(array(
            'post_type'      => 'travel_activity',
            'post_status'    => 'publish',
            'posts_per_page' => 5,
            's'              => $query,
        ));
        
        if ($activities->have_posts()) {
            while ($activities->have_posts()) {
                $activities->the_post();
                $post_id = get_the_ID();
                $image = get_post_meta($post_id, '_fth_external_image', true);
                if (!$image && has_post_thumbnail($post_id)) {
                    $image = get_the_post_thumbnail_url($post_id, 'thumbnail');
                }
                $cities = wp_get_post_terms($post_id, 'travel_city');
                $city_name = !empty($cities) ? $cities[0]->name : '';
                
                $results['activities'][] = array(
                    'id'    => $post_id,
                    'title' => get_the_title(),
                    'url'   => get_permalink(),
                    'image' => $image,
                    'city'  => $city_name,
                    'price' => get_post_meta($post_id, '_fth_price', true),
                );
            }
            wp_reset_postdata();
        }
        
        // Search cities
        $cities = get_terms(array(
            'taxonomy'   => 'travel_city',
            'hide_empty' => false,
            'search'     => $query,
            'number'     => 5,
        ));
        
        if (!is_wp_error($cities)) {
            foreach ($cities as $city) {
                $hero_image = get_term_meta($city->term_id, 'fth_hero_image', true);
                $activity_count = FTH_Templates::get_city_activity_count($city->term_id);
                
                $results['cities'][] = array(
                    'id'    => $city->term_id,
                    'name'  => $city->name,
                    'slug'  => $city->slug,
                    'url'   => get_term_link($city),
                    'image' => $hero_image,
                    'count' => $activity_count,
                );
            }
        }
        
        // Search categories
        $categories = get_terms(array(
            'taxonomy'   => 'travel_category',
            'hide_empty' => false,
            'search'     => $query,
            'number'     => 3,
        ));
        
        if (!is_wp_error($categories)) {
            foreach ($categories as $cat) {
                $icon = get_term_meta($cat->term_id, 'fth_icon', true);
                
                $results['categories'][] = array(
                    'id'   => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'url'  => get_term_link($cat),
                    'icon' => $icon,
                );
            }
        }
        
        wp_send_json($results);
    }
    
    /**
     * Search activities AJAX
     */
    public static function search_activities() {
        check_ajax_referer('fth_ajax_nonce', 'nonce');
        
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        
        $activities = FTH_Search::search_activities(array(
            'keyword'  => $keyword,
            'city'     => $city,
            'category' => $category,
            'type'     => $type,
            'paged'    => $page,
        ));
        
        $html = '';
        
        if ($activities->have_posts()) {
            while ($activities->have_posts()) {
                $activities->the_post();
                $html .= FTH_Templates::get_activity_card(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'html'       => $html,
            'found'      => $activities->found_posts,
            'max_pages'  => $activities->max_num_pages,
            'current'    => $page,
        ));
    }
    
    /**
     * Load more activities AJAX
     */
    public static function load_more_activities() {
        check_ajax_referer('fth_ajax_nonce', 'nonce');
        
        $page = isset($_POST['page']) ? absint($_POST['page']) : 1;
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        
        $activities = FTH_Search::search_activities(array(
            'city'     => $city,
            'category' => $category,
            'paged'    => $page,
        ));
        
        $html = '';
        
        if ($activities->have_posts()) {
            while ($activities->have_posts()) {
                $activities->the_post();
                $html .= FTH_Templates::get_activity_card(get_the_ID());
            }
            wp_reset_postdata();
        }
        
        wp_send_json_success(array(
            'html'      => $html,
            'has_more'  => $page < $activities->max_num_pages,
        ));
    }
    
    /**
     * Filter activities AJAX
     */
    public static function filter_activities() {
        check_ajax_referer('fth_ajax_nonce', 'nonce');
        
        $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
        $sort = isset($_POST['sort']) ? sanitize_text_field($_POST['sort']) : 'date';
        
        $orderby = 'date';
        $order = 'DESC';
        
        switch ($sort) {
            case 'price_low':
                $orderby = 'meta_value_num';
                $order = 'ASC';
                break;
            case 'price_high':
                $orderby = 'meta_value_num';
                $order = 'DESC';
                break;
            case 'rating':
                $orderby = 'meta_value_num';
                $order = 'DESC';
                break;
            case 'popular':
                $orderby = 'meta_value_num';
                $order = 'DESC';
                break;
        }
        
        $args = array(
            'city'     => $city,
            'category' => $category,
            'type'     => $type,
            'orderby'  => $orderby,
            'order'    => $order,
            'per_page' => 12,
        );
        
        if ($sort === 'price_low' || $sort === 'price_high') {
            $args['meta_key'] = '_fth_price';
        } elseif ($sort === 'rating') {
            $args['meta_key'] = '_fth_rating';
        } elseif ($sort === 'popular') {
            $args['meta_key'] = '_fth_review_count';
        }
        
        $activities = FTH_Search::search_activities($args);
        
        $html = '';
        
        if ($activities->have_posts()) {
            while ($activities->have_posts()) {
                $activities->the_post();
                $html .= FTH_Templates::get_activity_card(get_the_ID());
            }
            wp_reset_postdata();
        } else {
            $html = '<div class="fth-no-results"><p>No activities found matching your criteria.</p></div>';
        }
        
        wp_send_json_success(array(
            'html'      => $html,
            'found'     => $activities->found_posts,
            'max_pages' => $activities->max_num_pages,
        ));
    }
    
    /**
     * Get cities by country AJAX
     */
    public static function get_cities_by_country() {
        check_ajax_referer('fth_ajax_nonce', 'nonce');
        
        $country_id = isset($_POST['country_id']) ? absint($_POST['country_id']) : 0;
        
        if (!$country_id) {
            wp_send_json_error('Invalid country');
        }
        
        $cities = get_terms(array(
            'taxonomy'   => 'travel_city',
            'hide_empty' => false,
            'meta_query' => array(
                array(
                    'key'   => 'fth_parent_country',
                    'value' => $country_id,
                ),
            ),
        ));
        
        $options = array();
        
        if (!is_wp_error($cities)) {
            foreach ($cities as $city) {
                $options[] = array(
                    'id'   => $city->term_id,
                    'slug' => $city->slug,
                    'name' => $city->name,
                );
            }
        }
        
        wp_send_json_success($options);
    }
    
    /**
     * Admin preview activity AJAX
     */
    public static function admin_preview_activity() {
        check_ajax_referer('fth_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        
        if (!$post_id) {
            wp_send_json_error('Invalid post');
        }
        
        $html = FTH_Templates::get_activity_card($post_id);
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Admin bulk action AJAX
     */
    public static function admin_bulk_action() {
        check_ajax_referer('fth_admin_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Unauthorized');
        }
        
        $action = isset($_POST['bulk_action']) ? sanitize_text_field($_POST['bulk_action']) : '';
        $post_ids = isset($_POST['post_ids']) ? array_map('absint', $_POST['post_ids']) : array();
        
        if (empty($action) || empty($post_ids)) {
            wp_send_json_error('Invalid request');
        }
        
        $updated = 0;
        
        foreach ($post_ids as $post_id) {
            switch ($action) {
                case 'feature':
                    update_post_meta($post_id, '_fth_is_featured', '1');
                    $updated++;
                    break;
                case 'unfeature':
                    update_post_meta($post_id, '_fth_is_featured', '0');
                    $updated++;
                    break;
                case 'bestseller':
                    update_post_meta($post_id, '_fth_is_bestseller', '1');
                    $updated++;
                    break;
                case 'unbest':
                    update_post_meta($post_id, '_fth_is_bestseller', '0');
                    $updated++;
                    break;
            }
        }
        
        wp_send_json_success(array('updated' => $updated));
    }

    /**
     * Bulk import activities AND hotels for an entire country.
     * Iterates through all cities that belong to the chosen country term,
     * discovers activity/hotel links for each city and imports them.
     */
    public static function import_bulk_country() {
        self::begin_import_request();
        try {
            if (!check_ajax_referer('fth_import_publish', 'nonce', false)) {
                self::send_json_error_clean('Security check failed');
            }
            if (!current_user_can('edit_posts')) {
                self::send_json_error_clean('Unauthorized');
            }
            $country_id  = isset($_POST['country']) ? intval($_POST['country']) : 0;
            $import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : 'activities'; // 'activities' or 'hotels'
            $limit       = isset($_POST['limit']) ? max(1, min(300, intval($_POST['limit']))) : 60;
            $category    = isset($_POST['category']) ? intval($_POST['category']) : 0;

            if (!$country_id) {
                self::send_json_error_clean('Please select a country');
            }
            $country_term = get_term($country_id, 'travel_country');
            if (!$country_term || is_wp_error($country_term)) {
                self::send_json_error_clean('Country not found');
            }

            // Get all cities for this country
            $city_terms = get_terms(array(
                'taxonomy'   => 'travel_city',
                'hide_empty' => false,
                'meta_query' => array(array('key' => 'fth_parent_country', 'value' => $country_id)),
            ));
            if (empty($city_terms) || is_wp_error($city_terms)) {
                self::send_json_error_clean('No cities found for this country. Import at least one city first.');
            }

            $total_imported = 0;
            $total_checked  = 0;
            $all_errors     = array();
            $start          = time();

            foreach ($city_terms as $city_term) {
                if ((time() - $start) > 240) {
                    $all_errors[] = 'Time limit reached – some cities not processed. Run again to continue.';
                    break;
                }
                // Build the destination URL for this city (try slug-based discovery)
                $city_slug = $city_term->slug;
                $city_id   = $city_term->term_id;
                // Klook city destination ID stored in term meta (set during city import)
                $klook_dest_id = get_term_meta($city_id, 'fth_klook_dest_id', true);
                if ($klook_dest_id) {
                    $dest_url = 'https://www.klook.com/en-US/destination/c' . $klook_dest_id . '-' . $city_slug . '/';
                } else {
                    // Attempt to find the destination URL stored during city import
                    $dest_url = get_term_meta($city_id, 'fth_klook_url', true);
                }
                if (empty($dest_url)) {
                    $all_errors[] = 'No Klook URL for city: ' . $city_term->name;
                    continue;
                }

                // Fetch activity/hotel links from this city destination page
                $links = array();
                $try_urls = array($dest_url);
                if ($import_type === 'hotels') {
                    $try_urls[] = trailingslashit($dest_url) . '3-hotel/';
                    $try_urls[] = preg_replace('#/$#', '', $dest_url) . '/3-hotel/';
                }
                foreach ($try_urls as $try_url) {
                    for ($page = 1; $page <= 4; $page++) {
                        $page_url = ($page === 1) ? $try_url : add_query_arg('page', $page, $try_url);
                        $resp = self::remote_get($page_url, array('timeout' => 30));
                        if (is_wp_error($resp)) continue;
                        $body = wp_remote_retrieve_body($resp);
                        if (empty($body) || (strpos($body, '__NEXT_DATA__') === false && strpos($body, '/activity/') === false && strpos($body, '/hotels/') === false)) continue;
                        $found = ($import_type === 'hotels')
                            ? self::extract_hotel_links_from_html($body)
                            : self::extract_activity_links_from_html($body);
                        if (!empty($found)) {
                            $links = array_merge($links, $found);
                        }
                        if (count($links) >= $limit) break 2;
                    }
                }
                $links = array_values(array_unique($links));
                if (empty($links)) continue;
                $links = array_slice($links, 0, $limit);

                foreach ($links as $item_url) {
                    if ((time() - $start) > 240) break 2;
                    $total_checked++;
                    $params = array('city'=>$city_id,'country'=>$country_id,'category'=>$category,'publish'=>1,'is_featured'=>0,'is_bestseller'=>0);
                    $result = ($import_type === 'hotels')
                        ? self::import_hotel($item_url, $params, self::build_affiliate_redirect($item_url))
                        : self::import_activity($item_url, $params, self::build_affiliate_redirect($item_url));
                    if (!empty($result['success'])) {
                        $total_imported++;
                    } else {
                        $all_errors[] = !empty($result['message']) ? $result['message'] : 'Unknown error';
                    }
                }
            }

            $message = 'Country import done: ' . $total_imported . ' ' . $import_type . ' imported out of ' . $total_checked . ' found.';
            if (!empty($all_errors)) {
                $message .= ' Notes: ' . implode(' | ', array_slice($all_errors, 0, 5));
            }
            self::send_json_success_clean(array('imported'=>$total_imported,'checked'=>$total_checked,'message'=>$message));
        } catch (Throwable $e) {
            self::send_json_error_clean('Country import failed: ' . $e->getMessage());
        }
    }
}
