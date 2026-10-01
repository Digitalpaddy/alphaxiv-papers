<?php
/**
 * Plugin Name: alphaXiv Papers
 * Plugin URI:  https://www.meiersworld.de
 * Description: Adds a "Find related research papers" box to your articles. Search terms by the AI provider of your choice (Claude, OpenAI, Mistral, Grok, Gemini or any OpenAI-compatible service), papers from arXiv, links to alphaXiv. Release per article in the editor. DE + EN. / Fügt Artikeln eine Papers-Suche hinzu, Freigabe pro Artikel im Editor.
 * Version:     1.3
 * Letzte Änderung: 01.10.2026
 * Änderung:    Wählbarer KI-Anbieter (Anthropic, OpenAI, Mistral, xAI, Gemini, OpenRouter, eigener OpenAI-kompatibler Dienst), Verbindungstest; Kern-Datei in 1.3.
 * Author:      meiersworld.de
 * Author URI:  https://www.meiersworld.de
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: alphaxiv-papers
 * Requires at least: 5.8
 * Requires PHP: 7.0
 *
 * Idee und Entwicklung: meiersworld.de – https://www.meiersworld.de
 * Copyright (C) 2026 meiersworld.de
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the
 * GNU General Public License as published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version. Please keep this credit notice intact.
 */

if (!defined('ABSPATH')) { exit; }

// Schutz: Läuft schon die mu-plugin-Variante von meiersworld.de (sie definiert MW_AXP_VERSION),
// wird der Kern nicht geladen. So gibt es keinen Fatal Error durch doppelte Funktionen.
if (defined('MW_AXP_VERSION')) {
    add_action('admin_notices', 'mw_axp_conflict_notice');
    function mw_axp_conflict_notice() {
        echo '<div class="notice notice-warning"><p><strong>alphaXiv Papers:</strong> Another copy of this tool is already active (e.g. in mu-plugins). / Eine andere Kopie dieses Tools ist bereits aktiv (z. B. in mu-plugins). Bitte nur eine Variante nutzen.</p></div>';
    }
    return;
}

define('MW_AXP_FILE', __FILE__);
require_once dirname(__FILE__) . '/alphaxiv-papers-core.php';
