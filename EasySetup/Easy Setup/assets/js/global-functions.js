jQuery(document).ready(function($) {
    // Verificar se estamos no admin do WordPress
    if (typeof ebs_ajax_object === 'undefined') {
        return; // Sair se os dados AJAX não estiverem disponíveis
    }

    // Função para mostrar notificação de sucesso mais elegante
    function showSuccessMessage(message) {
        // Criar notificação no estilo WordPress
        var $notice = $('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');
        
        // Tentar adicionar após h1, se não existir, adicionar no topo da página
        if ($('.wrap h1').length > 0) {
            $('.wrap h1').after($notice);
        } else if ($('#wpbody-content').length > 0) {
            $('#wpbody-content').prepend($notice);
        } else {
            $('body').prepend($notice);
        }
        
        // Auto-remover após 4 segundos
        setTimeout(function() {
            $notice.fadeOut();
        }, 4000);
    }
    
    // Função para mostrar erro
    function showErrorMessage(message) {
        var $notice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
        
        // Tentar adicionar após h1, se não existir, adicionar no topo da página
        if ($('.wrap h1').length > 0) {
            $('.wrap h1').after($notice);
        } else if ($('#wpbody-content').length > 0) {
            $('#wpbody-content').prepend($notice);
        } else {
            $('body').prepend($notice);
        }
    }
    
    // Função para recarregar página com delay
    function reloadPageWithDelay(delay = 2000) {
        setTimeout(function() {
            location.reload();
        }, delay);
    }

    // Funções globais de teste disponíveis em qualquer página do admin
    window.ebs_test_functions = {
        clearCache: function() {
            console.log('🧹 Limpando cache do Elementor...');
            $.post(ebs_ajax_object.ajax_url, {
                action: 'ebs_clear_elementor_cache',
                nonce: ebs_ajax_object.nonce
            }, function(response) {
                console.log('✅ Cache:', response);
                if (response.success) {
                    showSuccessMessage('Cache do Elementor limpo com sucesso!');
                    console.log('✅ Cache limpo com sucesso!');
                } else {
                    showErrorMessage('Erro ao limpar cache: ' + response.data.message);
                    console.error('❌ Erro:', response.data.message);
                }
            }).fail(function(jqXHR) {
                console.error('❌ Erro na requisição:', jqXHR.responseText);
                showErrorMessage('Erro de comunicação ao limpar cache.');
            });
        },
        
        syncLibrary: function() {
            console.log('🔄 Sincronizando biblioteca do Elementor...');
            $.post(ebs_ajax_object.ajax_url, {
                action: 'ebs_sync_elementor_library', 
                nonce: ebs_ajax_object.nonce
            }, function(response) {
                console.log('✅ Biblioteca:', response);
                if (response.success) {
                    showSuccessMessage('Biblioteca do Elementor sincronizada com sucesso!');
                    console.log('✅ Biblioteca sincronizada com sucesso!');
                } else {
                    showErrorMessage('Erro ao sincronizar biblioteca: ' + response.data.message);
                    console.error('❌ Erro:', response.data.message);
                }
            }).fail(function(jqXHR) {
                console.error('❌ Erro na requisição:', jqXHR.responseText);
                showErrorMessage('Erro de comunicação ao sincronizar biblioteca.');
            });
        },
        
        debug: function() {
            console.log('🐛 Executando debug do Elementor...');
            $.post(ebs_ajax_object.ajax_url, {
                action: 'ebs_debug_elementor',
                nonce: ebs_ajax_object.nonce
            }, function(response) {
                console.log('%c=== 🔍 DEBUG INFO DO ELEMENTOR ===', 'color: #0073aa; font-weight: bold; font-size: 16px;');
                console.table(response.data.debug_info);
                console.log('%c===============================', 'color: #0073aa; font-weight: bold;');
                showSuccessMessage('Informações de debug exibidas no console.');
                console.log('✅ Debug executado com sucesso!');
            }).fail(function(jqXHR) {
                console.error('❌ Erro na requisição de debug:', jqXHR.responseText);
                showErrorMessage('Erro ao executar debug.');
            });
        },

        // Função para ir direto para a página do plugin
        goToPlugin: function() {
            window.location.href = ebs_ajax_object.ajax_url.replace('/admin-ajax.php', '/admin.php?page=easy-builder-setup');
        },

        // Função para mostrar ajuda
        help: function() {
            console.log('%c🔧 Easy Builder Setup - Funções Disponíveis:', 'color: #0073aa; font-weight: bold; font-size: 16px;');
            console.log('%c📋 Funções de Manutenção:', 'color: #666; font-weight: bold; font-size: 14px;');
            console.log('%c  ebs_test_functions.clearCache() - Limpar cache do Elementor', 'color: #666;');
            console.log('%c  ebs_test_functions.syncLibrary() - Sincronizar biblioteca do Elementor', 'color: #666;');
            console.log('%c🐛 Funções de Debug:', 'color: #666; font-weight: bold; font-size: 14px;');
            console.log('%c  ebs_test_functions.debug() - Mostrar informações de debug', 'color: #666;');
            console.log('%c🔗 Navegação:', 'color: #666; font-weight: bold; font-size: 14px;');
            console.log('%c  ebs_test_functions.goToPlugin() - Ir para a página do plugin', 'color: #666;');
            console.log('%c  ebs_test_functions.help() - Mostrar esta ajuda', 'color: #666;');
            console.log('%c💡 Dica: Todas as funções mostram notificações na tela e logs detalhados no console!', 'color: #d63638; font-style: italic;');
        }
    };
    
    // Mostrar informações de ajuda no console automaticamente (apenas uma vez por sessão)
    if (!sessionStorage.getItem('ebs_help_shown')) {
        setTimeout(function() {
            console.log('%c🔧 Easy Builder Setup Carregado!', 'color: #0073aa; font-weight: bold; font-size: 14px;');
            console.log('%c💡 Digite ebs_test_functions.help() para ver todas as funções disponíveis', 'color: #666; font-style: italic;');
            sessionStorage.setItem('ebs_help_shown', 'true');
        }, 1000);
    }
});