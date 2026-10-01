<?php
/**
 * alphaXiv Papers – Kern (wird von alphaxiv-papers.php geladen)
 * Version: 1.3 (01.10.2026)
 * Änderung: Wählbarer KI-Anbieter (Anthropic, OpenAI, Mistral, xAI, Gemini, OpenRouter, eigener OpenAI-kompatibler Dienst), Verbindungstest in den Einstellungen.
 *
 * Idee und Entwicklung: meiersworld.de – https://www.meiersworld.de
 * Copyright (C) 2026 meiersworld.de – GPL-2.0-or-later. Bitte den Credit unverändert lassen.
 */

if (!defined('ABSPATH')) { exit; }

define('MW_AXP_VERSION', '1.3');
if (!defined('MW_AXP_CACHE_TTL')) { define('MW_AXP_CACHE_TTL', 7 * DAY_IN_SECONDS); }

/* ---------- Einstellungen ---------- */

function mw_axp_defaults() {
    return array(
        'provider'    => 'anthropic',
        'api_key'     => '',
        'model'       => '',                        // leer = Standardmodell des Anbieters (gibt es nur bei Anthropic)
        'base_url'    => '',                        // nur bei "custom"
        'types'       => array('post' => 'auto'),   // Post-Type => Sprache (auto|de|en); vorhanden = Tool dafür verfügbar
        'default_on'  => 0,                         // Artikel ohne gesetzte Freigabe: 0 = gesperrt, 1 = freigegeben
        'rate_max'    => 10,                        // Suchen pro IP und Stunde (Cache-Treffer zählen nicht)
        'daily_cap'   => 200,                       // neue KI-Suchen pro Tag insgesamt (0 = unbegrenzt)
        'max_results' => 6,
        'cf'          => 0,                         // 1 = CF-Connecting-IP vertrauen (Seite liegt hinter Cloudflare)
        'credit'      => 1                          // Hinweis "Idee: meiersworld.de" in der Box anzeigen
    );
}

function mw_axp_opts() {
    $o = get_option('mw_axp_options', array());
    return array_merge(mw_axp_defaults(), is_array($o) ? $o : array());
}

function mw_axp_opt($key) {
    $o = mw_axp_opts();
    return isset($o[$key]) ? $o[$key] : null;
}

function mw_axp_providers() {
    return array(
        'anthropic'  => array('label' => 'Anthropic (Claude)', 'type' => 'anthropic', 'base' => '', 'model' => 'claude-haiku-4-5-20251001'),
        'openai'     => array('label' => 'OpenAI',             'type' => 'openai', 'base' => 'https://api.openai.com/v1', 'model' => ''),
        'mistral'    => array('label' => 'Mistral AI',         'type' => 'openai', 'base' => 'https://api.mistral.ai/v1', 'model' => ''),
        'xai'        => array('label' => 'xAI (Grok)',         'type' => 'openai', 'base' => 'https://api.x.ai/v1', 'model' => ''),
        'gemini'     => array('label' => 'Google Gemini',      'type' => 'openai', 'base' => 'https://generativelanguage.googleapis.com/v1beta/openai', 'model' => ''),
        'openrouter' => array('label' => 'OpenRouter',         'type' => 'openai', 'base' => 'https://openrouter.ai/api/v1', 'model' => ''),
        'custom'     => array('label' => mw_axp_x('Eigener Anbieter (OpenAI-kompatibel)', 'Custom provider (OpenAI-compatible)'), 'type' => 'openai', 'base' => '', 'model' => '')
    );
}

function mw_axp_provider() {
    $slug = (string) mw_axp_opt('provider');
    $all  = mw_axp_providers();
    return isset($all[$slug]) ? $slug : 'anthropic';
}

function mw_axp_valid_base_url($u) {
    $u = trim((string) $u);
    if ($u === '') { return ''; }
    $parts = parse_url($u);
    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) { return ''; }
    $local = in_array($parts['host'], array('localhost', '127.0.0.1', '[::1]'), true);
    if ($parts['scheme'] === 'https' || ($parts['scheme'] === 'http' && $local)) { return rtrim($u, '/'); }
    return '';
}

function mw_axp_base_url() {
    $all  = mw_axp_providers();
    $slug = mw_axp_provider();
    $base = ($slug === 'custom') ? mw_axp_valid_base_url(mw_axp_opt('base_url')) : $all[$slug]['base'];
    return apply_filters('mw_axp_base_url', $base, $slug);
}

function mw_axp_model() {
    $m = trim((string) mw_axp_opt('model'));
    if ($m === '') {
        $all = mw_axp_providers();
        $m   = $all[mw_axp_provider()]['model'];
    }
    return $m;
}

function mw_axp_api_key() {
    $slug = mw_axp_provider();
    $k    = (string) mw_axp_opt('api_key');
    if ($k === '' && defined('MW_AXP_API_KEY') && MW_AXP_API_KEY) { $k = (string) MW_AXP_API_KEY; }
    if ($k === '' && $slug === 'anthropic' && defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY) { $k = (string) ANTHROPIC_API_KEY; }
    return apply_filters('mw_axp_api_key', $k, $slug);
}

