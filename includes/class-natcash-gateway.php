<?php
/**
 * Classe de la passerelle de paiement Natcash
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Classe WC_Natcash_Gateway
 */
class WC_Natcash_Gateway extends WC_Payment_Gateway {
    
    /**
     * Constructeur de la classe
     */
    public function __construct() {
        $this->id = 'natcash';
        $this->icon = NATCASH_PAYMENT_PLUGIN_URL . 'assets/images/natcash-logo.png';
        $this->has_fields = true;
        $this->method_title = __('Natcash', 'natcash-payment');
        $this->method_description = __('Méthode de paiement manuelle pour Natcash en Haïti', 'natcash-payment');
        
        // Supports
        $this->supports = array(
            'products'
        );
        
        // Charger les paramètres
        $this->init_form_fields();
        $this->init_settings();
        
        // Définir les propriétés utilisateur
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->account_number = $this->get_option('account_number');
        $this->account_name = $this->get_option('account_name');
        $this->exchange_rate = $this->get_option('exchange_rate', '134');
        
        // Actions
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_thankyou_' . $this->id, array($this, 'thankyou_page'));
        add_action('woocommerce_email_before_order_table', array($this, 'email_instructions'), 10, 3);
        add_action('wp_ajax_natcash_upload_receipt', array($this, 'handle_receipt_upload'));
        add_action('wp_ajax_nopriv_natcash_upload_receipt', array($this, 'handle_receipt_upload'));
        
        // Hooks pour HPOS compatibility
        add_action('add_meta_boxes', array($this, 'add_order_meta_boxes'));
        add_action('woocommerce_process_shop_order_meta', array($this, 'save_order_meta_boxes'));
        
        // Actions AJAX pour admin
        add_action('wp_ajax_natcash_approve_payment', array($this, 'approve_payment'));
        add_action('wp_ajax_natcash_reject_payment', array($this, 'reject_payment'));
    }
    
    /**
     * Vérifier si la passerelle est disponible
     */
    public function is_available() {
        // Vérification de base : la passerelle doit être activée
        if ('yes' !== $this->enabled) {
            return false;
        }
        
        // Vérifier que les champs obligatoires sont configurés
        if (empty($this->account_number) || empty($this->account_name)) {
            return false;
        }
        
        // Vérifier que le taux de change est valide
        if (empty($this->exchange_rate) || !is_numeric($this->exchange_rate) || $this->exchange_rate <= 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Initialiser les champs de configuration
     */
    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Activer/Désactiver', 'natcash-payment'),
                'type' => 'checkbox',
                'label' => __('Activer la méthode de paiement Natcash', 'natcash-payment'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Titre', 'natcash-payment'),
                'type' => 'text',
                'description' => __('Titre affiché aux clients lors du checkout.', 'natcash-payment'),
                'default' => __('Natcash', 'natcash-payment'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'natcash-payment'),
                'type' => 'textarea',
                'description' => __('Description affichée aux clients lors du checkout.', 'natcash-payment'),
                'default' => __('Payez avec Natcash - Service de paiement mobile en Haïti.', 'natcash-payment'),
                'desc_tip' => true,
            ),
            'account_number' => array(
                'title' => __('Numéro de compte Natcash', 'natcash-payment'),
                'type' => 'text',
                'description' => __('Votre numéro de compte Natcash pour recevoir les paiements.', 'natcash-payment'),
                'default' => '',
                'desc_tip' => true,
                'custom_attributes' => array('required' => 'required'),
            ),
            'account_name' => array(
                'title' => __('Nom du compte Natcash', 'natcash-payment'),
                'type' => 'text',
                'description' => __('Le nom associé à votre compte Natcash.', 'natcash-payment'),
                'default' => '',
                'desc_tip' => true,
                'custom_attributes' => array('required' => 'required'),
            ),
            'exchange_rate' => array(
                'title' => __('Taux de change vers HTG', 'natcash-payment'),
                'type' => 'number',
                'description' => __('Taux de conversion de votre devise vers les Gourdes Haïtiennes (HTG). Par exemple, si 1 USD = 134 HTG, entrez 134. Si votre boutique utilise déjà HTG, ce taux ne sera pas utilisé.', 'natcash-payment'),
                'default' => '134',
                'desc_tip' => true,
                'custom_attributes' => array(
                    'step' => '0.01',
                    'min' => '0.01'
                )
            ),
        );
    }
    
