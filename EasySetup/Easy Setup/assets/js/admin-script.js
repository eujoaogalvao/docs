// Teste imediato para verificar se o arquivo está sendo carregado
console.log('🚀 ARQUIVO ADMIN-SCRIPT.JS CARREGADO!');

jQuery(document).ready(function($) {
    // Debug inicial
    console.log('🔧 Easy Builder Setup - Admin Script carregado!');
    console.log('jQuery disponível:', typeof $ !== 'undefined');
    console.log('ebs_ajax_object disponível:', typeof ebs_ajax_object !== 'undefined');
    if (typeof ebs_ajax_object !== 'undefined') {
        console.log('AJAX URL:', ebs_ajax_object.ajax_url);
        console.log('Nonce:', ebs_ajax_object.nonce);
    }
    
    // Verificar botões após carregamento
    setTimeout(function() {
        console.log('Botões de correção encontrados:', $('.ebs-fix-button').length);
        console.log('Botões de manutenção encontrados:', $('.ebs-maintenance-button').length);
    }, 500);

    // Função para mostrar notificação de sucesso mais elegante
    function showSuccessMessage(message) {
        // Criar notificação no estilo WordPress
        var $notice = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);
        
        // Auto-remover após 3 segundos
        setTimeout(function() {
            $notice.fadeOut();
        }, 3000);
    }
    
    // Função para mostrar erro
    function showErrorMessage(message) {
        var $notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after($notice);
    }
    
    // Função para recarregar página com delay
    function reloadPageWithDelay(delay = 2000) {
        setTimeout(function() {
            location.reload();
        }, delay);
    }

    // Função genérica para executar ações AJAX
    function executeAjaxAction($button, action) {
        var $item = $button.closest('.ebs-check-item, .ebs-maintenance-item');
        var $spinner = $item.find('.spinner');

        // Desabilitar botão e mostrar spinner
        $button.prop('disabled', true);
        $spinner.addClass('is-active');

        $.ajax({
            url: ebs_ajax_object.ajax_url,
            type: 'POST',
            data: {
                action: 'ebs_' + action, // Adiciona o prefixo ebs_
                nonce: ebs_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Mostrar mensagem de sucesso
                    showSuccessMessage(response.data.message);
                    
                    // Para ferramentas de manutenção, não recarregar a página
                    if (action.includes('cache') || action.includes('library') || action.includes('debug')) {
                        // Apenas reabilitar o botão após 2 segundos
                        setTimeout(function() {
                            $button.prop('disabled', false);
                        }, 2000);
                    } else {
                        // Para correções, recarregar a página para mostrar o novo status
                        reloadPageWithDelay(2000);
                    }
                } else {
                    // Reabilitar botão e mostrar erro
                    $button.prop('disabled', false);
                    showErrorMessage(response.data.message);
                }
            },
            error: function(jqXHR) {
                // Reabilitar botão e mostrar erro genérico
                $button.prop('disabled', false);
                // Tenta mostrar o erro do PHP se disponível, como aprendido no progress.md
                var errorMessage = 'Erro de comunicação. Verifique o console do navegador e os logs do PHP.';
                if (jqXHR.responseText) {
                    console.error('Erro do servidor:', jqXHR.responseText);
                    errorMessage = 'Erro retornado pelo servidor. Verifique o console para detalhes.';
                }
                showErrorMessage(errorMessage);
            },
            complete: function() {
                // Esconder spinner
                $spinner.removeClass('is-active');
            }
        });
    }

    // Event listener para botões de correção
    $('.ebs-fix-button').on('click', function(e) {
        e.preventDefault();
        var action = $(this).data('action');
        executeAjaxAction($(this), action);
    });

    // Event listener para botões de manutenção
    $(document).on('click', '.ebs-maintenance-button', function(e) {
        e.preventDefault();
        console.log('🔧 Botão de manutenção clicado!');
        var action = $(this).data('action');
        console.log('Ação:', action);
        
        // Verificar se o objeto AJAX está disponível
        if (typeof ebs_ajax_object === 'undefined') {
            console.error('❌ ebs_ajax_object não está disponível!');
            alert('Erro: Dados AJAX não carregados. Recarregue a página.');
            return;
        }
        
        executeAjaxAction($(this), action);
    });
});
