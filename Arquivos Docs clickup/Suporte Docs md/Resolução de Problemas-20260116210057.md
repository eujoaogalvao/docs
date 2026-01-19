# Resolução de Problemas

*   **Erro ao colar elemento:** _"Certifique-se de que ambos os sites estejam atualizados para a última versão do Elementor..."_
    
    Caso você NÃO TENHA instalado nosso backup, você precisa entender alguns pontos para não ter um erro como esse:
    
    ![](https://t9007068676.p.clickup-attachments.com/t9007068676/32dce073-73d5-46bb-98d5-dfe76babe1ed/image.png)
    
    ✅ **CHECKLIST PRA O EASY BUILDER FUNCIONAR LISO**
    
    1. Instalar o plugin Elementor gratuito direto do painel do WordPress
    2. Instalar o plugin Pro Elements via upload manual
    3. Atualizar o WordPress para uma versão recente
    4. Atualizar o Elementor e o Pro Elements se necessário
    5. Ativar em Aparencia > o tema Hello Elementor
    6. Ativar todos os recursos em Elementor > Configurações > Recursos
    7. Regenerar CSS e sincronizar biblioteca em Elementor > Ferramentas
    8. Criar nova página com layout definido como Elementor Canvas
    9. Criar container vazio antes de colar elementos do Easy Builder
    10. Clicar com botão direito > Colar de outro site > Ctrl + V
    
    **Possíveis Problemas:**
    
    *   Faltam os plugins: Elementor e Elementor PRO (Ou PRO Elements que é de graça e funciona igual)
        
        🛠️ Como resolver
        
        1. **Instala o Elementor (gratuito):**
        2. Vai no painel do WordPress > Plugins > Adicionar Novo > procura por "Elementor" e instala.
        3. **Instala o Pro Elements (versão free que desbloqueia as funções do Pro):**
        4. 📥 Baixa direto por aqui:
        5. 👉 [https://github.com/proelements/proelements/releases/download/v3.28.1/pro-elements.zip](https://github.com/proelements/proelements/releases/download/v3.28.1/pro-elements.zip)
        6. **Instala o Pro Elements no WordPress:**
            *   Vai em **Plugins > Adicionar Novo**
            *   Clica em **Enviar Plugin**
            *   Sobe o `.zip` que tu baixou
            *   Instala e ativa
        
          
        
          
        
          
        
    *   Wordpress ou Elementor/Elementor PRO desatualizados
        
        elho, se teu WordPress ou Elementor tá parecendo relíquia de museu, não adianta chorar: **o Easy Builder vai travar**. Versão velha não fala a mesma língua dos recursos novos — aí o colar não rola, os elementos bugam.
        
          
        
        🛠️ Como resolver
        
        1. **Atualiza o WordPress:**
            *   Vai no painel > **Painel > Atualizações**
            *   Se tiver atualização disponível, mete o dedo no botão **Atualizar Agora**
        2. **Atualiza o Elementor:**
            *   Vai em **Plugins > Plugins Instalados**
            *   Procura o Elementor (e o Pro ou Pro Elements)
            *   Se tiver botão de atualizar, clica sem dó
        3. **Não usa versão muito antiga do WP.**
        4. Tenta manter pelo menos nas versões dos últimos 6 meses, senão é pedir pra dar pau.
        
          
        
    *   Recursos avançados do elementor inativos: (Especialmente Containers)
        
        🛠️ Como resolver
        
        1. Vai no painel do WordPress
        2. Acessa **Elementor > Configurações > Aba “Recursos” (ou “Experimentos”)**
        3. Ativa tudo que tiver lá. Especialmente:
            *   ✅ **Container (Flexbox Container)**
            *   ✅ Elementos Aninhados
            *   ✅ Elementor Grid
            *   ✅ Animações Avançadas
        4. Depois, clica em **Salvar Alterações**
        
        🚨 Sem o Container ativado, **não cola nem por reza brava**. A maioria dos layouts novos do Easy Builder usa essa estrutura — que é mais leve, mais moderna e bem mais rápida.
        
    *   \[MAIS COMUM\] Biblioteca sem sincronia: Elementor > Ferramentas > Sincronizar biblioteca e recriar kits CSS
        1. Vai no painel do WordPress
        2. Acessa: **Elementor > Ferramentas**
        3. Clica em:
            *   🔁 **Regenerar arquivos CSS**
            *   📚 **Sincronizar Biblioteca**
        4. Salva e dá um refresh no Elementor
        
        💡 Isso força o Elementor a recarregar **as configurações corretas dos elementos, estilos e containers**.
        
          
        
        Sem isso, o Elementor fica preso em arquivos antigos, tipo cache velho com cheiro de mofo. Aí o Easy Builder cola a parada e... o resultado é uma zona. Faz isso e tu evita metade dos bugs visuais.
        
          
        
    *   Colar no lugar errado: Precisa colar em um novo container com botão direito + Colar de OUTRO SITE + CTRL V
        1. Cria um **novo container vazio** no Elementor
        2. Clica com o **botão direito** dentro dele
        3. Seleciona: **Colar de OUTRO SITE**
        4. Aí sim mete o **Ctrl + V**
        
        🚨 Importante: se tentar colar direto sem passar pelo botão direito e sem esse passo, o Easy Builder vai olhar pra tua cara e pensar “esse aí não merece sucesso”.
        
        Dica ninja: Se o botão não colar direito, **cria uma nova coluna**, cola nela, e depois **arrasta pro lugar certo**. Simples, funcional, sem estresse.
        
    *   Limpar dados de navegação do navegador (Cookies e Caches)
        
        Mano, se mesmo com tudo certo (plugins, atualizações, recursos ativados...) o Easy Builder **ainda tá surtando**, o vilão pode ser o navegador. Cache velho, cookies corrompidos, dados de sessão podres... tudo isso **trava o funcionamento da extensão**.
        
        🛠️ Como limpar o lixo digital
        
        1. Abre o navegador (Chrome, Edge, etc.)
        2. Aperta **Ctrl + Shift + Del**
        3. Marca:
            *   Cookies e outros dados do site
            *   Imagens e arquivos em cache
        4. Clica em **Limpar dados**
        
        💡 Pro efeito ser total, reinicia o navegador depois de limpar.
        
    *   \[Frequente\] Limite de memória do servidor (hostgator, hostinger, godaddy e afins)
        
        ⚙️ **COMO AUMENTAR O LIMITE DO PHP PRA USAR O EASY BUILDER SEM ERROS**
        
        Primeiro, verifica se realmente o problema é esse:
        
        1. Vai no painel do WordPress > Elementor > Ferramentas > Aba **“Informações do sistema”**
        2. Dá uma olhada nos valores técnicos: se tiver coisa muito baixa (tipo 64MB de memória ou tempo de execução menor que 60s), o erro pode estar vindo daí.
        
        🔧 Bora resolver isso no CPanel:
        
        1️⃣ Acessa o **CPanel** do seu site 2️⃣ Na busca, digita: `PHP` 3️⃣ Clica em: **MultiPHP INI Editor** 4️⃣ Escolhe o domínio do site que você tá trabalhando 5️⃣ Faz essas alterações:
        
        *   `memory_limit` ➡️ **1000M**
        *   `max_execution_time` ➡️ **1000**
        *   `post_max_size` ➡️ **100M**
        *   `upload_max_filesize` ➡️ **100M**
        *   `max_input_vars` ➡️ **100000**
        *   `max_input_time` ➡️ **128**
        
        👉 As três últimas opções da tela você **não mexe**
        
        👉 A opção “display errors” você **deixa desabilitada**
        
        6️⃣ Clica em **Aplicar**. Pronto!
        
        🔁 Agora volta pro Elementor:
        
        3. Vai em **Elementor > Ferramentas**
        4. Clica em:
            *   **Regenerar arquivos CSS**
            *   **Sincronizar biblioteca**
        
        💥 Agora sim, pode colar qualquer elemento no Easy Builder sem travar.
        
        🎥 Quer ver esse processo completo com tela e tudo?
        
        Assiste aqui o vídeo:
        
        [https://www.youtube.com/watch?v=ZvRUGoVYjeg](https://www.youtube.com/watch?v=ZvRUGoVYjeg)
        
    
      
    
*   Template está vindo todo desconfigurado.
    
    **COMO PERSONALIZAR CORES E FONTES NO EASY BUILDER EM 2 MINUTOS** 
    
    Tu já deve ter percebido que quando importa uma página pelo Easy Builder, tudo já vem responsivo. Massa. Mas se tu quiser deixar as fontes e cores com a cara do teu cliente (ou da tua marca), sem precisar mudar elemento por elemento, presta atenção:
    
    Primeiro, abre a página no Elementor normalmente. Aí no canto superior esquerdo, tu vai ver três tracinhos empilhados (tipo hambúrguer
    
    🍔). Clica neles e depois vai em **“Configurações do Site”**.
    
    1\. Cores Globais 🌈
    
    Em **Cores Globais**, tu vai ver quatro caixinhas: primária, secundária, texto e realce. Só clicar em cima e mudar a cor pra que quiser (pode arrastar na paleta ou colar o hex tipo `#FF0000`). Isso já altera em tudo que estiver usando essas cores globais. Sim, tudo mesmo.
    
    2\. Fontes Globais ✍️
    
    Agora, em **Fontes Globais**, o esquema é o mesmo. Tu pode escolher:
    
    *   A família da fonte (tipo Poppins, Bebas, etc)
    *   O peso (fino, médio, grosso)
    *   Tamanho, altura da linha, espaçamento…
    
    **Dica:** dá pra configurar diferente pra _desktop_, _tablet_ e _celular_ clicando no ícone de dispositivo no canto inferior esquerdo do editor. Digamos que a fonte ficou pequena no mobile? Vai lá no modo celular e aumenta só pra ele.
    
    Terminou de ajustar? Não esquece de clicar em **“Atualizar”** no cantinho. Espera a barrinha roxa carregar e pronto. Volta no site e tudo vai estar com as novas cores e fontes.
    
    **Importante:** essas mudanças só funcionam se os elementos estiverem usando as configurações globais. Se não tiverem, tu pode editar um por um — clicando no texto > aba “Estilo” > mudando fonte e cor direto ali.
    
    **Aviso:** em breve vai rolar uma opção pra importar as sessões _SEM_ essas configs globais, ou seja, vai colar a parada com o estilo exato do template — sem puxar cor nem fonte padrão do WP. Aguardem.
    
    **Quer ver isso na prática?** Tá aqui o link completo com o passo a passo em vídeo:
    
    [https://www.youtube.com/watch?v=YdBD9Zha4ms](https://www.youtube.com/watch?v=YdBD9Zha4ms)
    
    *