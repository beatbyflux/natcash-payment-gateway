<?php
/**
 * Template du formulaire de paiement Natcash
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtenir la devise actuelle
$current_currency = get_woocommerce_currency();
$currency_symbol = get_woocommerce_currency_symbol();

// Calculer les montants
$original_amount = $total_usd;
$converted_amount = $total_htg;

?>

<div id="natcash-payment-form" class="natcash-payment-form">
    
    <div class="natcash-amount-display">
        <h4><?php _e('Montant à payer:', 'natcash-payment'); ?></h4>
        <?php if ($current_currency === 'HTG'): ?>
            <p class="natcash-amount-htg">
                <strong><?php echo number_format($original_amount, 2, '.', ',') . ' HTG'; ?></strong>
            </p>
        <?php else: ?>
            <p class="natcash-amount-original">
                <strong><?php echo $currency_symbol . number_format($original_amount, 2, '.', ',') . ' ' . $current_currency; ?></strong>
            </p>
            <p class="natcash-amount-converted">
                <?php _e('Équivalent:', 'natcash-payment'); ?> 
                <strong><?php echo number_format($converted_amount, 2, '.', ',') . ' HTG'; ?></strong>
                <small>(<?php printf(__('Taux: 1 %s = %s HTG', 'natcash-payment'), $current_currency, number_format($this->exchange_rate, 2)); ?>)</small>
            </p>
        <?php endif; ?>
    </div>

    <div class="natcash-instructions">
        <h4><?php _e('Instructions de paiement:', 'natcash-payment'); ?></h4>
        <div class="natcash-payment-details">
            <p><strong><?php _e('1. Envoyez le montant via Natcash:', 'natcash-payment'); ?></strong></p>
            <ul>
                <li><?php _e('Numéro de compte:', 'natcash-payment'); ?> <strong><?php echo esc_html($this->account_number); ?></strong></li>
                <li><?php _e('Nom du compte:', 'natcash-payment'); ?> <strong><?php echo esc_html($this->account_name); ?></strong></li>
                <li><?php _e('Montant:', 'natcash-payment'); ?> <strong><?php echo number_format($converted_amount, 2, '.', ',') . ' HTG'; ?></strong></li>
            </ul>
        </div>
    </div>
    
    <div class="natcash-receipt-upload">
        <h4><?php _e('Télécharger votre reçu de paiement', 'natcash-payment'); ?> <span class="required">*</span></h4>
        <div class="file-upload-container">
            <input type="file" 
                   id="natcash_receipt" 
                   name="natcash_receipt" 
                   accept=".jpg,.jpeg,.png,.pdf" 
                   required>
            <label for="natcash_receipt" class="file-upload-label">
                <span class="upload-icon">📎</span>
                <span class="upload-text"><?php _e('Choisir un fichier', 'natcash-payment'); ?></span>
            </label>
            <div class="file-info">
                <small><?php _e('Formats acceptés : JPG, PNG, PDF (Max 5MB)', 'natcash-payment'); ?></small>
            </div>
        </div>
        <div class="file-preview" style="display: none;">
            <div class="preview-content">
                <span class="file-name"></span>
                <button type="button" class="remove-file">×</button>
            </div>
        </div>
        <div class="upload-error" style="display: none;"></div>
    </div>
    
    <div class="natcash-confirmation">
        <label class="confirmation-checkbox">
            <input type="checkbox" id="natcash_confirm" name="natcash_confirm" required>
            <span class="checkmark"></span>
            <?php _e('Je confirme avoir effectué le paiement et téléchargé le reçu correspondant', 'natcash-payment'); ?>
        </label>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var fileInput = $('#natcash_receipt');
    var filePreview = $('.file-preview');
    var uploadError = $('.upload-error');
    var fileName = $('.file-name');
    var removeButton = $('.remove-file');
    
    // Gestion de la sélection de fichier
    fileInput.on('change', function() {
        var file = this.files[0];
        uploadError.hide();
        
        if (file) {
            // Vérifier le type de fichier
            var allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (allowedTypes.indexOf(file.type) === -1) {
                showError(natcash_ajax.messages.invalid_file);
                this.value = '';
                return;
            }
            
            // Vérifier la taille du fichier (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showError(natcash_ajax.messages.file_too_large);
                this.value = '';
                return;
            }
            
            // Afficher l'aperçu
            fileName.text(file.name);
            filePreview.show();
            $('.file-upload-label .upload-text').text('<?php _e("Fichier sélectionné", "natcash-payment"); ?>');
        }
    });
    
    // Supprimer le fichier
    removeButton.on('click', function() {
        fileInput.val('');
        filePreview.hide();
        $('.file-upload-label .upload-text').text('<?php _e("Choisir un fichier", "natcash-payment"); ?>');
    });
    
    // Fonction pour afficher les erreurs
    function showError(message) {
        uploadError.text(message).show();
    }
    
    // Validation avant soumission
    $('form.checkout').on('checkout_place_order_natcash', function() {
        if (!fileInput.val()) {
            showError(natcash_ajax.messages.file_required);
            $('html, body').animate({
                scrollTop: $('.natcash-payment-form').offset().top - 100
            }, 500);
            return false;
        }
        
        if (!$('#natcash_confirm').is(':checked')) {
            showError('<?php _e("Veuillez confirmer que vous avez effectué le paiement", "natcash-payment"); ?>');
            return false;
        }
        
        return true;
    });
});
</script>
