<?php
/**
 * Plugin Name: Easy Setup
 * Plugin URI: https://easybuilder.com.br/
 * Description: Verifica e corrige automaticamente o ambiente WordPress para garantir que a importação de templates do Elementor funcione sem problemas.
 * Version: 1.0.0
 * Requires at least: 5.2
 * Requires PHP: 7.4
 * Author: Easy Builder Team
 * Author URI: https://easybuilder.com.br/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: easy-builder-setup
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Definir constantes do plugin
define( 'EBS_VERSION', '1.0.0' );
define( 'EBS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EBS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Incluir arquivos necessários
require_once EBS_PLUGIN_DIR . 'includes/admin-page.php';
require_once EBS_PLUGIN_DIR . 'includes/checks.php';
require_once EBS_PLUGIN_DIR . 'includes/actions.php';
require_once EBS_PLUGIN_DIR . 'includes/proelements-fixes.php';

// Hook de ativação do plugin
register_activation_hook( __FILE__, 'ebs_plugin_activation' );

// Hook de desativação do plugin
register_deactivation_hook( __FILE__, 'ebs_plugin_deactivation' );

/**
 * Função executada na ativação do plugin
 * Agenda a auto deleção após 6 horas
 */
function ebs_plugin_activation() {
    // Limpa agendamentos anteriores por segurança
    wp_clear_scheduled_hook( 'ebs_auto_delete_event' );
    // Registrar o timestamp de ativação
    update_option( 'ebs_activation_time', time() );
    // Agendar evento para auto delete em 6 horas (21600 segundos)
    if ( ! wp_next_scheduled( 'ebs_auto_delete_event' ) ) {
        wp_schedule_single_event( time() + 21600, 'ebs_auto_delete_event' );
    }
}

/**
 * Função executada na desativação do plugin
 * Remove o evento agendado se ainda não foi executado
 */
function ebs_plugin_deactivation() {
    // Remover evento agendado se existir
    wp_clear_scheduled_hook( 'ebs_auto_delete_event' );
    
    // Limpar opções relacionadas
    delete_option( 'ebs_activation_time' );
    delete_option( 'ebs_setup_finalized' );
}

/**
 * Função que executa a auto deleção do plugin
 */
function ebs_execute_auto_delete() {
    // Verificar se realmente passou o tempo necessário
    $activation_time = get_option( 'ebs_activation_time' );
    if ( ! $activation_time || ( time() - $activation_time ) < 21600 ) {
        return;
    }
    
    // Incluir arquivos necessários
    if ( ! function_exists( 'deactivate_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    // Desativar o plugin
    deactivate_plugins( plugin_basename( __FILE__ ) );
    
    // Limpar opções do banco de dados
    delete_option( 'ebs_activation_time' );
    delete_option( 'ebs_setup_finalized' );
    
    // Remover arquivos do plugin usando WordPress Filesystem API
    ebs_safe_delete_plugin();
}
add_action( 'ebs_auto_delete_event', 'ebs_execute_auto_delete' );

/**
 * Remove o plugin de forma segura
 */
function ebs_safe_delete_plugin() {
    // Usar a API de filesystem do WordPress
    if ( ! function_exists( 'WP_Filesystem' ) ) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    
    WP_Filesystem();
    global $wp_filesystem;
    
    $plugin_dir = EBS_PLUGIN_DIR;
    
    if ( $wp_filesystem && $wp_filesystem->is_dir( $plugin_dir ) ) {
        $wp_filesystem->rmdir( $plugin_dir, true );
    }
}

/**
 * Adiciona a página do plugin ao menu de administração do WordPress.
 */
function ebs_add_admin_menu() {
    add_menu_page(
        __( 'Easy Setup', 'easy-builder-setup' ),
        __( 'Easy Setup', 'easy-builder-setup' ),
        'manage_options',
        'easy-builder-setup',
        'ebs_render_admin_page',
        'dashicons-admin-settings',
        80
    );
}
add_action( 'admin_menu', 'ebs_add_admin_menu' );

/**
 * Enfileira os scripts e estilos para a página de administração do plugin.
 */
function ebs_enqueue_admin_assets( $hook ) {
    // Debug: mostrar qual hook está sendo usado
    if ( strpos( $hook, 'easy-builder-setup' ) !== false ) {
        error_log( 'EBS Debug: Hook detectado: ' . $hook );
    }
    
    // Verificar múltiplas possibilidades de hook da página
    $is_plugin_page = ( 
        $hook === 'toplevel_page_easy-builder-setup' || 
        $hook === 'easy-builder-setup' ||
        strpos( $hook, 'easy-builder-setup' ) !== false ||
        ( isset( $_GET['page'] ) && $_GET['page'] === 'easy-builder-setup' )
    );
    
    // CSS e JS específicos da página do plugin
    if ( $is_plugin_page ) {
        // CSS
        wp_enqueue_style(
            'ebs-admin-style',
            EBS_PLUGIN_URL . 'assets/css/admin-style.css',
            [],
            EBS_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'ebs-admin-script',
            EBS_PLUGIN_URL . 'assets/js/admin-script.js',
            [ 'jquery' ],
            EBS_VERSION,
            true
        );

        // Passa dados do PHP para o JavaScript específico da página
        wp_localize_script( 'ebs-admin-script', 'ebs_ajax_object', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ebs_ajax_nonce' ),
        ] );
        
        // Debug adicional
        error_log( 'EBS Debug: Scripts enfileirados para a página do plugin' );
    }

    // JavaScript global para funções de teste (carregado em todas as páginas admin)
    wp_enqueue_script(
        'ebs-global-functions',
        EBS_PLUGIN_URL . 'assets/js/global-functions.js',
        [ 'jquery' ],
        EBS_VERSION,
        true
    );

    // Passa dados do PHP para o JavaScript (para AJAX) - disponível globalmente
    wp_localize_script( 'ebs-global-functions', 'ebs_ajax_object', [
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'ebs_ajax_nonce' ),
    ] );
}
add_action( 'admin_enqueue_scripts', 'ebs_enqueue_admin_assets' );

/**
 * Exibe um aviso global no admin com a contagem regressiva de autodestruição.
 * Aviso não-dismissível, visível para administradores.
 */
function ebs_admin_autodelete_notice() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $activation_time = get_option( 'ebs_activation_time' );
    if ( ! $activation_time ) {
        return;
    }

    $elapsed   = time() - (int) $activation_time;
    $remaining = max( 0, 21600 - $elapsed ); // 6 horas

    if ( $remaining <= 0 ) {
        return; // Já deve ter sido removido ou será em breve
    }

    $hours   = floor( $remaining / 3600 );
    $minutes = floor( ( $remaining % 3600 ) / 60 );

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

    echo '<div class="notice notice-warning"><p>' . esc_html( sprintf( __( 'Este plugin se removerá automaticamente em %s.', 'easy-builder-setup' ), $time_str ) ) . '</p></div>';
}
add_action( 'admin_notices', 'ebs_admin_autodelete_notice' );
