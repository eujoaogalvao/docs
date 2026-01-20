<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Registrar todos os ganchos de ação AJAX
add_action( 'wp_ajax_ebs_handle_elementor', 'ebs_handle_elementor' );
add_action( 'wp_ajax_ebs_install_pro_elements', 'ebs_handle_install_pro_elements' );
add_action( 'wp_ajax_ebs_activate_container', 'ebs_handle_activate_container' );
add_action( 'wp_ajax_ebs_activate_nested_elements', 'ebs_handle_activate_nested_elements' );
add_action( 'wp_ajax_ebs_activate_hello_theme', 'ebs_handle_activate_hello_theme' );
add_action( 'wp_ajax_ebs_debug_elementor', 'ebs_debug_elementor_status' );
add_action( 'wp_ajax_ebs_clear_elementor_cache', 'ebs_handle_clear_elementor_cache' );
add_action( 'wp_ajax_ebs_sync_elementor_library', 'ebs_handle_sync_elementor_library' );
add_action( 'wp_ajax_ebs_debug_proelements', 'ebs_debug_proelements_status' );
add_action( 'wp_ajax_ebs_force_reinstall_proelements', 'ebs_force_reinstall_proelements' );
add_action( 'wp_ajax_ebs_cleanup_proelements', 'ebs_handle_cleanup_proelements' );

/**
 * Validação de segurança genérica para todas as ações.
 */
function ebs_ajax_security_check() {
    check_ajax_referer( 'ebs_ajax_nonce', 'nonce' );
}

// --- Handlers de Ação ---

function ebs_handle_elementor() {
    try {
        ebs_ajax_security_check();

        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $status = ebs_get_elementor_status();
        $plugin_slug = 'elementor/elementor.php';

        switch ($status) {
            case 'not_installed':
                $api = plugins_api('plugin_information', ['slug' => 'elementor', 'fields' => ['short_description' => false, 'sections' => false]]);
                if (is_wp_error($api)) {
                    wp_send_json_error(['message' => 'Erro ao obter informações do Elementor: ' . $api->get_error_message()]);
                    return;
                }
                
                $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
                $install_result = $upgrader->install($api->download_link);
                if (is_wp_error($install_result)) {
                    wp_send_json_error(['message' => 'Erro na instalação: ' . $install_result->get_error_message()]);
                    return;
                }
                
                if ($install_result === false) {
                    wp_send_json_error(['message' => 'Falha na instalação do Elementor. Verifique as permissões.']);
                    return;
                }
                // Fall through to activate

            case 'not_active':
                $activation_result = activate_plugin($plugin_slug);
                if (is_wp_error($activation_result)) {
                    wp_send_json_error(['message' => 'Erro na ativação: ' . $activation_result->get_error_message()]);
                    return;
                }
                wp_send_json_success(['message' => __('Elementor instalado e ativado com sucesso.', 'easy-builder-setup')]);
                break;

            case 'not_updated':
                $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
                $update_result = $upgrader->upgrade($plugin_slug);
                if (is_wp_error($update_result)) {
                    wp_send_json_error(['message' => 'Erro na atualização: ' . $update_result->get_error_message()]);
                    return;
                }
                
                if ($update_result === false) {
                    wp_send_json_error(['message' => 'Falha na atualização do Elementor.']);
                    return;
                }
                
                // Garantir que o plugin permaneça ativo após a atualização
                if (!is_plugin_active($plugin_slug)) {
                    $activation_result = activate_plugin($plugin_slug);
                    if (is_wp_error($activation_result)) {
                        wp_send_json_error(['message' => 'Elementor atualizado, mas falhou ao reativar: ' . $activation_result->get_error_message()]);
                        return;
                    }
                }
                
                wp_send_json_success(['message' => __('Elementor atualizado e reativado com sucesso.', 'easy-builder-setup')]);
                break;

            case 'ok':
                wp_send_json_success(['message' => __('Elementor já está OK.', 'easy-builder-setup')]);
                break;

            default:
                wp_send_json_error(['message' => 'Status desconhecido do Elementor: ' . $status]);
                break;
        }
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erro inesperado: ' . $e->getMessage()]);
    } catch (Error $e) {
        wp_send_json_error(['message' => 'Erro fatal: ' . $e->getMessage()]);
    }
}

