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

        // --- Préparation de l'appel API SIO ---
        // *** C'est ici qu'on pourrait appeler une fonction centralisée ***
        // Pour l'instant, on duplique un peu la logique de CF7

        $api_endpoint = $api_base_url . '/contacts';
        $contact_data = ['email' => $email];
        if (!empty($first_name)) {
            $contact_data['firstName'] = $first_name;
        }
        if (!empty($last_name)) {
            $contact_data['lastName'] = $last_name;
        }

        error_log('[WP SIO Connect Elementor] contact_data  ' . print_r($contact_data, true));
        if (!empty($tag_ids)) {
            $tag_ids_int = array_map('intval', $tag_ids);
            $tag_ids_int = array_filter($tag_ids_int);
            if (!empty($tag_ids_int)) {
                $contact_data['tags'] = $tag_ids_int;
            }
        }

        $body_json = json_encode($contact_data);
        if ($body_json === false) {
            error_log('[WP SIO Connect Elementor] Erreur encodage JSON pour données contact SIO (Form ID: ' . $form_id . ').');
            return;
        }

        $args = [
            'method' => 'POST',
            'headers' => [
                'X-API-Key' => $api_key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body' => $body_json,
            'timeout' => 15,
        ];

        // --- Exécution de l'appel API ---
        error_log('[WP SIO Connect Elementor] Tentative ajout contact SIO depuis Elementor ID: ' . $form_id . ' - Email: ' . $email . ' - Tags: ' . implode(',', $tag_ids));
        $response = wp_remote_post($api_endpoint, $args);

        // --- Gestion de la réponse ---
        if (is_wp_error($response)) {
            error_log('[WP SIO Connect Elementor] Erreur WP API SIO (Form ID: ' . $form_id . ') : ' . $response->get_error_message());
            // On pourrait vouloir notifier l'admin ici ? Ou juste logguer.
        } else {
            $response_code = wp_remote_retrieve_response_code($response);
            $response_body = wp_remote_retrieve_body($response);

            if ($response_code >= 200 && $response_code < 300) {
                error_log('[WP SIO Connect Elementor] Succès API SIO (Form ID: ' . $form_id . ') : Contact ' . $email . ' ajouté/mis à jour. Code: ' . $response_code);
            } else {
                error_log('[WP SIO Connect Elementor] Erreur API SIO (Form ID: ' . $form_id . ') : Code ' . $response_code . ' - Réponse : ' . $response_body);
            }
        }

        // Remarque : On n'interagit pas avec $ajax_handler ici. La soumission Elementor
        // continue son cours normal (actions après soumission comme l'email, etc.).
    }

} // Fin de la classe WP_Systemio_Connect_Elementor
?>