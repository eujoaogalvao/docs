<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Correções específicas para problemas do ProElements
 */

/**
 * Desabilita notificações de atualização para ProElements
 */
function ebs_disable_proelements_update_notifications($value) {
    if (!is_object($value)) {
        return $value;
    }
    
    // Encontrar o arquivo do ProElements
    $proelements_file = ebs_find_proelements_plugin_file();
    
    if ($proelements_file) {
        // Remover da lista de plugins que precisam de atualização
        if (isset($value->response[$proelements_file])) {
            unset($value->response[$proelements_file]);
        }
        
        // Adicionar à lista de plugins sem atualização disponível
        if (!isset($value->no_update)) {
            $value->no_update = array();
        }
        
        if (!isset($value->no_update[$proelements_file])) {
            $value->no_update[$proelements_file] = new stdClass();
            $value->no_update[$proelements_file]->id = $proelements_file;
            $value->no_update[$proelements_file]->slug = dirname($proelements_file);
            $value->no_update[$proelements_file]->plugin = $proelements_file;
            $value->no_update[$proelements_file]->new_version = '1.0.0';
            $value->no_update[$proelements_file]->url = '';
            $value->no_update[$proelements_file]->package = '';
        }
    }
    
    return $value;
}

/**
 * Suprime warnings específicos do ProElements
 */
function ebs_suppress_proelements_warnings() {
    // Suprimir warnings do updater do ProElements
    $error_handler = function($errno, $errstr, $errfile, $errline) {
        // Se o erro é do updater do ProElements, suprimir
        if (strpos($errfile, 'proelements') !== false && strpos($errfile, 'updater.php') !== false) {
            if (strpos($errstr, 'Trying to access array offset on value of type bool') !== false) {
                return true; // Suprimir este warning
            }
        }
        
        // Se é warning sobre constantes WP_DEBUG já definidas, suprimir
        if (strpos($errstr, 'Constant WP_DEBUG already defined') !== false ||
            strpos($errstr, 'Constant WP_DEBUG_LOG already defined') !== false ||
            strpos($errstr, 'Constant WP_DEBUG_DISPLAY already defined') !== false) {
            return true; // Suprimir este warning
        }
        
        return false; // Deixar outros erros passarem
    };
    
    set_error_handler($error_handler, E_WARNING);
}

/**
 * Corrige o cabeçalho do ProElements se necessário
 */
function ebs_fix_proelements_header() {
    $proelements_file = ebs_find_proelements_plugin_file();
    
    if (!$proelements_file) {
        return false;
    }
    
    $plugin_path = WP_PLUGIN_DIR . '/' . $proelements_file;
    
    if (!file_exists($plugin_path)) {
        return false;
    }
    
    // Ler o conteúdo do arquivo
    $content = file_get_contents($plugin_path);
    
    // Verificar se já tem um cabeçalho válido
    if (strpos($content, 'Plugin Name:') !== false) {
        return true; // Já tem cabeçalho
    }
    
    // Adicionar cabeçalho se não existir
    $header = "<?php\n/**\n * Plugin Name: ProElements\n * Description: Elementor Pro features for free\n * Version: 1.0.0\n * Author: ProElements Team\n */\n\n";
    
    // Se o arquivo começa com <?php, remover e adicionar o novo cabeçalho
    if (strpos($content, '<?php') === 0) {
        $content = substr($content, 5); // Remove <?php
        $content = $header . $content;
        
        // Salvar o arquivo corrigido
        return file_put_contents($plugin_path, $content) !== false;
    }
    
    return false;
}

/**
 * Inicializar as correções
 */
function ebs_init_proelements_fixes() {
    // Desabilitar notificações de atualização
    add_filter('site_transient_update_plugins', 'ebs_disable_proelements_update_notifications');
    add_filter('transient_update_plugins', 'ebs_disable_proelements_update_notifications');
    
    // Suprimir warnings
    ebs_suppress_proelements_warnings();
    
    // Corrigir cabeçalho se necessário
    add_action('admin_init', 'ebs_fix_proelements_header');
}

// Inicializar as correções
ebs_init_proelements_fixes();