<?php
// Empêcher l'accès direct au fichier
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Gère l'intégration avec Contact Form 7.
 */
class WP_Systemio_Connect_CF7 {

    /**
     * Initialise les hooks pour CF7 si le plugin est actif.
     */
    public static function init() {
        // Vérifier si Contact Form 7 est actif avant d'ajouter le hook
        if ( defined( 'WPCF7_VERSION' ) ) {
            add_action( 'wpcf7_before_send_mail', [ __CLASS__, 'handle_submission' ], 10, 3 );
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
    public static function handle_submission( $contact_form, &$abort, $submission ) {
        // Récupérer la clé API et l'URL de base via notre classe Admin (ou des helpers dédiés)
        $api_key = WP_Systemio_Connect_Admin::get_api_key();
        $api_base_url = WP_Systemio_Connect_Admin::get_api_base_url();

        // Si la clé API n'est pas configurée, on ne fait rien
        if ( empty( $api_key ) || empty($api_base_url) ) {
            error_log('[WP SIO Connect] Clé API SIO non configurée. Abandon de l\'envoi pour CF7.');
            return; // Sortir de la fonction
        }

        // Récupérer les données soumises
        $posted_data = $submission->get_posted_data();

        // --- Identification des champs ---
        // Pour l'instant, on suppose des noms communs. Il faudra rendre ça configurable.
        $email = '';
        $first_name = '';
        $last_name = ''; // Optionnel

        // Chercher l'email (essayer plusieurs noms communs)
        $email_field_names = ['your-email', 'email', 'adresse-email', 'user_email'];
        foreach ($email_field_names as $field_name) {
            if ( isset( $posted_data[$field_name] ) && is_email( $posted_data[$field_name] ) ) {
                $email = sanitize_email( $posted_data[$field_name] );
                break; // Arrêter dès qu'on a trouvé un email valide
            }
        }

        // Si aucun email valide n'est trouvé, on ne peut rien faire
        if ( empty( $email ) ) {
             error_log('[WP SIO Connect] Aucun champ email valide trouvé dans la soumission CF7.');
             return;
        }

        // Chercher le prénom/nom (essayer plusieurs noms communs)
        $name_field_names = ['your-name', 'name', 'nom', 'first-name', 'firstname', 'prenom'];
         foreach ($name_field_names as $field_name) {
             if ( isset( $posted_data[$field_name] ) && !empty(trim($posted_data[$field_name])) ) {
                 // On met tout dans first_name pour l'instant, SIO gère bien ça.
                 // On pourrait essayer de séparer si on a 'last-name' etc.
                 $first_name = sanitize_text_field( trim($posted_data[$field_name]) );
                 break;
             }
         }
         // On pourrait ajouter une logique similaire pour 'last_name' si nécessaire


        // --- Préparation de l'appel API SIO ---
        $api_endpoint = $api_base_url . '/contacts'; // Endpoint pour créer/mettre à jour un contact

        $contact_data = [
            'email' => $email,
        ];
        if ( ! empty( $first_name ) ) {
            $contact_data['firstName'] = $first_name;
        }
         // if ( ! empty( $last_name ) ) {
         //    $contact_data['lastName'] = $last_name;
         // }
         // On pourrait ajouter ici l'ID du tag si on l'avait déjà configuré
         // $contact_data['tags'] = [123]; // Exemple: Ajouter au tag avec ID 123

        $body_json = json_encode( $contact_data );

        if ( $body_json === false ) {
            error_log('[WP SIO Connect] Erreur encodage JSON pour données contact SIO.');
            return;
        }

        // Définir les arguments pour wp_remote_post
        $args = [
            'method'  => 'POST',
            'headers' => [
                'X-API-Key' => $api_key,
                'Accept'    => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'body'    => $body_json,
            'timeout' => 15, // Secondes
            // 'sslverify' => false, // Seulement si vous avez des problèmes SSL en local, à ne pas faire en production
        ];

        // --- Exécution de l'appel API ---
        error_log('[WP SIO Connect] Tentative d\'ajout contact SIO : ' . $email); // Log de débogage
        $response = wp_remote_post( $api_endpoint, $args );

        // --- Gestion de la réponse ---
        if ( is_wp_error( $response ) ) {
            // Erreur WordPress (connexion, timeout...)
            error_log('[WP SIO Connect] Erreur WP API SIO (CF7) : ' . $response->get_error_message());
        } else {
            $response_code = wp_remote_retrieve_response_code( $response );
            $response_body = wp_remote_retrieve_body( $response );

            if ( $response_code >= 200 && $response_code < 300 ) {
                // Succès ! Le contact a été créé ou mis à jour.
                 error_log('[WP SIO Connect] Succès API SIO (CF7) : Contact ' . $email . ' ajouté/mis à jour. Code: ' . $response_code);
                 // On pourrait loguer l'ID du contact retourné par SIO si disponible dans $response_body
            } else {
                // Erreur renvoyée par l'API SIO
                error_log('[WP SIO Connect] Erreur API SIO (CF7) : Code ' . $response_code . ' - Réponse : ' . $response_body);
            }
        }

        // Note: On ne modifie pas $abort ici, donc l'email de CF7 sera toujours envoyé normalement.
        // Si on voulait *remplacer* l'email CF7 par l'ajout SIO, on mettrait $abort = true;
    }

} // Fin de la classe WP_Systemio_Connect_CF7
?>