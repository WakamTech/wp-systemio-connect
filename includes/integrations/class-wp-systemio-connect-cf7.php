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
        // Récupérer le tableau de tags
        $tag_ids = isset($form_settings['tags']) && is_array($form_settings['tags']) ? $form_settings['tags'] : []; // <-- MODIFIÉ

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

        // --- Nouvelle Logique d'Envoi via le Service API ---
        $api_service = new WP_Systemio_Connect_Api_Service(); // Instancier le service

        if (!$api_service) { // Gérer si le constructeur a échoué (Admin non chargé?)
            error_log("[WP SIO Connect Divi] Failed to initialize API Service.");
            return;
        }

        // 1. Ajouter ou Mettre à jour le contact
        error_log("[WP SIO Connect Divi] Calling add_or_update_contact for email: " . $email);
        $contact_id = $api_service->add_or_update_contact($email, $first_name, $last_name);

        // 2. Si succès et si des tags sont sélectionnés, les ajouter un par un
        if ($contact_id !== false && !empty($tag_ids)) {
            error_log("[WP SIO Connect Divi] Contact processed (ID: $contact_id). Attempting to add " . count($tag_ids) . " tags.");
            foreach ($tag_ids as $tag_to_add) {
                $tag_id_int = absint($tag_to_add);
                if ($tag_id_int > 0) {
                    error_log("[WP SIO Connect Divi] Calling tag_contact for Contact ID: $contact_id, Tag ID: $tag_id_int");
                    $tag_success = $api_service->tag_contact($contact_id, $tag_id_int);
                    // On pourrait logguer si $tag_success est false
                    if (!$tag_success) {
                        error_log("[WP SIO Connect Divi] Failed to add tag ID: $tag_id_int to contact ID: $contact_id.");
                    }
                }
            }
        } elseif ($contact_id === false) {
            error_log("[WP SIO Connect Divi] Failed to add or update contact: " . $email);
        } else {
            error_log("[WP SIO Connect Divi] Contact processed (ID: $contact_id). No tags selected.");
        }
    }

} // Fin de la classe WP_Systemio_Connect_CF7
?>