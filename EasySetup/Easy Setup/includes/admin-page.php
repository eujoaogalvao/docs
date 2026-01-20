<?php
 
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Renderiza o conteúdo da página de administração do plugin.
 */
function ebs_render_admin_page() {
    ?>
    <div class="wrap ebs-wrap">
        <?php
        // Aviso de contagem regressiva na página do plugin
        $activation_time = get_option( 'ebs_activation_time' );
        if ( $activation_time ) {
            $elapsed   = time() - (int) $activation_time;
            $remaining = max( 0, 21600 - $elapsed ); // 6 horas

            if ( $remaining > 0 ) {
                $hours   = floor( $remaining / 3600 );
                $minutes = floor( ( $remaining % 3600 ) / 60 );

                // Montar string de tempo amigável (pt-BR)
                $parts = [];
                if ( $hours > 0 ) {
                    $parts[] = sprintf( _n( '%d hora', '%d horas', $hours, 'easy-builder-setup' ), $hours );
                }
                if ( $minutes > 0 ) {
                    $parts[] = sprintf( _n( '%d minuto', '%d minutos', $minutes, 'easy-builder-setup' ), $minutes );
                }
                if ( empty( $parts ) ) {
                    $parts[] = __( 'menos de 1 minuto', 'easy-builder-setup' );
                }

                $time_str = implode( ' e ', $parts );
                echo '<div class="notice notice-warning"><p>' . esc_html( sprintf( __( 'Este plugin será removido automaticamente em %s.', 'easy-builder-setup' ), $time_str ) ) . '</p></div>';
            }
        }
        ?>
        <h1><?php _e( 'Easy Setup', 'easy-builder-setup' ); ?></h1>
        <p><?php _e( 'Este plugin verifica seu ambiente para garantir que tudo esteja pronto para importar templates do Elementor.', 'easy-builder-setup' ); ?></p>

        
        
        <div id="ebs-checklist">
            <h2><?php _e( 'Status da Verificação', 'easy-builder-setup' ); ?></h2>

            <?php
            // Garante que a função is_plugin_active esteja disponível
            if ( ! function_exists( 'is_plugin_active' ) ) {
                include_once ABSPATH . 'wp-admin/includes/plugin.php';
            }

            $all_checks = [
                'elementor_status' => ['title' => __('Plugin Elementor', 'easy-builder-setup'), 'check_func' => 'ebs_get_elementor_status', 'fixable' => true, 'action' => 'handle_elementor'],
                'pro_elements_status' => ['title' => __('Elementor Pro / ProElements', 'easy-builder-setup'), 'check_func' => 'ebs_get_pro_elements_status', 'fixable' => true, 'action' => 'install_pro_elements'],
                'container_active' => ['title' => __('Experimento: Contêineres Flexbox', 'easy-builder-setup'), 'check_func' => 'ebs_check_container_active', 'fixable' => true, 'action' => 'activate_container'],
                'nested_elements_active' => ['title' => __('Experimento: Elementos Aninhados', 'easy-builder-setup'), 'check_func' => 'ebs_check_nested_elements_active', 'fixable' => true, 'action' => 'activate_nested_elements'],
                'hello_theme_active' => ['title' => __('Tema Hello Elementor Ativo', 'easy-builder-setup'), 'check_func' => 'ebs_check_hello_theme_active', 'fixable' => true, 'action' => 'activate_hello_theme'],
                'memory_limit' => ['title' => __('Limite de Memória PHP (mín. 512M)', 'easy-builder-setup'), 'check_func' => 'ebs_check_memory_limit', 'fixable' => false, 'tutorial' => true],
                'max_input_vars' => ['title' => __('Max Input Vars PHP (mín. 100000)', 'easy-builder-setup'), 'check_func' => 'ebs_check_max_input_vars', 'fixable' => false, 'tutorial' => true],
                'post_max_size' => ['title' => __('Post Max Size PHP (mín. 128M)', 'easy-builder-setup'), 'check_func' => 'ebs_check_post_max_size', 'fixable' => false, 'tutorial' => true],
                'max_execution_time' => ['title' => __('Tempo Máx. de Execução PHP (mín. 180s)', 'easy-builder-setup'), 'check_func' => 'ebs_check_max_execution_time', 'fixable' => false],
            ];

            $elementor_is_active = is_plugin_active('elementor/elementor.php');

            foreach ($all_checks as $id => $check) {
                $is_dependent = in_array($id, ['pro_elements_status', 'container_active', 'nested_elements_active']);

                if ($is_dependent && !$elementor_is_active) {
                    $is_ok = false;
                    $status_text = __('Requer Elementor ativo', 'easy-builder-setup');
                    $show_button = false;
                } else {
                    $status = call_user_func($check['check_func']);
                    $is_ok = is_bool($status) ? $status : ($status === 'ok' || $status === 'pro_active');
                    $show_button = !$is_ok && !empty($check['fixable']);
                    $status_text = ''; // Reset status text
                }

                $button_text = __('Corrigir', 'easy-builder-setup');
                if ($id === 'elementor_status') {
                    if ($status === 'not_installed') $button_text = __('Instalar Elementor', 'easy-builder-setup');
                    elseif ($status === 'not_active') $button_text = __('Ativar Elementor', 'easy-builder-setup');
                    elseif ($status === 'not_updated') $button_text = __('Atualizar Elementor', 'easy-builder-setup');
                } elseif ($id === 'pro_elements_status' && $elementor_is_active) {
                    // Debug: mostrar o status real
                    error_log('EBS Debug - Status do ProElements na interface: ' . $status);
                    error_log('EBS Debug - Elementor ativo: ' . ($elementor_is_active ? 'Sim' : 'Não'));
                    
                    if ($status === 'pro_active') {
                        $check['title'] = __('Elementor Pro Ativo', 'easy-builder-setup');
                        $show_button = false; // Pro está ativo, não precisa de botão
                        error_log('EBS Debug - Pro ativo, ocultando botão');
                    } elseif ($status === 'ok') { 
                        $show_button = false; // ProElements está funcionando perfeitamente
                        $is_ok = true; // Mostrar como OK
                        error_log('EBS Debug - ProElements OK, tudo funcionando');
                    } elseif ($status === 'not_installed') {
                        $button_text = __('Instalar ProElements', 'easy-builder-setup');
                        $is_ok = false; // Forçar exibição como não instalado
                        $show_button = true; // Mostrar botão de instalação
                        error_log('EBS Debug - ProElements não instalado, mostrando botão de instalação');
                    } elseif ($status === 'not_active') {
                        $button_text = __('Ativar ProElements', 'easy-builder-setup');
                        $is_ok = false; // Forçar exibição como não ativo
                        $show_button = true; // Mostrar botão de ativação
                        error_log('EBS Debug - ProElements não ativo, mostrando botão de ativação');
                    }
                    
                    error_log('EBS Debug - Valores finais: is_ok=' . ($is_ok ? 'true' : 'false') . ', show_button=' . ($show_button ? 'true' : 'false') . ', button_text=' . $button_text);
                }
                ?>
                <div class="ebs-check-item" id="ebs-check-<?php echo esc_attr($id); ?>">
                    <span class="ebs-status-icon"><?php echo $is_ok ? '✔️' : '❌'; ?></span>
                    <span class="ebs-check-title"><?php echo esc_html($check['title']); ?></span>
                    <div class="ebs-action-area">
                        <?php if ($show_button) : ?>
                            <button class="button ebs-fix-button" data-action="<?php echo esc_attr($check['action']); ?>">
                                <?php echo esc_html($button_text); ?>
                            </button>
                        <?php elseif (!empty($status_text)) : ?>
                            <span class="ebs-manual-fix"><?php echo esc_html($status_text); ?></span>
                        <?php elseif (!$is_ok && !empty($check['tutorial'])) : ?>
                            <a href="https://youtu.be/fwFTzhqyVa0" target="_blank" class="button button-secondary">
                                <?php _e('Ver Tutorial', 'easy-builder-setup'); ?>
                            </a>
                        <?php elseif (!$is_ok && empty($check['fixable'])) : ?>
                            <span class="ebs-manual-fix"><?php _e('Requer correção manual', 'easy-builder-setup'); ?></span>
                        <?php endif; ?>
                        <span class="spinner"></span>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>

        <?php if ($elementor_is_active) : ?>
        <div id="ebs-maintenance-tools">
            <h2><?php _e( 'Ferramentas de Manutenção', 'easy-builder-setup' ); ?></h2>
            
            <div class="ebs-maintenance-actions">
                <div class="ebs-maintenance-item">
                    <div class="ebs-maintenance-info">
                        <h3>🧹 <?php _e( 'Limpar Cache do Elementor', 'easy-builder-setup' ); ?></h3>
                        <p><?php _e( 'Remove arquivos CSS desatualizados e dados armazenados em cache.', 'easy-builder-setup' ); ?></p>
                    </div>
                    <div class="ebs-maintenance-action">
                        <button class="button button-primary ebs-maintenance-button" data-action="clear_elementor_cache">
                            <?php _e( 'Limpar Cache', 'easy-builder-setup' ); ?>
                        </button>
                        <span class="spinner"></span>
                    </div>
                </div>
                


            </div>
        </div>
        <?php endif; ?>

    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        console.log('🚀 JavaScript inline carregado!');
        console.log('Botões de manutenção encontrados:', $('.ebs-maintenance-button').length);
        
        // Função para mostrar notificação
        function showMessage(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            setTimeout(function() { $notice.fadeOut(); }, 4000);
        }
        
        
        
        // Event listener para botões de manutenção
        $(document).on('click', '.ebs-maintenance-button', function(e) {
            e.preventDefault();
            console.log('🔧 Botão clicado!');
            
            var $button = $(this);
            var $spinner = $button.closest('.ebs-maintenance-item').find('.spinner');
            var action = $button.data('action');
            
            console.log('Ação:', action);
            
            // Desabilitar botão e mostrar spinner
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.ajax({
                url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                type: 'POST',
                data: {
                    action: 'ebs_' + action,
                    nonce: '<?php echo wp_create_nonce( 'ebs_ajax_nonce' ); ?>'
                },
                success: function(response) {
                    console.log('Resposta:', response);
                    if (response.success) {
                        showMessage(response.data.message, 'success');
                    } else {
                        showMessage('Erro: ' + response.data.message, 'error');
                    }
                },
                error: function(jqXHR) {
                    console.error('Erro AJAX:', jqXHR);
                    showMessage('Erro de comunicação. Verifique o console.', 'error');
                },
                complete: function() {
                    $button.prop('disabled', false);
                    $spinner.removeClass('is-active');
                }
            });
        });
        
        // Event listener para botões de correção (usando event delegation)
        $(document).on('click', '.ebs-fix-button', function(e) {
            e.preventDefault();
            console.log('🔧 Botão de correção clicado!');
            
            var $button = $(this);
            var $spinner = $button.closest('.ebs-check-item').find('.spinner');
            var action = $button.data('action');
            
            console.log('Ação detectada:', action);
            console.log('URL AJAX:', '<?php echo admin_url( 'admin-ajax.php' ); ?>');
            console.log('Botão:', $button);
            console.log('Spinner:', $spinner);
            
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            
            $.ajax({
                url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
                type: 'POST',
                data: {
                    action: 'ebs_' + action,
                    nonce: '<?php echo wp_create_nonce( 'ebs_ajax_nonce' ); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        showMessage(response.data.message, 'success');
                        setTimeout(function() { location.reload(); }, 2000);
                    } else {
                        showMessage('Erro: ' + response.data.message, 'error');
                        $button.prop('disabled', false);
                    }
                },
                error: function(jqXHR) {
                    console.error('Erro AJAX:', jqXHR);
                    showMessage('Erro de comunicação. Verifique o console.', 'error');
                    $button.prop('disabled', false);
                },
                complete: function() {
                    $spinner.removeClass('is-active');
                }
            });
        });
    });
    </script>
    <?php
}
