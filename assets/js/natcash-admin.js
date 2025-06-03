/**
 * JavaScript pour le plugin Natcash Payment Gateway - Admin
 */

(function($) {
    'use strict';
    
    var NatcashAdmin = {
        
        /**
         * Initialiser l'admin
         */
        init: function() {
            this.bindEvents();
            this.initOrderActions();
            this.initReceiptModal();
        },
        
        /**
         * Lier les événements
         */
        bindEvents: function() {
            $(document).on('click', '.natcash-approve', this.approvePayment);
            $(document).on('click', '.natcash-reject', this.rejectPayment);
            $(document).on('click', '.natcash-receipt-view', this.viewReceipt);
            $(document).on('click', '.natcash-modal-close', this.closeModal);
        },
        
        /**
         * Initialiser les actions sur les commandes
         */
        initOrderActions: function() {
            // Ajouter des confirmations pour les actions critiques
            $('.natcash-reject').on('click', function(e) {
                if (!confirm('Êtes-vous sûr de vouloir rejeter ce paiement ?')) {
                    e.preventDefault();
                    return false;
                }
            });
        },
        
        /**
         * Initialiser la modal pour les reçus
         */
        initReceiptModal: function() {
            // Créer la modal si elle n'existe pas
            if ($('#natcash-receipt-modal').length === 0) {
                var modalHtml = `
                    <div id="natcash-receipt-modal" class="natcash-modal">
                        <div class="natcash-modal-content">
                            <span class="natcash-modal-close">&times;</span>
                            <div class="natcash-modal-body">
                                <h3>Reçu de paiement Natcash</h3>
                                <div class="receipt-container"></div>
                            </div>
                        </div>
                    </div>
                `;
                $('body').append(modalHtml);
            }
            
            // Fermer la modal en cliquant à l'extérieur
            $(document).on('click', '.natcash-modal', function(e) {
                if (e.target === this) {
                    NatcashAdmin.closeModal();
                }
            });
            
            // Fermer avec Escape
            $(document).on('keydown', function(e) {
                if (e.keyCode === 27) { // Escape
                    NatcashAdmin.closeModal();
                }
            });
        },
        
        /**
         * Approuver le paiement
         */
        approvePayment: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var orderId = $button.data('order-id');
            var nonce = $('#natcash_admin_nonce').val();
            
            if (!orderId || !nonce) {
                NatcashAdmin.showNotice('Erreur: Données manquantes', 'error');
                return;
            }
            
            // Désactiver le bouton et afficher le chargement
            $button.prop('disabled', true).text('Traitement...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'natcash_approve_payment',
                    order_id: orderId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        NatcashAdmin.showNotice(response.data.message, 'success');
                        
                        // Mettre à jour l'interface
                        $button.closest('.natcash-admin-actions').html(
                            '<p style="color: #00a32a; font-weight: bold;">✓ Paiement approuvé</p>'
                        );
                        
                        // Recharger la page après 2 secondes
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        NatcashAdmin.showNotice(response.data.message, 'error');
                        $button.prop('disabled', false).text('Approuver le paiement');
                    }
                },
                error: function() {
                    NatcashAdmin.showNotice('Erreur de communication avec le serveur', 'error');
                    $button.prop('disabled', false).text('Approuver le paiement');
                }
            });
        },
        
        /**
         * Rejeter le paiement
         */
        rejectPayment: function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var orderId = $button.data('order-id');
            var nonce = $('#natcash_admin_nonce').val();
            
            if (!orderId || !nonce) {
                NatcashAdmin.showNotice('Erreur: Données manquantes', 'error');
                return;
            }
            
            // Désactiver le bouton et afficher le chargement
            $button.prop('disabled', true).text('Traitement...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'natcash_reject_payment',
                    order_id: orderId,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        NatcashAdmin.showNotice(response.data.message, 'success');
                        
                        // Mettre à jour l'interface
                        $button.closest('.natcash-admin-actions').html(
                            '<p style="color: #d63638; font-weight: bold;">✗ Paiement rejeté</p>'
                        );
                        
                        // Recharger la page après 2 secondes
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        NatcashAdmin.showNotice(response.data.message, 'error');
                        $button.prop('disabled', false).text('Rejeter le paiement');
                    }
                },
                error: function() {
                    NatcashAdmin.showNotice('Erreur de communication avec le serveur', 'error');
                    $button.prop('disabled', false).text('Rejeter le paiement');
                }
            });
        },
        
        /**
         * Voir le reçu
         */
        viewReceipt: function(e) {
            e.preventDefault();
            
            var receiptUrl = $(this).attr('href');
            var $modal = $('#natcash-receipt-modal');
            var $container = $modal.find('.receipt-container');
            
            // Vider le conteneur
            $container.empty();
            
            // Déterminer le type de fichier
            var fileExtension = receiptUrl.split('.').pop().toLowerCase();
            
            if (['jpg', 'jpeg', 'png', 'gif'].indexOf(fileExtension) !== -1) {
                // Image
                var $img = $('<img>').attr('src', receiptUrl).css({
                    'max-width': '100%',
                    'height': 'auto',
                    'border': '1px solid #ddd',
                    'border-radius': '4px'
                });
                $container.append($img);
            } else if (fileExtension === 'pdf') {
                // PDF
                var $iframe = $('<iframe>').attr({
                    'src': receiptUrl,
                    'width': '100%',
                    'height': '500px',
                    'frameborder': '0'
                });
                $container.append($iframe);
            } else {
                // Lien de téléchargement
                var $link = $('<a>').attr({
                    'href': receiptUrl,
                    'target': '_blank',
                    'class': 'button button-primary'
                }).text('Télécharger le reçu');
                $container.append($link);
            }
            
            // Afficher la modal
            $modal.show();
        },
        
        /**
         * Fermer la modal
         */
        closeModal: function() {
            $('.natcash-modal').hide();
        },
        
        /**
         * Afficher une notification
         */
        showNotice: function(message, type) {
            type = type || 'info';
            
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible natcash-admin-notice">')
                .append('<p>' + message + '</p>')
                .append('<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button>');
            
            // Ajouter la notification
            $('.wrap h1').after($notice);
            
            // Gérer la fermeture
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
            
            // Auto-fermeture après 5 secondes pour les succès
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut(function() {
                        $(this).remove();
                    });
                }, 5000);
            }
        },
        
        /**
         * Utilitaires pour l'admin
         */
        utils: {
            /**
             * Formater une date
             */
            formatDate: function(dateString) {
                var date = new Date(dateString);
                return date.toLocaleDateString('fr-FR') + ' ' + date.toLocaleTimeString('fr-FR');
            },
            
            /**
             * Formater un montant
             */
            formatAmount: function(amount, currency) {
                currency = currency || 'HTG';
                return new Intl.NumberFormat('fr-FR', {
                    style: 'currency',
                    currency: currency
                }).format(amount);
            },
            
            /**
             * Copier dans le presse-papiers
             */
            copyToClipboard: function(text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(function() {
                        NatcashAdmin.showNotice('Copié dans le presse-papiers', 'success');
                    });
                } else {
                    // Fallback pour les navigateurs plus anciens
                    var $temp = $('<textarea>').val(text).appendTo('body').select();
                    document.execCommand('copy');
                    $temp.remove();
                    NatcashAdmin.showNotice('Copié dans le presse-papiers', 'success');
                }
            }
        },
        
        /**
         * Statistiques et rapports
         */
        stats: {
            /**
             * Initialiser les graphiques
             */
            initCharts: function() {
                // Placeholder pour les futurs graphiques
                console.log('Natcash Admin: Initialisation des graphiques');
            },
            
            /**
             * Mettre à jour les statistiques
             */
            updateStats: function() {
                // Placeholder pour la mise à jour des stats
                console.log('Natcash Admin: Mise à jour des statistiques');
            }
        }
    };
    
    // Initialiser quand le DOM est prêt
    $(document).ready(function() {
        NatcashAdmin.init();
    });
    
    // Exposer l'objet globalement
    window.NatcashAdmin = NatcashAdmin;
    
})(jQuery);

