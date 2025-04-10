<?php
// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use ElementorPro\Modules\Forms\Classes\Form_Record;
use ElementorPro\Modules\Forms\Classes\Ajax_Handler;

/**
 * Gère l'intégration avec Elementor Pro Forms.
 */
class WP_Systemio_Connect_Elementor
{

    /**
     * Initialise les hooks pour Elementor Pro Forms si le plugin est actif.
     */
    public static function init()
    {
        // Vérifier si Elementor Pro est actif (on peut vérifier une classe ou constante spécifique)
        // La classe Form_Record est un bon indicateur.

        // Cette vérification se fera maintenant APRÈS qu'Elementor Pro a eu la chance de se charger.
        if (class_exists('\ElementorPro\Modules\Forms\Classes\Form_Record')) {
            error_log('[WP SIO Connect Elementor] Elementor Pro Form_Record class FOUND. Adding hook.'); // Log de succès pour vérifier
            add_action('elementor_pro/forms/new_record', [__CLASS__, 'handle_submission'], 10, 2);
        } else {
            // Garder un log ici peut être utile si ça échoue toujours, mais ça ne devrait plus.
            error_log('[WP SIO Connect Elementor] Elementor Pro Form_Record class NOT FOUND even after plugins_loaded.');
        }
    }

    /**
     * Fonction appelée après la soumission réussie d'un formulaire Elementor Pro.
     *
     * @param Form_Record $record      L'objet contenant les données du formulaire soumis et ses métadonnées.
     * @param Ajax_Handler $ajax_handler L'objet gérant la réponse AJAX.
     */
    public static function handle_submission($record, $ajax_handler)
    {
        // --- Récupérer la configuration globale et l'API ---
        $api_key = WP_Systemio_Connect_Admin::get_api_key();
        $api_base_url = WP_Systemio_Connect_Admin::get_api_base_url();

        if (empty($api_key) || empty($api_base_url)) {
            // error_log('[WP SIO Connect Elementor] Clé API non configurée.');
            return; // Ne rien faire si l'API n'est pas prête
        }

        // --- Identifier le formulaire ---
        $form_id = $record->get_form_settings('form_id'); // Récupérer l'ID défini dans les réglages du formulaire Elementor
        $form_name = $record->get_form_settings('form_name'); // Récupérer le nom du formulaire

        // Pour notre config, on utilisera l'ID du formulaire s'il est défini, sinon le nom ?
        // Il faut être cohérent avec ce qu'on demandera à l'utilisateur dans l'admin.
        // Utilisons l'ID ('id') pour l'instant, car il est plus stable.
        if (empty($form_id)) {
            error_log('[WP SIO Connect Elementor] ID de formulaire Elementor non défini pour le formulaire : ' . $form_name);
            return; // On a besoin de l'ID pour récupérer la config
        }

        // --- Récupérer la configuration spécifique à CE formulaire Elementor ---
        $options = get_option('wp_systemio_connect_options');
        // On va stocker la config sous 'elementor_integrations'
        $form_settings = isset($options['elementor_integrations'][$form_id]) ? $options['elementor_integrations'][$form_id] : null;

        // Vérifier si l'intégration est activée pour ce formulaire et si le champ email est mappé
        if (!$form_settings || empty($form_settings['enabled']) || empty($form_settings['email_field'])) {
            error_log("[WP SIO Connect Elementor] Intégration désactivée ou champ email non mappé pour le formulaire ID: $form_id $form_settings .");
            return;
        }

        // --- Extraire les données en utilisant le mapping ---
        $email_field_id = $form_settings['email_field']; // ID du champ Elementor
        $fname_field_id = isset($form_settings['fname_field']) ? $form_settings['fname_field'] : '';
        $lname_field_id = isset($form_settings['lname_field']) ? $form_settings['lname_field'] : '';
        $tag_ids = isset($form_settings['tags']) && is_array($form_settings['tags']) ? $form_settings['tags'] : [];

        // Récupérer les données normalisées du formulaire
        // $raw_fields = $record->get('fields'); // Tableau [field_id => value]
        $form_fields = $record->get('fields'); // Renommons pour plus de clarté
        $email = '';
        $first_name = '';
        $last_name = '';

        // Extraire l'email
        if (isset($form_fields[$email_field_id]) && is_array($form_fields[$email_field_id]) && isset($form_fields[$email_field_id]['value'])) {
            $potential_email = $form_fields[$email_field_id]['value']; // <-- Extraire la clé 'value'
            if (is_string($potential_email)) {
                $email_value = sanitize_email($potential_email);
                if (is_email($email_value)) {
                    $email = $email_value;
                }
            } else {
                error_log("[WP SIO Connect Elementor] La 'value' du champ email ('$email_field_id') n'est pas une chaîne. Form ID: $form_id. Value: " . print_r($potential_email, true));
            }
        }

        // La vérification suivante empêchera de continuer si $email n'a pas été défini correctement
        if (empty($email)) {
            error_log("[WP SIO Connect Elementor] Email valide non trouvé pour le champ mappé '$email_field_id'. Form ID: $form_id.");
            return; // Email obligatoire
        }

        // Extraire le prénom (si mappé et trouvé)
        if (!empty($fname_field_id) && isset($form_fields[$fname_field_id]) && is_array($form_fields[$fname_field_id]) && isset($form_fields[$fname_field_id]['value'])) {
            $potential_fname = $form_fields[$fname_field_id]['value']; // <-- Extraire la clé 'value'
            if (is_string($potential_fname)) {
                $first_name = sanitize_text_field(trim($potential_fname));
            } else {
                error_log("[WP SIO Connect Elementor] La 'value' du champ prénom ('$fname_field_id') n'est pas une chaîne. Form ID: $form_id. Value: " . print_r($potential_fname, true));
            }
        }

        // Extraire le nom (si mappé et trouvé)
        if (!empty($lname_field_id) && isset($form_fields[$lname_field_id]) && is_array($form_fields[$lname_field_id]) && isset($form_fields[$lname_field_id]['value'])) {
            $potential_lname = $form_fields[$lname_field_id]['value']; // <-- Extraire la clé 'value'
            if (is_string($potential_lname)) {
                $last_name = sanitize_text_field(trim($potential_lname));
            } else {
                error_log("[WP SIO Connect Elementor] La 'value' du champ nom ('$lname_field_id') n'est pas une chaîne. Form ID: $form_id. Value: " . print_r($potential_lname, true));
            }
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

        // Remarque : On n'interagit pas avec $ajax_handler ici. La soumission Elementor
        // continue son cours normal (actions après soumission comme l'email, etc.).
    }

} // Fin de la classe WP_Systemio_Connect_Elementor
?>