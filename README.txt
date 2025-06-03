=== Natcash Payment Gateway for WooCommerce ===
Contributors: beatbyflux
Tags: woocommerce, payment, natcash, haiti, gourdes, mobile-payment
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Méthode de paiement manuelle pour Natcash en Haïti avec conversion automatique USD vers Gourdes et gestion des reçus.

== Description ==

Le plugin **Natcash Payment Gateway for WooCommerce** permet d'intégrer facilement la méthode de paiement Natcash dans votre boutique WooCommerce. Natcash est un service de paiement mobile populaire en Haïti.

### Fonctionnalités principales

* **Configuration simple** : Interface d'administration intuitive dans WooCommerce
* **Conversion automatique** : Conversion USD vers Gourdes haïtiennes (HTG) avec taux configurable
* **Gestion des reçus** : Upload et validation des reçus de paiement (JPG, PNG, PDF)
* **Workflow complet** : Gestion des statuts de commande avec approbation/rejet admin
* **Sécurisé** : Validation des fichiers, nonces, et permissions appropriées
* **Multilingue** : Support français et créole (à venir)

### Comment ça fonctionne

1. **Configuration** : Configurez votre numéro Natcash et le taux de change dans WooCommerce > Paramètres > Paiements
2. **Commande client** : Le client voit le montant converti en gourdes et les instructions de paiement
3. **Upload du reçu** : Le client télécharge son reçu de paiement Natcash
4. **Vérification admin** : L'administrateur vérifie et approuve/rejette le paiement
5. **Finalisation** : La commande est automatiquement mise à jour selon la décision

### Avantages

* **Adapté au marché haïtien** : Conversion automatique vers la monnaie locale
* **Interface utilisateur claire** : Instructions de paiement détaillées pour les clients
* **Gestion administrative efficace** : Outils d'approbation intégrés à WooCommerce
* **Sécurité renforcée** : Validation stricte des fichiers uploadés
* **Compatible** : Fonctionne avec tous les thèmes WooCommerce standards

== Installation ==

### Installation automatique

1. Allez dans votre tableau de bord WordPress
2. Naviguez vers **Extensions > Ajouter**
3. Recherchez "Natcash Payment Gateway"
4. Cliquez sur **Installer maintenant**
5. Activez le plugin

### Installation manuelle

1. Téléchargez le fichier ZIP du plugin
2. Allez dans **Extensions > Ajouter > Téléverser une extension**
3. Sélectionnez le fichier ZIP et cliquez sur **Installer maintenant**
4. Activez le plugin

### Configuration

1. Allez dans **WooCommerce > Paramètres > Paiements**
2. Trouvez "Natcash" et cliquez sur **Gérer**
3. Configurez les paramètres :
   * Activez la méthode de paiement
   * Entrez votre numéro de compte Natcash
   * Entrez le nom de votre compte Natcash
   * Définissez le taux de change USD vers Gourdes (défaut: 134)
   * Personnalisez le titre et la description si nécessaire
4. Sauvegardez les modifications

== Frequently Asked Questions ==

= Le plugin fonctionne-t-il avec tous les thèmes ? =

Oui, le plugin est conçu pour fonctionner avec tous les thèmes compatibles WooCommerce. Il utilise les hooks et filtres standards de WooCommerce.

= Puis-je modifier le taux de change ? =

Oui, vous pouvez modifier le taux de change USD vers Gourdes dans les paramètres du plugin. Le taux par défaut est de 134 HTG pour 1 USD.

= Quels formats de fichiers sont acceptés pour les reçus ? =

Les formats acceptés sont : JPG, JPEG, PNG et PDF. La taille maximale est de 5MB par fichier.

= Comment approuver ou rejeter un paiement ? =

Dans l'administration WordPress, allez dans **WooCommerce > Commandes**, ouvrez une commande payée avec Natcash, et utilisez les boutons "Approuver" ou "Rejeter" dans la meta box "Détails du paiement Natcash".

= Le plugin est-il sécurisé ? =

Oui, le plugin implémente plusieurs mesures de sécurité :
* Validation stricte des types de fichiers
* Vérification des permissions utilisateur
* Utilisation de nonces pour les formulaires
* Sanitisation de toutes les données

= Puis-je personnaliser les messages ? =

Oui, tous les messages sont traduits et peuvent être personnalisés via les fichiers de traduction ou des hooks WordPress.

== Screenshots ==

1. **Configuration admin** - Interface de configuration dans WooCommerce
2. **Formulaire de paiement** - Interface client avec conversion de devise
3. **Upload de reçu** - Zone de téléchargement du reçu de paiement
4. **Gestion des commandes** - Interface admin pour approuver/rejeter les paiements
5. **Email de confirmation** - Email automatique avec instructions de paiement

== Changelog ==

= 1.0.0 =
* Version initiale
* Configuration de base de la passerelle Natcash
* Conversion automatique USD vers HTG
* Système d'upload et validation des reçus
* Interface d'approbation/rejet pour les administrateurs
* Support multilingue (français)
* Validation de sécurité complète
* Documentation complète

== Upgrade Notice ==

= 1.0.0 =
Version initiale du plugin. Installation recommandée pour tous les marchands utilisant Natcash en Haïti.

== Support ==

Pour obtenir de l'aide ou signaler des problèmes :

* **Documentation** : Consultez la documentation complète sur GitHub
* **Support** : Créez un ticket sur le repository GitHub
* **Email** : Contactez-nous à contact@beatbyflux.com

== Développement ==

Ce plugin est développé et maintenu par **BeatByFlux**. Le code source est disponible sur GitHub.

### Contribuer

Les contributions sont les bienvenues ! Voici comment contribuer :

1. Fork le repository
2. Créez une branche pour votre fonctionnalité
3. Commitez vos changements
4. Poussez vers la branche
5. Créez une Pull Request

### Roadmap

Fonctionnalités prévues pour les prochaines versions :

* Support du créole haïtien
* API pour vérification automatique des paiements
* Statistiques et rapports avancés
* Intégration avec d'autres passerelles haïtiennes
* Mode sandbox pour les tests

== Crédits ==

* Développé par **BeatByFlux**
* Testé par la communauté e-commerce haïtienne
* Icônes par Dashicons (WordPress)
* Inspiré par les besoins du marché haïtien

== Licence ==

Ce plugin est distribué sous licence GPL v2 ou ultérieure. Vous êtes libre de l'utiliser, le modifier et le redistribuer selon les termes de cette licence.

