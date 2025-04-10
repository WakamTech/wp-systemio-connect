# Guide Utilisateur : WP Systeme.io Connect

**Version du Plugin :** [Mettre la version actuelle, ex: 0.2.0]
**Dernière Mise à Jour du Guide :** [Date]

Bienvenue dans le guide utilisateur de WP Systeme.io Connect ! Ce plugin est conçu pour vous aider à connecter facilement votre site WordPress à votre compte Systeme.io, principalement pour la gestion de vos contacts email collectés via les formulaires de votre site.

## Table des Matières

1.  [Introduction](#1-introduction)
2.  [Installation et Activation](#2-installation-et-activation)
3.  [Obtenir votre Clé API Systeme.io](#3-obtenir-votre-clé-api-systemeio)
4.  [Configuration Initiale : Onglet Réglages API](#4-configuration-initiale--onglet-réglages-api)
5.  [Connecter vos Formulaires : Onglet Formulaires](#5-connecter-vos-formulaires--onglet-formulaires)
    *   [Configuration Contact Form 7](#configuration-contact-form-7)
    *   [Configuration Elementor Pro Forms](#configuration-elementor-pro-forms)
    *   [Configuration Divi (Module Formulaire de Contact)](#configuration-divi-module-formulaire-de-contact)
6.  [Visualiser vos Contacts : Onglet Contacts](#6-visualiser-vos-contacts--onglet-contacts)
7.  [Ajouter un Contact Manuel : Onglet Contacts](#7-ajouter-un-contact-manuel--onglet-contacts)
8.  [Visualiser vos Tags : Onglet Tags](#8-visualiser-vos-tags--onglet-tags)
9.  [Dépannage](#9-dépannage)

---

## 1. Introduction

WP Systeme.io Connect agit comme un pont entre votre site WordPress et la plateforme marketing Systeme.io. Son objectif principal est de permettre l'envoi automatique des informations soumises via vos formulaires WordPress (Contact Form 7, Elementor Pro, Divi) vers votre liste de contacts Systeme.io.

Fonctionnalités clés :

*   Connexion sécurisée à l'API Systeme.io.
*   Configuration flexible par formulaire pour mapper les champs (Email, Prénom, Nom) et assigner des tags Systeme.io.
*   Visualisation de vos contacts et tags Systeme.io directement depuis l'administration WordPress.
*   Ajout manuel de contacts à Systeme.io depuis WordPress.

Ce plugin vise à simplifier la collecte de leads et la gestion initiale de vos contacts SIO sans nécessiter d'outils tiers complexes comme Zapier pour chaque formulaire.

---

## 2. Installation et Activation

L'installation suit le processus standard des extensions WordPress :

1.  **Téléchargement (si nécessaire) :** Si vous avez reçu le plugin sous forme de fichier `.zip`, conservez-le.
2.  **Accédez à votre administration WordPress.**
3.  Allez dans le menu **Extensions > Ajouter**.
4.  **Option A (Fichier .zip) :**
    *   Cliquez sur le bouton **Téléverser une extension** en haut de la page.
    *   Cliquez sur **Choisir un fichier** et sélectionnez le fichier `.zip` de `wp-systemio-connect`.
    *   Cliquez sur **Installer maintenant**.
5.  **Option B (Dépôt WordPress - si publié) :**
    *   Utilisez la barre de recherche pour trouver "WP Systeme.io Connect".
    *   Cliquez sur **Installer maintenant** sur le plugin correspondant.
6.  Une fois l'installation terminée, cliquez sur le bouton **Activer**.

Après activation, un nouveau menu nommé **"SIO Connect"** apparaîtra dans votre barre latérale d'administration WordPress.

---

## 3. Obtenir votre Clé API Systeme.io

Pour que le plugin puisse communiquer avec votre compte Systeme.io, vous devez fournir une Clé API.

1.  **Connectez-vous** à votre compte Systeme.io.
2.  Cliquez sur votre **photo de profil** en haut à droite, puis sur **Paramètres**.
3.  Dans le menu de gauche, cliquez sur **Clé API publique**.
    *   *(Note : Le nom exact du menu dans SIO peut légèrement varier)*.
4.  Si vous n'avez pas encore de clé, cliquez sur le bouton pour en **créer une**. Donnez-lui un nom reconnaissable (ex: "Connexion WP Site [NomDeVotreSite]").
5.  Une fois la clé créée, **copiez la valeur complète de la clé API**. C'est une longue chaîne de caractères. Gardez-la en sécurité.

    ![Emplacement approximatif de la clé API dans Systeme.io](lien_vers_screenshot_sio_api.png) *(Remplacez par un vrai screenshot si possible)*

---

## 4. Configuration Initiale : Onglet Réglages API

C'est la première étape essentielle après l'activation du plugin.

1.  Dans votre administration WordPress, allez dans le menu **SIO Connect**.
2.  Cliquez sur l'onglet **Réglages API**.
3.  Vous verrez deux champs principaux :
    *   **Clé API Systeme.io :** Collez ici la clé API que vous avez copiée depuis votre compte Systeme.io.
    *   **URL API Systeme.io :** Laissez la valeur par défaut (`https://api.systeme.io/api`) sauf si vous avez une raison spécifique de la changer (très rare).
4.  Cliquez sur le bouton **Enregistrer les Réglages API**.
5.  **Tester la Connexion :** Une fois la clé enregistrée, cliquez sur le bouton **Tester la Connexion**.
    *   Si tout est correct, un message de **succès** vert apparaîtra en haut de la page.
    *   Si la clé est incorrecte ou si un problème survient, un message d'**erreur** rouge apparaîtra. Vérifiez votre clé API et réessayez.

    ![Screenshot de l'onglet Réglages API](lien_vers_screenshot_settings_tab.png) *(Remplacez par un vrai screenshot)*

**La connexion API doit être fonctionnelle avant de pouvoir utiliser les autres fonctionnalités du plugin.**

---

## 5. Connecter vos Formulaires : Onglet Formulaires

Cet onglet vous permet de choisir quels formulaires de votre site doivent envoyer des données à Systeme.io et comment ces données doivent être mappées.

1.  Allez dans le menu **SIO Connect > Formulaires**.
2.  Le plugin détectera automatiquement si Contact Form 7, Elementor Pro ou Divi sont actifs et affichera les sections de configuration correspondantes.

### Configuration Contact Form 7

*   Le plugin listera les formulaires Contact Form 7 trouvés sur votre site.
*   Pour chaque formulaire que vous souhaitez connecter :
    1.  **Cochez la case "Activer pour Systeme.io"**.
    2.  **ID du Champ Email (\*) :** Entrez l'**identifiant exact** du champ email dans votre formulaire CF7 (par exemple, `your-email`). Ce champ est **obligatoire**.
    3.  **ID du Champ Prénom :** Entrez l'identifiant du champ contenant le prénom (ex: `your-name`). Laissez vide si non utilisé.
    4.  **ID du Champ Nom :** Entrez l'identifiant du champ contenant le nom de famille (ex: `last-name`). Laissez vide si non utilisé.
    5.  **Tags Systeme.io :** Cochez les tags SIO que vous souhaitez automatiquement assigner aux contacts soumis via ce formulaire. La liste des tags est récupérée depuis votre compte SIO (assurez-vous que la connexion API fonctionne).

    ![Screenshot de la config CF7](lien_vers_screenshot_cf7_config.png) *(Remplacez par un vrai screenshot)*

### Configuration Elementor Pro Forms

*   Comme Elementor ne permet pas de lister facilement tous les formulaires, vous devez ajouter manuellement chaque formulaire à connecter.
*   Cliquez sur **"Ajouter un formulaire Elementor"**.
*   Pour chaque formulaire ajouté :
    1.  **Nom du Formulaire Elementor (\*) :** Entrez le **Nom exact** que vous avez donné à votre formulaire dans l'éditeur Elementor (Widget Formulaire > Onglet Contenu > Nom du formulaire). **Ce nom doit être unique parmi les formulaires que vous connectez.** Ce champ est **obligatoire**.
    2.  **Cochez la case "Activer pour Systeme.io"**.
    3.  **ID Champ Email (\*) :** Entrez l'**ID exact** que vous avez défini pour votre champ Email dans Elementor (Réglages du champ > Onglet Avancé > ID). Ce champ est **obligatoire**.
    4.  **ID Champ Prénom :** Entrez l'ID du champ Prénom/Nom complet.
    5.  **ID Champ Nom :** Entrez l'ID du champ Nom de famille.
    6.  **Tags Systeme.io :** Cochez les tags SIO à assigner.

    ![Screenshot de la config Elementor](lien_vers_screenshot_elementor_config.png) *(Remplacez par un vrai screenshot)*

### Configuration Divi (Module Formulaire de Contact)

*   Similaire à Elementor, vous devez ajouter manuellement chaque module formulaire.
*   **Prérequis Divi :**
    *   Pour chaque module Formulaire de Contact, vous **devez** lui assigner un **ID CSS unique** (Réglages du module > Avancé > ID et classes CSS > ID CSS).
    *   Pour chaque champ (Email, Nom, etc.) que vous voulez utiliser, vous **devez** lui assigner un **ID de champ unique** (Réglages du champ > Avancé > ID et classes CSS > ID de champ).
*   Cliquez sur **"Ajouter un formulaire Divi"**.
*   Pour chaque formulaire ajouté :
    1.  **ID CSS du Module Formulaire (\*) :** Entrez l'**ID CSS exact** que vous avez défini pour le module Formulaire dans Divi. Ce champ est **obligatoire**.
    2.  **Cochez la case "Activer pour Systeme.io"**.
    3.  **ID du Champ Email (\*) :** Entrez l'**ID de champ exact** que vous avez défini pour le champ Email dans Divi. Ce champ est **obligatoire**.
    4.  **ID du Champ Prénom :** Entrez l'ID de champ du Prénom/Nom complet.
    5.  **ID du Champ Nom :** Entrez l'ID de champ du Nom de famille.
    6.  **Tags Systeme.io :** Cochez les tags SIO à assigner.

    ![Screenshot de la config Divi](lien_vers_screenshot_divi_config.png) *(Remplacez par un vrai screenshot)*

*   N'oubliez pas de cliquer sur **"Enregistrer les Réglages des Formulaires"** après avoir effectué vos configurations.

---

## 6. Visualiser vos Contacts : Onglet Contacts

Cet onglet vous permet d'avoir un aperçu rapide de vos contacts enregistrés dans Systeme.io sans quitter WordPress.

1.  Allez dans le menu **SIO Connect > Contacts**.
2.  Si la connexion API est active, le plugin récupérera et affichera une liste de vos contacts SIO.
3.  La table affiche généralement :
    *   L'adresse Email.
    *   Le Prénom (si disponible).
    *   Le Nom (si disponible).
    *   Les Tags assignés au contact.
    *   La date d'inscription.
4.  La liste est paginée. Utilisez les contrôles de pagination en bas (ou en haut) pour naviguer entre les pages.
5.  Vous pouvez cliquer sur le lien "Voir sur SIO" pour ouvrir la fiche détaillée du contact directement dans votre compte Systeme.io.

    ![Screenshot de l'onglet Contacts](lien_vers_screenshot_contacts_tab.png) *(Remplacez par un vrai screenshot)*

*Note : Cette vue est en lecture seule. La modification ou suppression de contacts n'est pas possible depuis cet écran.*

---

## 7. Ajouter un Contact Manuel : Onglet Contacts

Vous pouvez également ajouter un contact manuellement à Systeme.io depuis WordPress.

1.  Allez dans le menu **SIO Connect > Contacts**.
2.  Cliquez sur le bouton **"Ajouter un contact"** en haut de la page.
3.  Un formulaire apparaît :
    *   **Email (\*) :** Entrez l'adresse email du contact (obligatoire).
    *   **Prénom :** Entrez le prénom (optionnel).
    *   **Nom :** Entrez le nom de famille (optionnel).
    *   **Assigner des Tags :** Cochez les tags SIO que vous souhaitez assigner à ce nouveau contact.
4.  Cliquez sur le bouton **"Ajouter le Contact"**.
5.  Vous serez redirigé vers la liste des contacts avec un message de succès ou d'erreur. Le nouveau contact devrait apparaître dans la liste (cela peut prendre quelques instants pour se synchroniser).

    ![Screenshot du formulaire d'ajout](lien_vers_screenshot_add_contact_form.png) *(Remplacez par un vrai screenshot)*

---

## 8. Visualiser vos Tags : Onglet Tags

Cet onglet affiche simplement la liste des tags existants dans votre compte Systeme.io.

1.  Allez dans le menu **SIO Connect > Tags**.
2.  La liste de vos tags SIO (Nom et ID) sera affichée.

    ![Screenshot de l'onglet Tags](lien_vers_screenshot_tags_tab.png) *(Remplacez par un vrai screenshot)*

*Note : Cet écran est en lecture seule. La création ou modification de tags doit se faire directement dans Systeme.io.*

---

## 9. Dépannage

Si vous rencontrez des problèmes :

*   **Échec de Connexion API :**
    *   Vérifiez que vous avez copié/collé **exactement** la bonne clé API depuis Systeme.io dans l'onglet "Réglages API".
    *   Assurez-vous qu'il n'y a pas d'espaces avant ou après la clé.
    *   Cliquez à nouveau sur "Tester la Connexion". Si l'erreur persiste, vérifiez si votre hébergement WordPress bloque les connexions sortantes (rare, mais possible).
*   **Formulaire non envoyé à SIO :**
    *   Vérifiez que l'intégration est bien **activée** pour ce formulaire spécifique dans l'onglet "Formulaires".
    *   Vérifiez que le **champ Email est correctement mappé** (l'identifiant du champ est correct).
    *   **Elementor :** Assurez-vous que le **Nom du formulaire** dans les réglages du plugin correspond EXACTEMENT au nom défini dans Elementor et qu'il est unique.
    *   **Divi :** Assurez-vous que l'**ID CSS du module** et les **ID de champ** dans les réglages du plugin correspondent EXACTEMENT à ceux définis dans Divi.
    *   Vérifiez la connexion API dans l'onglet "Réglages API".
*   **Contact ajouté mais sans les tags :**
    *   Vérifiez que les tags étaient bien cochés lors de la configuration du formulaire ou de l'ajout manuel.
    *   Vérifiez les logs d'erreur PHP de votre site (souvent un fichier `debug.log` dans `wp-content` si le mode débogage de WordPress est activé). Il pourrait y avoir des messages d'erreur spécifiques lors de la tentative d'ajout des tags.
*   **Liste des contacts/tags vide ou erreur API :**
    *   Vérifiez à nouveau la connexion API.
    *   Votre compte SIO contient-il réellement des contacts/tags ?
    *   Vérifiez les logs d'erreur PHP pour des messages d'erreur de l'API lors de la récupération.

Si les problèmes persistent, n'hésitez pas à contacter le support du plugin (si disponible) en fournissant le plus de détails possible, y compris les messages d'erreur et les étapes que vous avez suivies.

---