// Vollständig konfiguriert? (Key, Modell und bei OpenAI-kompatiblen Anbietern die Basis-URL)
function mw_axp_ready() {
    $all = mw_axp_providers();
    if (!mw_axp_api_key() || mw_axp_model() === '') { return false; }
    if ($all[mw_axp_provider()]['type'] === 'openai' && mw_axp_base_url() === '') { return false; }
    return true;
}

// Zweisprachige Admin-Texte: Deutsch bei deutscher Oberfläche, sonst Englisch.
function mw_axp_x($de, $en) {
    $loc = function_exists('determine_locale') ? determine_locale() : get_locale();
    return (strpos($loc, 'de') === 0) ? $de : $en;
}

function mw_axp_post_types() {
    $types = mw_axp_opt('types');
    $list  = is_array($types) ? array_keys($types) : array();
    return apply_filters('mw_axp_post_types', $list);
}

function mw_axp_enabled_for_post($post_id) {
    $post_id = (int) $post_id;
    if ($post_id <= 0) { return false; }
    if (!in_array(get_post_type($post_id), mw_axp_post_types(), true)) { return false; }
    $flag = get_post_meta($post_id, 'mw_axp_enabled', true);              // '1' = freigegeben, '0' = gesperrt, '' = Standard
    $on   = ($flag === '') ? (bool) mw_axp_opt('default_on') : ($flag === '1');
    return (bool) apply_filters('mw_axp_enabled', $on, $post_id);
}

function mw_axp_lang_for_post($post_id) {
    $pt    = get_post_type($post_id);
    $types = mw_axp_opt('types');
    $lang  = (is_array($types) && isset($types[$pt])) ? $types[$pt] : 'auto';
    if ($lang === 'auto' && function_exists('pll_get_post_language')) {
        $pl = pll_get_post_language($post_id, 'slug');
        if ($pl) { $lang = $pl; }
    }
    if ($lang === 'auto') { $lang = get_locale(); }
    $lang = (strpos((string) $lang, 'de') === 0) ? 'de' : 'en';
    return apply_filters('mw_axp_lang', $lang, $post_id);
}

function mw_axp_texts($lang) {
    if ($lang === 'en') {
        return array(
            'title'   => 'Find related research papers',
            'intro'   => 'Search alphaXiv for scientific papers that match this article.',
            'button'  => 'Find papers',
            'loading' => 'Searching papers …',
            'queries' => 'Search terms used:',
            'none'    => 'No matching papers found for this article.',
            'error'   => 'The search failed. Please try again later.',
            'limit'   => 'Too many requests. Please try again later.',
            'read'    => 'Read on alphaXiv',
            'source'  => 'Search via arXiv, reading on alphaXiv.',
            'idea'    => 'Idea:'
        );
    }
    return array(
        'title'   => 'Passende Forschungs-Papers finden',
        'intro'   => 'Such auf alphaXiv nach wissenschaftlichen Papers, die zu diesem Artikel passen.',
        'button'  => 'Papers finden',
        'loading' => 'Suche läuft …',
        'queries' => 'Verwendete Suchbegriffe:',
        'none'    => 'Zu diesem Artikel wurden keine passenden Papers gefunden.',
        'error'   => 'Die Suche ist fehlgeschlagen. Bitte versuch es später noch einmal.',
        'limit'   => 'Zu viele Anfragen. Bitte versuch es später noch einmal.',
        'read'    => 'Auf alphaXiv lesen',
        'source'  => 'Suche über arXiv, Lesen auf alphaXiv.',
        'idea'    => 'Idee:'
    );
}

/* ---------- Backend: Einstellungsseite ---------- */

function mw_axp_register_settings() {
    register_setting('mw_axp_group', 'mw_axp_options', array('sanitize_callback' => 'mw_axp_sanitize'));
}
add_action('admin_init', 'mw_axp_register_settings');

function mw_axp_add_settings_page() {
    add_options_page('alphaXiv Papers', 'alphaXiv Papers', 'manage_options', 'mw-axp', 'mw_axp_settings_page');
}
add_action('admin_menu', 'mw_axp_add_settings_page');

function mw_axp_action_links($links) {
    array_unshift($links, '<a href="' . esc_url(admin_url('options-general.php?page=mw-axp')) . '">' . esc_html(mw_axp_x('Einstellungen', 'Settings')) . '</a>');
    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(MW_AXP_FILE), 'mw_axp_action_links');