/**
 * Verifica se o sistema tem os requisitos para instalar plugins
 */
function ebs_check_installation_requirements() {
    $errors = [];
    
    // Verificar permissões de escrita
    if (!wp_is_writable(WP_PLUGIN_DIR)) {
        $errors[] = 'Diretório de plugins não tem permissão de escrita: ' . WP_PLUGIN_DIR;
    }
    
    // Verificar se as funções necessárias existem
    if (!function_exists('download_url')) {
        $errors[] = 'Função download_url não está disponível';
    }
    
    // Verificar conectividade com GitHub
    $response = wp_remote_head('https://github.com', ['timeout' => 10]);
    if (is_wp_error($response)) {
        $errors[] = 'Não foi possível conectar ao GitHub: ' . $response->get_error_message();
    }
    
    return $errors;
}



/**
 * Força a reinstalação do ProElements
 */
function ebs_force_reinstall_proelements() {
    ebs_ajax_security_check();
    
    include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    include_once ABSPATH . 'wp-admin/includes/file.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    
    // 1. Verificar se há alguma versão do ProElements instalada
    $proelements_file = ebs_find_proelements_plugin_file();
    
    // 2. Se encontrou, desativar e excluir
    if ($proelements_file) {
        // Desativar primeiro
        if (is_plugin_active($proelements_file)) {
            deactivate_plugins($proelements_file);
        }
        
        // Excluir o plugin
        $plugin_dir = dirname(WP_PLUGIN_DIR . '/' . $proelements_file);
        if (is_dir($plugin_dir)) {
            // Usar WP_Filesystem para excluir
            global $wp_filesystem;
            if (!$wp_filesystem) {
                WP_Filesystem();
            }
            
            $wp_filesystem->delete($plugin_dir, true);
        }
    }
    
    // 3. Verificar e excluir diretórios relacionados ao ProElements
    $possible_dirs = [
        'pro-elements',
        'proelements',
        'proelements-main',
        'pro-elements-main'
    ];
    
    foreach ($possible_dirs as $dir) {
        $path = WP_PLUGIN_DIR . '/' . $dir;
        if (is_dir($path)) {
            // Usar WP_Filesystem para excluir
            global $wp_filesystem;
            if (!$wp_filesystem) {
                WP_Filesystem();
            }
            
            $wp_filesystem->delete($path, true);
        }
    }
    
    // 4. Limpar cache de plugins
    wp_cache_delete('plugins', 'plugins');
    
    // 5. Instalar o ProElements diretamente do GitHub
    $plugin_zip_url = 'https://github.com/proelements/proelements/archive/refs/heads/main.zip';
    
    // Criar um upgrader com skin silenciosa
    $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
    $install_result = $upgrader->install($plugin_zip_url);
    
    // Verificar se houve erro na instalação
    if (is_wp_error($install_result)) {
        wp_send_json_error(['message' => 'Erro na reinstalação do ProElements: ' . $install_result->get_error_message()]);
        return;
    }
    
    if ($install_result === false) {
        wp_send_json_error(['message' => 'Falha na reinstalação do ProElements. Verifique as permissões.']);
        return;
    }
    
    // Aguardar um momento para o sistema processar a instalação
    sleep(2);
    
    // Limpar cache novamente
    wp_cache_delete('plugins', 'plugins');
    
    // 6. Encontrar o arquivo principal do plugin
    $plugin_file = ebs_find_proelements_plugin_file();
    
    if (!$plugin_file) {
        wp_send_json_error(['message' => 'ProElements foi reinstalado, mas não foi possível encontrar o arquivo principal. Tente ativar manualmente.']);
        return;
    }
    
    // 7. Ativar o plugin
    $activation_result = activate_plugin($plugin_file);
    if (is_wp_error($activation_result)) {
        wp_send_json_error(['message' => 'ProElements foi reinstalado, mas falhou ao ativar: ' . $activation_result->get_error_message()]);
        return;
    }
    
    // 8. Verificar se foi realmente ativado
    if (!is_plugin_active($plugin_file)) {
        wp_send_json_error(['message' => 'ProElements foi reinstalado, mas não foi ativado. Tente ativar manualmente.']);
        return;
    }
    
    wp_send_json_success(['message' => 'ProElements foi reinstalado e ativado com sucesso!']);
}

