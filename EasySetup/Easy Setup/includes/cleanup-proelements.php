<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Limpa completamente qualquer instalação problemática do ProElements
 */
function ebs_cleanup_broken_proelements() {
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    // Inicializar WP_Filesystem
    global $wp_filesystem;
    if (!$wp_filesystem) {
        WP_Filesystem();
    }
    
    // 1. Limpar entradas problemáticas da lista de plugins ativos
    $active_plugins = get_option('active_plugins', []);
    $updated_active_plugins = [];
    $removed_plugins = [];
    
    foreach ($active_plugins as $plugin) {
        // Verificar se é um plugin ProElements
        if (stripos($plugin, 'proelements') !== false || stripos($plugin, 'pro-elements') !== false) {
            // Verificar se o arquivo realmente existe
            if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
                $removed_plugins[] = $plugin;
                continue; // Não adicionar à lista atualizada
            }
        }
        $updated_active_plugins[] = $plugin;
    }
    
    // Atualizar lista de plugins ativos se houve mudança
    if (count($updated_active_plugins) !== count($active_plugins)) {
        update_option('active_plugins', $updated_active_plugins);
    }
    
    // 2. Limpar também da lista de plugins ativos da rede (se for multisite)
    if (is_multisite()) {
        $network_active_plugins = get_site_option('active_sitewide_plugins', []);
        $updated_network_plugins = [];
        
        foreach ($network_active_plugins as $plugin => $time) {
            if (stripos($plugin, 'proelements') !== false || stripos($plugin, 'pro-elements') !== false) {
                if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin)) {
                    continue; // Não adicionar à lista atualizada
                }
            }
            $updated_network_plugins[$plugin] = $time;
        }
        
        if (count($updated_network_plugins) !== count($network_active_plugins)) {
            update_site_option('active_sitewide_plugins', $updated_network_plugins);
        }
    }
    
    // 3. Remover entradas do banco de dados e cache
    wp_cache_delete('plugins', 'plugins');
    wp_cache_delete('active_plugins', 'options');
    if (is_multisite()) {
        wp_cache_delete('active_sitewide_plugins', 'site-options');
    }
    
    // 4. Encontrar e remover todos os diretórios relacionados ao ProElements
    $removed_dirs = [];
    
    if (is_dir(WP_PLUGIN_DIR)) {
        $dirs = scandir(WP_PLUGIN_DIR);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir(WP_PLUGIN_DIR . '/' . $dir)) {
                // Verificar se o diretório contém "proelements" ou tem padrão do GitHub
                if (stripos($dir, 'proelements') !== false || 
                    preg_match('/proelements-proelements-[a-f0-9]+/', $dir) ||
                    preg_match('/proelements-[a-f0-9]+/', $dir)) {
                    
                    $full_path = WP_PLUGIN_DIR . '/' . $dir;
                    if ($wp_filesystem->delete($full_path, true)) {
                        $removed_dirs[] = $dir;
                    }
                }
            }
        }
    }
    
    // 5. Limpar cache novamente
    wp_cache_delete('plugins', 'plugins');
    wp_cache_flush();
    
    return [
        'removed_dirs' => $removed_dirs,
        'removed_plugins' => $removed_plugins
    ];
}

/**
 * Instala o ProElements de forma limpa e robusta
 */
