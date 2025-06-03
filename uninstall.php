<?php
/**
 * Fichier de désinstallation du plugin Natcash Payment Gateway
 * 
 * Ce fichier est exécuté lorsque le plugin est supprimé via l'interface d'administration WordPress
 */

// Empêcher l'accès direct
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Nettoyer les données du plugin lors de la désinstallation
 */
class NatcashPaymentUninstaller {
    
    /**
     * Exécuter la désinstallation
     */
    public static function uninstall() {
        // Supprimer les options du plugin
        self::delete_options();
        
        // Nettoyer les métadonnées des commandes
        self::clean_order_meta();
        
        // Supprimer les fichiers de reçus
        self::delete_receipt_files();
        
        // Nettoyer les tâches programmées
        self::clear_scheduled_tasks();
        
        // Supprimer les tables personnalisées si elles existent
        self::drop_custom_tables();
        
        // Nettoyer le cache
        self::clear_cache();
    }
    
    /**
     * Supprimer les options du plugin
     */
    private static function delete_options() {
        $options_to_delete = array(
            'woocommerce_natcash_settings',
            'natcash_payment_version',
            'natcash_payment_db_version',
            'natcash_payment_activation_date',
            'natcash_payment_stats',
        );
        
        foreach ($options_to_delete as $option) {
            delete_option($option);
            delete_site_option($option); // Pour les installations multisite
        }
    }
    
    /**
     * Nettoyer les métadonnées des commandes
     */
    private static function clean_order_meta() {
        global $wpdb;
        
        $meta_keys_to_delete = array(
            '_natcash_receipt_url',
            '_natcash_account_number',
            '_natcash_account_name',
            '_natcash_exchange_rate',
            '_natcash_amount_htg',
            '_natcash_payment_status',
            '_natcash_verification_date',
        );
        
        foreach ($meta_keys_to_delete as $meta_key) {
            $wpdb->delete(
                $wpdb->postmeta,
                array('meta_key' => $meta_key),
                array('%s')
            );
        }
    }
    
    /**
     * Supprimer les fichiers de reçus
     */
    private static function delete_receipt_files() {
        $upload_dir = wp_upload_dir();
        $natcash_dir = $upload_dir['basedir'] . '/natcash-receipts';
        
        if (is_dir($natcash_dir)) {
            self::delete_directory($natcash_dir);
        }
    }
    
    /**
     * Supprimer un dossier et tout son contenu
     */
    private static function delete_directory($dir) {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), array('.', '..'));
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                self::delete_directory($path);
            } else {
                unlink($path);
            }
        }
        
        return rmdir($dir);
    }
    
    /**
     * Nettoyer les tâches programmées
     */
    private static function clear_scheduled_tasks() {
        $scheduled_hooks = array(
            'natcash_cleanup_receipts',
            'natcash_daily_stats',
            'natcash_weekly_report',
        );
        
        foreach ($scheduled_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
    }
    
    /**
     * Supprimer les tables personnalisées
     */
    private static function drop_custom_tables() {
        global $wpdb;
        
        // Si des tables personnalisées ont été créées, les supprimer ici
        $tables_to_drop = array(
            $wpdb->prefix . 'natcash_transactions',
            $wpdb->prefix . 'natcash_logs',
        );
        
        foreach ($tables_to_drop as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
    }
    
    /**
     * Nettoyer le cache
     */
    private static function clear_cache() {
        // Nettoyer le cache WordPress
        wp_cache_flush();
        
        // Nettoyer les transients liés au plugin
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_natcash_%' 
             OR option_name LIKE '_transient_timeout_natcash_%'"
        );
        
        // Pour les installations multisite
        if (is_multisite()) {
            $wpdb->query(
                "DELETE FROM {$wpdb->sitemeta} 
                 WHERE meta_key LIKE '_site_transient_natcash_%' 
                 OR meta_key LIKE '_site_transient_timeout_natcash_%'"
            );
        }
    }
    
    /**
     * Nettoyer les capacités personnalisées
     */
    private static function remove_custom_capabilities() {
        $capabilities = array(
            'manage_natcash_payments',
            'view_natcash_receipts',
            'approve_natcash_payments',
        );
        
        $roles = array('administrator', 'shop_manager');
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $capability) {
                    $role->remove_cap($capability);
                }
            }
        }
    }
    
    /**
     * Nettoyer les données utilisateur (optionnel)
     * Cette fonction n'est pas appelée par défaut pour préserver les données utilisateur
     */
    private static function clean_user_data() {
        global $wpdb;
        
        // Supprimer les métadonnées utilisateur liées au plugin
        $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => 'natcash_payment_preferences'),
            array('%s')
        );
    }
    
    /**
     * Journaliser la désinstallation
     */
    private static function log_uninstall() {
        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'action' => 'plugin_uninstalled',
            'plugin' => 'natcash-payment-gateway',
            'version' => '1.0.0',
        );
        
        // Enregistrer dans les logs WordPress si disponible
        if (function_exists('error_log')) {
            error_log('Natcash Payment Gateway: Plugin désinstallé - ' . wp_json_encode($log_entry));
        }
    }
}

// Exécuter la désinstallation
NatcashPaymentUninstaller::uninstall();

// Journaliser la désinstallation
NatcashPaymentUninstaller::log_uninstall();