function ebs_handle_install_pro_elements() {
    error_log('EBS Debug - Função ebs_handle_install_pro_elements chamada');
    error_log('EBS Debug - POST data: ' . print_r($_POST, true));
    
    try {
        ebs_ajax_security_check();
        error_log('EBS Debug - Verificação de segurança passou');
    } catch (Exception $e) {
        error_log('EBS Debug - Erro na verificação de segurança: ' . $e->getMessage());
        wp_send_json_error(['message' => 'Erro de segurança: ' . $e->getMessage()]);
        return;
    }

    // Incluir o arquivo de limpeza
    error_log('EBS Debug - Incluindo arquivo de limpeza');
    require_once plugin_dir_path(__FILE__) . 'cleanup-proelements.php';

    // Verificar requisitos do sistema primeiro
    error_log('EBS Debug - Verificando requisitos do sistema');
    $requirements_errors = ebs_check_installation_requirements();
    if (!empty($requirements_errors)) {
        error_log('EBS Debug - Requisitos não atendidos: ' . implode('; ', $requirements_errors));
        wp_send_json_error(['message' => 'Requisitos não atendidos: ' . implode('; ', $requirements_errors)]);
        return;
    }

    error_log('EBS Debug - Obtendo status do ProElements');
    $status = ebs_get_pro_elements_status();
    error_log('EBS Debug - Status obtido: ' . $status);

    switch ($status) {
        case 'not_installed':
            error_log('EBS Debug - Entrando no caso not_installed');
            // Usar a função de instalação limpa
            error_log('EBS Debug - Chamando ebs_clean_install_proelements()');
            $result = ebs_clean_install_proelements();
            error_log('EBS Debug - Resultado da instalação: ' . print_r($result, true));
            
            if ($result['success']) {
                error_log('EBS Debug - Instalação bem-sucedida, enviando resposta de sucesso');
                wp_send_json_success(['message' => $result['message']]);
            } else {
                error_log('EBS Debug - Instalação falhou, enviando resposta de erro');
                wp_send_json_error(['message' => $result['message']]);
            }
            break;

        case 'not_active':
            // Primeiro tentar encontrar o plugin
            $plugin_file = ebs_find_proelements_plugin_file();
            
            if (!$plugin_file) {
                // Se não encontrou, pode estar quebrado - fazer instalação limpa
                $result = ebs_clean_install_proelements();
                
                if ($result['success']) {
                    wp_send_json_success(['message' => 'ProElements estava quebrado e foi reinstalado: ' . $result['message']]);
                } else {
                    wp_send_json_error(['message' => 'ProElements estava quebrado e falhou ao reinstalar: ' . $result['message']]);
                }
                return;
            }
            
            // Verificar se o arquivo realmente existe
            if (!file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)) {
                // Arquivo não existe - fazer instalação limpa
                $result = ebs_clean_install_proelements();
                
                if ($result['success']) {
                    wp_send_json_success(['message' => 'ProElements estava quebrado e foi reinstalado: ' . $result['message']]);
                } else {
                    wp_send_json_error(['message' => 'ProElements estava quebrado e falhou ao reinstalar: ' . $result['message']]);
                }
                return;
            }
            
            // Tentar ativar o plugin existente
            $activation_result = activate_plugin($plugin_file);
            if (is_wp_error($activation_result)) {
                wp_send_json_error(['message' => 'Erro ao ativar ProElements: ' . $activation_result->get_error_message()]);
                return;
            }
            
            // Verificar se realmente foi ativado
            sleep(1);
            if (!is_plugin_active($plugin_file)) {
                wp_send_json_error(['message' => 'ProElements não foi ativado corretamente. Tente ativar manualmente.']);
                return;
            }
            
            wp_send_json_success(['message' => __('ProElements ativado com sucesso!', 'easy-builder-setup')]);
            break;

        case 'ok':
            wp_send_json_success(['message' => __('ProElements já está instalado e ativo.', 'easy-builder-setup')]);
            break;

        case 'pro_active':
            wp_send_json_success(['message' => __('Elementor Pro está ativo. Nenhuma ação necessária.', 'easy-builder-setup')]);
            break;

        default:
            wp_send_json_error(['message' => 'Status desconhecido do ProElements: ' . $status]);
            break;
    }
}

