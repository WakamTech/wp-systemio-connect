<?php
// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Gère l'intégration avec Contact Form 7.
 */
class WP_Systemio_Connect_CF7
{

    /**
     * Initialise les hooks pour CF7 si le plugin est actif.
     */
    public static function init()
    {
        // Vérifier si Contact Form 7 est actif avant d'ajouter le hook
        if (defined('WPCF7_VERSION')) {
            add_action('wpcf7_before_send_mail', [__CLASS__, 'handle_submission'], 10, 3);
            // Le hook wpcf7_before_send_mail passe 3 arguments par défaut dans les versions récentes
        }
    }

    /**
     * Fonction appelée après la soumission réussie d'un formulaire CF7.
     *
     * @param WPCF7_ContactForm $contact_form L'objet du formulaire de contact.
     * @param bool              $abort        Référence booléenne. Mettre à true pour arrêter l'envoi de mail CF7.
     * @param WPCF7_Submission  $submission   L'objet de soumission contenant les données.
     */
    public static function handle_submission($contact_form, &$abort, $submission)
    {
        // Récupérer la clé API et l'URL de base
        $api_key = WP_Systemio_Connect_Admin::get_api_key();
        $api_base_url = WP_Systemio_Connect_Admin::get_api_base_url();

        // Si la clé API n'est pas configurée, on ne fait rien
        if (empty($api_key) || empty($api_base_url)) {
            // Pas besoin de log ici, car on va vérifier par formulaire
            return;
        }

        // --- Récupérer la configuration spécifique à CE formulaire ---
        $form_id = $contact_form->id();
        $options = get_option('wp_systemio_connect_options');
        $form_settings = isset($options['cf7_integrations'][$form_id]) ? $options['cf7_integrations'][$form_id] : null;

        // Vérifier si l'intégration est activée pour ce formulaire
        if (!$form_settings || empty($form_settings['enabled']) || empty($form_settings['email_field'])) {
            // Log si on veut savoir pourquoi on n'envoie pas
            // error_log("[WP SIO Connect] Intégration CF7 désactivée ou champ email non mappé pour le formulaire ID: $form_id.");
            return; // Sortir si non activé ou champ email non défini
        }

        // Récupérer les noms des champs mappés et les tags
        $email_field_name = $form_settings['email_field'];
        $fname_field_name = isset($form_settings['fname_field']) ? $form_settings['fname_field'] : '';
        $lname_field_name = isset($form_settings['lname_field']) ? $form_settings['lname_field'] : '';
        $tags_string = isset($form_settings['tags']) ? $form_settings['tags'] : '';

        // Récupérer les données soumises
        $posted_data = $submission->get_posted_data();

        // --- Extraire les données en utilisant le mapping ---
        $email = '';
        $first_name = '';
        $last_name = '';

        // Extraire l'email (champ obligatoire)
        if (isset($posted_data[$email_field_name]) && is_email($posted_data[$email_field_name])) {
            $email = sanitize_email($posted_data[$email_field_name]);
        } else {
            error_log("[WP SIO Connect] Champ email mappé '$email_field_name' non trouvé ou invalide dans la soumission CF7 ID: $form_id.");
            return; // Impossible d'envoyer sans email valide
        }

        // Extraire le prénom (si mappé et trouvé)
        if (!empty($fname_field_name) && isset($posted_data[$fname_field_name])) {
            $first_name = sanitize_text_field(trim($posted_data[$fname_field_name]));
        }

        // Extraire le nom (si mappé et trouvé)
        if (!empty($lname_field_name) && isset($posted_data[$lname_field_name])) {
            $last_name = sanitize_text_field(trim($posted_data[$lname_field_name]));
        }

        // --- Préparation de l'appel API SIO ---
        $api_endpoint = $api_base_url . '/contacts';
        $contact_data = [
            'email' => $email,
        ];
        if (!empty($first_name)) {
            $contact_data['firstName'] = $first_name;
        }
        if (!empty($last_name)) {
            $contact_data['lastName'] = $last_name;
        }

        // Traiter les tags SIO
        if (!empty($tags_string)) {
            // Convertir la chaîne "123, 456" en tableau d'entiers [123, 456]
            $tag_ids = array_map('absint', explode(',', $tags_string));
            $tag_ids = array_filter($tag_ids); // Enlever les zéros ou invalides
            if (!empty($tag_ids)) {
                // Vérifier la documentation de l'API SIO /contacts : comment passer les tags ?
                // Hypothèse : un tableau d'IDs sous la clé 'tags'
                $contact_data['tags'] = $tag_ids;
            }
        }

        $body_json = json_encode($contact_data);

        if ($body_json === false) {
            error_log('[WP SIO Connect] Erreur encodage JSON pour données contact SIO (Form ID: ' . $form_id . ').');
            return;
        }

        // Définir les arguments pour wp_remote_post (idem qu'avant)
        $args = [
            'method' => 'POST',
            'headers' => [ /* ... */],
            'body' => $body_json,
            'timeout' => 15,
        ];
        // Ne pas oublier de remettre les headers X-API-Key, Accept, Content-Type
        $args['headers'] = [
            'X-API-Key' => $api_key,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];


        // --- Exécution de l'appel API ---
        error_log('[WP SIO Connect] Tentative d\'ajout contact SIO depuis CF7 ID: ' . $form_id . ' - Email: ' . $email);
        $response = wp_remote_post($api_endpoint, $args);

        // --- Gestion de la réponse (idem qu'avant, mais avec log de l'ID) ---
        if (is_wp_error($response)) {
            error_log('[WP SIO Connect] Erreur WP API SIO (CF7 ID: ' . $form_id . ') : ' . $response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            if ($response_code >= 200 && $response_code < 300) {
                error_log('[WP SIO Connect] Succès API SIO (CF7 ID: ' . $form_id . ') : Contact ' . $email . ' ajouté/mis à jour. Code: ' . $response_code);
            } else {
                error_log('[WP SIO Connect] Erreur API SIO (CF7 ID: ' . $form_id . ') : Code ' . $response_code . ' - Réponse : ' . $response_body);
            }
        }
    }

} // Fin de la classe WP_Systemio_Connect_CF7
?>