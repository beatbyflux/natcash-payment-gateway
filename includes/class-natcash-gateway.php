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
        add_action('add_meta_boxes', array($this, 'add_order_meta_boxes'));
        add_action('wp_ajax_natcash_approve_payment', array($this, 'approve_payment'));
        add_action('wp_ajax_natcash_reject_payment', array($this, 'reject_payment'));
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
                'title' => __('Taux de change USD vers Gourdes', 'natcash-payment'),
                'type' => 'number',
                'description' => __('Taux de conversion de USD vers HTG.', 'natcash-payment'),
                'default' => '134',
                'desc_tip' => true,
                'custom_attributes' => array(
                    'step' => '0.01',
                    'min' => '1'
                ),
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
        
        // Calculer le montant en gourdes
        $total_usd = WC()->cart->get_total('');
        $total_htg = $this->convert_to_htg($total_usd);
        
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
        
        // Sauvegarder les informations de paiement
        $order->update_meta_data('_natcash_receipt_url', $receipt_url);
        $order->update_meta_data('_natcash_account_number', $this->account_number);
        $order->update_meta_data('_natcash_account_name', $this->account_name);
        $order->update_meta_data('_natcash_exchange_rate', $this->exchange_rate);
        $order->update_meta_data('_natcash_amount_htg', $this->convert_to_htg($order->get_total()));
        
        // Changer le statut de la commande
        $order->update_status('on-hold', __('En attente de vérification du paiement Natcash.', 'natcash-payment'));
        
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
     * Convertir USD en HTG
     */
    public function convert_to_htg($amount_usd) {
        $rate = floatval($this->exchange_rate);
        return $amount_usd * $rate;
    }
    
    /**
     * Formater le montant en gourdes
     */
    public function format_htg_amount($amount) {
        return number_format($amount, 2, '.', ',') . ' HTG';
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
        
        $amount_htg = $order->get_meta('_natcash_amount_htg');
        $account_number = $order->get_meta('_natcash_account_number');
        $account_name = $order->get_meta('_natcash_account_name');
        
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
        
        $receipt_url = $order->get_meta('_natcash_receipt_url');
        $amount_htg = $order->get_meta('_natcash_amount_htg');
        $account_number = $order->get_meta('_natcash_account_number');
        $account_name = $order->get_meta('_natcash_account_name');
        
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
}

