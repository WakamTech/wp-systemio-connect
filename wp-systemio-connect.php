<?php
/**
 * Plugin Name:       WP Systeme.io Connect
 * Plugin URI:        https://github.com/WakamTech/wp-systemio-connect  
 * Description:       Connecte les formulaires WordPress à Systeme.io pour une gestion facile des emails.
 * Version:           0.1.0
 * Author:            BO-VO Digital
 * Author URI:        https://bovo-digital.tech/ 
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-systemio-connect
 * Domain Path:       /languages
 */

// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}




// Définir des constantes utiles pour le plugin
define('WPSIO_CONNECT_VERSION', '0.1.0');
define('WPSIO_CONNECT_PATH', plugin_dir_path(__FILE__)); // Chemin système vers le dossier du plugin (avec / final)
define('WPSIO_CONNECT_URL', plugin_dir_url(__FILE__));   // URL vers le dossier du plugin (avec / final)
define('WPSIO_CONNECT_PLUGIN_BASENAME', plugin_basename(__FILE__)); // Utile pour les liens internes admin

// Inclure la classe principale pour l'admin (nous allons la créer juste après)
require_once WPSIO_CONNECT_PATH . 'admin/class-wp-systemio-connect-admin.php';

// Inclure le fichier dans wp-systemio-connect.php
require_once WPSIO_CONNECT_PATH . 'includes/class-wp-systemio-connect-api-service.php'; // <-- AJOUTER

// Inclure la classe pour l'intégration Contact Form 7
require_once WPSIO_CONNECT_PATH . 'includes/integrations/class-wp-systemio-connect-cf7.php'; // <-- AJOUTER CECI

// Inclure la classe pour l'intégration Elementor Pro
require_once WPSIO_CONNECT_PATH . 'includes/integrations/class-wp-systemio-connect-elementor.php'; // <-- AJOUTER

// Inclure et initialiser Divi
require_once WPSIO_CONNECT_PATH . 'includes/integrations/class-wp-systemio-connect-divi.php'; // <-- AJOUTER

// Initialiser les fonctionnalités d'administration
WP_Systemio_Connect_Admin::init();

/**
 * Fonction d'initialisation principale du plugin, accrochée à plugins_loaded.
 * C'est ici qu'on initialise les intégrations qui dépendent d'autres plugins.
 */
function wp_systemio_connect_initialize_integrations()
{
    // Initialiser l'intégration CF7 (on pourrait aussi la mettre ici pour la cohérence)
    // Si on vérifie WPCF7_VERSION dans son init(), ça ne pose pas de problème de le laisser ici.
    WP_Systemio_Connect_CF7::init();

    // Initialiser l'intégration Elementor SEULEMENT APRÈS que les plugins sont chargés
    WP_Systemio_Connect_Elementor::init();


}

// Accrocher notre fonction d'initialisation au bon moment
add_action('plugins_loaded', 'wp_systemio_connect_initialize_integrations');

// Ajouter une fonction séparée pour Divi sur 'init'
function wp_systemio_connect_initialize_divi()
{
    // init() vérifiera la classe ET_Builder_Module
    WP_Systemio_Connect_Divi::init();
}
add_action('after_setup_theme', 'wp_systemio_connect_initialize_divi');


// Dans wp-systemio-connect.php

/**
 * Charge le text domain du plugin pour la traduction.
 */
function wp_systemio_connect_load_textdomain()
{
    load_plugin_textdomain(
        'wp-systemio-connect',                 // Le Text Domain
        false,                                 // Deprecated argument
        dirname(plugin_basename(__FILE__)) . '/languages/' // Chemin relatif vers le dossier /languages/
    );
}
// Accrocher au bon moment (après le chargement des plugins)
add_action('plugins_loaded', 'wp_systemio_connect_load_textdomain');

// S'assurer que le dossier /languages/ existe bien à la racine du plugin
// (On l'a créé à l'étape 0)
?>