function ebs_handle_activate_container() {
    ebs_ajax_security_check();
    update_option( 'elementor_experiment-container', 'active' );
    wp_send_json_success( [ 'message' => __( 'Contêineres Flexbox ativados.', 'easy-builder-setup' ) ] );
}

function ebs_handle_activate_nested_elements() {
    ebs_ajax_security_check();
    // Dependência: Garante que os contêineres estejam ativos primeiro.
    if ( get_option( 'elementor_experiment-container' ) !== 'active' ) {
        update_option( 'elementor_experiment-container', 'active' );
    }
    update_option( 'elementor_experiment-nested-elements', 'active' );
    wp_send_json_success( [ 'message' => __( 'Elementos Aninhados ativados.', 'easy-builder-setup' ) ] );
}

function ebs_handle_activate_hello_theme() {
    ebs_ajax_security_check();

    include_once ABSPATH . 'wp-admin/includes/theme.php';
    include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

    $theme_slug = 'hello-elementor';

    // Verificar se o tema já existe
    $theme = wp_get_theme( $theme_slug );
    
    // Se o tema não estiver instalado, instale-o
    if ( ! $theme->exists() ) {
        // Usar a API do WordPress para obter informações do tema correto
        $api = themes_api( 'theme_information', [ 
            'slug' => $theme_slug,
            'fields' => [ 
                'downloadlink' => true,
                'description' => false,
                'sections' => false,
                'rating' => false,
                'ratings' => false,
                'downloaded' => false,
                'last_updated' => false,
                'tags' => false,
                'homepage' => false
            ] 
        ] );
        
        if ( is_wp_error( $api ) ) {
            wp_send_json_error( [ 'message' => 'Erro ao obter informações do tema Hello Elementor: ' . $api->get_error_message() ] );
            return;
        }
        
        // Verificar se é realmente o Hello Elementor
        if ( $api->slug !== 'hello-elementor' || strpos( strtolower( $api->name ), 'hello elementor' ) === false ) {
            wp_send_json_error( [ 'message' => 'Tema incorreto retornado pela API. Esperado: Hello Elementor, Recebido: ' . $api->name ] );
            return;
        }
        
        $upgrader = new Theme_Upgrader( new WP_Ajax_Upgrader_Skin() );
        $result = $upgrader->install( $api->download_link );
        
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => 'Erro na instalação do Hello Elementor: ' . $result->get_error_message() ] );
            return;
        }
        
        if ( $result === false ) {
            wp_send_json_error( [ 'message' => 'Falha na instalação do Hello Elementor. Verifique as permissões.' ] );
            return;
        }
    }

    // Verificar novamente se o tema foi instalado corretamente
    $theme = wp_get_theme( $theme_slug );
    if ( ! $theme->exists() ) {
        wp_send_json_error( [ 'message' => 'Hello Elementor foi baixado mas não foi encontrado. Verifique manualmente.' ] );
        return;
    }

    // Ativar o tema
    switch_theme( $theme_slug );
    
    // Aguardar um momento para o WordPress processar a mudança
    sleep(1);
    
    // Verificar se a ativação foi bem-sucedida
    $current_theme = get_option( 'stylesheet' );
    if ( $current_theme !== $theme_slug ) {
        // Se não ativou, pode ser que já esteja ativo ou houve um problema menor
        $active_theme = wp_get_theme();
        if ( $active_theme->get_stylesheet() === $theme_slug || 
             strpos( strtolower( $active_theme->get_name() ), 'hello elementor' ) !== false ) {
            wp_send_json_success( [ 'message' => __( 'Hello Elementor já estava ativo ou foi ativado com sucesso.', 'easy-builder-setup' ) ] );
        } else {
            wp_send_json_error( [ 'message' => 'Hello Elementor foi instalado mas não pôde ser ativado. Tema atual: ' . $active_theme->get_name() . '. Tente ativar manualmente.' ] );
        }
        return;
    }
    
    wp_send_json_success( [ 'message' => __( 'Tema Hello Elementor instalado e ativado com sucesso.', 'easy-builder-setup' ) ] );
}

/**
 * Função de debug para diagnosticar problemas com a detecção do Elementor
 */
