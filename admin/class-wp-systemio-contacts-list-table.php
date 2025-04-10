<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Charger WP_List_Table si elle n'est pas déjà chargée
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Crée une table pour afficher les contacts Systeme.io.
 */
class WP_Systemio_Contacts_List_Table extends WP_List_Table {

    private $api_service; // Pour faire l'appel API

    /**
     * Constructeur. Définit les noms, etc.
     */
    public function __construct( $api_service ) {
        parent::__construct( [
            'singular' => __( 'Contact SIO', 'wp-systemio-connect' ), // Singular name of the listed records
            'plural'   => __( 'Contacts SIO', 'wp-systemio-connect' ), // Plural name of the listed records
            'ajax'     => false // On ne gère pas l'AJAX pour l'instant
        ] );
        $this->api_service = $api_service;
    }

    /**
     * Colonnes par défaut (non affichées mais nécessaires).
     */
    function get_columns() {
        $columns = [
            // 'cb'        => '<input type="checkbox" />', // Checkbox pour actions groupées (pas pour l'instant)
            'email'      => __( 'Email', 'wp-systemio-connect' ),
            'first_name' => __( 'Prénom', 'wp-systemio-connect' ),
            'last_name'  => __( 'Nom', 'wp-systemio-connect' ),
            'tags'       => __( 'Tags', 'wp-systemio-connect' ),
            'registeredAt' => __( 'Inscrit le', 'wp-systemio-connect' ),
        ];
        return $columns;
    }

    /**
     * Colonnes triables (optionnel).
     * Clé = nom de colonne, Valeur = [orderby_api_field, is_numeric]
     */
    function get_sortable_columns() {
        $sortable_columns = [
            'email' => [ 'email', false ],
            'registeredAt' => [ 'registeredAt', false ], // Supposant que l'API permet le tri
        ];
        return $sortable_columns;
    }

    /**
     * Comment afficher les données pour chaque colonne.
     */
    function column_default( $item, $column_name ) {
         // $item est un contact individuel de l'API SIO
         switch ( $column_name ) {
             case 'email':
             case 'registeredAt':
                return isset($item[$column_name]) ? esc_html($item[$column_name]) : 'N/A';
             case 'tags':
                  if (isset($item['tags']) && is_array($item['tags'])) {
                      $tag_names = wp_list_pluck($item['tags'], 'name'); // Extrait juste les noms
                      return !empty($tag_names) ? esc_html(implode(', ', $tag_names)) : '—';
                  }
                  return '—';
             // Pour Prénom/Nom, il faut chercher dans le tableau 'fields'
             case 'first_name':
                $fname = '—';
                if (isset($item['fields']) && is_array($item['fields'])) {
                    foreach ($item['fields'] as $field) {
                        if (isset($field['slug']) && $field['slug'] === 'first_name' && isset($field['value'])) { // Slug peut être 'firstName' ou 'first_name' selon API? À vérifier
                            $fname = $field['value'];
                            break;
                        }
                         if (isset($field['slug']) && $field['slug'] === 'firstName' && isset($field['value'])) {
                            $fname = $field['value'];
                            break;
                        }
                    }
                }
                return esc_html($fname);
            case 'last_name':
                $lname = '—';
                 if (isset($item['fields']) && is_array($item['fields'])) {
                    foreach ($item['fields'] as $field) {
                        if (isset($field['slug']) && $field['slug'] === 'lastName' && isset($field['value'])) { // Slug peut être 'lastName' ou 'surname'? Vérifier la réponse API
                             $lname = $field['value'];
                             break;
                        }
                        if (isset($field['slug']) && $field['slug'] === 'surname' && isset($field['value'])) {
                            $lname = $field['value'];
                            break;
                       }
                    }
                 }
                return esc_html($lname);
             default:
                 return print_r( $item, true ); // Afficher tout si colonne inconnue
         }
    }

     /**
      * Affichage de la colonne 'email' avec actions (optionnel).
      */
     function column_email($item) {
         $email = isset($item['email']) ? $item['email'] : '';
         // Actions (ex: Voir dans SIO, Modifier - Pas implémenté)
         $actions = [
             // 'edit' => sprintf('<a href="?page=%s&action=%s&contact_id=%s">Modifier</a>', $_REQUEST['page'], 'edit_contact', $item['id']),
             // 'delete' => sprintf('<a href="?page=%s&action=%s&contact_id=%s">Supprimer</a>', $_REQUEST['page'], 'delete_contact', $item['id']),
             'view_sio' => sprintf('<a href="https://systeme.io/dashboard/contacts/contact/%s" target="_blank">Voir sur SIO</a>', isset($item['id']) ? $item['id'] : '0'), // Lien direct si ID disponible
         ];

         return sprintf('%1$s %2$s', esc_html($email), $this->row_actions($actions));
     }

    /**
     * Message si aucune donnée n'est trouvée.
     */
    function no_items() {
        _e( 'Aucun contact Systeme.io trouvé.', 'wp-systemio-connect' );
    }

    /**
     * Prépare les données pour l'affichage.
     * C'est ici qu'on appelle l'API SIO.
     */
    function prepare_items() {
        // Définir les colonnes
        $columns = $this->get_columns();
        $hidden = []; // Colonnes cachées
        $sortable = []; // $this->get_sortable_columns(); // Activer si on gère le tri
        $this->_column_headers = [ $columns, $hidden, $sortable ];

        // Récupérer la page actuelle pour la pagination WP List Table
        $current_page = $this->get_pagenum();
        $per_page = 20; // Nombre d'items par page dans WP

        // Appeler notre service API pour récupérer les données
        // On passe la page actuelle et une limite (peut être différente de $per_page si SIO a des limites fixes)
        $api_limit = 50; // Limite pour l'appel API SIO
        $result = $this->api_service->get_contacts( $current_page, $api_limit );

        if ( is_wp_error( $result ) ) {
             echo '<div class="notice notice-error"><p>Erreur API : ' . esc_html($result->get_error_message()) . '</p></div>';
             $this->items = [];
             $total_items = 0;
             $api_page_info = ['total' => 0, 'current_page' => 1, 'per_page' => $api_limit, 'last_page' => 1];
        } elseif (isset($result['items']) && isset($result['pagination'])) {
             $this->items = $result['items']; // Les données à afficher
             $api_page_info = $result['pagination'];
             $total_items = $api_page_info['total']; // Total d'items sur toutes les pages SIO
        } else {
             echo '<div class="notice notice-warning"><p>Format de réponse API inattendu.</p></div>';
             $this->items = [];
             $total_items = 0;
             $api_page_info = ['total' => 0, 'current_page' => 1, 'per_page' => $api_limit, 'last_page' => 1];
        }


        // Configurer la pagination pour WP_List_Table
        $this->set_pagination_args( [
            'total_items' => $total_items,
            'per_page'    => $per_page, // Combien on affiche par page dans WP (même si l'API en ramène plus)
            'total_pages' => ceil( $total_items / $per_page )
        ] );

         // Note : L'API SIO peut retourner $api_limit (ex: 50) résultats, mais WP_List_Table
         // peut être configuré pour n'en afficher que $per_page (ex: 20).
         // Il faudra ajuster l'appel API pour récupérer la bonne "page SIO"
         // correspondant à la "page WP List Table" si $per_page != $api_limit.
         // Pour l'instant, on suppose $per_page = $api_limit pour simplifier.
         // Ou alors, on demande toujours $api_limit et WP_List_Table gère l'affichage de $per_page.
         // La deuxième approche est plus simple ici.
    }

} // Fin de la classe WP_Systemio_Contacts_List_Table
?>