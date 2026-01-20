# Checklist: Recriação do Plugin Easy Builder Setup

Este documento serve como um plano de desenvolvimento expert para recriar o plugin, com foco em robustez, manutenibilidade e na nova arquitetura de correções individuais.

---

### Fase 1: Fundação do Plugin e Estrutura da UI

O objetivo desta fase é criar o esqueleto do plugin e a interface visual onde os status das verificações serão exibidos.

- [x] **1.1: Estrutura de Arquivos:** Criar a seguinte estrutura de diretórios e arquivos:
  ```
  /easy-builder-setup
  |-- easy-builder-setup.php      # Arquivo principal do plugin
  |-- /includes
  |   |-- admin-page.php          # Lógica para criar a página de admin e renderizar o HTML
  |   |-- checks.php              # Funções que realizam as verificações
  |   |-- actions.php             # Handlers para as chamadas AJAX de correção
  |-- /assets
  |   |-- /css
  |   |   |-- admin-style.css     # Estilos para a página de admin
  |   |-- /js
  |       |-- admin-script.js     # JavaScript para AJAX e manipulação da UI
  ```

- [x] **1.2: Arquivo Principal (`easy-builder-setup.php`):**
    - Definir os cabeçalhos do plugin (Nome, Versão, Autor, etc.).
    - Incluir os arquivos de `includes`.
    - Criar uma função para adicionar a página do plugin ao menu do WordPress (`add_action('admin_menu', ...)`).
    - Criar uma função para enfileirar os scripts e estilos (`add_action('admin_enqueue_scripts', ...)`), garantindo que só carreguem na página do plugin.

- [x] **1.3: Página de Administração (`admin-page.php`):**
    - Criar a função que renderiza o HTML da página.
    - Estruturar a página com um título e uma tabela ou lista para os itens de verificação.
    - Cada item deve ter um layout definido: `[Ícone de Status] [Nome da Verificação] [Descrição Curta] [Botão de Correção]`.
    - Inicialmente, os dados podem ser estáticos (hardcoded) para focar no layout.

---

### Fase 2: Lógica de Verificação

Nesta fase, implementaremos a lógica para verificar o estado real do ambiente WordPress.

- [x] **2.1: Implementar Funções de Verificação (`checks.php`):** Criar uma função para cada item a ser verificado. Cada função deve retornar um status claro (string ou booleano).
    - `ebs_check_elementor_active()`
    - `ebs_check_elementor_pro_active()` (ou alternativa como ProElements)
    - `ebs_check_container_status()`
    - `ebs_check_nested_elements_status()`
    - `ebs_check_hello_theme_active()`
    - `ebs_check_optimized_dom_disabled()`
    - `ebs_check_memory_limit()`
    - `ebs_check_max_execution_time()`

- [x] **2.2: Integração com a UI (`admin-page.php`):**
    - Na função que renderiza a página, chamar cada função de verificação do `checks.php`.
    - Usar o resultado para exibir dinamicamente o ícone de status (✔️ ou ❌) e a mensagem correspondente.
    - O botão "Corrigir" só deve ser exibido se o status for `false`.

---

### Fase 3: Ações de Correção Individuais (AJAX)

O coração da nova arquitetura. Cada correção será uma ação independente e segura.

- [x] **3.1: Registrar Ações AJAX (`actions.php`):**
    - Para cada item corrigível, registrar uma ação AJAX `wp_ajax_`.
    - Ex: `add_action('wp_ajax_ebs_activate_container', 'ebs_handle_activate_container');`

- [x] **3.2: Implementar Handlers de Ação (`actions.php`):**
    - Criar a função de callback para cada ação (ex: `ebs_handle_activate_container`).
    - **Regra de Ouro:** Cada handler deve fazer UMA coisa e fazê-la bem.
    - Implementar a lógica de correção, usando as lições aprendidas (`update_option` para experimentos, etc.).
    - **Tratamento de Erro Robusto:** Sempre retornar uma resposta JSON com `wp_send_json_success()` ou `wp_send_json_error()` para evitar o `parsererror` do passado.
    - **Lógica de Dependência:** O handler que ativa "Elementos Aninhados" deve primeiro garantir que "Contêineres" está ativo.

- [x] **3.3: JavaScript para Ações (`admin-script.js`):**
    - Passar o `admin-ajax.php` URL e um `nonce` de segurança para o script usando `wp_localize_script`.
    - Adicionar um event listener para os cliques nos botões "Corrigir". Usar seletores de dados (ex: `<button data-action="ebs_activate_container">`) para identificar a ação a ser executada.
    - Na função de clique:
        - Mostrar um feedback visual (ex: spinner, texto "Corrigindo...").
        - Enviar a requisição AJAX para o endpoint correto.
        - No retorno (`success`): atualizar a UI daquele item (ícone para ✔️, desabilitar/esconder o botão, mostrar mensagem de sucesso).
        - No retorno (`error`): mostrar uma mensagem de erro clara para o usuário.

---

### Fase 4: Refinamento e UX

O objetivo é polir o plugin para que seja intuitivo e profissional.

- [ ] **4.1: Estilização (`admin-style.css`):**
    - Criar uma aparência limpa e clara para a página, alinhada com a UI do WordPress.
    - Adicionar estilos para os status (cores para sucesso e falha), botões e feedback visual (loading/spinners).

- [ ] **4.2: Textos e Usabilidade:**
    - Revisar todas as descrições para que sejam fáceis de entender por um usuário não-técnico.
    - Adicionar tooltips ou links para documentação para os itens mais complexos (ex: `memory_limit`).

- [ ] **4.3: Teste Final:**
    - Realizar um teste completo em um ambiente WordPress limpo.
    - Testar cada botão de correção individualmente.
    - Testar cenários de dependência.
    - Garantir que não há erros no console do navegador ou nos logs do PHP.
