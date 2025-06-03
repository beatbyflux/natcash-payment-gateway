<?php
/**
 * Script de debug pour le plugin Natcash Payment Gateway
 * 
 * Placez ce fichier dans le dossier racine de WordPress et accédez-y via votre navigateur
 * pour diagnostiquer les problèmes avec le plugin Natcash.
 * 
 * URL: http://votre-site.com/debug-natcash.php
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
    <title>Debug Natcash Payment Gateway</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        .info { color: blue; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Debug Natcash Payment Gateway</h1>
    
    <?php
    echo '<div class="section">';
    echo '<h2>📋 Informations générales</h2>';
    
    // Vérifier WordPress
    echo '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
    
    // Vérifier WooCommerce
    if (class_exists('WooCommerce')) {
        echo '<p class="success">✅ WooCommerce est installé</p>';
        echo '<p><strong>WooCommerce Version:</strong> ' . WC()->version . '</p>';
        
        // Vérifier HPOS
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            $hpos_enabled = \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
            if ($hpos_enabled) {
                echo '<p class="info">ℹ️ HPOS (High-Performance Order Storage) est activé</p>';
            } else {
                echo '<p class="info">ℹ️ HPOS (High-Performance Order Storage) est désactivé</p>';
            }
        }
    } else {
        echo '<p class="error">❌ WooCommerce n\'est pas installé</p>';
    }
    
    // Vérifier le plugin Natcash
    if (class_exists('NatcashPaymentPlugin')) {
        echo '<p class="success">✅ Plugin Natcash Payment est chargé</p>';
    } else {
        echo '<p class="error">❌ Plugin Natcash Payment n\'est pas chargé</p>';
    }
    
    if (class_exists('WC_Natcash_Gateway')) {
        echo '<p class="success">✅ Classe WC_Natcash_Gateway est disponible</p>';
    } else {
        echo '<p class="error">❌ Classe WC_Natcash_Gateway n\'est pas disponible</p>';
    }
    echo '</div>';
    
    // Vérifier les passerelles de paiement
    echo '<div class="section">';
    echo '<h2>💳 Passerelles de paiement</h2>';
    
    if (function_exists('WC')) {
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        
        if (isset($payment_gateways['natcash'])) {
            $natcash_gateway = $payment_gateways['natcash'];
            echo '<p class="success">✅ Passerelle Natcash trouvée</p>';
            echo '<p><strong>ID:</strong> ' . $natcash_gateway->id . '</p>';
            echo '<p><strong>Titre:</strong> ' . $natcash_gateway->title . '</p>';
            echo '<p><strong>Activée:</strong> ' . ($natcash_gateway->enabled === 'yes' ? 'Oui' : 'Non') . '</p>';
            echo '<p><strong>Disponible:</strong> ' . ($natcash_gateway->is_available() ? 'Oui' : 'Non') . '</p>';
            
            // Vérifier la configuration
            echo '<h3>⚙️ Configuration</h3>';
            echo '<p><strong>Numéro de compte:</strong> ' . ($natcash_gateway->account_number ? '✅ Configuré' : '❌ Non configuré') . '</p>';
            echo '<p><strong>Nom du compte:</strong> ' . ($natcash_gateway->account_name ? '✅ Configuré' : '❌ Non configuré') . '</p>';
            echo '<p><strong>Taux de change:</strong> ' . $natcash_gateway->exchange_rate . '</p>';
            
        } else {
            echo '<p class="error">❌ Passerelle Natcash non trouvée dans la liste des passerelles</p>';
        }
        
        echo '<h3>📝 Toutes les passerelles disponibles:</h3>';
        echo '<ul>';
        foreach ($payment_gateways as $gateway) {
            $status = $gateway->enabled === 'yes' ? '✅' : '❌';
            echo '<li>' . $status . ' ' . $gateway->id . ' - ' . $gateway->title . '</li>';
        }
        echo '</ul>';
    }
    echo '</div>';
    
    // Vérifier les fichiers du plugin
    echo '<div class="section">';
    echo '<h2>📁 Fichiers du plugin</h2>';
    
    $plugin_files = array(
        'natcash-payment.php' => 'Fichier principal',
        'includes/class-natcash-gateway.php' => 'Classe de la passerelle',
        'templates/payment-form.php' => 'Template du formulaire',
        'assets/css/natcash-payment.css' => 'Styles frontend',
        'assets/js/natcash-payment.js' => 'JavaScript frontend'
    );
    
    $plugin_path = WP_PLUGIN_DIR . '/natcash-payment-gateway/';
    
    foreach ($plugin_files as $file => $description) {
        $file_path = $plugin_path . $file;
        if (file_exists($file_path)) {
            echo '<p class="success">✅ ' . $description . ' (' . $file . ')</p>';
        } else {
            echo '<p class="error">❌ ' . $description . ' (' . $file . ') - Fichier manquant</p>';
        }
    }
    echo '</div>';
    
    // Vérifier les hooks
    echo '<div class="section">';
    echo '<h2>🔗 Hooks et actions</h2>';
    
    global $wp_filter;
    
    $hooks_to_check = array(
        'woocommerce_payment_gateways',
        'plugins_loaded',
        'woocommerce_init'
    );
    
    foreach ($hooks_to_check as $hook) {
        if (isset($wp_filter[$hook])) {
            echo '<p class="success">✅ Hook ' . $hook . ' est enregistré</p>';
            
            // Afficher les callbacks pour ce hook
            foreach ($wp_filter[$hook]->callbacks as $priority => $callbacks) {
                foreach ($callbacks as $callback) {
                    if (is_array($callback['function'])) {
                        $class = is_object($callback['function'][0]) ? get_class($callback['function'][0]) : $callback['function'][0];
                        $method = $callback['function'][1];
                        echo '<p class="info">  → ' . $class . '::' . $method . ' (priorité: ' . $priority . ')</p>';
                    } else {
                        echo '<p class="info">  → ' . $callback['function'] . ' (priorité: ' . $priority . ')</p>';
                    }
                }
            }
        } else {
            echo '<p class="warning">⚠️ Hook ' . $hook . ' n\'est pas enregistré</p>';
        }
    }
    echo '</div>';
    
    // Vérifier les erreurs PHP
    echo '<div class="section">';
    echo '<h2>🐛 Erreurs PHP</h2>';
    
    $error_log = ini_get('error_log');
    if ($error_log && file_exists($error_log)) {
        $log_content = file_get_contents($error_log);
        $natcash_errors = array();
        
        $lines = explode("\n", $log_content);
        foreach ($lines as $line) {
            if (stripos($line, 'natcash') !== false || stripos($line, 'woocommerce') !== false) {
                $natcash_errors[] = $line;
            }
        }
        
        if (!empty($natcash_errors)) {
            echo '<p class="warning">⚠️ Erreurs trouvées dans les logs:</p>';
            echo '<pre>' . implode("\n", array_slice($natcash_errors, -10)) . '</pre>';
        } else {
            echo '<p class="success">✅ Aucune erreur liée à Natcash trouvée dans les logs</p>';
        }
    } else {
        echo '<p class="info">ℹ️ Fichier de log d\'erreurs non trouvé ou non configuré</p>';
    }
    echo '</div>';
    
    // Test de création d'instance
    echo '<div class="section">';
    echo '<h2>🧪 Test d\'instanciation</h2>';
    
    try {
        if (class_exists('WC_Natcash_Gateway')) {
            $test_gateway = new WC_Natcash_Gateway();
            echo '<p class="success">✅ Instance de WC_Natcash_Gateway créée avec succès</p>';
            echo '<p><strong>ID:</strong> ' . $test_gateway->id . '</p>';
            echo '<p><strong>Méthode disponible:</strong> ' . ($test_gateway->is_available() ? 'Oui' : 'Non') . '</p>';
        } else {
            echo '<p class="error">❌ Impossible de créer une instance de WC_Natcash_Gateway</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">❌ Erreur lors de la création de l\'instance: ' . $e->getMessage() . '</p>';
    }
    echo '</div>';
    
    // Recommandations
    echo '<div class="section">';
    echo '<h2>💡 Recommandations</h2>';
    
    if (!class_exists('WooCommerce')) {
        echo '<p class="error">🔧 Installez et activez WooCommerce</p>';
    }
    
    if (!class_exists('WC_Natcash_Gateway')) {
        echo '<p class="error">🔧 Vérifiez que le plugin Natcash est correctement installé et activé</p>';
    }
    
    if (class_exists('WC_Natcash_Gateway')) {
        $gateway = new WC_Natcash_Gateway();
        if (!$gateway->is_available()) {
            echo '<p class="warning">🔧 Configurez le numéro et nom de compte Natcash dans WooCommerce > Paramètres > Paiements</p>';
        }
    }
    
    echo '<p class="info">🔧 Après toute modification, videz le cache et testez à nouveau</p>';
    echo '</div>';
    ?>
    
    <div class="section">
        <h2>🔄 Actions</h2>
        <p><a href="<?php echo admin_url('admin.php?page=wc-settings&tab=checkout&section=natcash'); ?>" class="button">⚙️ Configurer Natcash</a></p>
        <p><a href="<?php echo admin_url('plugins.php'); ?>" class="button">🔌 Gérer les plugins</a></p>
        <p><a href="<?php echo wc_get_checkout_url(); ?>" class="button">🛒 Tester le checkout</a></p>
    </div>
    
    <p><em>⚠️ Supprimez ce fichier après utilisation pour des raisons de sécurité.</em></p>
</body>
</html>

