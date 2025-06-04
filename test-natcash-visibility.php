<?php
/**
 * Test de visibilité de la passerelle Natcash
 * 
 * Placez ce fichier dans le dossier racine de WordPress et accédez-y via votre navigateur
 * pour forcer l'affichage et tester la passerelle Natcash.
 * 
 * URL: http://votre-site.com/test-natcash-visibility.php
 */

// Charger WordPress
require_once('wp-config.php');
require_once('wp-load.php');

// Vérifier si l'utilisateur est administrateur
if (!current_user_can('manage_options')) {
    wp_die('Accès refusé. Vous devez être administrateur pour accéder à cette page.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Visibilité Natcash</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        .button { background: #0073aa; color: white; padding: 10px 15px; text-decoration: none; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>🧪 Test de Visibilité Natcash</h1>
    
    <?php
    echo '<div class="section">';
    echo '<h2>🔍 Diagnostic de la passerelle</h2>';
    
    // Vérifier WooCommerce
    if (!class_exists('WooCommerce')) {
        echo '<p class="error">❌ WooCommerce n\'est pas installé</p>';
        echo '</div></body></html>';
        exit;
    }
    
    echo '<p class="success">✅ WooCommerce est installé</p>';
    
    // Charger la classe Natcash si nécessaire
    if (!class_exists('WC_Natcash_Gateway')) {
        $plugin_path = WP_PLUGIN_DIR . '/natcash-payment-gateway/includes/class-natcash-gateway.php';
        if (file_exists($plugin_path)) {
            include_once $plugin_path;
            echo '<p class="info">ℹ️ Classe WC_Natcash_Gateway chargée manuellement</p>';
        } else {
            echo '<p class="error">❌ Fichier class-natcash-gateway.php non trouvé</p>';
            echo '</div></body></html>';
            exit;
        }
    }
    
    // Créer une instance de la passerelle
    try {
        $natcash_gateway = new WC_Natcash_Gateway();
        echo '<p class="success">✅ Instance WC_Natcash_Gateway créée</p>';
        
        // Tester les propriétés
        echo '<h3>⚙️ Configuration actuelle</h3>';
        echo '<p><strong>Activée:</strong> ' . ($natcash_gateway->enabled === 'yes' ? '✅ Oui' : '❌ Non') . '</p>';
        echo '<p><strong>Numéro de compte:</strong> ' . (!empty($natcash_gateway->account_number) ? '✅ Configuré (' . substr($natcash_gateway->account_number, 0, 4) . '...)' : '❌ Non configuré') . '</p>';
        echo '<p><strong>Nom du compte:</strong> ' . (!empty($natcash_gateway->account_name) ? '✅ Configuré (' . $natcash_gateway->account_name . ')' : '❌ Non configuré') . '</p>';
        echo '<p><strong>Taux de change:</strong> ' . $natcash_gateway->exchange_rate . '</p>';
        
        // Tester is_available()
        $is_available = $natcash_gateway->is_available();
        echo '<p><strong>Disponible:</strong> ' . ($is_available ? '✅ Oui' : '❌ Non') . '</p>';
        
        if (!$is_available) {
            echo '<h3>🔧 Raisons possibles de non-disponibilité</h3>';
            if ($natcash_gateway->enabled !== 'yes') {
                echo '<p class="error">❌ La passerelle n\'est pas activée</p>';
            }
            if (empty($natcash_gateway->account_number)) {
                echo '<p class="error">❌ Numéro de compte non configuré</p>';
            }
            if (empty($natcash_gateway->account_name)) {
                echo '<p class="error">❌ Nom du compte non configuré</p>';
            }
            if (empty($natcash_gateway->exchange_rate) || !is_numeric($natcash_gateway->exchange_rate) || $natcash_gateway->exchange_rate <= 0) {
                echo '<p class="error">❌ Taux de change invalide</p>';
            }
        }
        
        // Tester l'enregistrement dans WooCommerce
        echo '<h3>🔗 Enregistrement dans WooCommerce</h3>';
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        
        if (isset($payment_gateways['natcash'])) {
            echo '<p class="success">✅ Passerelle enregistrée dans WooCommerce</p>';
            $registered_gateway = $payment_gateways['natcash'];
            echo '<p><strong>Disponible via WC:</strong> ' . ($registered_gateway->is_available() ? '✅ Oui' : '❌ Non') . '</p>';
        } else {
            echo '<p class="error">❌ Passerelle NON enregistrée dans WooCommerce</p>';
            echo '<p class="info">ℹ️ Tentative d\'enregistrement manuel...</p>';
            
            // Forcer l'enregistrement
            WC()->payment_gateways->payment_gateways['natcash'] = $natcash_gateway;
            echo '<p class="success">✅ Passerelle enregistrée manuellement</p>';
        }
        
        // Afficher toutes les passerelles disponibles
        echo '<h3>📋 Toutes les passerelles</h3>';
        foreach ($payment_gateways as $gateway) {
            $status = $gateway->enabled === 'yes' ? '✅' : '❌';
            $available = $gateway->is_available() ? '✅' : '❌';
            echo '<p>' . $status . ' ' . $gateway->id . ' - ' . $gateway->title . ' (Disponible: ' . $available . ')</p>';
        }
        
        // Test de debug
        if (method_exists($natcash_gateway, 'debug_gateway_status')) {
            echo '<h3>🐛 Debug détaillé</h3>';
            $debug_info = $natcash_gateway->debug_gateway_status();
            echo '<pre>' . print_r($debug_info, true) . '</pre>';
        }
        
    } catch (Exception $e) {
        echo '<p class="error">❌ Erreur lors de la création de l\'instance: ' . $e->getMessage() . '</p>';
    }
    
    echo '</div>';
    
    // Actions de correction
    echo '<div class="section">';
    echo '<h2>🔧 Actions de correction</h2>';
    
    if (isset($natcash_gateway)) {
        if ($natcash_gateway->enabled !== 'yes') {
            echo '<p><a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=natcash') . '" class="button">⚙️ Activer Natcash</a></p>';
        }
        
        if (empty($natcash_gateway->account_number) || empty($natcash_gateway->account_name)) {
            echo '<p><a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=natcash') . '" class="button">⚙️ Configurer les paramètres</a></p>';
        }
        
        if ($natcash_gateway->is_available()) {
            echo '<p><a href="' . wc_get_checkout_url() . '" class="button">🛒 Tester le checkout</a></p>';
        }
    }
    
    echo '<p><a href="' . admin_url('plugins.php') . '" class="button">🔌 Gérer les plugins</a></p>';
    echo '</div>';
    
    // Instructions
    echo '<div class="section">';
    echo '<h2>📋 Instructions</h2>';
    echo '<ol>';
    echo '<li>Assurez-vous que le plugin Natcash est <strong>activé</strong></li>';
    echo '<li>Allez dans <strong>WooCommerce > Paramètres > Paiements</strong></li>';
    echo '<li>Cliquez sur <strong>Natcash > Gérer</strong></li>';
    echo '<li>Cochez <strong>"Activer Natcash"</strong></li>';
    echo '<li>Remplissez <strong>obligatoirement</strong> :';
    echo '<ul>';
    echo '<li>Numéro de compte Natcash</li>';
    echo '<li>Nom du compte Natcash</li>';
    echo '<li>Taux de change (ex: 134)</li>';
    echo '</ul></li>';
    echo '<li>Cliquez sur <strong>"Enregistrer les modifications"</strong></li>';
    echo '<li>Testez le checkout avec des produits dans le panier</li>';
    echo '</ol>';
    echo '</div>';
    ?>
    
    <p><em>⚠️ Supprimez ce fichier après utilisation pour des raisons de sécurité.</em></p>
</body>
</html>