    /**
     * Afficher les champs de paiement
     */
    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wptexturize($this->description));
        }
        
        // Vérifier que WooCommerce est disponible et que le panier existe
        if (!WC() || !WC()->cart) {
            echo '<p>' . __('Erreur: Impossible de calculer le montant.', 'natcash-payment') . '</p>';
            return;
        }
        
        // Obtenir le montant total du panier
        $total_amount = WC()->cart->get_total('edit');
        
        // Calculer le montant converti en HTG
        $total_htg = $this->convert_to_htg($total_amount);
        
        // Variables pour le template
        $total_usd = $total_amount; // Renommé pour compatibilité avec le template
        
        // Inclure le template de formulaire de paiement
        include NATCASH_PAYMENT_PLUGIN_PATH . 'templates/payment-form.php';
    }
    
    /**
     * Valider les champs de paiement
     */
    public function validate_fields() {
        if (empty($_POST['natcash_receipt'])) {
            wc_add_notice(__('Veuillez télécharger votre reçu de paiement Natcash', 'natcash-payment'), 'error');
            return false;
        }
        
        return true;
    }
    
    /**
     * Traiter le paiement
     */
    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return array(
                'result' => 'failure',
                'messages' => __('Commande introuvable.', 'natcash-payment')
            );
        }
        
        // Traiter l'upload du reçu
        $receipt_url = $this->process_receipt_upload($order_id);
        
        if (!$receipt_url) {
            wc_add_notice(__('Erreur lors du téléchargement du reçu. Veuillez réessayer.', 'natcash-payment'), 'error');
            return array(
                'result' => 'failure'
            );
        }
        
        // Sauvegarder les informations de paiement (compatible HPOS)
        $this->update_order_meta($order, '_natcash_receipt_url', $receipt_url);
        $this->update_order_meta($order, '_natcash_account_number', $this->account_number);
        $this->update_order_meta($order, '_natcash_account_name', $this->account_name);
        $this->update_order_meta($order, '_natcash_exchange_rate', $this->exchange_rate);
        $this->update_order_meta($order, '_natcash_amount_htg', $this->convert_to_htg($order->get_total()));
        
        // Changer le statut de la commande
        $order->update_status('on-hold', __('En attente de vérification du paiement Natcash.', 'natcash-payment'));
        
        // Sauvegarder la commande
        $order->save();
        
        // Vider le panier
        WC()->cart->empty_cart();
        
        // Envoyer l'email de confirmation
        $this->send_payment_instructions_email($order);
        
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order)
        );
    }
    
    /**
     * Traiter l'upload du reçu
     */
    private function process_receipt_upload($order_id) {
        if (!isset($_FILES['natcash_receipt']) || $_FILES['natcash_receipt']['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        
        $file = $_FILES['natcash_receipt'];
        
        // Vérifier le type de fichier
        $allowed_types = array('image/jpeg', 'image/png', 'application/pdf');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file['type'], $allowed_types)) {
            return false;
        }
        
        // Vérifier la taille du fichier (5MB max)
        if ($file['size'] > 5 * 1024 * 1024) {
            return false;
        }
        
        // Créer le dossier d'upload
        $upload_dir = wp_upload_dir();
        $natcash_dir = $upload_dir['basedir'] . '/natcash-receipts';
        
        if (!file_exists($natcash_dir)) {
            wp_mkdir_p($natcash_dir);
        }
        
        // Générer un nom de fichier unique
        $filename = 'receipt_' . $order_id . '_' . time() . '.' . $file_type['ext'];
        $filepath = $natcash_dir . '/' . $filename;
        
        // Déplacer le fichier
        if (move_uploaded_file($file['tmp_name'], $filepath)) {
            return $upload_dir['baseurl'] . '/natcash-receipts/' . $filename;
        }
        
        return false;
    }
    
    /**
     * Convertir le montant en gourdes haïtiennes
     */
    public function convert_to_htg($amount) {
        // Convertir en float pour s'assurer que c'est numérique
        $amount = floatval($amount);
        
        // Obtenir la devise actuelle de WooCommerce
        $current_currency = get_woocommerce_currency();
        
        // Si c'est déjà en HTG, pas de conversion nécessaire
        if ($current_currency === 'HTG') {
            return $amount;
        }
        
        // Pour toutes les autres devises, appliquer le taux de change
        $exchange_rate = floatval($this->exchange_rate);
        if ($exchange_rate <= 0) {
            $exchange_rate = 134; // Valeur par défaut
        }
        
        return $amount * $exchange_rate;
    }
    
    /**
     * Formater le montant en gourdes
     */
    public function format_htg_amount($amount) {
        $current_currency = get_woocommerce_currency();
        
        if ($current_currency === 'HTG') {
            return number_format($amount, 2, '.', ',') . ' HTG';
        } else {
            // Afficher la conversion
            $original_amount = floatval($amount) / floatval($this->exchange_rate);
            return number_format($original_amount, 2, '.', ',') . ' ' . $current_currency . ' = ' . number_format($amount, 2, '.', ',') . ' HTG';
        }
    }
    
    /**
     * Page de remerciement
     */
    public function thankyou_page($order_id) {
        $order = wc_get_order($order_id);
        
        if ($order && $order->get_payment_method() === $this->id) {
            echo '<div class="natcash-thankyou">';
            echo '<h3>' . __('Instructions de paiement Natcash', 'natcash-payment') . '</h3>';
            echo '<p>' . __('Votre commande a été reçue et est en attente de vérification du paiement.', 'natcash-payment') . '</p>';
            echo '<p>' . __('Nous vérifierons votre reçu de paiement et mettrons à jour le statut de votre commande sous peu.', 'natcash-payment') . '</p>';
            echo '</div>';
        }
    }
    
    /**
     * Instructions par email
     */
    public function email_instructions($order, $sent_to_admin, $plain_text = false) {
        if ($order->get_payment_method() !== $this->id || !$order->has_status('on-hold')) {
            return;
        }
        
        $amount_htg = $this->get_order_meta($order, '_natcash_amount_htg');
        $account_number = $this->get_order_meta($order, '_natcash_account_number');
        $account_name = $this->get_order_meta($order, '_natcash_account_name');
        
        if ($plain_text) {
            echo "\n" . __('INSTRUCTIONS DE PAIEMENT NATCASH', 'natcash-payment') . "\n";
            echo sprintf(__('Montant à payer: %s', 'natcash-payment'), $this->format_htg_amount($amount_htg)) . "\n";
            echo sprintf(__('Numéro de compte: %s', 'natcash-payment'), $account_number) . "\n";
            echo sprintf(__('Nom du compte: %s', 'natcash-payment'), $account_name) . "\n";
        } else {
            echo '<h3>' . __('Instructions de paiement Natcash', 'natcash-payment') . '</h3>';
            echo '<p><strong>' . sprintf(__('Montant à payer: %s', 'natcash-payment'), $this->format_htg_amount($amount_htg)) . '</strong></p>';
            echo '<p>' . sprintf(__('Numéro de compte: %s', 'natcash-payment'), $account_number) . '</p>';
            echo '<p>' . sprintf(__('Nom du compte: %s', 'natcash-payment'), $account_name) . '</p>';
        }
    }
    
    /**
     * Envoyer l'email d'instructions de paiement
     */
    private function send_payment_instructions_email($order) {
        $mailer = WC()->mailer();
        $message = $mailer->wrap_message(
            __('Instructions de paiement Natcash', 'natcash-payment'),
            sprintf(__('Merci pour votre commande. Voici les instructions pour compléter votre paiement Natcash.', 'natcash-payment'))
        );
        
        $mailer->send($order->get_billing_email(), __('Instructions de paiement - Commande #' . $order->get_order_number(), 'natcash-payment'), $message);
    }
    
    /**
     * Ajouter les meta boxes pour les commandes
     */
    public function add_order_meta_boxes() {
        add_meta_box(
            'natcash-payment-details',
            __('Détails du paiement Natcash', 'natcash-payment'),
            array($this, 'order_meta_box_content'),
            'shop_order',
            'side',
            'high'
        );
    }
    
    /**
     * Contenu de la meta box pour les commandes
     */
    public function order_meta_box_content($post) {
        $order = wc_get_order($post->ID);
        
        if ($order->get_payment_method() !== $this->id) {
            return;
        }
        
        $receipt_url = $this->get_order_meta($order, '_natcash_receipt_url');
        $amount_htg = $this->get_order_meta($order, '_natcash_amount_htg');
        $account_number = $this->get_order_meta($order, '_natcash_account_number');
        $account_name = $this->get_order_meta($order, '_natcash_account_name');
        
        echo '<div class="natcash-order-details">';
        
        if ($receipt_url) {
            echo '<p><strong>' . __('Reçu de paiement:', 'natcash-payment') . '</strong></p>';
            echo '<p><a href="' . esc_url($receipt_url) . '" target="_blank" class="button">' . __('Voir le reçu', 'natcash-payment') . '</a></p>';
        }
        
        echo '<p><strong>' . __('Montant en HTG:', 'natcash-payment') . '</strong> ' . $this->format_htg_amount($amount_htg) . '</p>';
        echo '<p><strong>' . __('Compte Natcash:', 'natcash-payment') . '</strong> ' . esc_html($account_number) . '</p>';
        echo '<p><strong>' . __('Nom du compte:', 'natcash-payment') . '</strong> ' . esc_html($account_name) . '</p>';
        
        if ($order->has_status('on-hold')) {
            echo '<div class="natcash-admin-actions">';
            echo '<button type="button" class="button button-primary natcash-approve" data-order-id="' . $order->get_id() . '">' . __('Approuver le paiement', 'natcash-payment') . '</button>';
            echo '<button type="button" class="button natcash-reject" data-order-id="' . $order->get_id() . '">' . __('Rejeter le paiement', 'natcash-payment') . '</button>';
            echo '</div>';
        }
        
        echo '</div>';
        
        wp_nonce_field('natcash_admin_action', 'natcash_admin_nonce');
    }
    
    /**
     * Sauvegarder les meta boxes pour les commandes
     */
    public function save_order_meta_boxes($order_id) {
        if (!isset($_POST['natcash_admin_nonce']) || !wp_verify_nonce($_POST['natcash_admin_nonce'], 'natcash_admin_action')) {
            return;
        }
        
        if (isset($_POST['_natcash_receipt_url'])) {
            update_post_meta($order_id, '_natcash_receipt_url', sanitize_text_field($_POST['_natcash_receipt_url']));
        }
        
        if (isset($_POST['_natcash_account_number'])) {
            update_post_meta($order_id, '_natcash_account_number', sanitize_text_field($_POST['_natcash_account_number']));
        }
        
        if (isset($_POST['_natcash_account_name'])) {
            update_post_meta($order_id, '_natcash_account_name', sanitize_text_field($_POST['_natcash_account_name']));
        }
        
        if (isset($_POST['_natcash_exchange_rate'])) {
            update_post_meta($order_id, '_natcash_exchange_rate', sanitize_text_field($_POST['_natcash_exchange_rate']));
        }
        
        if (isset($_POST['_natcash_amount_htg'])) {
            update_post_meta($order_id, '_natcash_amount_htg', sanitize_text_field($_POST['_natcash_amount_htg']));
        }
    }
    
    /**
     * Mettre à jour les métadonnées de commande (compatible HPOS)
     */
    private function update_order_meta($order, $key, $value) {
        if (method_exists($order, 'update_meta_data')) {
            // HPOS compatible
            $order->update_meta_data($key, $value);
        } else {
            // Fallback pour les anciennes versions
            update_post_meta($order->get_id(), $key, $value);
        }
    }
    
    /**
     * Obtenir les métadonnées de commande (compatible HPOS)
     */
    private function get_order_meta($order, $key, $single = true) {
        if (method_exists($order, 'get_meta')) {
            // HPOS compatible
            return $order->get_meta($key, $single);
        } else {
            // Fallback pour les anciennes versions
            return get_post_meta($order->get_id(), $key, $single);
        }
    }
    
    /**
     * Approuver le paiement
     */
    public function approve_payment() {
        check_ajax_referer('natcash_admin_action', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Permissions insuffisantes.', 'natcash-payment'));
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if ($order && $order->get_payment_method() === $this->id) {
            $order->payment_complete();
            $order->add_order_note(__('Paiement Natcash approuvé par l\'administrateur.', 'natcash-payment'));
            
            wp_send_json_success(array(
                'message' => __('Paiement approuvé avec succès.', 'natcash-payment')
            ));
        }
        
        wp_send_json_error(array(
            'message' => __('Erreur lors de l\'approbation du paiement.', 'natcash-payment')
        ));
    }
    
    /**
     * Rejeter le paiement
     */
    public function reject_payment() {
        check_ajax_referer('natcash_admin_action', 'nonce');
        
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('Permissions insuffisantes.', 'natcash-payment'));
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if ($order && $order->get_payment_method() === $this->id) {
            $order->update_status('failed', __('Paiement Natcash rejeté par l\'administrateur.', 'natcash-payment'));
            
            wp_send_json_success(array(
                'message' => __('Paiement rejeté.', 'natcash-payment')
            ));
        }
        
        wp_send_json_error(array(
            'message' => __('Erreur lors du rejet du paiement.', 'natcash-payment')
        ));
    }
    
    /**
     * Gérer l'upload AJAX du reçu
     */
    public function handle_receipt_upload() {
        check_ajax_referer('natcash_nonce', 'nonce');
        
        if (!isset($_FILES['receipt'])) {
            wp_send_json_error(array(
                'message' => __('Aucun fichier reçu.', 'natcash-payment')
            ));
        }
        
        $file = $_FILES['receipt'];
        
        // Validation du fichier
        $allowed_types = array('image/jpeg', 'image/png', 'application/pdf');
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array(
                'message' => __('Format de fichier non autorisé. Utilisez JPG, PNG ou PDF uniquement', 'natcash-payment')
            ));
        }
        
        if ($file['size'] > 5 * 1024 * 1024) {
            wp_send_json_error(array(
                'message' => __('Fichier trop volumineux. Maximum 5MB autorisé', 'natcash-payment')
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Fichier validé avec succès.', 'natcash-payment')
        ));
    }
    
    /**
     * Fonction de debug pour vérifier l'état de la passerelle
     */
    public function debug_gateway_status() {
        $debug_info = array(
            'enabled' => $this->enabled,
            'account_number' => !empty($this->account_number) ? 'Configuré' : 'Non configuré',
            'account_name' => !empty($this->account_name) ? 'Configuré' : 'Non configuré',
            'exchange_rate' => $this->exchange_rate,
            'is_available' => $this->is_available() ? 'Oui' : 'Non',
            'woocommerce_loaded' => class_exists('WooCommerce') ? 'Oui' : 'Non',
            'cart_exists' => (WC() && WC()->cart) ? 'Oui' : 'Non',
            'cart_empty' => (WC() && WC()->cart) ? (WC()->cart->is_empty() ? 'Oui' : 'Non') : 'N/A'
        );
        
        error_log('Natcash Gateway Debug: ' . print_r($debug_info, true));
        return $debug_info;
    }
}
