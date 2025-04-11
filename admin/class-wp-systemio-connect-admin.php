<?php
// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WP_Systemio_Connect_Admin
{

    private static $options; // Pour stocker les options récupérées

    /**
     * Initialise les hooks admin.
     */
    public static function init()
    {
        // Récupérer les options une fois
        self::$options = get_option('wp_systemio_connect_options');

        // Ajouter la page d'options au menu Réglages
        // add_action('admin_menu', [__CLASS__, 'add_options_page']);

        // Dans WP_Systemio_Connect_Admin::init() ou une méthode dédiée à l'admin_menu
        add_action('admin_menu', [__CLASS__, 'register_main_menu_page']);

        // Enregistrer les réglages, sections et champs
        add_action('admin_init', [__CLASS__, 'register_settings']);

        // Ajouter un lien "Réglages" sur la page des plugins (optionnel mais pratique)
        add_filter('plugin_action_links_' . WPSIO_CONNECT_PLUGIN_BASENAME, [__CLASS__, 'add_settings_link']);


    }

    /**
     * Ajoute le lien "Réglages" sur la page des plugins.
     */
    public static function add_settings_link($links)
    {
        // Pointer vers la nouvelle page principale (l'onglet par défaut sera chargé)
        $settings_link = '<a href="' . admin_url('admin.php?page=wp-systemio-connect') . '">' . __('Réglages', 'wp-systemio-connect') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    /**
     * Ajoute la page d'options dans le menu "Réglages" de WordPress.
     */
    public static function add_options_page()
    {
        add_options_page(
            __('WP Systeme.io Connect', 'wp-systemio-connect'), // Titre de la page (<title>)
            __('Systeme.io Connect', 'wp-systemio-connect'), // Titre dans le menu
            'manage_options', // Capacité requise pour voir la page
            'wp-systemio-connect', // Slug de la page (unique)
            [__CLASS__, 'render_options_page'] // Fonction qui affiche le contenu de la page
        );
    }

    // Nouvelle méthode
    public static function register_main_menu_page()
    {
        add_menu_page(
            __('Systeme.io Connect - Réglages', 'wp-systemio-connect'), // Titre de la page (balise <title>)
            __('WP SIO Connect', 'wp-systemio-connect'),                  // Titre dans le menu
            'manage_options',                                             // Capacité requise
            'wp-systemio-connect',                                        // Slug de la page principale (identifiant unique)
            [__CLASS__, 'render_admin_page_wrapper'],                   // Fonction callback qui gérera l'affichage des onglets
            'dashicons-email-alt',                                        // Icône (choisir dans les Dashicons)
            75 // Position approximative dans le menu (plus bas = plus bas)
        );
    }
    /**
     * Enregistre les paramètres du plugin via l'API Settings de WordPress.
     */
    public static function register_settings()
    {
        // --- Groupe et Champs pour l'Onglet Settings ---
        register_setting(
            'wp_systemio_connect_settings_group', // Nouveau groupe pour Settings
            'wp_systemio_connect_options',        // On garde le même nom d'option globale pour l'instant
            [__CLASS__, 'sanitize_options']     // La fonction sanitize devra gérer les différents groupes potentiels
        );

        add_settings_section(
            'wp_systemio_connect_section_api',
            __('Connexion API Systeme.io', 'wp-systemio-connect'),
            [__CLASS__, 'render_section_api_description'],
            'wp_systemio_connect_settings_page' // Nouvelle page "virtuelle" pour ce groupe
        );

        add_settings_field(
            'api_key',
            __('Clé API Systeme.io', 'wp-systemio-connect'),
            [__CLASS__, 'render_field_api_key'],
            'wp_systemio_connect_settings_page', // Page "virtuelle"
            'wp_systemio_connect_section_api',
            ['label_for' => 'wp_systemio_connect_api_key']
        );

        add_settings_field(
            'api_base_url',
            __('URL API Systeme.io', 'wp-systemio-connect'),
            [__CLASS__, 'render_field_api_base_url'],
            'wp_systemio_connect_settings_page', // Page "virtuelle"
            'wp_systemio_connect_section_api',
            ['label_for' => 'wp_systemio_connect_api_base_url']
        );

        // --- NOUVEAU : Groupe et Champs pour l'Onglet Formulaires ---
        register_setting(
            'wp_systemio_connect_forms_group', // Nouveau groupe dédié aux formulaires
            'wp_systemio_connect_options',     // Toujours la même option globale
            [__CLASS__, 'sanitize_options']  // La même fonction sanitize gérera tout
        );

        // --- Section et Champ pour CF7 ---
        if (defined('WPCF7_VERSION')) {
            add_settings_section(
                'wp_systemio_connect_section_cf7',
                __('Intégration Contact Form 7', 'wp-systemio-connect'),
                [__CLASS__, 'render_section_cf7_description'],
                'wp_systemio_connect_forms_page' // Nouvelle page "virtuelle" pour ce groupe
            );
            add_settings_field(
                'cf7_form_settings',
                __('Configuration des Formulaires CF7', 'wp-systemio-connect'),
                [__CLASS__, 'render_field_cf7_form_settings'],
                'wp_systemio_connect_forms_page', // Page "virtuelle"
                'wp_systemio_connect_section_cf7'
            );
        }

        // --- Section et Champ pour Elementor ---
        if (class_exists('\ElementorPro\Modules\Forms\Classes\Form_Record')) {
            add_settings_section(
                'wp_systemio_connect_section_elementor',
                __('Intégration Elementor Pro Forms', 'wp-systemio-connect'),
                [__CLASS__, 'render_section_elementor_description'],
                'wp_systemio_connect_forms_page' // Page "virtuelle"
            );
            add_settings_field(
                'elementor_form_settings',
                __('Configuration des Formulaires Elementor', 'wp-systemio-connect'),
                [__CLASS__, 'render_field_elementor_form_settings'],
                'wp_systemio_connect_forms_page', // Page "virtuelle"
                'wp_systemio_connect_section_elementor'
            );
        }

        // --- Section et Champ pour Divi ---
        if (function_exists('et_builder_add_main_elements') || defined('ET_BUILDER_THEME')) {
            add_settings_section(
                'wp_systemio_connect_section_divi',
                __('Intégration Divi (Formulaire de Contact)', 'wp-systemio-connect'),
                [__CLASS__, 'render_section_divi_description'],
                'wp_systemio_connect_forms_page' // Page "virtuelle"
            );
            add_settings_field(
                'divi_form_settings',
                __('Configuration des Formulaires Divi', 'wp-systemio-connect'),
                [__CLASS__, 'render_field_divi_form_settings'],
                'wp_systemio_connect_forms_page', // Page "virtuelle"
                'wp_systemio_connect_section_divi'
            );
        }
    }

    // Dans WP_Systemio_Connect_Admin
    public static function render_admin_page_wrapper()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Récupérer l'onglet actif, défaut sur 'forms'
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'forms';

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('WP Systeme.io Connect', 'wp-systemio-connect'); ?></h1>

            <?php
            // --- AFFICHAGE DES ERREURS/SUCCÈS DU TRANSIENT (Test Connexion) ---
            $transient_errors = get_transient('settings_errors');
            if (!empty($transient_errors)) {
                echo '<div id="setting-error-settings_updated" class="notice notice-alt inline">'; // Utiliser des classes WP pour le style
                foreach ($transient_errors as $error) {
                    // Déterminer la classe CSS en fonction du type d'erreur
                    $css_class = 'notice-info'; // Défaut
                    if (isset($error['type'])) {
                        switch ($error['type']) {
                            case 'error':
                                $css_class = 'notice-error';
                                break;
                            case 'success':
                                $css_class = 'notice-success';
                                break;
                            case 'warning':
                                $css_class = 'notice-warning';
                                break;
                        }
                    }
                    // Afficher le message
                    if (isset($error['message'])) {
                        echo '<div class="notice ' . esc_attr($css_class) . ' is-dismissible"><p><strong>' . wp_kses_post($error['message']) . '</strong></p></div>';
                    }
                }
                echo '</div>';
                // Supprimer le transient pour qu'il ne s'affiche qu'une fois
                delete_transient('settings_errors');
            }
            // --- FIN AFFICHAGE TRANSIENT ---
    
            // Afficher aussi les erreurs standard de l'API Settings (après sauvegarde via options.php)
            // On doit choisir un slug. Celui de l'option principale est souvent utilisé.
            settings_errors('wp_systemio_connect_options'); // Affichera les erreurs ajoutées dans sanitize_options
            // Ou, si on veut séparer par groupe : settings_errors('wp_systemio_connect_settings_group'); mais add_settings_error doit utiliser ce slug aussi.
            ?>
            <nav class="nav-tab-wrapper wp-clearfix"
                aria-label="<?php esc_attr_e('Onglets secondaires', 'wp-systemio-connect'); ?>">
                <a href="?page=wp-systemio-connect&tab=forms"
                    class="nav-tab <?php echo $active_tab === 'forms' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Formulaires', 'wp-systemio-connect'); ?>
                </a>
                <a href="?page=wp-systemio-connect&tab=contacts"
                    class="nav-tab <?php echo $active_tab === 'contacts' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Contacts', 'wp-systemio-connect'); ?>
                </a>
                <a href="?page=wp-systemio-connect&tab=tags"
                    class="nav-tab <?php echo $active_tab === 'tags' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Tags', 'wp-systemio-connect'); ?>
                </a>
                <a href="?page=wp-systemio-connect&tab=settings"
                    class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Réglages API', 'wp-systemio-connect'); ?>
                </a>
                <!-- Ajouter futurs onglets ici -->
            </nav>

            <div class="tab-content" style="margin-top: 20px;">
                <?php
                // Appeler la fonction de rendu pour l'onglet actif
                switch ($active_tab) {
                    case 'contacts':
                        self::render_contacts_tab();
                        break;
                    case 'tags':
                        self::render_tags_tab();
                        break;
                    case 'settings':
                        self::render_settings_tab();
                        break;
                    case 'forms':
                    default:
                        self::render_forms_tab();
                        break;
                }
                ?>
            </div><!-- .tab-content -->

        </div><!-- .wrap -->
        <?php
    }

    // Créer des méthodes vides (ou avec placeholder) pour chaque onglet pour l'instant
    public static function render_forms_tab()
    {
        echo '<h2>' . esc_html__('Configuration des Intégrations de Formulaires', 'wp-systemio-connect') . '</h2>';
        echo '<p>' . esc_html__('Activez et configurez les formulaires que vous souhaitez connecter à Systeme.io.', 'wp-systemio-connect') . '</p>';

        // Vérifier si l'API est configurée avant d'afficher les options de formulaire? Optionnel mais peut être utile.
        if (!self::get_api_key()) {
            echo '<div class="notice notice-warning inline"><p>';
            printf(
                __('Veuillez d\'abord <a href="%s">configurer votre clé API Systeme.io</a> dans l\'onglet Réglages API pour activer ces options.', 'wp-systemio-connect'),
                esc_url(admin_url('admin.php?page=wp-systemio-connect&tab=settings'))
            );
            echo '</p></div>';
            // On pourrait return ici pour ne rien afficher d'autre, ou juste griser les options.
            // Pour l'instant, on affiche quand même pour voir la structure.
        }

        ?>
        <form action="options.php" method="post">
            <?php
            // Sécurité et champs cachés pour ce groupe spécifique
            settings_fields('wp_systemio_connect_forms_group'); // Utiliser le groupe des formulaires
    
            // Afficher les sections et champs enregistrés pour 'wp_systemio_connect_forms_page'
            // L'API Settings affichera les sections CF7, Elementor, Divi si les conditions (if defined/class_exists) sont remplies
            do_settings_sections('wp_systemio_connect_forms_page');

            // Bouton de sauvegarde pour cet onglet
            submit_button(__('Enregistrer les Réglages des Formulaires', 'wp-systemio-connect'));
            ?>
        </form>
        <?php
    }

    /**
     * Affiche le contenu de l'onglet Contacts : soit la liste, soit le formulaire d'ajout/modification.
     */
    public static function render_contacts_tab()
    {
        // Déterminer l'action demandée (lister par défaut)
        $action = isset($_GET['action']) ? sanitize_key($_GET['action']) : 'list';

        // --- Affichage du Titre et du Bouton Ajouter ---
        echo '<h2>';
        esc_html_e('Contacts Systeme.io', 'wp-systemio-connect');

        // Afficher le bouton "Ajouter" seulement si on est sur la vue liste
        if ($action === 'list') {
            printf(
                ' <a href="%s" class="page-title-action">%s</a>',
                esc_url(admin_url('admin.php?page=wp-systemio-connect&tab=contacts&action=add_new')),
                esc_html__('Ajouter un contact', 'wp-systemio-connect')
            );
        }
        echo '</h2>';

        // --- Afficher les Messages de Statut (Succès/Erreur) ---
        if (isset($_GET['message'])) {
            $message_code = sanitize_key($_GET['message']);
            $message_text = '';
            $message_type = 'info'; // success, error, warning, info

            switch ($message_code) {
                case 'contact_added_tags_ok':
                    $message_text = __('Contact ajouté avec succès à Systeme.io (tags inclus).', 'wp-systemio-connect');
                    $message_type = 'success';
                    break;
                case 'contact_added_tags_error':
                    $message_text = __('Contact ajouté avec succès, mais une erreur est survenue lors de l\'assignation d\'un ou plusieurs tags. Vérifiez les logs.', 'wp-systemio-connect');
                    $message_type = 'warning';
                    break;
                case 'contact_updated': // Pour le futur
                    $message_text = __('Contact mis à jour avec succès.', 'wp-systemio-connect');
                    $message_type = 'success';
                    break;
                case 'contact_deleted': // Pour le futur
                    $message_text = __('Contact supprimé avec succès.', 'wp-systemio-connect');
                    $message_type = 'success';
                    break;
                case 'add_error_email':
                    $message_text = __('Erreur : L\'adresse email fournie était invalide ou manquante.', 'wp-systemio-connect');
                    $message_type = 'error';
                    break;
                case 'add_error_api':
                    $message_text = __('Erreur : Impossible d\'ajouter ou mettre à jour le contact via l\'API Systeme.io. Vérifiez les logs pour plus de détails.', 'wp-systemio-connect');
                    $message_type = 'error';
                    break;
                case 'add_error_internal':
                    $message_text = __('Une erreur interne s\'est produite. Impossible de traiter la demande.', 'wp-systemio-connect');
                    $message_type = 'error';
                    break;
                // Ajouter d'autres cas futurs ici
            }

            if ($message_text) {
                echo '<div id="message" class="notice notice-' . esc_attr($message_type) . ' is-dismissible"><p>' . esc_html($message_text) . '</p></div>';
            }
        }

        // --- Vérification de la Clé API (essentiel pour toute action) ---
        if (!self::get_api_key()) {
            echo '<div class="notice notice-error"><p>';
            printf(
                __('<strong>Action requise :</strong> Veuillez <a href="%s">configurer votre clé API Systeme.io</a> dans l\'onglet Réglages API pour accéder aux contacts.', 'wp-systemio-connect'),
                esc_url(admin_url('admin.php?page=wp-systemio-connect&tab=settings'))
            );
            echo '</p></div>';
            return; // Ne rien afficher d'autre si la clé manque
        }

        // --- Affichage Conditionnel : Formulaire ou Liste ---
        if ($action === 'add_new' /* || $action === 'edit_contact' */) { // Décommenter pour l'édition future
            // Afficher le formulaire d'ajout/modification
            self::render_contact_form( /* $action */); // Passer l'action si render_contact_form gère l'édition
        } else {
            // Afficher la liste des contacts par défaut
            echo '<p>' . esc_html__('Liste des contacts récupérés depuis votre compte Systeme.io.', 'wp-systemio-connect') . '</p>';

            // Inclure, Instancier et Afficher la WP_List_Table
            require_once WPSIO_CONNECT_PATH . 'admin/class-wp-systemio-contacts-list-table.php';
            $api_service = new WP_Systemio_Connect_Api_Service();
            if (!$api_service || !method_exists($api_service, 'get_contacts')) {
                echo '<div class="notice notice-error"><p>' . esc_html__('Erreur interne: Service API non disponible.', 'wp-systemio-connect') . '</p></div>';
                return;
            }
            $list_table = new WP_Systemio_Contacts_List_Table($api_service);
            $list_table->prepare_items(); // Fait l'appel API et gère les erreurs API internes

            // Ajouter un formulaire autour pour la pagination et les actions futures (bulk actions, filtres...)
            ?>
            <form method="get">
                <?php // Champs cachés importants pour WP_List_Table (pagination, tri) et notre navigation ?>
                <input type="hidden" name="page" value="<?php echo esc_attr($_REQUEST['page'] ?? 'wp-systemio-connect'); ?>" />
                <input type="hidden" name="tab" value="contacts" />
                <?php // Ajouter d'autres champs cachés si on ajoute des filtres ou du tri ?>

                <?php
                // Afficher la table (gère aussi "aucun item trouvé")
                $list_table->display();
                ?>
            </form>
            <?php
        } // Fin du else (affichage liste)

    } // Fin render_contacts_tab
    public static function render_tags_tab()
    {
        echo '<h2>' . esc_html__('Gestion des Tags Systeme.io', 'wp-systemio-connect') . '</h2>';
        echo '<p>' . esc_html__('La liste des tags SIO sera affichée ici.', 'wp-systemio-connect') . '</p>';
        // Implémentation future
    }

    public static function render_settings_tab()
    {
        ?>
        <form action="options.php" method="post">
            <?php
            // Sécurité et champs cachés pour ce groupe spécifique
            settings_fields('wp_systemio_connect_settings_group'); // Utiliser le nom du groupe défini dans register_setting
    
            // Afficher les sections et champs enregistrés pour 'wp_systemio_connect_settings_page'
            do_settings_sections('wp_systemio_connect_settings_page');

            // Bouton de sauvegarde pour cet onglet
            submit_button(__('Enregistrer les Réglages API', 'wp-systemio-connect'));
            ?>
        </form>

        <?php // Section pour le bouton de test (reste comme avant) ?>
        <hr>
        <h2><?php _e('Tester la Connexion API', 'wp-systemio-connect'); ?></h2>
        <p><?php _e('Cliquez sur le bouton ci-dessous pour vérifier si la clé API enregistrée fonctionne.', 'wp-systemio-connect'); ?>
        </p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="wp_systemio_connect_test_connection">
            <?php wp_nonce_field('wp_systemio_connect_test_connection_nonce', 'wp_systemio_connect_nonce'); ?>
            <?php submit_button(__('Tester la Connexion', 'wp-systemio-connect'), 'secondary', 'submit_test_connection', false); ?>
        </form>
        <?php
    }

    /**
     * Affiche la description de la section API (optionnel).
     */
    public static function render_section_api_description()
    {
        echo '<p>' . __('Entrez votre clé API Systeme.io pour connecter votre site.', 'wp-systemio-connect') . '</p>';
        // On pourrait ajouter un lien vers la doc SIO pour trouver la clé API
    }

    /**
     * Affiche le champ input pour la Clé API.
     */
    public static function render_field_api_key()
    {
        $api_key = isset(self::$options['api_key']) ? self::$options['api_key'] : '';
        ?>
        <input type='password' id='wp_systemio_connect_api_key' name='wp_systemio_connect_options[api_key]'
            value='<?php echo esc_attr($api_key); ?>' class='regular-text'
            placeholder='<?php esc_attr_e('Votre clé API secrète', 'wp-systemio-connect'); ?>'>
        <p class="description">
            <?php printf(
                __('Vous pouvez trouver votre clé API dans vos <a href="%s" target="_blank">paramètres Systeme.io</a> (section Clé API publique).', 'wp-systemio-connect'),
                'https://systeme.io/dashboard/settings/api-keys' // Mettre le lien exact si possible
            ); ?>
        </p>
        <?php
    }

    /**
     * Affiche le champ input pour l'URL de base de l'API.
     */
    public static function render_field_api_base_url()
    {
        // Utiliser une valeur par défaut si non définie
        $default_url = 'https://api.systeme.io/api';
        $api_base_url = isset(self::$options['api_base_url']) && !empty(self::$options['api_base_url']) ? self::$options['api_base_url'] : $default_url;
        ?>
        <input type='url' id='wp_systemio_connect_api_base_url' name='wp_systemio_connect_options[api_base_url]'
            value='<?php echo esc_attr($api_base_url); ?>' class='regular-text'
            placeholder='<?php echo esc_attr($default_url); ?>'>
        <p class="description">
            <?php _e('Laissez la valeur par défaut sauf si vous savez ce que vous faites.', 'wp-systemio-connect'); ?>
        </p>
        <?php
    }

    /**
     * Affiche le contenu HTML de la page d'options.
     */
    public static function render_options_page()
    {
        if (!current_user_can('manage_options')) {
            return; // Sécurité : vérifier à nouveau les droits
        }

        // Afficher les erreurs/messages de succès enregistrés (ex: après sauvegarde ou test de connexion)
        settings_errors('wp_systemio_connect_options'); // Utilise le nom de l'option comme slug pour les messages

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <form action="options.php" method="post">
                <?php
                // Output les champs cachés nécessaires (nonce, action, option_page)
                settings_fields('wp_systemio_connect_options_group');

                // Affiche les sections et champs enregistrés pour cette page ('wp-systemio-connect')
                do_settings_sections('wp-systemio-connect');

                // Affiche le bouton de sauvegarde
                submit_button(__('Enregistrer les modifications', 'wp-systemio-connect'));
                ?>
            </form>

            <?php // Section pour le bouton de test (hors du formulaire principal) ?>
            <hr>
            <h2><?php _e('Tester la Connexion API', 'wp-systemio-connect'); ?></h2>
            <p><?php _e('Cliquez sur le bouton ci-dessous pour vérifier si la clé API enregistrée fonctionne.', 'wp-systemio-connect'); ?>
            </p>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="wp_systemio_connect_test_connection">
                <?php wp_nonce_field('wp_systemio_connect_test_connection_nonce', 'wp_systemio_connect_nonce'); ?>
                <?php submit_button(__('Tester la Connexion', 'wp-systemio-connect'), 'secondary', 'submit_test_connection', false); ?>
                <?php // Le 'false' à la fin empêche le <p> autour du bouton si on veut le styler différemment ?>
            </form>

        </div><!-- .wrap -->
        <?php
    }

    /**
     * Gère l'action du bouton "Tester la Connexion".
     * Doit être hooké via admin_post_{action}.
     */
    public static function handle_test_connection()
    {
        // 1. Vérifier le nonce pour la sécurité CSRF
        if (!isset($_POST['wp_systemio_connect_nonce']) || !wp_verify_nonce($_POST['wp_systemio_connect_nonce'], 'wp_systemio_connect_test_connection_nonce')) {
            wp_die(__('Échec de la vérification de sécurité.', 'wp-systemio-connect'));
        }

        // 2. Vérifier les droits utilisateur
        if (!current_user_can('manage_options')) {
            wp_die(__('Vous n\'avez pas les permissions suffisantes pour effectuer cette action.', 'wp-systemio-connect'));
        }

        // 3. Récupérer la clé API et l'URL SAUVEGARDÉES
        $options = get_option('wp_systemio_connect_options');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        $api_base_url = isset($options['api_base_url']) && !empty($options['api_base_url']) ? $options['api_base_url'] : 'https://api.systeme.io/api';


        if (empty($api_key)) {
            add_settings_error(
                'wp_systemio_connect_options', // Slug pour les messages
                'api_key_missing',             // ID de l'erreur
                __('Erreur : La clé API n\'est pas configurée. Veuillez l\'enregistrer avant de tester.', 'wp-systemio-connect'),
                'error'                        // Type de message ('error', 'success', 'warning', 'info')
            );
        } else {
            // 4. Essayer de faire un appel API simple (ex: récupérer les tags)
            $endpoint = $api_base_url . '/tags'; // Endpoint pour lister les tags
            $args = [
                'headers' => [
                    'X-API-Key' => $api_key,
                    'Accept' => 'application/json', // Important pour SIO V2
                    'Content-Type' => 'application/json', // Souvent nécessaire
                ],
                'timeout' => 15, // Augmenter le timeout si nécessaire
            ];

            $response = wp_remote_get($endpoint, $args);

            // 5. Analyser la réponse
            if (is_wp_error($response)) {
                // Erreur WordPress (connexion impossible, timeout, etc.)
                add_settings_error(
                    'wp_systemio_connect_options',
                    'api_wp_error',
                    __('Erreur WordPress lors de la connexion à l\'API : ', 'wp-systemio-connect') . $response->get_error_message(),
                    'error'
                );
            } else {
                $response_code = wp_remote_retrieve_response_code($response);
                $response_body = wp_remote_retrieve_body($response);
                $data = json_decode($response_body, true); // Tenter de décoder le JSON

                if ($response_code >= 200 && $response_code < 300) {
                    // Succès !
                    add_settings_error(
                        'wp_systemio_connect_options',
                        'api_success',
                        sprintf(
                            __('Succès ! Connexion à l\'API Systeme.io établie. (Code: %d)', 'wp-systemio-connect'),
                            $response_code
                        ),
                        'success' // Message de succès
                    );
                    // Optionnel : afficher le nombre de tags trouvés
                    if (isset($data['items']) && is_array($data['items'])) {
                        add_settings_error(
                            'wp_systemio_connect_options',
                            'api_success_tags',
                            sprintf(__('%d tags trouvés.', 'wp-systemio-connect'), count($data['items'])),
                            'info' // Juste une information
                        );
                    }

                } else {
                    // Erreur de l'API Systeme.io (clé invalide, etc.)
                    $error_message = __('Erreur inconnue de l\'API.', 'wp-systemio-connect');
                    if ($data && isset($data['message'])) {
                        $error_message = $data['message'];
                    } elseif ($data && isset($data['error']) && isset($data['error']['message'])) {
                        $error_message = $data['error']['message'];
                    } elseif (!empty($response_body)) {
                        $error_message = wp_strip_all_tags($response_body); // Afficher le corps si pas de message JSON clair
                    }

                    add_settings_error(
                        'wp_systemio_connect_options',
                        'api_remote_error',
                        sprintf(
                            __('Erreur de l\'API Systeme.io (Code: %d) : %s', 'wp-systemio-connect'),
                            $response_code,
                            esc_html($error_message) // Sécuriser le message d'erreur
                        ),
                        'error'
                    );
                }
            }
        }

        // 6. Enregistrer les messages pour affichage sur la page de redirection
        set_transient('settings_errors', get_settings_errors(), 30); // Stocke les erreurs temporairement

        // 7. Rediriger vers la page de réglages
        $redirect_url = admin_url('admin.php?page=wp-systemio-connect&tab=settings');
        wp_redirect(add_query_arg('settings-updated', 'false', $redirect_url)); // 'false' pour ne pas afficher "Réglages enregistrés."
        exit;
    }

    // Helper pour récupérer une option spécifique (on pourrait le mettre ailleurs aussi)
    public static function get_option($key, $default = null)
    {
        // Assurer que les options sont chargées
        if (is_null(self::$options)) {
            self::$options = get_option('wp_systemio_connect_options');
        }

        return isset(self::$options[$key]) ? self::$options[$key] : $default;
    }

    // Helper pour récupérer la clé API
    public static function get_api_key()
    {
        return self::get_option('api_key', '');
    }

    // Helper pour récupérer l'URL de base de l'API
    public static function get_api_base_url()
    {
        return self::get_option('api_base_url', 'https://api.systeme.io/api');
    }

    /**
     * Récupère la liste des formulaires Contact Form 7.
     * @return array Tableau d'objets WP_Post représentant les formulaires CF7, ou tableau vide.
     */
    public static function get_cf7_forms()
    {
        if (!defined('WPCF7_VERSION')) {
            return []; // CF7 n'est pas actif
        }

        $forms = get_posts([
            'post_type' => 'wpcf7_contact_form',
            'post_status' => 'publish',
            'numberposts' => -1, // Récupérer tous les formulaires
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        return $forms;
    }

    /**
     * Affiche la description de la section d'intégration CF7.
     */
    public static function render_section_cf7_description()
    {
        echo '<p>' . __('Configurez ici comment vos formulaires Contact Form 7 envoient des données à Systeme.io.', 'wp-systemio-connect') . '</p>';
    }

    /**
     * Affiche les options de configuration pour chaque formulaire CF7 détecté.
     */
    public static function render_field_cf7_form_settings()
    {
        $cf7_forms = self::get_cf7_forms();

        if (empty($cf7_forms)) {
            echo '<p>' . __('Aucun formulaire Contact Form 7 trouvé ou le plugin n\'est pas actif.', 'wp-systemio-connect') . '</p>';
            return;
        }

        // Récupérer les options globales pour avoir les réglages CF7 sauvegardés
        // On accède via self::$options qui est chargé dans init()
        $cf7_settings = isset(self::$options['cf7_integrations']) ? self::$options['cf7_integrations'] : [];

        echo '<div class="wp-sio-cf7-forms-container">';

        foreach ($cf7_forms as $form) {
            $form_id = $form->ID;
            $form_title = $form->post_title;
            // Récupérer les réglages spécifiques à ce formulaire
            $settings = isset($cf7_settings[$form_id]) ? $cf7_settings[$form_id] : [];

            // Valeurs par défaut pour les champs de ce formulaire
            $enabled = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;
            $email_field = isset($settings['email_field']) ? $settings['email_field'] : 'your-email'; // Suggestion par défaut
            $fname_field = isset($settings['fname_field']) ? $settings['fname_field'] : 'your-name'; // Suggestion par défaut
            $lname_field = isset($settings['lname_field']) ? $settings['lname_field'] : ''; // Optionnel
            // $tags = isset($settings['tags']) ? $settings['tags'] : ''; // IDs de tags SIO, séparés par virgule
            $selected_tags = isset($settings['tags']) && is_array($settings['tags']) ? $settings['tags'] : []; // NOUVEAU: attend un tableau d'IDs
            ?>
            <div class="wp-sio-cf7-form-config"
                style="border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 15px; background: #fff;">
                <h4><?php echo esc_html($form_title); ?> (ID: <?php echo esc_html($form_id); ?>)</h4>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="wp_sio_cf7_<?php echo $form_id; ?>_enabled">
                                    <?php _e('Activer pour Systeme.io', 'wp-systemio-connect'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="hidden"
                                    name="wp_systemio_connect_options[cf7_integrations][<?php echo $form_id; ?>][enabled]"
                                    value="0">
                                <input type="checkbox" id="wp_sio_cf7_<?php echo $form_id; ?>_enabled"
                                    name="wp_systemio_connect_options[cf7_integrations][<?php echo $form_id; ?>][enabled]" value="1"
                                    <?php checked($enabled, 1); ?>>
                                <p class="description">
                                    <?php _e('Cochez pour envoyer les données de ce formulaire vers Systeme.io.', 'wp-systemio-connect'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr class="wp-sio-cf7-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                            <th scope="row">
                                <label for="wp_sio_cf7_<?php echo $form_id; ?>_email_field">
                                    <?php _e('Champ Email (*)', 'wp-systemio-connect'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" id="wp_sio_cf7_<?php echo $form_id; ?>_email_field"
                                    name="wp_systemio_connect_options[cf7_integrations][<?php echo $form_id; ?>][email_field]"
                                    value="<?php echo esc_attr($email_field); ?>" class="regular-text" placeholder="your-email"
                                    required>
                                <p class="description">
                                    <?php _e('Nom du champ CF7 contenant l\'adresse email (ex: your-email).', 'wp-systemio-connect'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr class="wp-sio-cf7-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                            <th scope="row">
                                <label for="wp_sio_cf7_<?php echo $form_id; ?>_fname_field">
                                    <?php _e('Champ Prénom', 'wp-systemio-connect'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" id="wp_sio_cf7_<?php echo $form_id; ?>_fname_field"
                                    name="wp_systemio_connect_options[cf7_integrations][<?php echo $form_id; ?>][fname_field]"
                                    value="<?php echo esc_attr($fname_field); ?>" class="regular-text" placeholder="your-name">
                                <p class="description">
                                    <?php _e('Nom du champ CF7 pour le prénom (ex: your-name, first-name). Laisser vide si non utilisé.', 'wp-systemio-connect'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr class="wp-sio-cf7-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                            <th scope="row">
                                <label for="wp_sio_cf7_<?php echo $form_id; ?>_lname_field">
                                    <?php _e('Champ Nom', 'wp-systemio-connect'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" id="wp_sio_cf7_<?php echo $form_id; ?>_lname_field"
                                    name="wp_systemio_connect_options[cf7_integrations][<?php echo $form_id; ?>][lname_field]"
                                    value="<?php echo esc_attr($lname_field); ?>" class="regular-text" placeholder="last-name">
                                <p class="description">
                                    <?php _e('Nom du champ CF7 pour le nom de famille (ex: last-name). Laisser vide si non utilisé.', 'wp-systemio-connect'); ?>
                                </p>
                            </td>
                        </tr>
                        <?php /* --- NOUVEAU CHAMP TAGS --- */ ?>
                        <tr class="wp-sio-cf7-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                            <th scope="row">
                                <?php _e('Tags Systeme.io', 'wp-systemio-connect'); ?>
                            </th>
                            <td>
                                <?php
                                // Essayer de récupérer les tags depuis notre fonction
                                $available_tags = self::get_systemio_tags();

                                if (is_wp_error($available_tags)) {
                                    // Afficher un message d'erreur si la récupération a échoué
                                    echo '<p class="notice notice-warning" style="margin-left: 0;">';
                                    echo '<strong>' . __('Erreur de récupération des tags :', 'wp-systemio-connect') . '</strong><br>';
                                    echo esc_html($available_tags->get_error_message());
                                    // Ajouter un bouton pour forcer le rafraîchissement ? (plus complexe)
                                    echo '</p>';
                                    // Afficher quand même un champ caché pour ne pas effacer la sélection précédente lors de la sauvegarde
                                    foreach ($selected_tags as $tag_id) {
                                        echo '<input type="hidden" name="wp_systemio_connect_options[cf7_integrations][' . $form_id . '][tags][]" value="' . esc_attr($tag_id) . '">';
                                    }

                                } elseif (empty($available_tags)) {
                                    echo '<p>' . __('Aucun tag trouvé dans votre compte Systeme.io ou l\'API n\'est pas configuréedd.', 'wp-systemio-connect') . '</p>';
                                } else {
                                    // Afficher les cases à cocher
                                    echo '<div class="wp-sio-tags-checkbox-list" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 5px; background: #f9f9f9;">';
                                    // Champ caché pour s'assurer que 'tags' est envoyé même si rien n'est coché (pour effacer la sélection précédente)
                                    echo '<input type="hidden" name="wp_systemio_connect_options[cf7_integrations][' . $form_id . '][tags]" value="">';

                                    foreach ($available_tags as $tag_id => $tag_name) {
                                        $checkbox_id = 'wp_sio_cf7_' . $form_id . '_tag_' . $tag_id;
                                        $is_checked = in_array((string) $tag_id, $selected_tags); // Comparaison de chaînes pour être sûr
                                        ?>
                                        <label for="<?php echo esc_attr($checkbox_id); ?>" style="display: block; margin-bottom: 3px;">
                                            <input type="checkbox" id="<?php echo esc_attr($checkbox_id); ?>"
                                                name="wp_systemio_connect_options[cf7_integrations][<?php echo $form_id; ?>][tags][]"
                                                value="<?php echo esc_attr($tag_id); ?>" <?php checked($is_checked); ?>>
                                            <?php echo esc_html($tag_name); ?> (ID: <?php echo esc_html($tag_id); ?>)
                                        </label>
                                        <?php
                                    }
                                    echo '</div>'; // .wp-sio-tags-checkbox-list
                                    echo '<p class="description">' . __('Cochez les tags à ajouter au contact lors de la soumission.', 'wp-systemio-connect') . '</p>';
                                }
                                ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div><!-- .wp-sio-cf7-form-config -->
            <?php
        } // end foreach $cf7_forms

        echo '</div><!-- .wp-sio-cf7-forms-container -->';

        // Ajouter un peu de JS pour masquer/afficher les champs conditionnels
        self::add_conditional_display_js();

    } // Fin render_field_cf7_form_settings

    /**
     * Ajoute le script JS pour gérer l'affichage conditionnel des options CF7.
     */
    public static function add_conditional_display_js()
    {
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const containers = document.querySelectorAll('.wp-sio-cf7-forms-container');
                containers.forEach(container => {
                    const checkboxes = container.querySelectorAll('input[type="checkbox"][id$="_enabled"]');
                    checkboxes.forEach(checkbox => {
                        const formConfigDiv = checkbox.closest('.wp-sio-cf7-form-config');
                        const conditionalFields = formConfigDiv.querySelectorAll('.wp-sio-cf7-conditional-fields');

                        function toggleFields() {
                            conditionalFields.forEach(fieldRow => {
                                fieldRow.style.display = checkbox.checked ? '' : 'none';
                            });
                        }

                        // État initial au chargement
                        toggleFields();

                        // Mettre à jour lors du changement
                        checkbox.addEventListener('change', toggleFields);
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Nettoie les options avant de les sauvegarder dans la base de données.
     * Très important pour la sécurité !
     */
    public static function sanitize_options($input)
    {
        // Récupérer TOUTES les options existantes
        $existing_options = get_option('wp_systemio_connect_options', []); // [] comme valeur par défaut si l'option n'existe pas encore

        // Initialiser le tableau des nouvelles options nettoyées (basé sur les existantes)
        $sanitized_options = $existing_options;

        // Identifier quel groupe a soumis le formulaire (un peu complexe sans info directe)
        // Astuce: Vérifier quels champs sont présents dans $input. Si 'api_key' est là, c'est probablement le groupe Settings.
        // ATTENTION: Cette méthode peut être fragile si les noms de champs ne sont pas uniques entre les groupes.

        $is_settings_submission = isset($input['api_key']) || isset($input['api_base_url']);
        $is_forms_submission = isset($input['cf7_integrations']) || isset($input['elementor_integrations']) || isset($input['divi_integrations']); // A adapter quand on fera le groupe Formulaires

        if ($is_settings_submission) {
            error_log('[WP SIO Sanitize Debug] Processing SETTINGS submission.');
            // --- Nettoyage des options API ---
            if (isset($input['api_key'])) {
                $sanitized_options['api_key'] = trim(wp_kses($input['api_key'], []));
            } else {
                // Si on soumet depuis Settings et que la clé est absente, la vider (important)
                $sanitized_options['api_key'] = '';
            }

            if (isset($input['api_base_url'])) {
                $url = esc_url_raw(trim($input['api_base_url']));
                if (empty($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
                    $sanitized_options['api_base_url'] = 'https://api.systeme.io/api';
                } else {
                    $sanitized_options['api_base_url'] = $url;
                }
            } else {
                // Si on soumet depuis Settings et que l'URL est absente, remettre la valeur par défaut (ou vider?)
                $sanitized_options['api_base_url'] = 'https://api.systeme.io/api';
            }
        } elseif ($is_forms_submission) {
            error_log('[WP SIO Sanitize Debug] Processing FORMS submission.');
            // Nettoyer et mettre à jour SEULEMENT les clés des formulaires

            // --- CF7 ---
            if (isset($input['cf7_integrations']) && is_array($input['cf7_integrations'])) {
                $sanitized_cf7 = [];
                foreach ($input['cf7_integrations'] as $form_id => $settings) {
                    // S'assurer que form_id est un entier positif
                    $form_id = absint($form_id);
                    if ($form_id <= 0 || !is_array($settings)) {
                        continue; // Ignorer les entrées invalides
                    }

                    $sanitized_settings = [];
                    // Nettoyer chaque champ de réglage pour ce formulaire
                    $sanitized_settings['enabled'] = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;

                    // Si le formulaire n'est pas activé, on peut ignorer les autres champs ou les garder vides
                    if ($sanitized_settings['enabled']) {
                        $sanitized_settings['email_field'] = isset($settings['email_field']) ? sanitize_text_field($settings['email_field']) : '';
                        $sanitized_settings['fname_field'] = isset($settings['fname_field']) ? sanitize_text_field($settings['fname_field']) : '';
                        $sanitized_settings['lname_field'] = isset($settings['lname_field']) ? sanitize_text_field($settings['lname_field']) : '';

                        // Nettoyer les tags : enlever espaces, garder chiffres et virgules
                        // --- NOUVEAU Nettoyage des Tags ---
                        if (isset($settings['tags'])) {
                            if (is_array($settings['tags'])) {
                                // Si c'est un tableau (venu des checkboxes)
                                // Convertir chaque ID en entier positif et filtrer les zéros/invalides
                                $sanitized_settings['tags'] = array_filter(array_map('absint', $settings['tags']));
                            } elseif (is_string($settings['tags']) && $settings['tags'] === '') {
                                // Si c'est la chaîne vide (venue du champ caché quand rien n'est coché)
                                $sanitized_settings['tags'] = []; // Stocker comme tableau vide
                            } else {
                                // Cas improbable ou si on a gardé le champ caché de l'étape précédente
                                $sanitized_settings['tags'] = [];
                            }
                        } else {
                            // Si la clé 'tags' n'est pas envoyée du tout (ne devrait pas arriver avec le champ caché)
                            $sanitized_settings['tags'] = [];
                        }

                        // **Validation importante** : S'assurer que le champ email est bien renseigné si activé
                        if (empty($sanitized_settings['email_field'])) {
                            // Peut-être désactiver automatiquement ou afficher une erreur ?
                            // Pour l'instant, on le laisse mais ce n'est pas idéal.
                            add_settings_error(
                                'wp_systemio_connect_options',
                                'cf7_email_missing_' . $form_id,
                                sprintf(__('Attention : Le champ Email est obligatoire pour le formulaire CF7 ID %d lorsqu\'il est activé pour Systeme.io.', 'wp-systemio-connect'), $form_id),
                                'warning' // Ou 'error' si on veut être plus strict
                            );
                            // On pourrait forcer enabled à false ici:
                            // $sanitized_settings['enabled'] = false;
                        }

                    } else {
                        // Si désactivé, on sauvegarde quand même les champs vides pour ne pas perdre la config
                        $sanitized_settings['email_field'] = isset($settings['email_field']) ? sanitize_text_field($settings['email_field']) : '';
                        $sanitized_settings['fname_field'] = isset($settings['fname_field']) ? sanitize_text_field($settings['fname_field']) : '';
                        $sanitized_settings['lname_field'] = isset($settings['lname_field']) ? sanitize_text_field($settings['lname_field']) : '';
                        // Sauvegarder les tags aussi
                        if (isset($settings['tags']) && is_array($settings['tags'])) {
                            $sanitized_settings['tags'] = array_filter(array_map('absint', $settings['tags']));
                        } else {
                            $sanitized_settings['tags'] = [];
                        }
                    }


                    $sanitized_cf7[$form_id] = $sanitized_settings;
                }
                $sanitized_options['cf7_integrations'] = $sanitized_cf7;
            } elseif (array_key_exists('cf7_integrations', $input)) {
                // Si la clé existe mais est vide (ex: si on a supprimé la dernière config CF7 via JS)
                $sanitized_options['cf7_integrations'] = [];
            }
            // Si la clé n'est pas dans $input, on ne touche pas à $sanitized_options['cf7_integrations']

            // --- Elementor ---
            if (isset($input['elementor_integrations']) && is_array($input['elementor_integrations'])) {
                error_log('[WP SIO Connect Admin] Options being saved: '); // Log avant de retourner

                $sanitized_elementor = [];
                foreach ($input['elementor_integrations'] as $index_or_id => $settings) {
                    // Récupérer le vrai ID du formulaire depuis le champ dédié
                    $form_id = isset($settings['form_id']) ? sanitize_text_field(trim($settings['form_id'])) : '';

                    // Si l'ID est vide ou si ce n'est pas un tableau de settings valide, on ignore cette ligne
                    if (empty($form_id) || !is_array($settings)) {
                        continue;
                    }

                    // Utiliser $form_id comme clé pour le tableau final
                    $sanitized_settings = [];
                    $sanitized_settings['enabled'] = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;

                    // Nettoyer les champs de mapping et tags (similaire à CF7)
                    $sanitized_settings['email_field'] = isset($settings['email_field']) ? sanitize_text_field($settings['email_field']) : '';
                    $sanitized_settings['fname_field'] = isset($settings['fname_field']) ? sanitize_text_field($settings['fname_field']) : '';
                    $sanitized_settings['lname_field'] = isset($settings['lname_field']) ? sanitize_text_field($settings['lname_field']) : '';

                    if (isset($settings['tags'])) {
                        if (is_array($settings['tags'])) {
                            $sanitized_settings['tags'] = array_filter(array_map('absint', $settings['tags']));
                        } elseif (is_string($settings['tags']) && $settings['tags'] === '') {
                            $sanitized_settings['tags'] = [];
                        } else {
                            $sanitized_settings['tags'] = []; // Fallback
                        }
                    } else {
                        $sanitized_settings['tags'] = [];
                    }


                    // Validation cruciale : ID Formulaire et Champ Email doivent être définis si activé
                    if ($sanitized_settings['enabled']) {
                        if (empty($sanitized_settings['email_field'])) {
                            add_settings_error(
                                'wp_systemio_connect_options',
                                'elementor_email_missing_' . $form_id,
                                sprintf(__('Attention : L\'ID du Champ Email est obligatoire pour le formulaire Elementor "%s" lorsqu\'il est activé.', 'wp-systemio-connect'), esc_html($form_id)),
                                'warning'
                            );
                            // On pourrait désactiver ici pour forcer la correction :
                            // $sanitized_settings['enabled'] = false;
                        }
                    }
                    // Même si désactivé, on stocke la config pour ne pas la perdre
                    $sanitized_elementor[$form_id] = $sanitized_settings; // Utiliser le vrai form_id comme clé
                }
                $sanitized_options['elementor_integrations'] = $sanitized_elementor;
            } elseif (array_key_exists('elementor_integrations', $input)) {
                $sanitized_options['elementor_integrations'] = [];
            }
            // Si non soumis, on ne touche pas

            // --- Divi ---
            if (isset($input['divi_integrations']) && is_array($input['divi_integrations'])) {
                $sanitized_divi = [];
                foreach ($input['divi_integrations'] as $index => $settings) {
                    $css_id = isset($settings['css_id']) ? sanitize_html_class(trim($settings['css_id'])) : ''; // sanitize_html_class est bien pour les ID/classes CSS

                    if (empty($css_id) || !is_array($settings)) {
                        continue;
                    }

                    $sanitized_settings = [];
                    $sanitized_settings['enabled'] = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;

                    // Nettoyer mapping champs (ID des champs Divi)
                    $sanitized_settings['email_field'] = isset($settings['email_field']) ? sanitize_text_field($settings['email_field']) : '';
                    $sanitized_settings['fname_field'] = isset($settings['fname_field']) ? sanitize_text_field($settings['fname_field']) : '';
                    $sanitized_settings['lname_field'] = isset($settings['lname_field']) ? sanitize_text_field($settings['lname_field']) : '';

                    // --- Nettoyage des Tags ---
                    if (isset($settings['tags'])) {
                        if (is_array($settings['tags'])) {
                            // Cas normal : vient des checkboxes cochées (ou d'une sauvegarde précédente)
                            // Convertir chaque ID en entier positif et filtrer les zéros/invalides
                            $sanitized_settings['tags'] = array_filter(array_map('absint', $settings['tags']));
                            // Optionnel : Ré-indexer le tableau pour avoir des clés numériques séquentielles (0, 1, 2...)
                            // $sanitized_settings['tags'] = array_values($sanitized_settings['tags']);
                        } elseif (is_string($settings['tags']) && $settings['tags'] === '') {
                            // Cas où aucune case n'est cochée, le champ caché envoie une chaîne vide
                            $sanitized_settings['tags'] = []; // Stocker comme tableau vide
                        } elseif (is_string($settings['tags']) && !empty($settings['tags'])) {
                            // Cas où les données pourraient provenir d'un champ caché contenant des IDs lors d'une erreur de récupération API
                            // On essaie de parser la chaîne comme si elle contenait des IDs séparés par des virgules
                            $tags_raw = sanitize_text_field($settings['tags']);
                            $tags_cleaned = preg_replace('/[^0-9,]/', '', $tags_raw);
                            $tags_cleaned = trim(preg_replace('/,+/', ',', $tags_cleaned), ',');
                            if (!empty($tags_cleaned)) {
                                $tag_ids = array_map('absint', explode(',', $tags_cleaned));
                                $sanitized_settings['tags'] = array_filter($tag_ids);
                                // $sanitized_settings['tags'] = array_values($sanitized_settings['tags']); // Optionnel
                            } else {
                                $sanitized_settings['tags'] = [];
                            }
                        } else {
                            // Cas par défaut ou inattendu (ni tableau, ni chaîne vide, ni chaîne d'IDs)
                            $sanitized_settings['tags'] = [];
                        }
                    } else {
                        // Si la clé 'tags' n'est pas envoyée du tout (ne devrait pas arriver avec le champ caché)
                        $sanitized_settings['tags'] = [];
                    }
                    // À ce stade, $sanitized_settings['tags'] devrait toujours être un tableau (potentiellement vide).

                    // Validation si activé (comme avant)
                    if ($sanitized_settings['enabled'] && empty($sanitized_settings['email_field'])) {
                        add_settings_error( /* ... */);
                    }

                    $sanitized_divi[$css_id] = $sanitized_settings; // Utiliser l'ID CSS comme clé
                }
                $sanitized_options['divi_integrations'] = $sanitized_divi;
            } elseif (array_key_exists('divi_integrations', $input)) {
                $sanitized_options['divi_integrations'] = [];
            }
            // Si non soumis, on ne touche pas

            // Ne pas toucher aux clés 'api_key', 'api_base_url' ici

        } else {
            // Cas inconnu ou soumission vide ? Ne rien faire ou logguer.
            error_log('[WP SIO Sanitize Debug] Unknown submission type or empty input.');
        }

        error_log('[WP SIO Sanitize Debug] FINAL Options structure after specific update: ' . print_r($sanitized_options, true));
        return $sanitized_options; // Retourner le tableau fusionné et nettoyé
    }

    // Dans la classe WP_Systemio_Connect_Admin (admin/class-wp-systemio-connect-admin.php)

    /**
     * Récupère les tags depuis l'API Systeme.io avec mise en cache.
     *
     * @param bool $force_refresh Forcer le rafraîchissement du cache.
     * @return array|WP_Error Tableau des tags [id => name] en cas de succès, WP_Error en cas d'échec.
     */
    public static function get_systemio_tags($force_refresh = true)
    {
        $cache_key = 'wp_sio_connect_tags_cache';
        $tags = get_transient($cache_key);

        // Si le cache existe et qu'on ne force pas le rafraîchissement, le retourner
        if (false !== $tags && !$force_refresh) {
            return $tags;
        }

        // Sinon, appeler l'API
        $api_key = self::get_api_key();
        $api_base_url = self::get_api_base_url();

        if (empty($api_key) || empty($api_base_url)) {
            return new WP_Error('api_not_configured', __('L\'API Systeme.io n\'est pas configurée.', 'wp-systemio-connect'));
        }

        $endpoint = $api_base_url . '/tags';
        // Ajouter des paramètres pour la pagination si nécessaire (ex: ?limit=100)
        // L'API V2 de SIO utilise la pagination, il faudra peut-être boucler si > 50 tags par défaut
        // Pour commencer, on prend la première page (souvent suffisant)
        $endpoint .= '?limit=100'; // Récupérer jusqu'à 100 tags

        $args = [
            'headers' => [
                'X-API-Key' => $api_key,
                'Accept' => 'application/json',
            ],
            'timeout' => 15,
        ];

        $response = wp_remote_get($endpoint, $args);

        if (is_wp_error($response)) {
            error_log('[WP SIO Connect] Erreur WP lors de la récupération des tags SIO : ' . $response->get_error_message());
            return $response; // Renvoyer l'erreur WP
        }



        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);

        if ($response_code >= 200 && $response_code < 300 && isset($data['items']) && is_array($data['items'])) {
            $formatted_tags = [];
            foreach ($data['items'] as $tag) {
                if (isset($tag['id']) && isset($tag['name'])) {
                    $formatted_tags[$tag['id']] = $tag['name'];
                }
            }
            // Mettre en cache pour 1 heure (3600 secondes)
            set_transient($cache_key, $formatted_tags, HOUR_IN_SECONDS);
            return $formatted_tags;

            // TODO: Gérer la pagination s'il y a plus de tags que la limite retournée ('nextPageUrl' dans la réponse ?)

        } else {
            $error_message = __('Impossible de récupérer les tags.', 'wp-systemio-connect');
            if ($data && isset($data['message'])) {
                $error_message = $data['message'];
            } elseif (!empty($response_body)) {
                $error_message .= ' Réponse API : ' . wp_strip_all_tags($response_body);
            }
            error_log('[WP SIO Connect] Erreur API SIO lors de la récupération des tags : Code ' . $response_code . ' - ' . $error_message);
            return new WP_Error('api_error', sprintf(__('Erreur API Systeme.io (Code: %d) : %s', 'wp-systemio-connect'), $response_code, esc_html($error_message)));
        }
    }

    /**
     * Affiche la description de la section d'intégration Elementor.
     */
    public static function render_section_elementor_description()
    {
        echo '<p>' . __('Configurez ici les formulaires Elementor Pro que vous souhaitez connecter à Systeme.io.', 'wp-systemio-connect') . '</p>';
        echo '<p>' . __('Vous devez spécifier l\'<b>ID du formulaire</b> défini dans les réglages du widget Formulaire Elementor (onglet "Avancé" > "ID CSS" ou onglet "Contenu" > "Options additionnelles" > "ID").', 'wp-systemio-connect') . '</p>';
    }

    /**
     * Affiche les options de configuration pour les formulaires Elementor.
     * Utilise une approche de répéteur simple pour ajouter/supprimer des formulaires.
     */
    public static function render_field_elementor_form_settings()
    {
        // Récupérer les réglages Elementor sauvegardés
        $elementor_settings = isset(self::$options['elementor_integrations']) ? self::$options['elementor_integrations'] : [];

        ?>
        <div id="wp-sio-elementor-forms-container">
            <?php
            // Afficher les formulaires déjà configurés
            if (!empty($elementor_settings)) {
                foreach ($elementor_settings as $form_id => $settings) {
                    // S'assurer que l'ID n'est pas juste un index numérique si on a mal sauvegardé
                    if (is_int($form_id))
                        continue;
                    self::render_elementor_form_row($form_id, $settings);
                }
            } else {
                // Afficher une ligne vide par défaut si aucun n'est configuré
                self::render_elementor_form_row('', []); // Ligne modèle vide
            }
            ?>
            <!-- Modèle pour ajouter dynamiquement (via JS) -->
            <template id="wp-sio-elementor-form-row-template">
                <?php self::render_elementor_form_row('__INDEX__', []); ?>
            </template>

        </div>
        <button type="button" id="wp-sio-add-elementor-form" class="button">
            <?php _e('Ajouter un formulaire Elementor', 'wp-systemio-connect'); ?>
        </button>

        <?php
        // Ajouter le JS pour le répéteur
        self::add_elementor_repeater_js();
    }

    /**
     * Affiche une ligne de configuration pour UN formulaire Elementor.
     *
     * @param string $form_id L'ID du formulaire Elementor (la clé dans notre tableau d'options).
     * @param array $settings Les réglages sauvegardés pour ce formulaire.
     */
    private static function render_elementor_form_row($form_id, $settings)
    {
        $form_id_attr = esc_attr($form_id); // Pour les attributs HTML
        // Générer un index unique pour les nouveaux champs (remplacé par le vrai form_id à la sauvegarde)
        $index = $form_id ? $form_id : '__INDEX__';

        // Valeurs par défaut
        $enabled = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;
        $email_field = isset($settings['email_field']) ? $settings['email_field'] : 'email'; // Suggestion
        $fname_field = isset($settings['fname_field']) ? $settings['fname_field'] : 'name'; // Suggestion
        $lname_field = isset($settings['lname_field']) ? $settings['lname_field'] : '';
        $selected_tags = isset($settings['tags']) && is_array($settings['tags']) ? $settings['tags'] : [];

        ?>
        <div class="wp-sio-elementor-form-config" data-index="<?php echo $index; ?>"
            style="border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 15px; background: #f0f0f0;">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="wp_sio_elementor_<?php echo $index; ?>_form_id">
                                <?php _e('ID du Formulaire Elementor (*)', 'wp-systemio-connect'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="wp_sio_elementor_<?php echo $index; ?>_form_id"
                                name="wp_systemio_connect_options[elementor_integrations][<?php echo $index; ?>][form_id]"
                                value="<?php echo $form_id_attr; ?>" class="regular-text wp-sio-elementor-form-id-input"
                                placeholder="<?php esc_attr_e('ID défini dans Elementor', 'wp-systemio-connect'); ?>" required>
                            <p class="description">
                                <?php _e('Doit correspondre exactement à l\'ID du formulaire dans Elementor.', 'wp-systemio-connect'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wp_sio_elementor_<?php echo $index; ?>_enabled">
                                <?php _e('Activer pour Systeme.io', 'wp-systemio-connect'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="hidden"
                                name="wp_systemio_connect_options[elementor_integrations][<?php echo $index; ?>][enabled]"
                                value="0">
                            <input type="checkbox" id="wp_sio_elementor_<?php echo $index; ?>_enabled"
                                name="wp_systemio_connect_options[elementor_integrations][<?php echo $index; ?>][enabled]"
                                value="1" <?php checked($enabled, 1); ?> class="wp-sio-elementor-enable-checkbox">
                            <p class="description">
                                <?php _e('Cochez pour envoyer les données de ce formulaire vers Systeme.io.', 'wp-systemio-connect'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="wp-sio-elementor-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                        <th scope="row">
                            <label for="wp_sio_elementor_<?php echo $index; ?>_email_field">
                                <?php _e('ID Champ Email (*)', 'wp-systemio-connect'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="wp_sio_elementor_<?php echo $index; ?>_email_field"
                                name="wp_systemio_connect_options[elementor_integrations][<?php echo $index; ?>][email_field]"
                                value="<?php echo esc_attr($email_field); ?>" class="regular-text" placeholder="email" required>
                            <p class="description">
                                <?php _e('ID du champ Email dans le formulaire Elementor (ex: email).', 'wp-systemio-connect'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="wp-sio-elementor-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                        <th scope="row">
                            <label for="wp_sio_elementor_<?php echo $index; ?>_fname_field">
                                <?php _e('ID Champ Prénom', 'wp-systemio-connect'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="wp_sio_elementor_<?php echo $index; ?>_fname_field"
                                name="wp_systemio_connect_options[elementor_integrations][<?php echo $index; ?>][fname_field]"
                                value="<?php echo esc_attr($fname_field); ?>" class="regular-text" placeholder="name">
                            <p class="description">
                                <?php _e('ID du champ Prénom/Nom (ex: name, fname). Laisser vide si non utilisé.', 'wp-systemio-connect'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="wp-sio-elementor-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                        <th scope="row">
                            <label for="wp_sio_elementor_<?php echo $index; ?>_lname_field">
                                <?php _e('ID Champ Nom', 'wp-systemio-connect'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="wp_sio_elementor_<?php echo $index; ?>_lname_field"
                                name="wp_systemio_connect_options[elementor_integrations][<?php echo $index; ?>][lname_field]"
                                value="<?php echo esc_attr($lname_field); ?>" class="regular-text" placeholder="lname">
                            <p class="description">
                                <?php _e('ID du champ Nom de famille (ex: lname). Laisser vide si non utilisé.', 'wp-systemio-connect'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="wp-sio-elementor-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                        <th scope="row">
                            <?php _e('Tags Systeme.io', 'wp-systemio-connect'); ?>
                        </th>
                        <td>
                            <?php /* Réutilisation de la logique d'affichage des tags CF7 */
                            $available_tags = self::get_systemio_tags();
                            if (is_wp_error($available_tags)) { /* Afficher erreur */
                                echo '<p class="notice notice-warning" style="margin-left: 0;">';
                                echo '<strong>' . __('Erreur tags :', 'wp-systemio-connect') . '</strong> ' . esc_html($available_tags->get_error_message());
                                echo '</p>';
                                // Champ caché pour préserver la sélection
                                echo '<input type="hidden" name="wp_systemio_connect_options[elementor_integrations][' . $index . '][tags]" value="' . esc_attr(json_encode($selected_tags)) . '">'; // Store as JSON string? Safer might be individual hidden fields if needed. For now, let sanitize handle it.
                            } elseif (empty($available_tags)) { /* Aucun tag trouvé */
                                echo '<p>' . __('Aucun tag trouvé.', 'wp-systemio-connect') . '</p>';
                            } else { ?>
                                <div class="wp-sio-tags-checkbox-list"
                                    style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 5px; background: #f9f9f9;">
                                    <input type="hidden"
                                        name="wp_systemio_connect_options[elementor_integrations][<?php echo $index; ?>][tags]"
                                        value="">
                                    <?php foreach ($available_tags as $tag_id => $tag_name):
                                        $checkbox_id = 'wp_sio_elementor_' . $index . '_tag_' . $tag_id;
                                        $is_checked = in_array((string) $tag_id, array_map('strval', $selected_tags)); // Ensure string comparison
                                        ?>
                                        <label for="<?php echo esc_attr($checkbox_id); ?>" style="display: block; margin-bottom: 3px;">
                                            <input type="checkbox" id="<?php echo esc_attr($checkbox_id); ?>"
                                                name="wp_systemio_connect_options[elementor_integrations][<?php echo $index; ?>][tags][]"
                                                value="<?php echo esc_attr($tag_id); ?>" <?php checked($is_checked); ?>>
                                            <?php echo esc_html($tag_name); ?> (ID: <?php echo esc_html($tag_id); ?>)
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description"><?php _e('Cochez les tags à ajouter.', 'wp-systemio-connect'); ?></p>
                            <?php } // Fin else $available_tags ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align: right;">
                            <button type="button" class="button button-link-delete wp-sio-remove-elementor-form">
                                <?php _e('Supprimer ce formulaire', 'wp-systemio-connect'); ?>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div><!-- .wp-sio-elementor-form-config -->
        <?php
    }

    /**
     * Ajoute le script JS pour le répéteur Elementor et l'affichage conditionnel.
     */
    private static function add_elementor_repeater_js()
    {
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.getElementById('wp-sio-elementor-forms-container');
                const template = document.getElementById('wp-sio-elementor-form-row-template');
                const addButton = document.getElementById('wp-sio-add-elementor-form');

                if (!container || !template || !addButton) return;

                // Fonction pour gérer l'affichage conditionnel
                function setupConditionalDisplay(formConfigDiv) {
                    const checkbox = formConfigDiv.querySelector('.wp-sio-elementor-enable-checkbox');
                    const conditionalFields = formConfigDiv.querySelectorAll('.wp-sio-elementor-conditional-fields');

                    function toggleFields() {
                        conditionalFields.forEach(fieldRow => {
                            fieldRow.style.display = checkbox.checked ? '' : 'none';
                        });
                    }
                    if (checkbox) {
                        toggleFields(); // État initial
                        checkbox.addEventListener('change', toggleFields);
                    }
                }

                // Fonction pour gérer la suppression
                function setupRemoveButton(formConfigDiv) {
                    const removeButton = formConfigDiv.querySelector('.wp-sio-remove-elementor-form');
                    if (removeButton) {
                        removeButton.addEventListener('click', function (e) {
                            e.preventDefault();
                            if (confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir supprimer la configuration de ce formulaire ?', 'wp-systemio-connect')); ?>')) {
                                formConfigDiv.remove();
                                // S'assurer qu'il reste au moins une ligne (peut-être vide) ? Optionnel.
                                if (container.querySelectorAll('.wp-sio-elementor-form-config').length === 0) {
                                    addFormRow(); // Ajouter une nouvelle ligne vide si tout est supprimé
                                }
                            }
                        });
                    }
                }

                // Fonction pour gérer le changement d'ID de formulaire dans les attributs name
                function setupFormIdSync(formConfigDiv) {
                    const formIdInput = formConfigDiv.querySelector('.wp-sio-elementor-form-id-input');
                    if (!formIdInput) return;

                    formIdInput.addEventListener('input', function () {
                        const newFormId = this.value.trim().replace(/[^a-zA-Z0-9_-]/g, ''); // Nettoyer l'ID pour l'usage comme clé
                        const currentConfigDiv = this.closest('.wp-sio-elementor-form-config');
                        const inputsToUpdate = currentConfigDiv.querySelectorAll('[name^="wp_systemio_connect_options[elementor_integrations"]');

                        inputsToUpdate.forEach(input => {
                            const nameAttr = input.getAttribute('name');
                            // Remplacer l'index (ex: __INDEX__ ou l'ancien form_id) par le nouveau
                            const newName = nameAttr.replace(/\[elementor_integrations\]\[([^\]]+)\]/, `[elementor_integrations][${newFormId || '__INDEX__'}]`);
                            input.setAttribute('name', newName);
                        });
                        // Mettre à jour aussi l'index data
                        // ... calcul de newCssId (ou newFormName/newFormId) ...
                        const replacementKey = newCssId || ('__INDEX__' + Date.now()); // Utilise un index unique si vide
                        currentConfigDiv.dataset.index = replacementKey;
                        // Mettre à jour les ID/for des labels/champs si nécessaire (pas fait ici pour simplifier)
                    });
                }


                // Fonction pour ajouter une nouvelle ligne
                function addFormRow() {
                    const clone = template.content.cloneNode(true);
                    const newIndex = Date.now(); // Utiliser un timestamp comme index temporaire unique
                    const newRow = clone.querySelector('.wp-sio-elementor-form-config');

                    // Remplacer __INDEX__ par le nouvel index unique dans le HTML cloné
                    newRow.innerHTML = newRow.innerHTML.replace(/__INDEX__/g, newIndex);
                    newRow.dataset.index = newIndex; // Mettre à jour l'attribut data-index

                    container.appendChild(newRow);

                    // Ré-appliquer les gestionnaires d'événements sur la nouvelle ligne
                    setupConditionalDisplay(newRow);
                    setupRemoveButton(newRow);
                    setupFormIdSync(newRow);
                }

                // Gérer l'ajout
                addButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    addFormRow();
                });

                // Appliquer les gestionnaires aux lignes existantes au chargement
                container.querySelectorAll('.wp-sio-elementor-form-config').forEach(row => {
                    setupConditionalDisplay(row);
                    setupRemoveButton(row);
                    setupFormIdSync(row);
                });

            });
        </script>
        <?php
    }

    /**
     * Affiche la description de la section d'intégration Divi.
     */
    public static function render_section_divi_description()
    {
        echo '<p>' . __('Configurez ici les modules "Formulaire de Contact" Divi que vous souhaitez connecter à Systeme.io.', 'wp-systemio-connect') . '</p>';
        echo '<p><strong>' . __('Important :', 'wp-systemio-connect') . '</strong> ' . __('Pour chaque module Formulaire de Contact à connecter, vous devez lui assigner un <strong>ID CSS unique</strong> dans ses réglages Avancés (ID et classes CSS -> ID CSS).', 'wp-systemio-connect') . '</p>';
        echo '<p>' . __('De plus, pour mapper les champs (Email, Prénom, etc.), vous devez assigner un <strong>ID de champ</strong> unique à chaque champ pertinent dans les réglages de ce champ (Avancé -> ID et classes CSS -> ID de champ).', 'wp-systemio-connect') . '</p>';
    }

    /**
     * Affiche les options de configuration pour les formulaires Divi (basé sur ID CSS).
     * Utilise une approche de répéteur similaire à Elementor.
     */
    public static function render_field_divi_form_settings()
    {
        // Récupérer les réglages Divi sauvegardés
        $divi_settings = isset(self::$options['divi_integrations']) ? self::$options['divi_integrations'] : [];

        ?>
        <div id="wp-sio-divi-forms-container">
            <?php
            if (!empty($divi_settings)) {
                foreach ($divi_settings as $css_id => $settings) {
                    if (is_int($css_id))
                        continue; // Skip numeric index if any issue during save
                    self::render_divi_form_row($css_id, $settings);
                }
            } else {
                self::render_divi_form_row('', []); // Ligne modèle vide
            }
            ?>
            <!-- Modèle pour ajout dynamique -->
            <template id="wp-sio-divi-form-row-template">
                <?php self::render_divi_form_row('__INDEX__', []); ?>
            </template>

        </div>
        <button type="button" id="wp-sio-add-divi-form" class="button">
            <?php _e('Ajouter un formulaire Divi', 'wp-systemio-connect'); ?>
        </button>

        <?php
        // Ajouter le JS pour le répéteur Divi (peut être factorisé avec Elementor JS ?)
        self::add_divi_repeater_js(); // Fonction JS à créer, similaire à add_elementor_repeater_js
    }

    /**
     * Affiche une ligne de configuration pour UN formulaire Divi.
     *
     * @param string $css_id L'ID CSS du module Formulaire (la clé dans nos options, ou __INDEX__...).
     * @param array $settings Les réglages sauvegardés pour cette clé.
     */
    private static function render_divi_form_row($css_id, $settings)
    {
        // Utiliser l'ID CSS comme index pour les champs s'il est défini, sinon l'index temporaire
        $index = $css_id ?: '__INDEX__' . uniqid(); // Assurer un index unique pour les nouvelles lignes

        // Valeurs actuelles ou par défaut
        $current_css_id = ($css_id && strpos($css_id, '__INDEX__') === false) ? $css_id : ''; // N'affiche que les vrais ID CSS sauvegardés
        $enabled = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;
        // ID des CHAMPS définis dans Divi
        $email_field = isset($settings['email_field']) ? $settings['email_field'] : ''; // Pas de suggestion par défaut, force l'utilisateur
        $fname_field = isset($settings['fname_field']) ? $settings['fname_field'] : '';
        $lname_field = isset($settings['lname_field']) ? $settings['lname_field'] : '';
        $selected_tags = isset($settings['tags']) && is_array($settings['tags']) ? $settings['tags'] : [];

        ?>
        <div class="wp-sio-divi-form-config" data-index="<?php echo esc_attr($index); ?>"
            style="border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 15px; background: #f9f9f9;">
            <table class="form-table" role="presentation">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="wp_sio_divi_<?php echo esc_attr($index); ?>_css_id">
                                <?php _e('ID CSS du Module Formulaire (*)', 'wp-systemio-connect'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="wp_sio_divi_<?php echo esc_attr($index); ?>_css_id"
                                name="wp_systemio_connect_options[divi_integrations][<?php echo esc_attr($index); ?>][css_id]"
                                value="<?php echo esc_attr($current_css_id); ?>" class="regular-text wp-sio-divi-css-id-input"
                                placeholder="<?php esc_attr_e('ID CSS unique défini dans Divi', 'wp-systemio-connect'); ?>"
                                required>
                            <p class="description">
                                <?php _e('Doit correspondre à l\'ID CSS défini sur le module Formulaire (Avancé > ID et classes CSS > ID CSS).', 'wp-systemio-connect'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="wp_sio_divi_<?php echo esc_attr($index); ?>_enabled">
                                <?php _e('Activer pour Systeme.io', 'wp-systemio-connect'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="hidden"
                                name="wp_systemio_connect_options[divi_integrations][<?php echo esc_attr($index); ?>][enabled]"
                                value="0">
                            <input type="checkbox" id="wp_sio_divi_<?php echo esc_attr($index); ?>_enabled"
                                name="wp_systemio_connect_options[divi_integrations][<?php echo esc_attr($index); ?>][enabled]"
                                value="1" <?php checked($enabled, 1); ?> class="wp-sio-divi-enable-checkbox">
                            <p class="description">
                                <?php _e('Cochez pour envoyer les données de ce formulaire vers Systeme.io.', 'wp-systemio-connect'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="wp-sio-divi-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                        <th scope="row">
                            <label for="wp_sio_divi_<?php echo esc_attr($index); ?>_email_field">
                                <?php _e('ID du Champ Email (*)', 'wp-systemio-connect'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="wp_sio_divi_<?php echo esc_attr($index); ?>_email_field"
                                name="wp_systemio_connect_options[divi_integrations][<?php echo esc_attr($index); ?>][email_field]"
                                value="<?php echo esc_attr($email_field); ?>" class="regular-text"
                                placeholder="<?php esc_attr_e('Ex: contact-email', 'wp-systemio-connect'); ?>" required>
                            <p class="description">
                                <?php _e('ID défini sur le champ Email lui-même (Réglages du champ > Avancé > ID de champ).', 'wp-systemio-connect'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="wp-sio-divi-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                        <th scope="row">
                            <label for="wp_sio_divi_<?php echo esc_attr($index); ?>_fname_field">
                                <?php _e('ID du Champ Prénom', 'wp-systemio-connect'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="wp_sio_divi_<?php echo esc_attr($index); ?>_fname_field"
                                name="wp_systemio_connect_options[divi_integrations][<?php echo esc_attr($index); ?>][fname_field]"
                                value="<?php echo esc_attr($fname_field); ?>" class="regular-text"
                                placeholder="<?php esc_attr_e('Ex: contact-prenom', 'wp-systemio-connect'); ?>">
                            <p class="description">
                                <?php _e('ID défini sur le champ Prénom (ou Nom complet). Laisser vide si non utilisé.', 'wp-systemio-connect'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="wp-sio-divi-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                        <th scope="row">
                            <label for="wp_sio_divi_<?php echo esc_attr($index); ?>_lname_field">
                                <?php _e('ID du Champ Nom', 'wp-systemio-connect'); ?>
                            </label>
                        </th>
                        <td>
                            <input type="text" id="wp_sio_divi_<?php echo esc_attr($index); ?>_lname_field"
                                name="wp_systemio_connect_options[divi_integrations][<?php echo esc_attr($index); ?>][lname_field]"
                                value="<?php echo esc_attr($lname_field); ?>" class="regular-text"
                                placeholder="<?php esc_attr_e('Ex: contact-nom', 'wp-systemio-connect'); ?>">
                            <p class="description">
                                <?php _e('ID défini sur le champ Nom de famille. Laisser vide si non utilisé.', 'wp-systemio-connect'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr class="wp-sio-divi-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                        <th scope="row">
                            <?php _e('Tags Systeme.io', 'wp-systemio-connect'); ?>
                        </th>
                        <td>
                            <?php
                            // Essayer de récupérer les tags depuis la fonction mise en cache
                            $available_tags = self::get_systemio_tags();

                            if (is_wp_error($available_tags)) {
                                // Afficher un message d'erreur si la récupération a échoué
                                echo '<p class="notice notice-warning" style="margin-left: 0; padding: 5px;">';
                                echo '<strong>' . __('Erreur de récupération des tags :', 'wp-systemio-connect') . '</strong><br>';
                                echo esc_html($available_tags->get_error_message());
                                echo '</p>';
                                // Inclure des champs cachés pour les tags sélectionnés pour ne pas les perdre à la sauvegarde
                                foreach ($selected_tags as $tag_id) {
                                    if (!empty($tag_id)) { // S'assurer que l'ID n'est pas vide
                                        echo '<input type="hidden" name="wp_systemio_connect_options[divi_integrations][' . esc_attr($index) . '][tags][]" value="' . esc_attr($tag_id) . '">';
                                    }
                                }
                            } elseif (empty($available_tags)) {
                                echo '<p>' . __('Aucun tag trouvé dans votre compte Systeme.io ou l\'API n\'est pas configurée.', 'wp-systemio-connect') . '</p>';
                            } else {
                                // Afficher les cases à cocher
                                echo '<div class="wp-sio-tags-checkbox-list" style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 5px; background: #fff;">';
                                // Champ caché pour s'assurer que 'tags' est envoyé même si rien n'est coché (pour effacer la sélection précédente)
                                echo '<input type="hidden" name="wp_systemio_connect_options[divi_integrations][' . esc_attr($index) . '][tags]" value="">';

                                foreach ($available_tags as $tag_id => $tag_name) {
                                    $checkbox_id = 'wp_sio_divi_' . esc_attr($index) . '_tag_' . $tag_id;
                                    // S'assurer que la comparaison fonctionne (les IDs peuvent être entiers ou chaînes)
                                    $is_checked = in_array((string) $tag_id, array_map('strval', $selected_tags), true);
                                    ?>
                                    <label for="<?php echo esc_attr($checkbox_id); ?>"
                                        style="display: block; margin-bottom: 3px; font-weight: normal;">
                                        <input type="checkbox" id="<?php echo esc_attr($checkbox_id); ?>"
                                            name="wp_systemio_connect_options[divi_integrations][<?php echo esc_attr($index); ?>][tags][]"
                                            value="<?php echo esc_attr($tag_id); ?>" <?php checked($is_checked); ?>>
                                        <?php echo esc_html($tag_name); ?> (ID: <?php echo esc_html($tag_id); ?>)
                                    </label>
                                    <?php
                                }
                                echo '</div>'; // .wp-sio-tags-checkbox-list
                                echo '<p class="description">' . __('Cochez les tags SIO à ajouter au contact lors de la soumission.', 'wp-systemio-connect') . '</p>';
                            }
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="text-align: right; padding-top: 10px;">
                            <button type="button" class="button button-link-delete wp-sio-remove-divi-form"
                                style="color: #a00;">
                                <?php _e('Supprimer ce formulaire', 'wp-systemio-connect'); ?>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div><!-- .wp-sio-divi-form-config -->
        <?php
    }

    /**
     * Ajoute le script JS pour le répéteur Divi et l'affichage conditionnel.
     * NOTE : Ce code est très similaire à celui d'Elementor. Une factorisation serait idéale
     * dans une version ultérieure pour éviter la duplication.
     */    /**
     * Ajoute le script JS pour le répéteur Divi et l'affichage conditionnel.
     */
    private static function add_divi_repeater_js() {
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.getElementById('wp-sio-divi-forms-container');
                const template = document.getElementById('wp-sio-divi-form-row-template');
                const addButton = document.getElementById('wp-sio-add-divi-form');

                // Vérification initiale des éléments principaux
                if (!container) { console.error('WP SIO Connect: Divi container #wp-sio-divi-forms-container not found.'); return; }
                if (!template) { console.error('WP SIO Connect: Divi template #wp-sio-divi-form-row-template not found.'); return; }
                if (!addButton) { console.error('WP SIO Connect: Divi add button #wp-sio-add-divi-form not found.'); return; }

                console.log('WP SIO Connect: Divi Repeater JS Initializing...');

                // --- Fonction pour initialiser une ligne (existante ou nouvelle) ---
                function initializeFormRow(formConfigDiv) {
                    console.log('WP SIO Connect: Initializing row:', formConfigDiv);
                    setupConditionalDisplay(formConfigDiv);
                    setupRemoveButton(formConfigDiv);
                    setupCssIdSync(formConfigDiv);
                }

                // --- Fonction pour gérer l'affichage conditionnel ---
                function setupConditionalDisplay(formConfigDiv) {
                    const checkbox = formConfigDiv.querySelector('.wp-sio-divi-enable-checkbox');
                    // Important: Utiliser querySelectorAll pour récupérer une NodeList
                    const conditionalFields = formConfigDiv.querySelectorAll('.wp-sio-divi-conditional-fields');

                    // Vérifier si les éléments sont trouvés
                    if (!checkbox) { console.warn('WP SIO Connect: Enable checkbox (.wp-sio-divi-enable-checkbox) not found in row:', formConfigDiv); return; }
                    if (conditionalFields.length === 0) { console.warn('WP SIO Connect: Conditional fields (.wp-sio-divi-conditional-fields) not found in row:', formConfigDiv); return; }
                    console.log(`WP SIO Connect: Found ${conditionalFields.length} conditional fields for checkbox:`, checkbox);


                    function toggleFields() {
                        console.log('WP SIO Connect: Toggling fields. Checkbox checked:', checkbox.checked);
                        conditionalFields.forEach(fieldRow => {
                            fieldRow.style.display = checkbox.checked ? '' : 'none'; // Utiliser '' pour revenir à l'affichage par défaut (table-row)
                        });
                    }

                    toggleFields(); // Appliquer l'état initial
                    checkbox.removeEventListener('change', toggleFields); // Enlever l'ancien listener au cas où
                    checkbox.addEventListener('change', toggleFields); // Ajouter le listener
                }

                // --- Fonction pour gérer la suppression ---
                function setupRemoveButton(formConfigDiv) {
                    const removeButton = formConfigDiv.querySelector('.wp-sio-remove-divi-form');
                    if (removeButton) {
                         // Enlever les anciens listeners pour éviter les doublons si ré-initialisé
                         removeButton.replaceWith(removeButton.cloneNode(true));
                         // Récupérer le nouveau bouton cloné pour attacher l'event
                         const newRemoveButton = formConfigDiv.querySelector('.wp-sio-remove-divi-form');
                         if (!newRemoveButton) return;

                        newRemoveButton.addEventListener('click', function (e) {
                            e.preventDefault();
                             console.log('WP SIO Connect: Remove button clicked for row:', formConfigDiv);
                            if (confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir supprimer la configuration de ce formulaire Divi ?', 'wp-systemio-connect')); ?>')) {
                                formConfigDiv.remove();
                                if (container.querySelectorAll('.wp-sio-divi-form-config').length === 0) {
                                    console.log('WP SIO Connect: No rows left, adding empty one.');
                                    addFormRow();
                                }
                            }
                        });
                    } else {
                        console.warn('WP SIO Connect: Remove button (.wp-sio-remove-divi-form) not found in row:', formConfigDiv);
                    }
                }

                // --- Fonction pour synchroniser l'ID CSS dans les attributs name ---
                function setupCssIdSync(formConfigDiv) {
                    const cssIdInput = formConfigDiv.querySelector('.wp-sio-divi-css-id-input');
                    if (!cssIdInput) { console.warn('WP SIO Connect: CSS ID input (.wp-sio-divi-css-id-input) not found in row:', formConfigDiv); return; }

                     // Enlever les anciens listeners pour éviter les doublons
                    cssIdInput.replaceWith(cssIdInput.cloneNode(true));
                    const newCssIdInput = formConfigDiv.querySelector('.wp-sio-divi-css-id-input');
                    if (!newCssIdInput) return;


                    newCssIdInput.addEventListener('input', function () {
                        let newCssId = this.value.trim();
                        newCssId = newCssId.replace(/[^a-zA-Z0-9_-]/g, '');
                        if (newCssId.length > 0 && !/^[a-zA-Z_]/.test(newCssId)) {
                            newCssId = '_' + newCssId;
                        }

                        const currentConfigDiv = this.closest('.wp-sio-divi-form-config');
                        const inputsToUpdate = currentConfigDiv.querySelectorAll('[name^="wp_systemio_connect_options[divi_integrations"]');
                        const currentKey = currentConfigDiv.dataset.index || '__INDEX__'; // Clé actuelle

                         console.log(`WP SIO Connect: Syncing CSS ID. New ID: '${newCssId}', Current Key: '${currentKey}'`);

                        const replacementKey = newCssId || currentKey; // Garder l'index si l'ID devient vide ? Ou utiliser un nouveau __INDEX__? Gardons l'index pour la stabilité pendant la saisie.

                        inputsToUpdate.forEach(input => {
                            const nameAttr = input.getAttribute('name');
                            if (nameAttr) {
                                const regex = new RegExp(`\\[divi_integrations\\]\\[${currentKey.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')}\\]`);
                                const newName = nameAttr.replace(regex, `[divi_integrations][${replacementKey}]`);
                                input.setAttribute('name', newName);
                            }
                        });
                        currentConfigDiv.dataset.index = replacementKey; // Mettre à jour le data-index aussi
                    });
                }


                // --- Fonction pour ajouter une nouvelle ligne ---
                function addFormRow() {
                    console.log('WP SIO Connect: Adding new Divi form row...');
                    const clone = template.content.cloneNode(true);
                    const newIndex = '__INDEX__' + Date.now();
                    const newRow = clone.querySelector('.wp-sio-divi-form-config');

                    if (!newRow) { console.error('WP SIO Connect: Could not find .wp-sio-divi-form-config in template clone.'); return; }

                    // Itérer sur tous les éléments avec __INDEX__ et le remplacer
                    newRow.querySelectorAll('[id*="__INDEX__"], [name*="__INDEX__"], [for*="__INDEX__"]').forEach(el => {
                        if (el.id) el.id = el.id.replace(/__INDEX__/g, newIndex);
                        if (el.name) el.name = el.name.replace(/__INDEX__/g, newIndex);
                        if (el.htmlFor) el.htmlFor = el.htmlFor.replace(/__INDEX__/g, newIndex);
                    });
                     newRow.dataset.index = newIndex; // Mettre à jour l'attribut data-index

                    container.appendChild(newRow);
                    console.log('WP SIO Connect: New row added, initializing it:', newRow);

                    // Initialiser les scripts sur la nouvelle ligne
                    initializeFormRow(newRow);
                }

                // --- Initialisation ---
                addButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    addFormRow();
                });

                // Initialiser les lignes existantes au chargement
                console.log('WP SIO Connect: Initializing existing Divi rows...');
                container.querySelectorAll('.wp-sio-divi-form-config').forEach(row => {
                    initializeFormRow(row);
                });
                console.log('WP SIO Connect: Divi Repeater JS Initialized.');

            });
        </script>
        <?php
    }

    // Dans WP_Systemio_Connect_Admin
    /**
     * Affiche le formulaire dédié pour ajouter un nouveau contact SIO.
     */
    private static function render_contact_form()
    {
        $page_title = __('Ajouter un nouveau contact Systeme.io', 'wp-systemio-connect');
        $submit_button_text = __('Ajouter le Contact', 'wp-systemio-connect');
        $action_nonce = 'wp_sio_add_contact_nonce';
        $action_name = 'wp_sio_add_contact'; // Action pour admin-post

        ?>
        <h3><?php echo esc_html($page_title); ?></h3>
        <p><?php esc_html_e('Entrez les informations du contact et sélectionnez les tags à assigner.', 'wp-systemio-connect'); ?>
        </p>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php // Champs cachés pour la sécurité et l'action ?>
            <input type="hidden" name="action" value="<?php echo esc_attr($action_name); ?>">
            <?php wp_nonce_field($action_nonce, 'wp_sio_contact_nonce'); ?>

            <table class="form-table">
                <tbody>
                    <tr class="form-field form-required">
                        <th scope="row">
                            <label for="sio_contact_email"><?php _e('Email', 'wp-systemio-connect'); ?> <span
                                    class="description">(requis)</span></label>
                        </th>
                        <td>
                            <input type="email" id="sio_contact_email" name="sio_contact[email]" value="" required
                                aria-required="true" class="regular-text">
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row">
                            <label for="sio_contact_fname"><?php _e('Prénom', 'wp-systemio-connect'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="sio_contact_fname" name="sio_contact[first_name]" value=""
                                class="regular-text">
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row">
                            <label for="sio_contact_lname"><?php _e('Nom', 'wp-systemio-connect'); ?></label>
                        </th>
                        <td>
                            <input type="text" id="sio_contact_lname" name="sio_contact[last_name]" value=""
                                class="regular-text">
                        </td>
                    </tr>
                    <tr class="form-field">
                        <th scope="row">
                            <?php _e('Assigner des Tags', 'wp-systemio-connect'); ?>
                        </th>
                        <td>
                            <?php
                            // Récupérer les tags disponibles via la méthode de la classe Admin (ou Service API si on l'y déplace)
                            $available_tags = self::get_systemio_tags(); // Ou $api_service->get_tags();
                    
                            if (is_wp_error($available_tags)) {
                                echo '<p class="notice notice-warning inline" style="margin-left: 0;">' . sprintf(esc_html__('Erreur de récupération des tags : %s', 'wp-systemio-connect'), esc_html($available_tags->get_error_message())) . '</p>';
                            } elseif (empty($available_tags)) {
                                echo '<p>' . __('Aucun tag trouvé dans votre compte Systeme.io.', 'wp-systemio-connect') . '</p>';
                            } else {
                                echo '<div class="wp-sio-tags-checkbox-list" style="max-height: 200px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #fff;">';
                                // Pas besoin de champ caché ici car si rien n'est coché, le tableau 'tags' ne sera pas envoyé, ce qui est ok.
                                foreach ($available_tags as $tag_id => $tag_name) {
                                    $checkbox_id = 'sio_contact_tag_' . $tag_id;
                                    ?>
                                    <label for="<?php echo esc_attr($checkbox_id); ?>"
                                        style="display: block; margin-bottom: 5px; font-weight: normal;">
                                        <input type="checkbox" id="<?php echo esc_attr($checkbox_id); ?>" name="sio_contact[tags][]"
                                            <?php // Envoyer comme un tableau d'IDs ?> value="<?php echo esc_attr($tag_id); ?>">
                                        <?php echo esc_html($tag_name); ?> (ID: <?php echo esc_html($tag_id); ?>)
                                    </label>
                                    <?php
                                }
                                echo '</div>'; // .wp-sio-tags-checkbox-list
                                echo '<p class="description">' . __('Cochez les tags à assigner à ce nouveau contact.', 'wp-systemio-connect') . '</p>';
                            }
                            ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php submit_button($submit_button_text); ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wp-systemio-connect&tab=contacts')); ?>"
                class="button button-secondary"><?php _e('Annuler', 'wp-systemio-connect'); ?></a>

        </form>
        <?php
    }

    // Dans WP_Systemio_Connect_Admin
    /**
     * Gère la soumission du formulaire d'ajout de contact.
     */
    public static function handle_add_contact()
    {
        error_log("[WP SIO Connect Admin] Contact created/updated. Adding ");

        // 1. Nonce & Permissions Check (comme avant)
        if (!isset($_POST['wp_sio_contact_nonce']) || !wp_verify_nonce($_POST['wp_sio_contact_nonce'], 'wp_sio_add_contact_nonce')) {
            wp_die( /*...*/);
        }
        if (!current_user_can('manage_options')) {
            wp_die( /*...*/);
        }

        // 3. Récupérer et valider les données
        $contact_data = isset($_POST['sio_contact']) && is_array($_POST['sio_contact']) ? $_POST['sio_contact'] : [];
        $email = isset($contact_data['email']) ? sanitize_email(trim($contact_data['email'])) : ''; // trim() ajouté
        $first_name = isset($contact_data['first_name']) ? sanitize_text_field(trim($contact_data['first_name'])) : ''; // trim() ajouté
        $last_name = isset($contact_data['last_name']) ? sanitize_text_field(trim($contact_data['last_name'])) : ''; // trim() ajouté
        // Récupérer les tags sélectionnés
        $selected_tags = isset($contact_data['tags']) && is_array($contact_data['tags']) ? $contact_data['tags'] : [];
        // Nettoyer les IDs de tags (s'assurer que ce sont des entiers)
        $selected_tags = array_filter(array_map('absint', $selected_tags));

        // Valider l'email
        if (empty($email) || !is_email($email)) {
            wp_redirect(admin_url('admin.php?page=wp-systemio-connect&tab=contacts&action=add_new&message=add_error_email')); // Rediriger vers le formulaire avec erreur
            exit;
        }

        // 4. Appeler le service API
        $api_service = new WP_Systemio_Connect_Api_Service();
        if (!$api_service || !method_exists($api_service, 'add_or_update_contact') || !method_exists($api_service, 'tag_contact')) {
            error_log('[WP SIO Connect Admin] API Service not available or methods missing.');
            wp_redirect(admin_url('admin.php?page=wp-systemio-connect&tab=contacts&message=add_error_internal')); // Erreur interne
            exit;
        }

        $contact_id = $api_service->add_or_update_contact($email, $first_name, $last_name);

        // 5. Gérer le résultat
        $redirect_url = admin_url('admin.php?page=wp-systemio-connect&tab=contacts');

        if ($contact_id !== false) {
            // Succès création/màj contact ! Maintenant, ajouter les tags.
            $tags_added_successfully = true; // Supposer le succès initialement
            if (!empty($selected_tags)) {
                error_log("[WP SIO Connect Admin] Contact $contact_id created/updated. Adding " . count($selected_tags) . " tags.");
                foreach ($selected_tags as $tag_id) {
                    $tag_success = $api_service->tag_contact($contact_id, $tag_id);
                    if (!$tag_success) {
                        $tags_added_successfully = false; // Marquer si un tag échoue
                        error_log("[WP SIO Connect Admin] Failed to add tag $tag_id to contact $contact_id.");
                        // Continuer d'essayer les autres tags ? Oui.
                    }
                }
            }

            // Adapter le message de redirection
            if ($tags_added_successfully) {
                $redirect_url = add_query_arg('message', 'contact_added_tags_ok', $redirect_url);
            } else {
                $redirect_url = add_query_arg('message', 'contact_added_tags_error', $redirect_url); // Succès partiel
            }

        } else {
            // Échec création/màj contact
            $redirect_url = add_query_arg('message', 'add_error_api', $redirect_url);
        }

        wp_redirect($redirect_url);
        exit;
    }

    // Ne pas oublier l'action admin_post pour cette fonction :
    // add_action( 'admin_post_wp_sio_add_contact', [ 'WP_Systemio_Connect_Admin', 'handle_add_contact' ] );

} // Fin de la classe WP_Systemio_Connect_Admin



// Hook pour l'action admin-post déclenchée par le bouton de test
add_action('admin_post_wp_systemio_connect_test_connection', ['WP_Systemio_Connect_Admin', 'handle_test_connection']);

// Dans wp-systemio-connect.php (ou WP_Systemio_Connect_Admin::init())
add_action('admin_post_wp_sio_add_contact', ['WP_Systemio_Connect_Admin', 'handle_add_contact']);
?>