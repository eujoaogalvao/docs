<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Verifica o status completo do Elementor (instalado, ativo, atualizado).
 * @return string 'ok' | 'not_installed' | 'not_active' | 'not_updated'
 */
function ebs_get_elementor_status() {
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $plugin_slug = 'elementor/elementor.php';
    $installed_plugins = get_plugins();

    // Debug: verificar se o plugin está na lista
    if ( ! isset( $installed_plugins[$plugin_slug] ) ) {
        return 'not_installed';
    }

    // Verificar se está ativo
    if ( ! is_plugin_active( $plugin_slug ) ) {
        return 'not_active';
    }

    // Verificar se precisa de atualização (só se estiver ativo)
    // Força a verificação de atualizações
    wp_update_plugins();
    $updates = get_site_transient( 'update_plugins' );
    if ( isset( $updates->response[$plugin_slug] ) ) {
        return 'not_updated';
    }

    return 'ok';
}

/**
 * Verifica se o ProElements está realmente ativo
 * @return bool
 */
function ebs_check_pro_elements_active() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    // Se o Elementor Pro oficial está ativo, retorna true
    if (is_plugin_active('elementor-pro/elementor-pro.php')) {
        return true;
    }
    
    // Procurar pelo ProElements
    $proelements_file = ebs_find_proelements_plugin_file();
    
    // Se encontrou e está ativo, retorna true
    if ($proelements_file && is_plugin_active($proelements_file)) {
        return true;
    }
    
    return false;
}

/**
 * Verifica o status do Elementor Pro / ProElements.
 * @return string 'pro_active' | 'not_installed' | 'not_active' | 'ok'
 */
function ebs_get_pro_elements_status() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    // Se o Pro oficial está ativo, está tudo certo
    if (is_plugin_active('elementor-pro/elementor-pro.php')) {
        return 'pro_active';
    }

    // Forçar atualização da lista de plugins
    wp_cache_delete('plugins', 'plugins');
    $all_plugins = get_plugins();
    
    // Verificar se existe algum plugin com "proelements" no nome do arquivo
    $proelements_exists = false;
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        if (stripos($plugin_file, 'proelements') !== false || 
            stripos($plugin_file, 'pro-elements') !== false) {
            $proelements_exists = true;
            break;
        }
    }
    
    // Se não existe nenhum plugin com "proelements" no nome, retorna not_installed
    if (!$proelements_exists) {
        error_log('EBS Debug - Nenhum plugin com "proelements" no nome encontrado');
        return 'not_installed';
    }
    
    // Usar a função de busca para encontrar o arquivo principal
    $proelements_file = ebs_find_proelements_plugin_file();
    
    // Se não encontrou nenhum arquivo principal, retorna not_installed
    if (!$proelements_file) {
        error_log('EBS Debug - ProElements NÃO encontrado');
        return 'not_installed';
    }
    
    error_log('EBS Debug - ProElements encontrado: ' . $proelements_file . ' (Ativo: ' . (is_plugin_active($proelements_file) ? 'Sim' : 'Não') . ')');
    
    // Se encontrou mas não está ativo
    if (!is_plugin_active($proelements_file)) {
        return 'not_active';
    }
    
    return 'ok'; // ProElements está instalado e ativo
}

/**
 * Verifica se o experimento "Contêineres Flexbox" do Elementor está ativo.
 * @return bool
 */
function ebs_check_container_active() {
    if ( ! class_exists( '\Elementor\Plugin' ) ) return false;
    return get_option( 'elementor_experiment-container' ) === 'active';
}

/**
 * Verifica se o experimento "Elementos Aninhados" do Elementor está ativo.
 * @return bool
 */
function ebs_check_nested_elements_active() {
    if ( ! class_exists( '\Elementor\Plugin' ) ) return false;
    return get_option( 'elementor_experiment-nested-elements' ) === 'active';
}

/**
 * Verifica se o tema "Hello Elementor" está ativo.
 * @return bool
 */
function ebs_check_hello_theme_active() {
    return wp_get_theme()->get_template() === 'hello-elementor';
}

/**
 * Encontra o arquivo principal do plugin ProElements após a instalação
 */
function ebs_find_proelements_plugin_file() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    // Forçar atualização da lista de plugins
    wp_cache_delete('plugins', 'plugins');
    $all_plugins = get_plugins();
    
    // Possíveis slugs do ProElements (incluindo variações do GitHub)
    $possible_slugs = [
        'pro-elements/pro-elements.php',
        'proelements/pro-elements.php', 
        'proelements/proelements.php',
        'proelements-main/pro-elements.php',
        'proelements-main/proelements.php',
        'pro-elements-main/pro-elements.php',
        'pro-elements/index.php',
        'proelements/index.php'
    ];
    
    foreach ($possible_slugs as $slug) {
        if (isset($all_plugins[$slug])) {
            return $slug;
        }
    }
    
    // Busca mais específica por ProElements (evitando nosso próprio plugin)
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        $name = isset($plugin_data['Name']) ? strtolower($plugin_data['Name']) : '';
        $description = isset($plugin_data['Description']) ? strtolower($plugin_data['Description']) : '';
        
        // Excluir nosso próprio plugin
        if (strpos($plugin_file, 'EasyBuilderSetup') !== false || 
            strpos($plugin_file, 'easy-builder-setup') !== false) {
            continue;
        }
        
        // Buscar especificamente por ProElements
        if (stripos($name, 'proelements') !== false || 
            stripos($name, 'pro elements') !== false ||
            stripos($plugin_file, 'proelements') !== false) {
            return $plugin_file;
        }
    }
    
    return false;
}

/**
 * Verifica se o limite de memória do PHP é de pelo menos 512M.
 * @return bool
 */
function ebs_check_memory_limit() {
    $memory_limit = ini_get( 'memory_limit' );
    $limit_bytes = wp_convert_hr_to_bytes( $memory_limit );
    // 512MB em bytes
    return $limit_bytes >= 536870912;
}

/**
 * Verifica se o tempo máximo de execução do PHP é de pelo menos 180 segundos.
 * @return bool
 */
function ebs_check_max_execution_time() {
    $max_execution_time = ini_get( 'max_execution_time' );
    return $max_execution_time >= 180;
}

/**
 * Verifica se o max_input_vars é de pelo menos 100000.
 * @return bool
 */
function ebs_check_max_input_vars() {
    $max_input_vars = ini_get( 'max_input_vars' );
    return $max_input_vars >= 100000;
}

/**
 * Verifica se o post_max_size é de pelo menos 128M.
 * @return bool
 */
function ebs_check_post_max_size() {
    $post_max_size = ini_get( 'post_max_size' );
    $size_bytes = wp_convert_hr_to_bytes( $post_max_size );
    // 128MB em bytes
    return $size_bytes >= 134217728;
}