function ebs_debug_elementor_status() {
    ebs_ajax_security_check();
    
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    $plugin_slug = 'elementor/elementor.php';
    $installed_plugins = get_plugins();
    
    $debug_info = [
        'plugin_slug' => $plugin_slug,
        'plugin_exists' => isset($installed_plugins[$plugin_slug]),
        'is_active' => is_plugin_active($plugin_slug),
        'status_function_result' => ebs_get_elementor_status(),
        'all_plugins_count' => count($installed_plugins),
        'elementor_class_exists' => class_exists('\Elementor\Plugin')
    ];
    
    // Se o plugin existe, pegar mais informações
    if (isset($installed_plugins[$plugin_slug])) {
        $debug_info['plugin_data'] = $installed_plugins[$plugin_slug];
        
        // Verificar atualizações
        wp_update_plugins();
        $updates = get_site_transient( 'update_plugins' );
        $debug_info['has_update'] = isset($updates->response[$plugin_slug]);
        if (isset($updates->response[$plugin_slug])) {
            $debug_info['update_info'] = $updates->response[$plugin_slug];
        }
    }
    
    wp_send_json_success(['debug_info' => $debug_info]);
}

/**
 * Limpa o cache do Elementor
 */
function ebs_handle_clear_elementor_cache() {
    ebs_ajax_security_check();
    
    // Verificar se o Elementor está ativo
    if (!class_exists('\Elementor\Plugin')) {
        wp_send_json_error(['message' => 'Elementor não está ativo.']);
        return;
    }
    
    try {
        // Limpar cache CSS do Elementor
        if (method_exists('\Elementor\Plugin', 'instance')) {
            $elementor = \Elementor\Plugin::instance();
            
            // Limpar cache de CSS
            if (isset($elementor->files_manager)) {
                $elementor->files_manager->clear_cache();
            }
            
            // Limpar cache de posts
            if (method_exists($elementor, 'posts_css_manager')) {
                $elementor->posts_css_manager->clear_cache();
            }
        }
        
        // Limpar transients relacionados ao Elementor
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_elementor_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_elementor_%'");
        
        // Limpar cache de objetos se disponível
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        wp_send_json_success(['message' => __('Cache do Elementor limpo com sucesso.', 'easy-builder-setup')]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erro ao limpar cache: ' . $e->getMessage()]);
    }
}

/**
 * Sincroniza a biblioteca do Elementor
 */
function ebs_handle_sync_elementor_library() {
    ebs_ajax_security_check();
    
    // Verificar se o Elementor está ativo
    if (!class_exists('\Elementor\Plugin')) {
        wp_send_json_error(['message' => 'Elementor não está ativo.']);
        return;
    }
    
    try {
        // Acessar o gerenciador de templates do Elementor
        $elementor = \Elementor\Plugin::instance();
        
        if (!isset($elementor->templates_manager)) {
            wp_send_json_error(['message' => 'Gerenciador de templates não está disponível.']);
            return;
        }
        
        $templates_manager = $elementor->templates_manager;
        
        // Verificar se existe o método de sincronização
        if (method_exists($templates_manager, 'get_source')) {
            $source = $templates_manager->get_source('remote');
            
            if ($source && method_exists($source, 'sync_templates')) {
                // Forçar sincronização da biblioteca
                $source->sync_templates();
                
                // Limpar cache relacionado aos templates
                delete_transient('elementor_remote_info_api_data');
                delete_transient('elementor_library_get_templates');
                
                wp_send_json_success(['message' => __('Biblioteca do Elementor sincronizada com sucesso.', 'easy-builder-setup')]);
            } else {
                wp_send_json_error(['message' => 'Método de sincronização não encontrado.']);
            }
        } else {
            // Método alternativo: limpar cache da biblioteca
            delete_transient('elementor_remote_info_api_data');
            delete_transient('elementor_library_get_templates');
            
            // Limpar cache de templates locais
            global $wpdb;
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_elementor_library_%'");
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_elementor_library_%'");
            
            wp_send_json_success(['message' => __('Cache da biblioteca do Elementor limpo. A sincronização será feita automaticamente na próxima vez que acessar a biblioteca.', 'easy-builder-setup')]);
        }
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erro ao sincronizar biblioteca: ' . $e->getMessage()]);
    }
}

/**
 * Função de debug específica para ProElements
 */
function ebs_debug_proelements_status() {
    ebs_ajax_security_check();
    
    if (!function_exists('get_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    
    // Forçar limpeza do cache
    wp_cache_delete('plugins', 'plugins');
    $all_plugins = get_plugins();
    
    $debug_info = [
        'total_plugins' => count($all_plugins),
        'pro_elements_status' => ebs_get_pro_elements_status(),
        'found_plugin_file' => ebs_find_proelements_plugin_file(),
        'elementor_pro_active' => is_plugin_active('elementor-pro/elementor-pro.php'),
        'plugins_with_pro_or_element' => [],
        'plugin_dir_contents' => [],
        'wp_plugin_dir' => WP_PLUGIN_DIR,
        'wp_plugin_dir_writable' => wp_is_writable(WP_PLUGIN_DIR)
    ];
    
    // Verificar conteúdo do diretório de plugins
    if (is_dir(WP_PLUGIN_DIR)) {
        $dirs = scandir(WP_PLUGIN_DIR);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir(WP_PLUGIN_DIR . '/' . $dir)) {
                if (stripos($dir, 'pro') !== false || stripos($dir, 'element') !== false) {
                    $debug_info['plugin_dir_contents'][] = [
                        'dir' => $dir,
                        'files' => is_dir(WP_PLUGIN_DIR . '/' . $dir) ? scandir(WP_PLUGIN_DIR . '/' . $dir) : []
                    ];
                }
            }
        }
    }
    
    // Listar plugins que contenham "pro" ou "element"
    foreach ($all_plugins as $plugin_file => $plugin_data) {
        $name = isset($plugin_data['Name']) ? strtolower($plugin_data['Name']) : '';
        $file_lower = strtolower($plugin_file);
        
        if (strpos($name, 'pro') !== false || strpos($name, 'element') !== false ||
            strpos($file_lower, 'pro') !== false || strpos($file_lower, 'element') !== false) {
            $debug_info['plugins_with_pro_or_element'][] = [
                'file' => $plugin_file,
                'name' => isset($plugin_data['Name']) ? $plugin_data['Name'] : 'N/A',
                'version' => isset($plugin_data['Version']) ? $plugin_data['Version'] : 'N/A',
                'is_active' => is_plugin_active($plugin_file),
                'file_exists' => file_exists(WP_PLUGIN_DIR . '/' . $plugin_file)
            ];
        }
    }
    
    // Se encontrou um arquivo específico, verificar detalhes
    $found_file = ebs_find_proelements_plugin_file();
    if ($found_file) {
        $debug_info['found_file_exists'] = file_exists(WP_PLUGIN_DIR . '/' . $found_file);
        $debug_info['found_file_path'] = WP_PLUGIN_DIR . '/' . $found_file;
        $debug_info['found_file_is_active'] = is_plugin_active($found_file);
        
        if (file_exists(WP_PLUGIN_DIR . '/' . $found_file)) {
            $debug_info['found_file_header'] = get_plugin_data(WP_PLUGIN_DIR . '/' . $found_file);
        }
    }
    
    wp_send_json_success(['debug_info' => $debug_info]);
}
/**

 * Handler para limpeza de ProElements problemáticos
 */
function ebs_handle_cleanup_proelements() {
    ebs_ajax_security_check();
    
    // Incluir o arquivo de limpeza
    require_once plugin_dir_path(__FILE__) . 'cleanup-proelements.php';
    
    try {
        $cleanup_result = ebs_cleanup_broken_proelements();
        
        $message = 'Limpeza concluída. ';
        if (!empty($cleanup_result['removed_dirs'])) {
            $message .= 'Diretórios removidos: ' . implode(', ', $cleanup_result['removed_dirs']) . '. ';
        }
        if (!empty($cleanup_result['removed_plugins'])) {
            $message .= 'Plugins problemáticos removidos da lista ativa: ' . implode(', ', $cleanup_result['removed_plugins']) . '.';
        }
        if (empty($cleanup_result['removed_dirs']) && empty($cleanup_result['removed_plugins'])) {
            $message .= 'Nenhum problema encontrado.';
        }
        
        wp_send_json_success([
            'message' => $message,
            'cleanup_result' => $cleanup_result
        ]);
        
    } catch (Exception $e) {
        wp_send_json_error(['message' => 'Erro durante a limpeza: ' . $e->getMessage()]);
    }
}