/**
 * Extensions pour WooCommerce Admin
 */
jQuery(document).ready(function($) {
    
    // Améliorer l'affichage des commandes Natcash dans la liste
    $('.column-order_status').each(function() {
        var $cell = $(this);
        var $order = $cell.closest('tr');
        
        if ($order.find('.natcash-payment-method').length > 0) {
            $cell.append('<span class="natcash-indicator" title="Paiement Natcash">💳</span>');
        }
    });
    
    // Ajouter des filtres rapides
    if ($('.tablenav .alignleft .actions').length > 0) {
        var $filterContainer = $('.tablenav .alignleft .actions').first();
        
        var natcashFilter = `
            <select name="natcash_status" id="natcash-status-filter">
                <option value="">Tous les paiements Natcash</option>
                <option value="pending">En attente</option>
                <option value="approved">Approuvés</option>
                <option value="rejected">Rejetés</option>
            </select>
        `;
        
        $filterContainer.append(natcashFilter);
    }
    
    // Améliorer l'interface des paramètres
    $('.woocommerce_page_wc-settings').find('input[name*="natcash"]').each(function() {
        var $input = $(this);
        var $row = $input.closest('tr');
        
        // Ajouter des icônes aux champs importants
        if ($input.attr('name').indexOf('account_number') !== -1) {
            $row.find('th').prepend('<span class="dashicons dashicons-phone" style="color: #0073aa;"></span> ');
        } else if ($input.attr('name').indexOf('exchange_rate') !== -1) {
            $row.find('th').prepend('<span class="dashicons dashicons-money-alt" style="color: #00a32a;"></span> ');
        }
    });
    
    // Validation en temps réel pour les paramètres
    $('input[name*="natcash_exchange_rate"]').on('input', function() {
        var value = parseFloat($(this).val());
        var $feedback = $(this).next('.rate-feedback');
        
        if ($feedback.length === 0) {
            $feedback = $('<span class="rate-feedback"></span>');
            $(this).after($feedback);
        }
        
        if (value < 1 || value > 1000) {
            $feedback.text('Taux inhabituel').css('color', '#d63638');
        } else {
            $feedback.text('Taux valide').css('color', '#00a32a');
        }
    });
});

