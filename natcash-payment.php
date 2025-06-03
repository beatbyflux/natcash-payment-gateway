<?php
/**
 * Plugin Name: Natcash Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/beatbyflux/natcash-payment-gateway
 * Description: Méthode de paiement manuelle pour Natcash en Haïti
 * Version: 1.0.0
 * Author: BeatByFlux
 * Author URI: https://beatbyflux.com
 * Text Domain: natcash-payment
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('NATCASH_PAYMENT_VERSION', '1.0.0');
define('NATCASH_PAYMENT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NATCASH_PAYMENT_PLUGIN_PATH', plugin_dir_path(__FILE__));

/**
 * Classe principale du plugin Natcash Payment
 */
class NatcashPaymentPlugin {
    
    /**
     * Instance unique du plugin
     */
    private static $instance = null;
    
    /**
     * Obtenir l'instance unique du plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé pour empêcher l'instanciation directe
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Initialiser le plugin
     */
    public function init() {
        // Vérifier si WooCommerce est actif
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Charger les fichiers de traduction
        load_plugin_textdomain('natcash-payment', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Inclure la classe principale de la passerelle
        include_once NATCASH_PAYMENT_PLUGIN_PATH . 'includes/class-natcash-gateway.php';
        
        // Ajouter la passerelle à WooCommerce
        add_filter('woocommerce_payment_gateways', array($this, 'add_gateway_class'));
        
        // Enregistrer les scripts et styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Hooks d'activation et de désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Ajouter la classe de passerelle à WooCommerce
     */
    public function add_gateway_class($gateways) {
        $gateways[] = 'WC_Natcash_Gateway';
        return $gateways;
    }
    
    /**
     * Enregistrer les scripts frontend
     */
    public function enqueue_scripts() {
        if (is_checkout() || is_wc_endpoint_url('order-pay')) {
            wp_enqueue_style(
                'natcash-payment-style',
                NATCASH_PAYMENT_PLUGIN_URL . 'assets/css/natcash-payment.css',
                array(),
                NATCASH_PAYMENT_VERSION
            );
            
            wp_enqueue_script(
                'natcash-payment-script',
                NATCASH_PAYMENT_PLUGIN_URL . 'assets/js/natcash-payment.js',
                array('jquery'),
                NATCASH_PAYMENT_VERSION,
                true
            );
            
            wp_localize_script('natcash-payment-script', 'natcash_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('natcash_nonce'),
                'messages' => array(
                    'file_required' => __('Veuillez télécharger votre reçu de paiement Natcash', 'natcash-payment'),
                    'invalid_file' => __('Format de fichier non autorisé. Utilisez JPG, PNG ou PDF uniquement', 'natcash-payment'),
                    'file_too_large' => __('Fichier trop volumineux. Maximum 5MB autorisé', 'natcash-payment'),
                    'upload_error' => __('Erreur lors du téléchargement. Veuillez réessayer', 'natcash-payment')
                )
            ));
        }
    }
    
    /**
     * Enregistrer les scripts admin
     */
    public function admin_enqueue_scripts($hook) {
        if ('woocommerce_page_wc-settings' === $hook || 'post.php' === $hook) {
            wp_enqueue_style(
                'natcash-admin-style',
                NATCASH_PAYMENT_PLUGIN_URL . 'assets/css/natcash-admin.css',
                array(),
                NATCASH_PAYMENT_VERSION
            );
            
            wp_enqueue_script(
                'natcash-admin-script',
                NATCASH_PAYMENT_PLUGIN_URL . 'assets/js/natcash-admin.js',
                array('jquery'),
                NATCASH_PAYMENT_VERSION,
                true
            );
        }
    }
    
    /**
     * Notice si WooCommerce n'est pas installé
     */
    public function woocommerce_missing_notice() {
        echo '<div class="error"><p><strong>' . 
             sprintf(
                 __('Natcash Payment Gateway nécessite WooCommerce pour fonctionner. Veuillez %sinstaller et activer WooCommerce%s.', 'natcash-payment'),
                 '<a href="' . admin_url('plugin-install.php?s=woocommerce&tab=search&type=term') . '">',
                 '</a>'
             ) . 
             '</strong></p></div>';
    }
    
    /**
     * Actions lors de l'activation du plugin
     */
    public function activate() {
        // Créer le dossier d'upload pour les reçus si nécessaire
        $upload_dir = wp_upload_dir();
        $natcash_dir = $upload_dir['basedir'] . '/natcash-receipts';
        
        if (!file_exists($natcash_dir)) {
            wp_mkdir_p($natcash_dir);
            
            // Créer un fichier .htaccess pour sécuriser le dossier
            $htaccess_content = "Options -Indexes\n";
            $htaccess_content .= "<Files *.php>\n";
            $htaccess_content .= "deny from all\n";
            $htaccess_content .= "</Files>\n";
            
            file_put_contents($natcash_dir . '/.htaccess', $htaccess_content);
        }
    }
    
    /**
     * Actions lors de la désactivation du plugin
     */
    public function deactivate() {
        // Nettoyer les tâches programmées si nécessaire
        wp_clear_scheduled_hook('natcash_cleanup_receipts');
    }
}

// Initialiser le plugin
NatcashPaymentPlugin::get_instance();

