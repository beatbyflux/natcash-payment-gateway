/**
 * JavaScript pour le plugin Natcash Payment Gateway - Frontend
 */

(function($) {
    'use strict';
    
    var NatcashPayment = {
        
        /**
         * Initialiser le plugin
         */
        init: function() {
            this.bindEvents();
            this.initFileUpload();
            this.initFormValidation();
        },
        
        /**
         * Lier les événements
         */
        bindEvents: function() {
            $(document).on('change', '#natcash_receipt', this.handleFileSelection);
            $(document).on('click', '.remove-file', this.removeFile);
            $(document).on('updated_checkout', this.updateAmounts);
        },
        
        /**
         * Initialiser l'upload de fichier
         */
        initFileUpload: function() {
            var self = this;
            
            // Drag and drop
            $('.file-upload-container').on('dragover dragenter', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).addClass('drag-over');
            });
            
            $('.file-upload-container').on('dragleave dragend', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
            });
            
            $('.file-upload-container').on('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                $(this).removeClass('drag-over');
                
                var files = e.originalEvent.dataTransfer.files;
                if (files.length > 0) {
                    $('#natcash_receipt')[0].files = files;
                    self.handleFileSelection.call($('#natcash_receipt')[0]);
                }
            });
        },
        
        /**
         * Gérer la sélection de fichier
         */
        handleFileSelection: function() {
            var file = this.files[0];
            var $container = $(this).closest('.natcash-receipt-upload');
            var $preview = $container.find('.file-preview');
            var $error = $container.find('.upload-error');
            var $fileName = $container.find('.file-name');
            var $uploadText = $container.find('.upload-text');
            
            // Réinitialiser les messages d'erreur
            $error.hide();
            
            if (!file) {
                $preview.hide();
                $uploadText.text(natcash_ajax.messages.choose_file || 'Choisir un fichier');
                return;
            }
            
            // Validation du type de fichier
            var allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
            if (allowedTypes.indexOf(file.type) === -1) {
                NatcashPayment.showError($error, natcash_ajax.messages.invalid_file);
                this.value = '';
                return;
            }
            
            // Validation de la taille
            if (file.size > 5 * 1024 * 1024) { // 5MB
                NatcashPayment.showError($error, natcash_ajax.messages.file_too_large);
                this.value = '';
                return;
            }
            
            // Afficher l'aperçu
            $fileName.text(file.name);
            $preview.show();
            $uploadText.text('Fichier sélectionné');
            
            // Ajouter une classe de succès
            $container.addClass('file-selected');
            
            // Prévisualisation pour les images
            if (file.type.startsWith('image/')) {
                NatcashPayment.showImagePreview(file, $preview);
            }
        },
        
        /**
         * Afficher l'aperçu d'image
         */
        showImagePreview: function(file, $preview) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var $img = $('<img>').attr('src', e.target.result).css({
                    'max-width': '100px',
                    'max-height': '100px',
                    'margin-top': '10px',
                    'border-radius': '4px',
                    'border': '1px solid #ddd'
                });
                
                $preview.find('.preview-content').append($img);
            };
            reader.readAsDataURL(file);
        },
        
        /**
         * Supprimer le fichier
         */
        removeFile: function(e) {
            e.preventDefault();
            
            var $container = $(this).closest('.natcash-receipt-upload');
            var $fileInput = $container.find('#natcash_receipt');
            var $preview = $container.find('.file-preview');
            var $uploadText = $container.find('.upload-text');
            var $error = $container.find('.upload-error');
            
            $fileInput.val('');
            $preview.hide();
            $preview.find('img').remove();
            $uploadText.text('Choisir un fichier');
            $error.hide();
            $container.removeClass('file-selected');
        },
        
        /**
         * Initialiser la validation du formulaire
         */
        initFormValidation: function() {
            var self = this;
            
            // Validation avant soumission
            $('form.checkout').on('checkout_place_order_natcash', function() {
                return self.validateForm();
            });
            
            // Validation en temps réel
            $('#natcash_confirm').on('change', function() {
                if ($(this).is(':checked')) {
                    $(this).closest('.natcash-confirmation').removeClass('error');
                }
            });
        },
        
        /**
         * Valider le formulaire
         */
        validateForm: function() {
            var isValid = true;
            var $form = $('.natcash-payment-form');
            
            // Vérifier le fichier
            if (!$('#natcash_receipt').val()) {
                this.showError($('.upload-error'), natcash_ajax.messages.file_required);
                isValid = false;
            }
            
            // Vérifier la confirmation
            if (!$('#natcash_confirm').is(':checked')) {
                var $confirmation = $('.natcash-confirmation');
                $confirmation.addClass('error');
                this.showError($confirmation.find('.upload-error'), 'Veuillez confirmer que vous avez effectué le paiement');
                isValid = false;
            }
            
            if (!isValid) {
                // Faire défiler vers le formulaire
                $('html, body').animate({
                    scrollTop: $form.offset().top - 100
                }, 500);
            }
            
            return isValid;
        },
        
        /**
         * Mettre à jour les montants
         */
        updateAmounts: function() {
            // Cette fonction sera appelée quand le checkout est mis à jour
            // Utile si les montants changent dynamiquement
            console.log('Natcash: Montants mis à jour');
        },
        
        /**
         * Afficher une erreur
         */
        showError: function($errorContainer, message) {
            $errorContainer.text(message).show();
            
            // Masquer l'erreur après 5 secondes
            setTimeout(function() {
                $errorContainer.fadeOut();
            }, 5000);
        },
        
        /**
         * Afficher un message de succès
         */
        showSuccess: function($container, message) {
            var $success = $('<div class="natcash-success"></div>').text(message);
            $container.prepend($success);
            
            setTimeout(function() {
                $success.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        /**
         * Utilitaires
         */
        utils: {
            /**
             * Formater la taille de fichier
             */
            formatFileSize: function(bytes) {
                if (bytes === 0) return '0 Bytes';
                
                var k = 1024;
                var sizes = ['Bytes', 'KB', 'MB', 'GB'];
                var i = Math.floor(Math.log(bytes) / Math.log(k));
                
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            },
            
            /**
             * Obtenir l'extension du fichier
             */
            getFileExtension: function(filename) {
                return filename.split('.').pop().toLowerCase();
            },
            
            /**
             * Vérifier si c'est une image
             */
            isImage: function(file) {
                return file.type.startsWith('image/');
            }
        }
    };
    
    // Initialiser quand le DOM est prêt
    $(document).ready(function() {
        NatcashPayment.init();
    });
    
    // Exposer l'objet globalement pour les extensions
    window.NatcashPayment = NatcashPayment;
    
})(jQuery);

/**
 * Styles CSS additionnels injectés via JavaScript
 */
jQuery(document).ready(function($) {
    // Ajouter des styles pour le drag and drop
    var dragDropStyles = `
        <style>
        .file-upload-container.drag-over {
            background-color: #e3f2fd !important;
            border: 2px dashed #2196f3 !important;
        }
        
        .file-upload-container.drag-over .file-upload-label {
            background-color: #2196f3 !important;
            transform: scale(1.05);
        }
        
        .natcash-confirmation.error {
            border: 1px solid #d63638;
            background-color: #ffeaea;
            padding: 10px;
            border-radius: 4px;
        }
        
        .file-selected .file-upload-label {
            background-color: #00a32a !important;
        }
        
        .file-selected .file-upload-label:hover {
            background-color: #008a20 !important;
        }
        </style>
    `;
    
    $('head').append(dragDropStyles);
});