function mw_axp_sanitize($in) {
    $old = mw_axp_opts();
    $in  = is_array($in) ? $in : array();
    $out = array();

    $providers = mw_axp_providers();
    $prov      = (isset($in['provider']) && is_string($in['provider']) && isset($providers[$in['provider']])) ? $in['provider'] : 'anthropic';
    $changed   = ($prov !== $old['provider']);
    $out['provider'] = $prov;

    $key = isset($in['api_key']) ? preg_replace('/[^A-Za-z0-9_\-\.~]/', '', trim((string) $in['api_key'])) : '';
    if (!empty($in['clear_key'])) { $out['api_key'] = ''; }
    elseif ($key !== '')          { $out['api_key'] = $key; }
    elseif ($changed)             { $out['api_key'] = ''; }   // Key eines anderen Anbieters nicht mitnehmen
    else                          { $out['api_key'] = $old['api_key']; }

    $out['model']    = isset($in['model']) ? preg_replace('/[^A-Za-z0-9._\-\/:]/', '', (string) $in['model']) : '';
    $out['base_url'] = isset($in['base_url']) ? mw_axp_valid_base_url(esc_url_raw(trim((string) $in['base_url']))) : '';

    $types = array();
    $valid = array('auto', 'de', 'en');
    if (!empty($in['types']) && is_array($in['types'])) {
        foreach ($in['types'] as $pt => $row) {
            $pt = sanitize_key($pt);
            if ($pt === '' || !post_type_exists($pt) || empty($row['on'])) { continue; }
            $types[$pt] = (isset($row['lang']) && in_array($row['lang'], $valid, true)) ? $row['lang'] : 'auto';
        }
    }
    $out['types'] = $types;

    $out['default_on']  = empty($in['default_on']) ? 0 : 1;
    $out['cf']          = empty($in['cf']) ? 0 : 1;
    $out['credit']      = empty($in['credit']) ? 0 : 1;
    $out['rate_max']    = max(1, min(100, (int) (isset($in['rate_max']) ? $in['rate_max'] : 10)));
    $out['daily_cap']   = max(0, min(10000, (int) (isset($in['daily_cap']) ? $in['daily_cap'] : 200)));
    $out['max_results'] = max(1, min(10, (int) (isset($in['max_results']) ? $in['max_results'] : 6)));
    return $out;
}

