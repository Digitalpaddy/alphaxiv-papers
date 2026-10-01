<?php
/**
 * alphaXiv Papers – Aufräumen bei der Deinstallation
 * Version: 1.2 (01.10.2026)
 * Änderung: Erstversion – löscht Einstellungen und zwischengespeicherte Suchergebnisse.
 *
 * Idee und Entwicklung: meiersworld.de – https://www.meiersworld.de
 * Die Freigabe-Markierungen an den Artikeln (Meta-Feld mw_axp_enabled) bleiben bewusst erhalten.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) { exit; }

delete_option('mw_axp_options');

global $wpdb;
$like_a = $wpdb->esc_like('_transient_mw_axp_') . '%';
$like_b = $wpdb->esc_like('_transient_timeout_mw_axp_') . '%';
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $like_a, $like_b));
