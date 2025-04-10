<?php
// Empêcher l'accès direct au fichier
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Gère les interactions avec l'API Systeme.io V2.
 */
class WP_Systemio_Connect_Api_Service
{

    private $api_key;
    private $base_url;

    /**
     * Constructeur. Récupère les clés API.
     */
    public function __construct()
    {
        // Utiliser les helpers de la classe Admin pour récupérer les options
        // S'assurer que la classe Admin est chargée avant d'instancier ce service
        if (!class_exists('WP_Systemio_Connect_Admin')) {
            error_log('[WP SIO Connect API Service] ERROR: WP_Systemio_Connect_Admin class not found.');
            // Gérer cette erreur ? Peut-être via une exception ou retourner null/false ?
            $this->api_key = null;
            $this->base_url = null;
            return;
        }
        $this->api_key = WP_Systemio_Connect_Admin::get_api_key();
        $this->base_url = WP_Systemio_Connect_Admin::get_api_base_url();
    }

    /**
     * Vérifie si l'API est configurée.
     * @return bool
     */
    private function is_configured()
    {
        return !empty($this->api_key) && !empty($this->base_url);
    }

    /**
     * Effectue une requête générique à l'API SIO.
     *
     * @param string $method 'GET' ou 'POST'.
     * @param string $endpoint Endpoint API (ex: '/contacts').
     * @param array $body_data Données à envoyer dans le corps (pour POST).
     * @return array|WP_Error Tableau ['code' => int, 'body' => array|string] ou WP_Error.
     */
    private function make_request($method, $endpoint, $body_data = [])
    {
        if (!$this->is_configured()) {
            return new WP_Error('api_not_configured', __('L\'API Systeme.io n\'est pas configurée.', 'wp-systemio-connect'));
        }

        $url = $this->base_url . $endpoint;
        $args = [
            'method' => strtoupper($method),
            'headers' => [
                'X-API-Key' => $this->api_key,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'timeout' => 15,
        ];

        if (strtoupper($method) === 'POST' && !empty($body_data)) {
            $args['body'] = json_encode($body_data);
            if ($args['body'] === false) {
                return new WP_Error('json_encode_error', __('Erreur lors de l\'encodage JSON des données.', 'wp-systemio-connect'));
            }
        }

        // Effectuer la requête
        $response = wp_remote_request($url, $args); // Utiliser wp_remote_request pour GET/POST

        // Gérer les erreurs WP (connexion, timeout...)
        if (is_wp_error($response)) {
            error_log('[WP SIO Connect API Service] WP Error during API request to ' . $endpoint . ': ' . $response->get_error_message());
            return $response;
        }

        // Analyser la réponse
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body_raw = wp_remote_retrieve_body($response);
        $response_body_decoded = json_decode($response_body_raw, true);

        // Retourner un tableau structuré avec code et corps (décodé si possible, sinon brut)
        return [
            'code' => $response_code,
            'body' => (json_last_error() === JSON_ERROR_NONE) ? $response_body_decoded : $response_body_raw,
        ];
    }

    /**
     * Crée ou met à jour un contact dans Systeme.io.
     * Adhère à la structure V2 avec un tableau 'fields'.
     *
     * @param string $email L'email du contact.
     * @param string|null $first_name Prénom.
     * @param string|null $last_name Nom.
     * @return int|false ID du contact en cas de succès, false en cas d'erreur.
     */
    public function add_or_update_contact($email, $first_name = null, $last_name = null)
    {
        if (empty($email) || !is_email($email)) {
            error_log('[WP SIO Connect API Service] add_or_update_contact: Invalid email provided.');
            return false;
        }

        // Initialiser les données de base
        $contact_data = ['email' => $email];

        // Préparer le tableau 'fields' pour les autres informations
        $fields_array = [];

        if (!empty($first_name)) {
            $fields_array[] = [
                'slug' => 'first_name', // Utiliser le slug standard SIO
                'value' => $first_name
            ];
        }

        if (!empty($last_name)) {
            $fields_array[] = [
                'slug' => 'surname', // Utiliser le slug standard SIO
                'value' => $last_name
            ];
        }

        // Ajouter le tableau 'fields' seulement s'il n'est pas vide
        if (!empty($fields_array)) {
            $contact_data['fields'] = $fields_array;
        }

        // Log des données envoyées (utile pour le débogage)
        error_log('[WP SIO Connect API Service] add_or_update_contact: Data being sent to /contacts: ' . print_r($contact_data, true));

        // Effectuer la requête POST
        $result = $this->make_request('POST', '/contacts', $contact_data);

        // --- Le reste de la gestion des erreurs et du succès reste identique ---
        if (is_wp_error($result)) {
            error_log('[WP SIO Connect API Service] add_or_update_contact: WP Error - ' . $result->get_error_message());
            return false;
        }

        if ($result['code'] >= 200 && $result['code'] < 300) {
            if (isset($result['body']['id'])) {
                error_log('[WP SIO Connect API Service] add_or_update_contact: Success for email ' . $email . '. Contact ID: ' . $result['body']['id']);
                return (int) $result['body']['id'];
            } else {
                error_log('[WP SIO Connect API Service] add_or_update_contact: Success code ' . $result['code'] . ' but contact ID not found in response body for email ' . $email . '. Body: ' . print_r($result['body'], true));
                return false;
            }
        } else {
            $error_message = is_array($result['body']) && isset($result['body']['message']) ? $result['body']['message'] : print_r($result['body'], true);
            error_log('[WP SIO Connect API Service] add_or_update_contact: API Error ' . $result['code'] . ' for email ' . $email . '. Message: ' . $error_message);
            return false;
        }
    } // Fin add_or_update_contact

    /**
     * Ajoute un tag à un contact spécifique.
     *
     * @param int $contact_id ID du contact SIO.
     * @param int $tag_id ID du tag SIO.
     * @return bool True en cas de succès, false en cas d'erreur.
     */
    public function tag_contact($contact_id, $tag_id)
    {
        $contact_id = absint($contact_id);
        $tag_id = absint($tag_id);

        if ($contact_id <= 0 || $tag_id <= 0) {
            error_log('[WP SIO Connect API Service] tag_contact: Invalid contact_id or tag_id.');
            return false;
        }

        $endpoint = '/contacts/' . $contact_id . '/tags';
        $body_data = ['tagId' => $tag_id]; // Le corps attendu par l'API SIO

        $result = $this->make_request('POST', $endpoint, $body_data);

        // Vérifier les erreurs
        if (is_wp_error($result)) {
            error_log('[WP SIO Connect API Service] tag_contact: WP Error - ' . $result->get_error_message());
            return false;
        }

        // Vérifier le code de réponse HTTP (souvent 204 No Content ou 200 OK pour ce type d'action)
        if ($result['code'] >= 200 && $result['code'] < 300) {
            error_log('[WP SIO Connect API Service] tag_contact: Success - Added tag ' . $tag_id . ' to contact ' . $contact_id . '. Code: ' . $result['code']);
            return true;
        } else {
            // Erreur de l'API SIO
            $error_message = is_array($result['body']) && isset($result['body']['message']) ? $result['body']['message'] : print_r($result['body'], true);
            error_log('[WP SIO Connect API Service] tag_contact: API Error ' . $result['code'] . ' adding tag ' . $tag_id . ' to contact ' . $contact_id . '. Message: ' . $error_message);
            return false;
        }
    }

    /**
     * Récupère une liste de contacts depuis Systeme.io.
     * Gère la pagination basique.
     *
     * @param int $page Le numéro de page à récupérer (commence à 1).
     * @param int $limit Le nombre de contacts par page (max SIO ? 50 ou 100 ? vérifier doc).
     * @return array|WP_Error Tableau ['items' => array, 'total' => int, 'current_page' => int, 'per_page' => int, 'last_page' => int] ou WP_Error.
     */
    public function get_contacts($page = 1, $limit = 50)
    {
        // Construire l'endpoint avec les paramètres de pagination
        // L'API V2 utilise souvent 'page' et 'limit'
        $endpoint = '/contacts?page=' . absint($page) . '&limit=' . absint($limit);

        // Faire l'appel GET
        $result = $this->make_request('GET', $endpoint);

        // Vérifier les erreurs WP
        if (is_wp_error($result)) {
            error_log('[WP SIO Connect API Service] get_contacts: WP Error - ' . $result->get_error_message());
            return $result; // Renvoyer l'erreur WP
        }

        // Vérifier le code de réponse HTTP (devrait être 200 OK)
        if ($result['code'] === 200 && is_array($result['body']) && isset($result['body']['items'])) {
            // Succès ! L'API V2 retourne souvent une structure avec 'items' et des métadonnées de pagination
            $contacts = $result['body']['items'];
            $pagination_info = [
                'total' => isset($result['body']['total']) ? (int) $result['body']['total'] : count($contacts), // Total d'items sur toutes les pages
                'current_page' => isset($result['body']['page']) ? (int) $result['body']['page'] : $page,
                'per_page' => isset($result['body']['limit']) ? (int) $result['body']['limit'] : $limit,
                // Calculer la dernière page
                'last_page' => 0,
            ];
            if ($pagination_info['per_page'] > 0 && $pagination_info['total'] > 0) {
                $pagination_info['last_page'] = ceil($pagination_info['total'] / $pagination_info['per_page']);
            } elseif (!empty($contacts)) {
                $pagination_info['last_page'] = $pagination_info['current_page']; // Si pas d'info totale, on suppose qu'on est sur la seule/dernière page
            }


            error_log('[WP SIO Connect API Service] get_contacts: Success - Retrieved ' . count($contacts) . ' contacts for page ' . $page);

            return [
                'items' => $contacts,
                'pagination' => $pagination_info,
            ];

        } else {
            // Erreur de l'API SIO ou format de réponse inattendu
            $error_message = is_array($result['body']) && isset($result['body']['message']) ? $result['body']['message'] : print_r($result['body'], true);
            error_log('[WP SIO Connect API Service] get_contacts: API Error ' . $result['code'] . '. Message: ' . $error_message);
            return new WP_Error('api_error_get_contacts', sprintf(__('Erreur API Systeme.io (%d) lors de la récupération des contacts: %s', 'wp-systemio-connect'), $result['code'], esc_html($error_message)));
        }
    }

} // Fin de la classe WP_Systemio_Connect_Api_Service