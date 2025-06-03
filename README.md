# Natcash Payment Gateway for WooCommerce

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![WooCommerce](https://img.shields.io/badge/WooCommerce-4.0%2B-purple.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4.svg)
![License](https://img.shields.io/badge/license-GPL%20v2%2B-green.svg)

Méthode de paiement manuelle pour Natcash en Haïti avec conversion automatique USD vers Gourdes et gestion complète des reçus.

## 🚀 Fonctionnalités

- ✅ **Configuration simple** via l'interface WooCommerce
- 💱 **Conversion automatique** USD vers Gourdes haïtiennes (HTG)
- 📄 **Gestion des reçus** avec upload sécurisé (JPG, PNG, PDF)
- 🔐 **Workflow d'approbation** pour les administrateurs
- 🌐 **Multilingue** (français inclus, créole à venir)
- 🛡️ **Sécurisé** avec validation complète des fichiers
- 📱 **Responsive** et compatible avec tous les thèmes WooCommerce

## 📋 Prérequis

- WordPress 5.0 ou supérieur
- WooCommerce 4.0 ou supérieur
- PHP 7.4 ou supérieur
- Compte Natcash actif

## 🔧 Installation

### Via WordPress Admin

1. Téléchargez le plugin depuis les releases GitHub
2. Allez dans **Extensions > Ajouter > Téléverser une extension**
3. Sélectionnez le fichier ZIP et installez
4. Activez le plugin

### Via FTP

1. Téléchargez et décompressez le plugin
2. Uploadez le dossier `natcash-payment-gateway` dans `/wp-content/plugins/`
3. Activez le plugin dans l'interface d'administration

### Via Composer

```bash
composer require beatbyflux/natcash-payment-gateway
```

## ⚙️ Configuration

1. Allez dans **WooCommerce > Paramètres > Paiements**
2. Trouvez "Natcash" et cliquez sur **Gérer**
3. Configurez les paramètres :

| Paramètre | Description | Requis |
|-----------|-------------|---------|
| Activer/Désactiver | Active la méthode de paiement | ✅ |
| Titre | Nom affiché aux clients | ✅ |
| Description | Description pour les clients | ❌ |
| Numéro de compte | Votre numéro Natcash | ✅ |
| Nom du compte | Nom associé au compte | ✅ |
| Taux de change | USD vers HTG (défaut: 134) | ✅ |

## 🎯 Utilisation

### Pour les clients

1. **Sélection du paiement** : Choisir "Natcash" au checkout
2. **Visualisation du montant** : Voir la conversion USD → HTG
3. **Instructions de paiement** : Suivre les instructions affichées
4. **Upload du reçu** : Télécharger le reçu de paiement
5. **Confirmation** : Confirmer et finaliser la commande

### Pour les administrateurs

1. **Réception de la commande** : Notification automatique
2. **Vérification du reçu** : Consulter le reçu uploadé
3. **Approbation/Rejet** : Utiliser les boutons dans la commande
4. **Mise à jour automatique** : Le statut se met à jour automatiquement

## 🔒 Sécurité

Le plugin implémente plusieurs mesures de sécurité :

- **Validation des fichiers** : Types MIME et taille vérifiés
- **Nonces WordPress** : Protection CSRF sur tous les formulaires
- **Permissions** : Vérification des droits utilisateur
- **Sanitisation** : Nettoyage de toutes les données
- **Dossier sécurisé** : Reçus stockés avec protection `.htaccess`

## 🎨 Personnalisation

### Hooks disponibles

```php
// Modifier le taux de change dynamiquement
add_filter('natcash_exchange_rate', function($rate, $order) {
    return $rate; // Votre logique personnalisée
}, 10, 2);

// Personnaliser les instructions de paiement
add_filter('natcash_payment_instructions', function($instructions, $order) {
    return $instructions; // Vos instructions personnalisées
}, 10, 2);

// Action après approbation du paiement
add_action('natcash_payment_approved', function($order_id) {
    // Votre logique personnalisée
});
```

### CSS personnalisé

```css
/* Personnaliser l'apparence du formulaire */
.natcash-payment-form {
    /* Vos styles personnalisés */
}

.natcash-amount-display {
    /* Personnaliser l'affichage du montant */
}
```

## 📁 Structure du projet

```
natcash-payment-gateway/
├── natcash-payment.php          # Fichier principal du plugin
├── includes/
│   └── class-natcash-gateway.php # Classe principale de la passerelle
├── templates/
│   └── payment-form.php         # Template du formulaire de paiement
├── assets/
│   ├── css/
│   │   ├── natcash-payment.css  # Styles frontend
│   │   └── natcash-admin.css    # Styles admin
│   ├── js/
│   │   ├── natcash-payment.js   # JavaScript frontend
│   │   └── natcash-admin.js     # JavaScript admin
│   └── images/
│       └── natcash-logo.png     # Logo Natcash
├── languages/
│   └── natcash-payment-fr_FR.po # Traductions françaises
├── uninstall.php                # Script de désinstallation
├── README.md                    # Documentation
└── README.txt                   # Description WordPress
```

## 🧪 Tests

### Tests manuels

1. **Configuration** : Vérifier tous les paramètres
2. **Checkout** : Tester le processus de commande complet
3. **Upload** : Tester différents types et tailles de fichiers
4. **Approbation** : Tester le workflow admin
5. **Emails** : Vérifier les notifications automatiques

### Tests automatisés (à venir)

```bash
# Installation des dépendances de test
composer install --dev

# Exécution des tests
vendor/bin/phpunit
```

## 🐛 Dépannage

### Problèmes courants

**Le plugin ne s'affiche pas au checkout**
- Vérifiez que WooCommerce est installé et actif
- Assurez-vous que la méthode est activée dans les paramètres

**Erreur d'upload de fichier**
- Vérifiez les permissions du dossier `wp-content/uploads/`
- Contrôlez la taille maximale d'upload PHP

**Conversion de devise incorrecte**
- Vérifiez le taux de change dans les paramètres
- Assurez-vous que les montants sont en USD

### Logs de débogage

Activez le débogage WordPress pour voir les logs détaillés :

```php
// Dans wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Voici comment contribuer :

1. **Fork** le repository
2. **Créez** une branche pour votre fonctionnalité (`git checkout -b feature/nouvelle-fonctionnalite`)
3. **Commitez** vos changements (`git commit -am 'Ajouter nouvelle fonctionnalité'`)
4. **Poussez** vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. **Créez** une Pull Request

### Standards de code

- Suivre les [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Commenter le code en français
- Tester toutes les modifications
- Mettre à jour la documentation

## 📈 Roadmap

### Version 1.1.0
- [ ] Support du créole haïtien
- [ ] API de vérification automatique
- [ ] Mode sandbox pour les tests

### Version 1.2.0
- [ ] Statistiques et rapports avancés
- [ ] Intégration avec d'autres passerelles haïtiennes
- [ ] Notifications push

### Version 2.0.0
- [ ] Interface de gestion avancée
- [ ] Support multi-devises
- [ ] API REST complète

## 📞 Support

- **Documentation** : [GitHub Wiki](https://github.com/beatbyflux/natcash-payment-gateway/wiki)
- **Issues** : [GitHub Issues](https://github.com/beatbyflux/natcash-payment-gateway/issues)
- **Email** : contact@beatbyflux.com
- **Discord** : [Serveur BeatByFlux](https://discord.gg/beatbyflux)

## 📄 Licence

Ce projet est sous licence GPL v2 ou ultérieure. Voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👥 Crédits

- **Développé par** : [BeatByFlux](https://beatbyflux.com)
- **Testé par** : La communauté e-commerce haïtienne
- **Inspiré par** : Les besoins du marché haïtien

---

**⭐ Si ce plugin vous aide, n'hésitez pas à lui donner une étoile sur GitHub !**