function ebs_clean_install_proelements() {
    error_log('EBS Debug - Iniciando ebs_clean_install_proelements()');
    
    // 1. Limpar qualquer instalação problemática primeiro
    error_log('EBS Debug - Chamando ebs_cleanup_broken_proelements()');
    $cleanup_result = ebs_cleanup_broken_proelements();
    error_log('EBS Debug - Resultado da limpeza: ' . print_r($cleanup_result, true));
    $removed_dirs = $cleanup_result['removed_dirs'];
    
    // 2. Aguardar um momento para o sistema processar
    error_log('EBS Debug - Aguardando 2 segundos');
    sleep(2);
    
    // 3. Instalar o ProElements
    error_log('EBS Debug - Incluindo arquivos necessários para instalação');
    include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    include_once ABSPATH . 'wp-admin/includes/file.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    
    // Obter a URL da última versão via GitHub API
    error_log('EBS Debug - Obtendo URL da última versão via GitHub API');
    $api_url = 'https://api.github.com/repos/proelements/proelements/releases/latest';
    $api_response = wp_remote_get($api_url, ['timeout' => 15]);
    
    if (is_wp_error($api_response)) {
        error_log('EBS Debug - Erro ao acessar GitHub API: ' . $api_response->get_error_message());
        // Fallback para URL direta se API falhar
        $plugin_zip_url = 'https://codeload.github.com/proelements/proelements/zip/refs/heads/main';
    } else {
        $api_code = wp_remote_retrieve_response_code($api_response);
        if ($api_code === 200) {
            $api_body = wp_remote_retrieve_body($api_response);
            $release_data = json_decode($api_body, true);
            
            if (isset($release_data['zipball_url'])) {
                $plugin_zip_url = $release_data['zipball_url'];
                error_log('EBS Debug - URL da última versão obtida: ' . $plugin_zip_url);
            } else {
                error_log('EBS Debug - Dados da API não contêm zipball_url, usando fallback');
                $plugin_zip_url = 'https://codeload.github.com/proelements/proelements/zip/refs/heads/main';
            }
        } else {
            error_log('EBS Debug - GitHub API retornou código: ' . $api_code . ', usando fallback');
            $plugin_zip_url = 'https://codeload.github.com/proelements/proelements/zip/refs/heads/main';
        }
    }
    
    error_log('EBS Debug - URL final do ZIP: ' . $plugin_zip_url);
    
    // Testar conectividade com a URL final
    error_log('EBS Debug - Testando conectividade com URL final');
    $response = wp_remote_head($plugin_zip_url, [
        'timeout' => 15,
        'redirection' => 5,
        'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url')
    ]);
    
    if (is_wp_error($response)) {
        error_log('EBS Debug - Erro de conectividade: ' . $response->get_error_message());
        return ['success' => false, 'message' => 'Erro de conectividade com GitHub: ' . $response->get_error_message()];
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    error_log('EBS Debug - Código de resposta final: ' . $response_code);
    
    if ($response_code !== 200) {
        error_log('EBS Debug - URL final retornou código: ' . $response_code);
        return ['success' => false, 'message' => 'GitHub retornou código de erro: ' . $response_code];
    }
    
    // Criar um upgrader com skin silenciosa
    error_log('EBS Debug - Criando upgrader');
    $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
    error_log('EBS Debug - Iniciando instalação');
    $install_result = $upgrader->install($plugin_zip_url);
    
    error_log('EBS Debug - Resultado da instalação: ' . ($install_result === true ? 'Sucesso' : (is_wp_error($install_result) ? 'Erro: ' . $install_result->get_error_message() : 'Falha: ' . print_r($install_result, true))));
    
    if (is_wp_error($install_result)) {
        error_log('EBS Debug - Erro na instalação: ' . $install_result->get_error_message());
        return ['success' => false, 'message' => 'Erro na instalação: ' . $install_result->get_error_message()];
    }
    
    if ($install_result === false || $install_result === null) {
        error_log('EBS Debug - Falha na instalação do ProElements');
        return ['success' => false, 'message' => 'Falha na instalação do ProElements. Verifique conectividade com GitHub.'];
    }
    
    // 4. Aguardar processamento
    error_log('EBS Debug - Instalação bem-sucedida, aguardando 3 segundos');
    sleep(3);
    
    // 5. Limpar cache e encontrar o plugin
    error_log('EBS Debug - Limpando cache de plugins');
    wp_cache_delete('plugins', 'plugins');
    wp_cache_flush();
    
    // 6. Tentar encontrar o plugin instalado várias vezes
    error_log('EBS Debug - Procurando arquivo do plugin');
    $plugin_file = null;
    $attempts = 0;
    
    while (!$plugin_file && $attempts < 10) {
        $plugin_file = ebs_find_proelements_plugin_file();
        error_log('EBS Debug - Tentativa ' . ($attempts + 1) . ': ' . ($plugin_file ? 'Encontrado: ' . $plugin_file : 'Não encontrado'));
        if (!$plugin_file) {
            sleep(1);
            wp_cache_delete('plugins', 'plugins');
            $attempts++;
        }
    }
    
    if (!$plugin_file) {
        // Debug: listar o que foi encontrado
        $debug_info = [];
        if (is_dir(WP_PLUGIN_DIR)) {
            $dirs = scandir(WP_PLUGIN_DIR);
            foreach ($dirs as $dir) {
                if ($dir !== '.' && $dir !== '..' && is_dir(WP_PLUGIN_DIR . '/' . $dir)) {
                    if (stripos($dir, 'pro') !== false || stripos($dir, 'element') !== false) {
                        $debug_info[] = $dir;
                    }
                }
            }
        }
        
        return [
            'success' => false, 
            'message' => 'ProElements foi instalado, mas não foi possível encontrar o arquivo principal. Diretórios encontrados: ' . implode(', ', $debug_info),
            'removed_dirs' => $removed_dirs,
            'debug_dirs' => $debug_info
        ];
    }
    
    // 7. Verificar se o arquivo realmente existe
    if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
        return [
            'success' => false,
            'message' => 'Arquivo do plugin não existe: ' . $plugin_file,
            'removed_dirs' => $removed_dirs
        ];
    }
    
    // 8. Tentar ativar o plugin
    $activation_result = activate_plugin($plugin_file);
    if (is_wp_error($activation_result)) {
        return [
            'success' => false,
            'message' => 'ProElements foi instalado (' . $plugin_file . '), mas falhou ao ativar: ' . $activation_result->get_error_message(),
            'removed_dirs' => $removed_dirs,
            'plugin_file' => $plugin_file
        ];
    }
    
    // 9. Verificar se foi realmente ativado
    $is_active = false;
    $check_attempts = 0;
    
    while (!$is_active && $check_attempts < 5) {
        sleep(1);
        $is_active = is_plugin_active($plugin_file);
        $check_attempts++;
    }
    
    if (!$is_active) {
        return [
            'success' => false,
            'message' => 'ProElements foi instalado (' . $plugin_file . '), mas não foi ativado automaticamente.',
            'removed_dirs' => $removed_dirs,
            'plugin_file' => $plugin_file
        ];
    }
    
    return [
        'success' => true,
        'message' => 'ProElements instalado e ativado com sucesso!',
        'removed_dirs' => $removed_dirs,
        'plugin_file' => $plugin_file
    ];
}