function mw_axp_settings_page() {
    if (!current_user_can('manage_options')) { return; }
    $o    = mw_axp_opts();
    $n    = 'mw_axp_options';
    $have = (string) $o['api_key'];
    $const = (defined('ANTHROPIC_API_KEY') && ANTHROPIC_API_KEY);

    echo '<div class="wrap"><h1>alphaXiv Papers</h1>';
    if (!mw_axp_ready()) {
        echo '<div class="notice notice-warning"><p>' . esc_html(mw_axp_x('Die KI-Anbindung ist noch nicht vollständig (API-Key, Modell, bei eigenem Anbieter die Basis-URL). Ohne sie funktioniert die Suche nicht.', 'The AI connection is not complete yet (API key, model, and the base URL for a custom provider). The search does not work without it.')) . '</p></div>';
    }
    echo '<form method="post" action="options.php">';
    settings_fields('mw_axp_group');
    echo '<table class="form-table" role="presentation">';

    // KI-Anbieter
    echo '<tr><th scope="row"><label for="mw-axp-prov">' . esc_html(mw_axp_x('KI-Anbieter', 'AI provider')) . '</label></th><td>';
    echo '<select id="mw-axp-prov" name="' . esc_attr($n) . '[provider]">';
    foreach (mw_axp_providers() as $slug => $pv) {
        echo '<option value="' . esc_attr($slug) . '"' . selected(mw_axp_provider(), $slug, false) . '>' . esc_html($pv['label']) . '</option>';
    }
    echo '</select>';
    echo '<p class="description">' . esc_html(mw_axp_x('Nach einem Wechsel bitte API-Key und Modell des neuen Anbieters eintragen und speichern.', 'After switching, enter the new provider\'s API key and model and save.')) . '</p>';
    echo '</td></tr>';

    // Basis-URL (nur Custom)
    echo '<tr><th scope="row"><label for="mw-axp-base">' . esc_html(mw_axp_x('Basis-URL', 'Base URL')) . '</label></th><td>';
    echo '<input type="url" id="mw-axp-base" class="regular-text" name="' . esc_attr($n) . '[base_url]" value="' . esc_attr($o['base_url']) . '" placeholder="https://api.example.com/v1">';
    echo '<p class="description">' . esc_html(mw_axp_x('Nur bei "Eigener Anbieter": Adresse bis einschließlich /v1 (OpenAI-kompatibel), z. B. https://api.groq.com/openai/v1. Nur https; http ist nur für localhost erlaubt.', 'Only for "Custom provider": address up to and including /v1 (OpenAI-compatible), e.g. https://api.groq.com/openai/v1. https only; http is allowed for localhost only.')) . '</p></td></tr>';

    // API-Key
    $env_key = (defined('MW_AXP_API_KEY') && MW_AXP_API_KEY) || (mw_axp_provider() === 'anthropic' && $const);
    echo '<tr><th scope="row"><label for="mw-axp-key">API-Key</label></th><td>';
    echo '<input type="password" id="mw-axp-key" class="regular-text" name="' . esc_attr($n) . '[api_key]" value="" autocomplete="new-password" placeholder="sk-…">';
    if ($have !== '') {
        echo '<p class="description">' . esc_html(mw_axp_x('Gespeichert (endet auf ', 'Saved (ends in ')) . esc_html(substr($have, -4)) . '). ' . esc_html(mw_axp_x('Leer lassen, um ihn zu behalten.', 'Leave empty to keep it.')) . '</p>';
        echo '<label><input type="checkbox" name="' . esc_attr($n) . '[clear_key]" value="1"> ' . esc_html(mw_axp_x('Gespeicherten Key löschen', 'Delete saved key')) . '</label>';
    } elseif ($env_key) {
        echo '<p class="description">' . esc_html(mw_axp_x('Es wird ein Key aus der wp-config.php verwendet (MW_AXP_API_KEY bzw. bei Anthropic ANTHROPIC_API_KEY).', 'A key from wp-config.php is used (MW_AXP_API_KEY, or ANTHROPIC_API_KEY for Anthropic).')) . '</p>';
    }
    echo '<p class="description">' . esc_html(mw_axp_x('Den Key erstellst du im Konto deines Anbieters. Er wird nur serverseitig genutzt und in der Datenbank gespeichert; sicherer ist eine Konstante in der wp-config.php.', 'Create the key in your provider account. It is only used server-side and stored in the database; a constant in wp-config.php is safer.')) . '</p>';
    echo '</td></tr>';

    // Modell
    $all_p = mw_axp_providers();
    $ph    = $all_p[mw_axp_provider()]['model'];
    echo '<tr><th scope="row"><label for="mw-axp-model">' . esc_html(mw_axp_x('Modell', 'Model')) . '</label></th><td>';
    echo '<input type="text" id="mw-axp-model" class="regular-text" name="' . esc_attr($n) . '[model]" value="' . esc_attr($o['model']) . '" placeholder="' . esc_attr($ph) . '">';
    echo '<p class="description">' . esc_html($ph !== '' ? mw_axp_x('Leer lassen für den Standard (' . $ph . '). Ein kleines, günstiges Modell reicht.', 'Leave empty for the default (' . $ph . '). A small, cheap model is enough.') : mw_axp_x('Pflichtfeld: Modellname laut Modellliste deines Anbieters. Ein kleines, günstiges Modell reicht.', 'Required: model name from your provider\'s model list. A small, cheap model is enough.')) . '</p></td></tr>';

    // Post-Types
    echo '<tr><th scope="row">' . esc_html(mw_axp_x('Inhaltstypen', 'Content types')) . '</th><td>';
    $pts = get_post_types(array('public' => true), 'objects');
    foreach ($pts as $slug => $obj) {
        if ($slug === 'attachment') { continue; }
        $on   = isset($o['types'][$slug]);
        $lang = $on ? $o['types'][$slug] : 'auto';
        echo '<p><label><input type="checkbox" name="' . esc_attr($n) . '[types][' . esc_attr($slug) . '][on]" value="1"' . checked($on, true, false) . '> ' . esc_html($obj->labels->singular_name) . ' <code>' . esc_html($slug) . '</code></label> ';
        echo '<select name="' . esc_attr($n) . '[types][' . esc_attr($slug) . '][lang]">';
        foreach (array('auto' => mw_axp_x('Sprache automatisch', 'Language: automatic'), 'de' => 'Deutsch', 'en' => 'English') as $v => $label) {
            echo '<option value="' . esc_attr($v) . '"' . selected($lang, $v, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></p>';
    }
    echo '<p class="description">' . esc_html(mw_axp_x('Für diese Typen erscheint im Editor die Freigabe-Checkbox. "Automatisch" nutzt die Websprache (bei Polylang die Artikelsprache).', 'These types get the release checkbox in the editor. "Automatic" uses the site language (the post language with Polylang).')) . '</p>';
    echo '</td></tr>';

    // Standard
    echo '<tr><th scope="row">' . esc_html(mw_axp_x('Standard', 'Default')) . '</th><td>';
    echo '<label><input type="checkbox" name="' . esc_attr($n) . '[default_on]" value="1"' . checked((int) $o['default_on'], 1, false) . '> ' . esc_html(mw_axp_x('Artikel ohne gesetzte Freigabe automatisch freigeben', 'Release articles automatically unless they are blocked')) . '</label>';
    echo '<p class="description">' . esc_html(mw_axp_x('Empfohlen: aus. Die Suche ergibt nicht bei jedem Artikel Sinn, deshalb gibst du sie pro Artikel frei.', 'Recommended: off. The search does not make sense for every article, so you release it per article.')) . '</p></td></tr>';

    // Limits
    echo '<tr><th scope="row">' . esc_html(mw_axp_x('Limits', 'Limits')) . '</th><td>';
    echo '<p><label>' . esc_html(mw_axp_x('Suchen pro IP und Stunde', 'Searches per IP and hour')) . ' <input type="number" min="1" max="100" name="' . esc_attr($n) . '[rate_max]" value="' . (int) $o['rate_max'] . '" class="small-text"></label></p>';
    echo '<p><label>' . esc_html(mw_axp_x('Neue KI-Suchen pro Tag insgesamt (0 = unbegrenzt)', 'New AI searches per day in total (0 = unlimited)')) . ' <input type="number" min="0" max="10000" name="' . esc_attr($n) . '[daily_cap]" value="' . (int) $o['daily_cap'] . '" class="small-text"></label></p>';
    echo '<p><label>' . esc_html(mw_axp_x('Papers pro Suche', 'Papers per search')) . ' <input type="number" min="1" max="10" name="' . esc_attr($n) . '[max_results]" value="' . (int) $o['max_results'] . '" class="small-text"></label></p>';
    echo '<p class="description">' . esc_html(mw_axp_x('Ergebnisse werden 7 Tage pro Artikel zwischengespeichert; Cache-Treffer kosten nichts.', 'Results are cached for 7 days per article; cache hits cost nothing.')) . '</p></td></tr>';

    // Cloudflare
    echo '<tr><th scope="row">Cloudflare</th><td>';
    echo '<label><input type="checkbox" name="' . esc_attr($n) . '[cf]" value="1"' . checked((int) $o['cf'], 1, false) . '> ' . esc_html(mw_axp_x('Meine Seite liegt hinter Cloudflare (Besucher-IP aus CF-Connecting-IP lesen)', 'My site is behind Cloudflare (read the visitor IP from CF-Connecting-IP)')) . '</label></td></tr>';

    // Credit
    echo '<tr><th scope="row">Credit</th><td>';
    echo '<label><input type="checkbox" name="' . esc_attr($n) . '[credit]" value="1"' . checked((int) $o['credit'], 1, false) . '> ' . esc_html(mw_axp_x('Kleinen Hinweis "Idee: meiersworld.de" in der Box anzeigen', 'Show a small "Idea: meiersworld.de" note in the box')) . '</label></td></tr>';

    echo '</table>';
    submit_button();
    echo '</form>';
    mw_axp_render_test();
    echo '<hr><p>alphaXiv Papers ' . esc_html(MW_AXP_VERSION) . ' &middot; ' . esc_html(mw_axp_x('Idee und Entwicklung:', 'Idea and development:')) . ' <a href="https://www.meiersworld.de" target="_blank" rel="noopener">meiersworld.de</a></p>';
    echo '</div>';
}

/* ---------- Backend: Freigabe pro Artikel ---------- */

function mw_axp_add_metabox() {
    foreach (mw_axp_post_types() as $pt) {
        add_meta_box('mw_axp_box', 'alphaXiv Papers', 'mw_axp_render_metabox', $pt, 'side', 'default');
    }
}
add_action('add_meta_boxes', 'mw_axp_add_metabox');

function mw_axp_render_metabox($post) {
    wp_nonce_field('mw_axp_save', 'mw_axp_nonce');
    $checked = mw_axp_enabled_for_post($post->ID);
    echo '<label><input type="checkbox" name="mw_axp_enabled" value="1"' . checked($checked, true, false) . '> ' . esc_html(mw_axp_x('Papers-Suche freigeben', 'Enable papers search')) . '</label>';
    echo '<p class="description">' . esc_html(mw_axp_x('Nur aktivieren, wenn der Artikel zu wissenschaftlichen Papers passt (KI, Technik, Forschung). Ohne Haken erscheint unter dem Artikel keine Suche.', 'Only enable this if the article fits scientific papers (AI, tech, research). Without the checkmark no search appears below the article.')) . '</p>';
}

function mw_axp_save_metabox($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
    if (!isset($_POST['mw_axp_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['mw_axp_nonce'])), 'mw_axp_save')) { return; }
    if (!current_user_can('edit_post', $post_id)) { return; }
    if (!in_array(get_post_type($post_id), mw_axp_post_types(), true)) { return; }
    update_post_meta($post_id, 'mw_axp_enabled', empty($_POST['mw_axp_enabled']) ? '0' : '1');
}
add_action('save_post', 'mw_axp_save_metabox');

function mw_axp_list_column($columns) {
    $columns['mw_axp'] = 'Papers';
    return $columns;
}

function mw_axp_list_column_content($column, $post_id) {
    if ($column !== 'mw_axp') { return; }
    echo mw_axp_enabled_for_post($post_id) ? '<span title="' . esc_attr(mw_axp_x('Papers-Suche freigegeben', 'Papers search enabled')) . '">&#10003;</span>' : '<span style="opacity:.4">&ndash;</span>';
}

function mw_axp_register_columns() {
    foreach (mw_axp_post_types() as $pt) {
        add_filter('manage_' . $pt . '_posts_columns', 'mw_axp_list_column');
        add_action('manage_' . $pt . '_posts_custom_column', 'mw_axp_list_column_content', 10, 2);
    }
}
add_action('admin_init', 'mw_axp_register_columns');

/* ---------- Frontend: Box und Script ---------- */

// Der Inhalt bleibt bewusst leer (nur ein Container). Die Texte setzt das Script im Footer,
// damit Autolink-/Glossar-Plugins, die the_content verändern, nichts zerstören.
function mw_axp_append_box($content) {
    if (!is_singular() || !in_the_loop() || !is_main_query()) { return $content; }
    $post_id = get_the_ID();
    if (!mw_axp_enabled_for_post($post_id)) { return $content; }
    if (post_password_required($post_id)) { return $content; }
    return $content . '<div class="mw-axp" id="mw-axp" data-post="' . (int) $post_id . '"></div>';
}
add_filter('the_content', 'mw_axp_append_box', 20);

function mw_axp_footer_script() {
    if (!is_singular()) { return; }
    $post_id = get_queried_object_id();
    if (!mw_axp_enabled_for_post($post_id) || post_password_required($post_id)) { return; }

    $cfg = array(
        'ajax'   => admin_url('admin-ajax.php'),
        'post'   => (int) $post_id,
        'credit' => (bool) mw_axp_opt('credit'),
        't'      => mw_axp_texts(mw_axp_lang_for_post($post_id))
    );
    echo '<style>
.mw-axp{border:1px solid rgba(128,128,128,.35);border-radius:8px;padding:1rem 1.25rem;margin:2rem 0;font-size:.95em}
.mw-axp h3{margin:0 0 .4rem}
.mw-axp p{margin:.3rem 0}
.mw-axp button{font:inherit;cursor:pointer;padding:.5rem 1rem;border-radius:6px;border:1px solid currentColor;background:transparent;color:inherit}
.mw-axp button[disabled]{opacity:.6;cursor:wait}
.mw-axp ul{list-style:none;margin:1rem 0 0;padding:0}
.mw-axp li{padding:.75rem 0;border-top:1px solid rgba(128,128,128,.25)}
.mw-axp .mw-axp-meta{opacity:.7;font-size:.85em}
.mw-axp .mw-axp-small{opacity:.7;font-size:.8em;margin-top:.8rem}
</style>' . "\n";
    echo '<script>var MW_AXP=' . wp_json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP) . ';' . "\n";
    echo <<<'JS'
(function () {
  var box = document.getElementById('mw-axp');
  if (!box || !window.MW_AXP) { return; }
  var T = MW_AXP.t;

  function el(tag, cls, txt) {
    var e = document.createElement(tag);
    if (cls) { e.className = cls; }
    if (txt !== undefined) { e.textContent = txt; }
    return e;
  }

  var head = el('h3', '', T.title);
  var intro = el('p', '', T.intro);
  var btn = el('button', '', T.button);
  btn.type = 'button';
  var out = el('div', '');
  box.appendChild(head);
  box.appendChild(intro);
  box.appendChild(btn);
  box.appendChild(out);

  function link(href, txt) {
    var a = el('a', '', txt);
    a.href = href;
    a.target = '_blank';
    a.rel = 'noopener nofollow';
    return a;
  }

  if (MW_AXP.credit) {
    var cr = el('p', 'mw-axp-small', T.idea + ' ');
    var ca = el('a', '', 'meiersworld.de');
    ca.href = 'https://www.meiersworld.de';
    ca.target = '_blank';
    ca.rel = 'noopener';
    cr.appendChild(ca);
    box.appendChild(cr);
  }

  function render(data) {
    out.innerHTML = '';
    if (data.queries && data.queries.length) {
      out.appendChild(el('p', 'mw-axp-small', T.queries + ' ' + data.queries.join(' · ')));
    }
    if (!data.papers || !data.papers.length) {
      out.appendChild(el('p', '', T.none));
      return;
    }
    var ul = el('ul', '');
    data.papers.forEach(function (p) {
      var li = el('li', '');
      var t = el('strong', '');
      t.appendChild(link(p.alphaxiv, p.title));
      li.appendChild(t);
      li.appendChild(el('div', 'mw-axp-meta', p.authors + (p.date ? ' · ' + p.date : '')));
      if (p.abstract) { li.appendChild(el('p', '', p.abstract)); }
      var row = el('div', 'mw-axp-meta');
      row.appendChild(link(p.alphaxiv, T.read));
      row.appendChild(document.createTextNode(' · '));
      row.appendChild(link(p.arxiv, 'arXiv'));
      li.appendChild(row);
      ul.appendChild(li);
    });
    out.appendChild(ul);
    out.appendChild(el('p', 'mw-axp-small', T.source));
  }

  function fail(msg) {
    out.innerHTML = '';
    out.appendChild(el('p', '', msg));
  }

  btn.addEventListener('click', function () {
    btn.disabled = true;
    out.innerHTML = '';
    out.appendChild(el('p', '', T.loading));
    var body = new URLSearchParams();
    body.append('action', 'mw_axp_search');
    body.append('post_id', MW_AXP.post);
    fetch(MW_AXP.ajax + '?nocache=' + Date.now(), { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btn.disabled = false;
        if (j && j.success) { render(j.data); return; }
        fail(j && j.data && j.data.code === 'limit' ? T.limit : T.error);
      })
      .catch(function () { btn.disabled = false; fail(T.error); });
  });
})();
</script>

JS;
}
add_action('wp_footer', 'mw_axp_footer_script', 50);

/* ---------- AJAX ---------- */

add_action('wp_ajax_mw_axp_search', 'mw_axp_ajax');
add_action('wp_ajax_nopriv_mw_axp_search', 'mw_axp_ajax');

function mw_axp_respond_error($code) {
    if (ob_get_length()) { ob_end_clean(); }
    wp_send_json_error(array('code' => $code));
}

function mw_axp_client_ip() {
    $ip = '';
    if (mw_axp_opt('cf') && !empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return apply_filters('mw_axp_client_ip', sanitize_text_field(wp_unslash($ip)));
}

function mw_axp_rate_ok() {
    $key = 'mw_axp_rl_' . md5(mw_axp_client_ip());
    $n   = (int) get_transient($key);
    if ($n >= (int) mw_axp_opt('rate_max')) { return false; }
    set_transient($key, $n + 1, HOUR_IN_SECONDS);
    return true;
}

function mw_axp_budget_ok() {
    $cap = (int) mw_axp_opt('daily_cap');
    if ($cap <= 0) { return true; }
    $key = 'mw_axp_day_' . gmdate('Ymd');
    $n   = (int) get_transient($key);
    if ($n >= $cap) { return false; }
    set_transient($key, $n + 1, DAY_IN_SECONDS);
    return true;
}

function mw_axp_ajax() {
    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $post    = $post_id ? get_post($post_id) : null;
    if (!$post || $post->post_status !== 'publish' || !mw_axp_enabled_for_post($post_id) || post_password_required($post)) {
        mw_axp_respond_error('invalid');
    }

    $cache_key = 'mw_axp_' . $post_id . '_' . md5($post->post_modified_gmt);
    $cached    = get_transient($cache_key);
    if (is_array($cached)) {
        if (ob_get_length()) { ob_end_clean(); }
        wp_send_json_success($cached);
    }

    if (!mw_axp_ready()) { mw_axp_respond_error('config'); }
    if (!mw_axp_rate_ok() || !mw_axp_budget_ok()) { mw_axp_respond_error('limit'); }

    $plan = mw_axp_get_queries($post);
    if (!is_array($plan)) { mw_axp_respond_error('llm'); }

    $result = array('queries' => array(), 'papers' => array());
    if (!empty($plan['relevant']) && !empty($plan['queries'])) {
        $papers = mw_axp_arxiv_search($plan['queries']);
        if (!is_array($papers)) { mw_axp_respond_error('arxiv'); }
        $result = array('queries' => $plan['queries'], 'papers' => $papers);
    }

    set_transient($cache_key, $result, MW_AXP_CACHE_TTL);
    if (ob_get_length()) { ob_end_clean(); }
    wp_send_json_success($result);
}

/* ---------- KI-Anbieter ---------- */

// Eine Anfrage an den gewählten Anbieter. Rückgabe: array('text' => ..., 'error' => '' | Fehlertext)
function mw_axp_llm($system, $user, $timeout = 12) {
    $all  = mw_axp_providers();
    $type = $all[mw_axp_provider()]['type'];
    $key  = mw_axp_api_key();
    $model = mw_axp_model();
    if (!$key || $model === '') { return array('text' => '', 'error' => 'config'); }

    if ($type === 'anthropic') {
        $url     = 'https://api.anthropic.com/v1/messages';
        $headers = array('x-api-key' => $key, 'anthropic-version' => '2023-06-01', 'content-type' => 'application/json');
        $body    = array('model' => $model, 'max_tokens' => 300, 'system' => $system, 'messages' => array(array('role' => 'user', 'content' => $user)));
    } else {
        $base = mw_axp_base_url();
        if ($base === '') { return array('text' => '', 'error' => 'config'); }
        $url     = rtrim($base, '/') . '/chat/completions';
        $headers = array('Authorization' => 'Bearer ' . $key, 'content-type' => 'application/json');
        $body    = array('model' => $model, 'messages' => array(array('role' => 'system', 'content' => $system), array('role' => 'user', 'content' => $user)));
    }

    $resp = wp_remote_post($url, array('timeout' => $timeout, 'headers' => $headers, 'body' => wp_json_encode($body)));
    if (is_wp_error($resp)) { return array('text' => '', 'error' => $resp->get_error_message()); }

    $code = (int) wp_remote_retrieve_response_code($resp);
    $data = json_decode(wp_remote_retrieve_body($resp), true);
    if ($code !== 200) {
        $msg = '';
        if (is_array($data) && isset($data['error']['message'])) { $msg = (string) $data['error']['message']; }
        elseif (is_array($data) && isset($data['error']) && is_string($data['error'])) { $msg = $data['error']; }
        $msg = function_exists('mb_substr') ? mb_substr($msg, 0, 200) : substr($msg, 0, 200);
        return array('text' => '', 'error' => 'HTTP ' . $code . ($msg !== '' ? ': ' . $msg : ''));
    }

    $text = '';
    if ($type === 'anthropic') {
        if (!empty($data['content']) && is_array($data['content'])) {
            foreach ($data['content'] as $block) {
                if (isset($block['type']) && $block['type'] === 'text') { $text .= $block['text']; }
            }
        }
    } elseif (isset($data['choices'][0]['message']['content'])) {
        $c = $data['choices'][0]['message']['content'];
        if (is_string($c)) { $text = $c; }
        elseif (is_array($c)) {
            foreach ($c as $part) {
                if (is_array($part) && isset($part['text'])) { $text .= $part['text']; }
            }
        }
    }
    return array('text' => $text, 'error' => ($text === '') ? 'empty response' : '');
}

function mw_axp_test_ajax() {
    check_ajax_referer('mw_axp_test', 'nonce');
    if (!current_user_can('manage_options')) { wp_send_json_error(array('message' => 'forbidden')); }
    if (!mw_axp_ready()) { wp_send_json_error(array('message' => mw_axp_x('Die KI-Anbindung ist noch nicht vollständig. Bitte zuerst speichern.', 'The AI connection is not complete yet. Please save first.'))); }
    $r = mw_axp_llm('Reply with the single word OK.', 'ping', 15);
    if ($r['error'] !== '') { wp_send_json_error(array('message' => $r['error'])); }
    wp_send_json_success(array('message' => mw_axp_x('Verbindung erfolgreich.', 'Connection successful.')));
}
add_action('wp_ajax_mw_axp_test', 'mw_axp_test_ajax');

function mw_axp_render_test() {
    $cfg = array('ajax' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('mw_axp_test'), 'busy' => mw_axp_x('Teste …', 'Testing …'));
    echo '<p><button type="button" class="button" id="mw-axp-test">' . esc_html(mw_axp_x('Verbindung testen (gespeicherte Einstellungen)', 'Test connection (saved settings)')) . '</button> <span id="mw-axp-test-out"></span></p>';
    echo '<script>var MW_AXP_TEST=' . wp_json_encode($cfg, JSON_HEX_TAG | JSON_HEX_AMP) . ';' . "\n";
    echo <<<'JS'
(function () {
  var b = document.getElementById('mw-axp-test'), o = document.getElementById('mw-axp-test-out');
  if (!b || !o) { return; }
  b.addEventListener('click', function () {
    o.textContent = MW_AXP_TEST.busy;
    b.disabled = true;
    var body = new URLSearchParams();
    body.append('action', 'mw_axp_test');
    body.append('nonce', MW_AXP_TEST.nonce);
    fetch(MW_AXP_TEST.ajax + '?nocache=' + Date.now(), { method: 'POST', body: body, credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (j) { b.disabled = false; o.textContent = (j && j.data && j.data.message) ? j.data.message : 'Error'; })
      .catch(function () { b.disabled = false; o.textContent = 'Error'; });
  });
})();
</script>

JS;
}

/* ---------- Suchbegriffe per KI ---------- */

function mw_axp_get_queries($post) {
    $text = wp_strip_all_tags(strip_shortcodes($post->post_content));
    $text = trim(preg_replace('/\s+/u', ' ', $text));
    $text = function_exists('mb_substr') ? mb_substr($text, 0, 6000) : substr($text, 0, 6000);

    $system = 'You select arXiv search phrases for a blog article. Answer with JSON only, no code fences: '
        . '{"relevant":true|false,"queries":["phrase 1","phrase 2","phrase 3"]}. '
        . 'Set relevant=true only if the article deals with a topic that scientific papers on arXiv could cover '
        . '(AI, machine learning, computer science, physics, math, statistics, quantitative research). '
        . 'Otherwise relevant=false and queries=[]. '
        . 'Queries: 2 to 3 English phrases of 2 to 5 words each, specific to the core topic, no quotation marks, no operators.';

    $user = "Title: " . $post->post_title . "\n\nText:\n" . $text;

    $r = mw_axp_llm($system, $user, 12);
    if ($r['error'] !== '') { return false; }

    $raw  = trim(preg_replace('/^```(?:json)?|```$/m', '', $r['text']));
    $json = json_decode($raw, true);
    if (!is_array($json) && preg_match('/\{.*\}/s', $raw, $m)) { $json = json_decode($m[0], true); }
    if (!is_array($json)) { return false; }

    $queries = array();
    if (!empty($json['queries']) && is_array($json['queries'])) {
        foreach ($json['queries'] as $q) {
            $q = trim(preg_replace('/["\s]+/u', ' ', (string) $q));
            if ($q !== '') { $queries[] = $q; }
        }
    }
    return array('relevant' => !empty($json['relevant']), 'queries' => array_slice($queries, 0, 3));
}

/* ---------- arXiv-Suche (Links zeigen auf alphaXiv) ---------- */

function mw_axp_arxiv_search($queries) {
    $parts = array();
    foreach ($queries as $q) { $parts[] = 'all:"' . $q . '"'; }
    $search = implode(' OR ', $parts);

    $url = 'https://export.arxiv.org/api/query?search_query=' . rawurlencode($search)
         . '&start=0&max_results=' . (int) mw_axp_opt('max_results') . '&sortBy=relevance&sortOrder=descending';

    $resp = wp_remote_get($url, array('timeout' => 10, 'headers' => array('User-Agent' => 'alphaxiv-papers/' . MW_AXP_VERSION . ' (+https://www.meiersworld.de)')));
    if (is_wp_error($resp) || (int) wp_remote_retrieve_response_code($resp) !== 200) { return false; }

    $prev = libxml_use_internal_errors(true);
    $xml  = simplexml_load_string(wp_remote_retrieve_body($resp));
    libxml_clear_errors();
    libxml_use_internal_errors($prev);
    if (!$xml) { return false; }

    $papers = array();
    foreach ($xml->entry as $e) {
        $id_url = (string) $e->id;
        if (!preg_match('#/abs/(.+)$#', $id_url, $m)) { continue; }
        $arxiv_id = preg_replace('/v\d+$/', '', trim($m[1]));

        $names = array();
        foreach ($e->author as $a) { $names[] = (string) $a->name; }
        $authors = implode(', ', array_slice($names, 0, 3)) . (count($names) > 3 ? ' et al.' : '');

        $abstract = trim(preg_replace('/\s+/u', ' ', (string) $e->summary));
        if (function_exists('mb_strlen') && mb_strlen($abstract) > 260) { $abstract = rtrim(mb_substr($abstract, 0, 260)) . ' …'; }

        $papers[] = array(
            'title'    => trim(preg_replace('/\s+/u', ' ', (string) $e->title)),
            'authors'  => $authors,
            'date'     => substr((string) $e->published, 0, 4),
            'abstract' => $abstract,
            'alphaxiv' => 'https://www.alphaxiv.org/abs/' . $arxiv_id,
            'arxiv'    => 'https://arxiv.org/abs/' . $arxiv_id
        );
    }
    return $papers;
}
