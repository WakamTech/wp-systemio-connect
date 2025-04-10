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
    public static function add_settings_link( $links ) {
        // Pointer vers la nouvelle page principale (l'onglet par défaut sera chargé)
        $settings_link = '<a href="' . admin_url( 'admin.php?page=wp-systemio-connect' ) . '">' . __( 'Réglages', 'wp-systemio-connect' ) . '</a>';
        array_unshift( $links, $settings_link );
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
        // 1. Enregistrer le groupe d'options principal
        register_setting(
            'wp_systemio_connect_options_group', // Nom du groupe (utilisé dans settings_fields())
            'wp_systemio_connect_options',       // Nom de l'option dans la BDD (wp_options table)
            [__CLASS__, 'sanitize_options']    // Fonction de nettoyage avant sauvegarde (IMPORTANT)
        );

        // 2. Ajouter une section de réglages
        add_settings_section(
            'wp_systemio_connect_section_api', // ID unique de la section
            __('Connexion API Systeme.io', 'wp-systemio-connect'), // Titre de la section
            [__CLASS__, 'render_section_api_description'], // Fonction affichant une description (optionnel)
            'wp-systemio-connect' // Slug de la page où afficher cette section
        );

        // 3. Ajouter le champ pour la clé API
        add_settings_field(
            'api_key', // ID unique du champ
            __('Clé API Systeme.io', 'wp-systemio-connect'), // Label du champ
            [__CLASS__, 'render_field_api_key'], // Fonction qui affiche le champ (<input>)
            'wp-systemio-connect', // Slug de la page
            'wp_systemio_connect_section_api', // ID de la section où placer ce champ
            ['label_for' => 'wp_systemio_connect_api_key'] // Associe le label au champ pour l'accessibilité
        );

        // 4. Ajouter le champ pour l'URL de base de SIO (utile si ça change un jour)
        add_settings_field(
            'api_base_url', // ID unique du champ
            __('URL API Systeme.io', 'wp-systemio-connect'), // Label du champ
            [__CLASS__, 'render_field_api_base_url'], // Fonction qui affiche le champ (<input>)
            'wp-systemio-connect', // Slug de la page
            'wp_systemio_connect_section_api', // ID de la section où placer ce champ
            ['label_for' => 'wp_systemio_connect_api_base_url'] // Associe le label au champ pour l'accessibilité
        );

        // 5. --- NOUVELLE SECTION : Intégration CF7 ---
        //    On l'affiche seulement si CF7 est actif
        if (defined('WPCF7_VERSION')) {
            add_settings_section(
                'wp_systemio_connect_section_cf7', // ID unique
                __('Intégration Contact Form 7', 'wp-systemio-connect'), // Titre
                [__CLASS__, 'render_section_cf7_description'], // Callback pour description
                'wp-systemio-connect' // Page où afficher
            );

            // 4. --- NOUVEAU CHAMP : Réglages par formulaire CF7 ---
            //    Ce champ unique va afficher les réglages pour TOUS les formulaires CF7
            add_settings_field(
                'cf7_form_settings', // ID unique du champ (logique)
                __('Configuration des Formulaires', 'wp-systemio-connect'), // Label principal
                [__CLASS__, 'render_field_cf7_form_settings'], // Callback qui affichera la liste
                'wp-systemio-connect', // Page où afficher
                'wp_systemio_connect_section_cf7' // Section où afficher
            );
        }

        // --- NOUVELLE SECTION : Intégration Elementor Pro ---
        //    On l'affiche seulement si Elementor Pro est actif
        if (class_exists('\ElementorPro\Modules\Forms\Classes\Form_Record')) {
            add_settings_section(
                'wp_systemio_connect_section_elementor', // ID unique
                __('Intégration Elementor Pro Forms', 'wp-systemio-connect'), // Titre
                [__CLASS__, 'render_section_elementor_description'], // Callback description
                'wp-systemio-connect' // Page
            );

            // --- NOUVEAU CHAMP : Réglages des formulaires Elementor ---
            add_settings_field(
                'elementor_form_settings', // ID logique
                __('Configuration des Formulaires', 'wp-systemio-connect'), // Label
                [__CLASS__, 'render_field_elementor_form_settings'], // Callback
                'wp-systemio-connect', // Page
                'wp_systemio_connect_section_elementor' // Section
            );
        }

        // --- NOUVELLE SECTION : Intégration Divi ---
        if (function_exists('et_builder_add_main_elements') || defined('ET_BUILDER_THEME')) { // Autre test pour Divi
            add_settings_section(
                'wp_systemio_connect_section_divi', // ID unique
                __('Intégration Divi (Formulaire de Contact)', 'wp-systemio-connect'), // Titre
                [__CLASS__, 'render_section_divi_description'], // Callback description
                'wp-systemio-connect' // Page
            );

            // --- NOUVEAU CHAMP : Réglages des formulaires Divi ---
            add_settings_field(
                'divi_form_settings', // ID logique
                __('Configuration des Formulaires', 'wp-systemio-connect'), // Label
                [__CLASS__, 'render_field_divi_form_settings'], // Callback
                'wp-systemio-connect', // Page
                'wp_systemio_connect_section_divi' // Section
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
        echo '<h2>' . esc_html__('Configuration des Formulaires', 'wp-systemio-connect') . '</h2>';
        echo '<p>' . esc_html__('Le contenu de la configuration des formulaires (CF7, Elementor, Divi) ira ici.', 'wp-systemio-connect') . '</p>';
        // On déplacera la logique de rendu et de sauvegarde ici plus tard
    }

    public static function render_contacts_tab()
    {
        echo '<h2>' . esc_html__('Gestion des Contacts Systeme.io', 'wp-systemio-connect') . '</h2>';
        echo '<p>' . esc_html__('La liste des contacts SIO sera affichée ici.', 'wp-systemio-connect') . '</p>';
        // Implémentation future
    }

    public static function render_tags_tab()
    {
        echo '<h2>' . esc_html__('Gestion des Tags Systeme.io', 'wp-systemio-connect') . '</h2>';
        echo '<p>' . esc_html__('La liste des tags SIO sera affichée ici.', 'wp-systemio-connect') . '</p>';
        // Implémentation future
    }

    public static function render_settings_tab()
    {
        echo '<h2>' . esc_html__('Réglages de Connexion API Systeme.io', 'wp-systemio-connect') . '</h2>';
        echo '<p>' . esc_html__('Les champs Clé API, URL API et le bouton Test iront ici.', 'wp-systemio-connect') . '</p>';
        // On déplacera la logique de rendu et de sauvegarde ici plus tard
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
        $redirect_url = admin_url('options-general.php?page=wp-systemio-connect');
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
        // $input contient toutes les données soumises par le formulaire de réglages
        $sanitized_options = [];

        // --- Nettoyage des options API (comme avant) ---
        if (isset($input['api_key'])) {
            $sanitized_options['api_key'] = trim(wp_kses($input['api_key'], []));
        }
        if (isset($input['api_base_url'])) {
            $url = esc_url_raw(trim($input['api_base_url']));
            if (empty($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
                $sanitized_options['api_base_url'] = 'https://api.systeme.io/api';
            } else {
                $sanitized_options['api_base_url'] = $url;
            }
        }

        // --- NOUVEAU: Nettoyage des options d'intégration CF7 ---
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
        }

        // --- NOUVEAU: Nettoyage des options d'intégration Elementor ---
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
        }


        // --- NOUVEAU: Nettoyage Intégration Divi ---
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
        }


        return $sanitized_options;
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
     */
    private static function add_divi_repeater_js()
    {
        ?>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.getElementById('wp-sio-divi-forms-container');
                const template = document.getElementById('wp-sio-divi-form-row-template');
                const addButton = document.getElementById('wp-sio-add-divi-form');

                if (!container || !template || !addButton) {
                    console.error('WP SIO Connect: Divi repeater JS elements not found.');
                    return;
                }

                // --- Fonction pour gérer l'affichage conditionnel ---
                function setupConditionalDisplay(formConfigDiv) {
                    const checkbox = formConfigDiv.querySelector('.wp-sio-divi-enable-checkbox');
                    const conditionalFields = formConfigDiv.querySelectorAll('.wp-sio-divi-conditional-fields');

                    function toggleFields() {
                        conditionalFields.forEach(fieldRow => {
                            fieldRow.style.display = checkbox.checked ? '' : 'none';
                        });
                    }
                    if (checkbox) {
                        toggleFields(); // État initial
                        checkbox.addEventListener('change', toggleFields);
                    } else {
                        console.warn('WP SIO Connect: Enable checkbox not found in Divi row', formConfigDiv);
                    }
                }

                // --- Fonction pour gérer la suppression ---
                function setupRemoveButton(formConfigDiv) {
                    const removeButton = formConfigDiv.querySelector('.wp-sio-remove-divi-form');
                    if (removeButton) {
                        removeButton.addEventListener('click', function (e) {
                            e.preventDefault();
                            if (confirm('<?php echo esc_js(__('Êtes-vous sûr de vouloir supprimer la configuration de ce formulaire Divi ?', 'wp-systemio-connect')); ?>')) {
                                formConfigDiv.remove();
                                // S'assurer qu'il reste au moins une ligne (peut-être vide) pour l'ajout
                                if (container.querySelectorAll('.wp-sio-divi-form-config').length === 0) {
                                    addFormRow(); // Ajouter une nouvelle ligne vide si tout est supprimé
                                }
                            }
                        });
                    } else {
                        console.warn('WP SIO Connect: Remove button not found in Divi row', formConfigDiv);
                    }
                }

                // --- Fonction pour synchroniser l'ID CSS dans les attributs name ---
                function setupCssIdSync(formConfigDiv) {
                    const cssIdInput = formConfigDiv.querySelector('.wp-sio-divi-css-id-input');
                    if (!cssIdInput) {
                        console.warn('WP SIO Connect: CSS ID input not found in Divi row', formConfigDiv);
                        return;
                    }

                    cssIdInput.addEventListener('input', function () {
                        // Nettoyer l'ID pour usage comme clé et attribut
                        // Autorise lettres, chiffres, tiret, underscore. Commence par une lettre ou underscore par sécurité pour CSS.
                        let newCssId = this.value.trim();
                        newCssId = newCssId.replace(/[^a-zA-Z0-9_-]/g, '');
                        if (newCssId.length > 0 && ! /^[a-zA-Z_]/.test(newCssId)) {
                            newCssId = '_' + newCssId; // Préfixer si ne commence pas par lettre/underscore
                        }

                        const currentConfigDiv = this.closest('.wp-sio-divi-form-config');
                        const inputsToUpdate = currentConfigDiv.querySelectorAll('[name^="wp_systemio_connect_options[divi_integrations"]');
                        // ... calcul de newCssId (ou newFormName/newFormId) ...
                        const replacementKey = newCssId || ('__INDEX__' + Date.now()); // Utilise un index unique si vide
                        currentConfigDiv.dataset.index = replacementKey;
                        const currentKey = currentConfigDiv.dataset.index; // L'index actuel ou temporaire

                        inputsToUpdate.forEach(input => {
                            const nameAttr = input.getAttribute('name');
                            if (nameAttr) {
                                // Remplacer l'index/clé actuelle par le nouvel ID CSS (ou __INDEX__ si vide)
                                // Regex pour cibler la clé spécifique à cette ligne
                                const regex = new RegExp(`\\[divi_integrations\\]\\[${currentKey.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&')}\\]`); // Échapper les caractères spéciaux dans la clé actuelle
                                const replacementKey = newCssId || '__INDEX__';
                                const newName = nameAttr.replace(regex, `[divi_integrations][${replacementKey}]`);
                                input.setAttribute('name', newName);
                            }
                        });
                        // Mettre à jour aussi l'index data
                        // ... calcul de newCssId (ou newFormName/newFormId) ...
                        const replacementKey = newCssId || ('__INDEX__' + Date.now()); // Utilise un index unique si vide
                        // ... remplacer la clé dans les attributs name en utilisant replacementKey ...
                        currentConfigDiv.dataset.index = replacementKey;
                        // On pourrait aussi vouloir mettre à jour les ID des champs et les 'for' des labels, mais c'est plus complexe et moins critique pour la sauvegarde
                    });
                }


                // --- Fonction pour ajouter une nouvelle ligne ---
                function addFormRow() {
                    const clone = template.content.cloneNode(true);
                    const newIndex = '__INDEX__' + Date.now(); // Utiliser un préfixe et timestamp comme index temporaire unique
                    const newRow = clone.querySelector('.wp-sio-divi-form-config');

                    if (!newRow) {
                        console.error('WP SIO Connect: Could not find .wp-sio-divi-form-config in template clone.');
                        return;
                    }

                    // Remplacer __INDEX__ par le nouvel index unique dans le HTML cloné
                    // Faire la substitution sur les attributs pertinents plutôt que innerHTML pour préserver les events potentiels
                    newRow.innerHTML = newRow.innerHTML.replace(/__INDEX__/g, newIndex); // Simple pour cet exemple
                    newRow.dataset.index = newIndex; // Mettre à jour l'attribut data-index

                    container.appendChild(newRow);

                    // Ré-appliquer les gestionnaires d'événements sur la nouvelle ligne clonée et ajoutée
                    const addedRowElement = container.querySelector(`[data-index="${newIndex}"]`);
                    if (addedRowElement) {
                        setupConditionalDisplay(addedRowElement);
                        setupRemoveButton(addedRowElement);
                        setupCssIdSync(addedRowElement);
                    } else {
                        console.error('WP SIO Connect: Could not find the newly added Divi row element.');
                    }
                }

                // --- Initialisation ---

                // Gérer l'ajout
                addButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    addFormRow();
                });

                // Appliquer les gestionnaires aux lignes existantes au chargement
                container.querySelectorAll('.wp-sio-divi-form-config').forEach(row => {
                    setupConditionalDisplay(row);
                    setupRemoveButton(row);
                    setupCssIdSync(row);
                });

            });
        </script>
        <?php
    }

} // Fin de la classe WP_Systemio_Connect_Admin



// Hook pour l'action admin-post déclenchée par le bouton de test
add_action('admin_post_wp_systemio_connect_test_connection', ['WP_Systemio_Connect_Admin', 'handle_test_connection']);

?>