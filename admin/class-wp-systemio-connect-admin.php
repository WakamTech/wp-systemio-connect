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
        add_action('admin_menu', [__CLASS__, 'add_options_page']);

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
        $settings_link = '<a href="' . admin_url('options-general.php?page=wp-systemio-connect') . '">' . __('Réglages', 'wp-systemio-connect') . '</a>';
        array_unshift($links, $settings_link); // Ajoute au début du tableau
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
     * Nettoie les options avant de les sauvegarder dans la base de données.
     * Très important pour la sécurité !
     */
    public static function sanitize_options($input)
    {
        $sanitized_options = [];

        if (isset($input['api_key'])) {
            // Ne pas utiliser sanitize_text_field qui pourrait casser la clé.
            // Utiliser wp_kses avec un tableau vide pour supprimer tout HTML/JS potentiel,
            // mais garder les caractères de la clé. trim() enlève les espaces avant/après.
            $sanitized_options['api_key'] = trim(wp_kses($input['api_key'], []));
        }

        if (isset($input['api_base_url'])) {
            // Valider que c'est une URL, sinon remettre la valeur par défaut ou vider ?
            $url = esc_url_raw(trim($input['api_base_url']));
            // Si l'URL n'est pas valide ou est vide, on peut remettre la valeur par défaut
            if (empty($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
                $sanitized_options['api_base_url'] = 'https://api.systeme.io/api';
            } else {
                $sanitized_options['api_base_url'] = $url;
            }
        }

        return $sanitized_options;
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
            $tags = isset($settings['tags']) ? $settings['tags'] : ''; // IDs de tags SIO, séparés par virgule

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
                        <tr class="wp-sio-cf7-conditional-fields" style="<?php echo $enabled ? '' : 'display:none;'; ?>">
                            <th scope="row">
                                <label for="wp_sio_cf7_<?php echo $form_id; ?>_tags">
                                    <?php _e('Tags Systeme.io', 'wp-systemio-connect'); ?>
                                </label>
                            </th>
                            <td>
                                <input type="text" id="wp_sio_cf7_<?php echo $form_id; ?>_tags"
                                    name="wp_systemio_connect_options[cf7_integrations][<?php echo $form_id; ?>][tags]"
                                    value="<?php echo esc_attr($tags); ?>" class="regular-text" placeholder="123, 456">
                                <p class="description">
                                    <?php _e('IDs des tags SIO à ajouter au contact, séparés par des virgules (ex: 123, 456). Laisser vide si aucun tag.', 'wp-systemio-connect'); ?>
                                </p>
                                <?php // TODO: Ajouter un bouton "Récupérer les tags via API" plus tard ?>
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
} // Fin de la classe WP_Systemio_Connect_Admin



// Hook pour l'action admin-post déclenchée par le bouton de test
add_action('admin_post_wp_systemio_connect_test_connection', ['WP_Systemio_Connect_Admin', 'handle_test_connection']);

?>