<?php
// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Gère l'intégration avec le module Formulaire de Contact de Divi.
 */
class WP_Systemio_Connect_Divi
{

    /**
     * Initialise les hooks pour Divi si le thème/builder est actif.
     */
    public static function init()
    {
        error_log('[WP SIO Connect Divi] Initialisation...');

        $is_divi_theme = defined('ET_BUILDER_THEME');
        $is_divi_builder_plugin = function_exists('et_maybe_enable_classic_editor');

        error_log('[WP SIO Connect Divi] is_divi_theme: ' . ($is_divi_theme ? 'yes' : 'no'));
        error_log('[WP SIO Connect Divi] is_divi_builder_plugin: ' . ($is_divi_builder_plugin ? 'yes' : 'no'));

        if ($is_divi_theme || $is_divi_builder_plugin) {
            error_log('[WP SIO Connect Divi] Divi detected. Adding hook.');
            add_action('et_pb_contact_form_submit', [__CLASS__, 'handle_submission'], 10, 3);
        } else {
            error_log('[WP SIO Connect Divi] Divi NOT detected.');
        }
    }

    /**
     * Fonction appelée lors de la soumission d'un formulaire de contact Divi.
     *
     * Les arguments exacts de ce hook doivent être vérifiés.
     * Hypothèse basée sur des recherches :
     * @param array $processed_fields Tableau des champs traités [field_id => value].
     * @param bool  $et_contact_error Indique si une erreur a eu lieu avant notre hook.
     * @param array $contact_form_info Infos sur le formulaire (peut contenir l'ID CSS ?).
     */
    public static function handle_submission($processed_fields, $et_contact_error, $contact_form_info)
    {
        error_log('[WP SIO Connect Divi] handle_submission triggered.');
        error_log('[WP SIO Connect Divi] Processed Fields: ' . print_r($processed_fields, true));
        error_log('[WP SIO Connect Divi] Error Status: ' . print_r($et_contact_error, true));
        error_log('[WP SIO Connect Divi] Contact Form Info: ' . print_r($contact_form_info, true));

        // Si une erreur s'est produite avant (ex: validation Divi), ne rien faire
        if ($et_contact_error) {
            error_log('[WP SIO Connect Divi] Aborting due to previous error ($et_contact_error is true).');
            return;
        }

        // --- Identifier le formulaire via son ID CSS ---
        // C'est la partie la plus incertaine. Où trouver l'ID CSS ('formulaire-sio-principal') ?
        // Option 1: Est-il dans $contact_form_info ?
        $form_css_id = null;
        if (isset($contact_form_info['contact_form_id'])) { // 'module_id' pourrait être l'ID CSS ? A VERIFIER
            $form_css_id = sanitize_text_field($contact_form_info['contact_form_id']);
            error_log('[WP SIO Connect Divi] Found "contact_form_id " in contact_form_info: ' . $form_css_id);
        }
        // Option 2: Est-il passé dans $_POST avec un nom spécifique ?
        // Inspecter $_POST peut être nécessaire si l'option 1 échoue.
        // Exemple: $form_css_id = isset($_POST['et_pb_css_id_field']) ? sanitize_text_field($_POST['et_pb_css_id_field']) : null;

        if (empty($form_css_id)) {
            error_log('[WP SIO Connect Divi] Could not determine the Form CSS ID. Aborting.');
            // Il faut trouver comment récupérer l'ID CSS défini par l'utilisateur sur le module.
            return;
        }

        // --- Récupérer la configuration globale et l'API ---
        $api_key = WP_Systemio_Connect_Admin::get_api_key();
        $api_base_url = WP_Systemio_Connect_Admin::get_api_base_url();
        if (empty($api_key) || empty($api_base_url)) {
            return;
        }

        // --- Récupérer la configuration spécifique à ce formulaire via son ID CSS ---
        $options = get_option('wp_systemio_connect_options');
        // On stockera sous 'divi_integrations' avec l'ID CSS comme clé
        $form_settings = isset($options['divi_integrations'][$form_css_id]) ? $options['divi_integrations'][$form_css_id] : null;

        // Vérifier si activé et email mappé
        if (!$form_settings || empty($form_settings['enabled']) || empty($form_settings['email_field'])) {
            error_log("[WP SIO Connect Divi] Config lookup failed or integration disabled/incomplete for CSS ID: [$form_css_id]");
            return;
        }

        // --- Extraire les données en utilisant le mapping ---
        $email_field_id = $form_settings['email_field']; // ID du CHAMP défini dans Divi (ex: contact-email)
        $fname_field_id = isset($form_settings['fname_field']) ? $form_settings['fname_field'] : '';
        $lname_field_id = isset($form_settings['lname_field']) ? $form_settings['lname_field'] : '';
        $tag_ids = isset($form_settings['tags']) && is_array($form_settings['tags']) ? $form_settings['tags'] : [];

        // $processed_fields contient normalement [field_id => value]
        $email = '';
        $first_name = '';
        $last_name = '';

        // Extraire l'email
        if (isset($processed_fields[$email_field_id]) && is_array($processed_fields[$email_field_id]) && isset($processed_fields[$email_field_id]['value'])) {
            $potential_email = $processed_fields[$email_field_id]['value'];

            if (is_string($potential_email)) { // Vérifier d'abord si c'est une chaîne
                $trimmed_email = trim($potential_email); // <-- TRIM ICI !
                if (is_email($trimmed_email)) { // <-- Test sur la valeur trimmée
                    $email = sanitize_email($trimmed_email); // <-- Nettoyer la valeur trimmée
                    error_log("[WP SIO Connect Divi] DEBUG: Email validated: " . $email); // Log de succès pour confirmation
                } else {
                    error_log("[WP SIO Connect Divi] is_email() returned false for trimmed value: [" . $trimmed_email . "]. CSS ID: [$form_css_id]");
                }
            } else {
                error_log("[WP SIO Connect Divi] Email field value is not a string for field '$email_field_id'. CSS ID: [$form_css_id]");
            }
        }

        if (empty($email)) {
            error_log("[WP SIO Connect Divi] Valid email not found or not extracted for mapped field '$email_field_id'. CSS ID: [$form_css_id].");
            return; // Arrêter si aucun email valide n'a été trouvé/extrait
        }

        // Extraire le prénom (appliquer trim aussi par précaution)
        if (!empty($fname_field_id) && isset($processed_fields[$fname_field_id]) && is_array($processed_fields[$fname_field_id]) && isset($processed_fields[$fname_field_id]['value']) && is_string($processed_fields[$fname_field_id]['value'])) {
            $first_name = sanitize_text_field(trim($processed_fields[$fname_field_id]['value'])); // <-- TRIM ICI
        }

        // Extraire le nom (appliquer trim aussi par précaution)
        if (!empty($lname_field_id) && isset($processed_fields[$lname_field_id]) && is_array($processed_fields[$lname_field_id]) && isset($processed_fields[$lname_field_id]['value']) && is_string($processed_fields[$lname_field_id]['value'])) {
            $last_name = sanitize_text_field(trim($processed_fields[$lname_field_id]['value'])); // <-- TRIM ICI
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

} // Fin de la classe
?>