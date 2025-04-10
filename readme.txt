=== WP Systeme.io Connect ===
Contributors: TonNomOuPseudo
Tags: systeme.io, email, marketing, api, forms, elementor, divi, contact form 7
Requires at least: 5.6  // Version WP minimale testée
Tested up to: 6.5     // Version WP maximale testée
Requires PHP: 7.4    // Version PHP minimale requise
Stable tag: 0.2.0   // La version actuelle de ton plugin
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connecte facilement vos formulaires WordPress (Elementor, Divi, CF7...) à Systeme.io pour gérer vos contacts et emails.

== Description ==

Ce plugin fournit un pont simple et efficace entre votre site WordPress et votre compte Systeme.io.
*   Connectez votre clé API Systeme.io en toute sécurité.
*   Intégrez vos formulaires Contact Form 7, Elementor Pro et Divi.
*   Configurez quels champs envoyer (Email, Prénom, Nom).
*   Assignez automatiquement des tags Systeme.io lors de la soumission.
*   Visualisez vos contacts et tags Systeme.io directement depuis WordPress.
*   Ajoutez de nouveaux contacts à Systeme.io depuis WordPress.

Plus besoin de Zapier ou de configurations complexes pour chaque formulaire !

== Installation ==

1.  Téléversez le dossier `wp-systemio-connect` dans le répertoire `/wp-content/plugins/`.
2.  Activez l'extension via le menu 'Extensions' de WordPress.
3.  Allez dans le nouveau menu "SIO Connect" dans votre administration WordPress.
4.  Allez dans l'onglet "Réglages API" et entrez votre clé API Systeme.io. Testez la connexion.
5.  Allez dans l'onglet "Formulaires" pour configurer les intégrations souhaitées.

== Frequently Asked Questions ==

= Quels plugins de formulaires sont supportés ? =
Actuellement : Contact Form 7, Elementor Pro Forms, et le module Formulaire de Contact de Divi.

= Où trouver ma clé API Systeme.io ? =
Connectez-vous à votre compte Systeme.io, allez dans vos Paramètres (icône engrenage), puis cliquez sur "Clé API publique" dans le menu de gauche.

= Comment identifier mon formulaire Elementor/Divi ? =
*   **Elementor :** Utilisez le "Nom du formulaire" défini dans l'onglet "Contenu" du widget Formulaire. Assurez-vous que ce nom est unique.
*   **Divi :** Assignez un "ID CSS" unique au module Formulaire de Contact dans ses réglages "Avancé" > "ID et classes CSS". Utilisez cet ID CSS dans les réglages du plugin. Assignez aussi des "ID de champ" uniques à chaque champ que vous voulez mapper.

= Les champs personnalisés sont-ils gérés ? =
Pas pour l'instant. Seuls l'email, le prénom et le nom sont envoyés lors de l'ajout/connexion.

== Screenshots ==
<!-- Optionnel : Liens vers des images illustrant l'utilisation -->
1. Onglet Réglages API
2. Onglet Configuration des Formulaires
3. Onglet Liste des Contacts

== Changelog ==

= 0.2.0 =
*   Ajout Onglet Contacts (Visualisation, Ajout basique)
*   Ajout Onglet Tags (Visualisation)
*   Refonte de l'appel API pour utiliser la création puis le tag séparément.
*   Refonte de l'interface admin avec menu principal et onglets.
*   Ajout intégration Divi.

= 0.1.0 =
*   Version initiale. Connexion API. Intégration CF7 et Elementor.

== Upgrade Notice ==
<!-- Optionnel -->