-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 17-Maio-2026 às 02:21
-- Versão do servidor: 10.4.32-MariaDB
-- versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `ipikk_db`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `alumni`
--

CREATE TABLE `alumni` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `ano_conclusao` varchar(10) DEFAULT NULL,
  `foto_url` varchar(255) DEFAULT 'foto/sem_foto.png',
  `percurso_texto` text DEFAULT NULL,
  `cargo_atual` varchar(100) DEFAULT NULL,
  `empresa` varchar(100) DEFAULT NULL,
  `destaque` tinyint(1) DEFAULT 0,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `alumni`
--

INSERT INTO `alumni` (`id`, `nome`, `curso_id`, `ano_conclusao`, `foto_url`, `percurso_texto`, `cargo_atual`, `empresa`, `destaque`, `ordem`, `ativo`, `created_at`, `updated_at`) VALUES
(1, 'Renato Monana', 11, '2026', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778620915/ipikk/alumni/gbyki4gny0jx4umnqs2z.jpg', 'Bom de desenhar.', 'Arquitecto', 'Sonangol', 0, 0, 1, '2026-05-12 21:21:56', '2026-05-12 21:21:56');

-- --------------------------------------------------------

--
-- Estrutura da tabela `areas`
--

CREATE TABLE `areas` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `descricao_curta` varchar(255) DEFAULT NULL,
  `descricao_completa` text DEFAULT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `icone_classe` varchar(50) DEFAULT NULL,
  `cor_primaria` varchar(7) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `visualizacoes` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `areas`
--

INSERT INTO `areas` (`id`, `nome`, `slug`, `descricao_curta`, `descricao_completa`, `imagem_url`, `icone_classe`, `cor_primaria`, `ordem`, `visualizacoes`, `ativo`, `created_at`) VALUES
(10, 'Construção Civil', 'constru-o-civil', 'Boa area', 'Grande area', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778604891/ipikk/areas/ksxw8z0zyuoc2eihx5t3.jpg', 'fa-helmet-safety', '#6c757d', 0, 0, 1, '2026-05-09 15:19:38');

-- --------------------------------------------------------

--
-- Estrutura da tabela `categorias_galeria`
--

CREATE TABLE `categorias_galeria` (
  `id` int(11) NOT NULL,
  `nome` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `cor_classe` varchar(50) DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `categorias_galeria`
--

INSERT INTO `categorias_galeria` (`id`, `nome`, `slug`, `cor_classe`, `icone`, `ordem`, `ativo`, `created_at`) VALUES
(20, 'Desenhador projectista', 'desenhador-projectista', '#5d7000', 'fa-tag', 0, 1, '2026-05-12 21:39:51');

-- --------------------------------------------------------

--
-- Estrutura da tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `id` int(11) NOT NULL,
  `instituicao_nome` varchar(200) DEFAULT 'Instituto Médio Politécnico Industrial do Kilamba Kiaxi',
  `instituicao_acronimo` varchar(20) DEFAULT 'IPIKK',
  `instituicao_slogan` varchar(200) DEFAULT 'Um diferencial para a sua formação',
  `endereco_completo` text DEFAULT NULL,
  `cidade` varchar(100) DEFAULT 'Luanda',
  `provincia` varchar(100) DEFAULT 'Luanda',
  `telefone` varchar(20) DEFAULT '933 096 705',
  `telefone_alternativo` varchar(20) DEFAULT NULL,
  `email_geral` varchar(100) DEFAULT 'geral@ipikk.ao',
  `email_inscricoes` varchar(100) DEFAULT 'inscricoes@ipikk.ao',
  `whatsapp_numero` varchar(20) DEFAULT '244933096705',
  `horario_funcionamento` varchar(200) DEFAULT 'Segunda a Sexta: 7:00 - 17:40',
  `horario_inscricoes` varchar(200) DEFAULT 'Segunda a Sexta, das 8h às 16h',
  `logo_url` varchar(255) DEFAULT 'foto/ipikk_new_logo.png',
  `logo_rodape_url` varchar(255) DEFAULT 'foto/ipikk_new_logo_1.png',
  `favicon_url` varchar(255) DEFAULT 'foto/ipikk_new_logo.png',
  `rede_social_facebook` varchar(255) DEFAULT NULL,
  `rede_social_instagram` varchar(255) DEFAULT NULL,
  `rede_social_linkedin` varchar(255) DEFAULT NULL,
  `rede_social_youtube` varchar(255) DEFAULT NULL,
  `rede_social_twitter` varchar(255) DEFAULT NULL,
  `rede_social_tiktok` varchar(255) DEFAULT NULL,
  `mostrar_social_header` tinyint(1) DEFAULT 1,
  `mostrar_social_footer` tinyint(1) DEFAULT 1,
  `social_nova_janela` tinyint(1) DEFAULT 0,
  `cor_primaria` varchar(7) DEFAULT '#003072',
  `cor_azul_claro` varchar(7) DEFAULT '#2e86c1',
  `cor_azul_escuro` varchar(7) DEFAULT '#001a40',
  `cor_verde_acento` varchar(7) DEFAULT '#0a9396',
  `cor_verde_claro` varchar(7) DEFAULT '#94d2bd',
  `cor_texto` varchar(7) DEFAULT '#212529',
  `cor_fundo` varchar(7) DEFAULT '#f8f9fa',
  `fonte_principal` varchar(50) DEFAULT 'Poppins',
  `fonte_secundaria` varchar(50) DEFAULT 'Montserrat',
  `tamanho_fonte` varchar(10) DEFAULT '16px',
  `altura_linha` varchar(10) DEFAULT '1.6',
  `efeito_animacoes` tinyint(1) DEFAULT 1,
  `efeito_transicoes` tinyint(1) DEFAULT 1,
  `efeito_hover` tinyint(1) DEFAULT 1,
  `efeito_sombras` tinyint(1) DEFAULT 1,
  `intensidade_sombra` varchar(10) DEFAULT '0.1',
  `borda_arredondada` varchar(10) DEFAULT '12px',
  `css_personalizado` text DEFAULT NULL,
  `smtp_host` varchar(100) DEFAULT NULL,
  `smtp_porta` int(11) DEFAULT NULL,
  `smtp_seguranca` varchar(20) DEFAULT NULL,
  `smtp_email` varchar(100) DEFAULT NULL,
  `smtp_senha` varchar(255) DEFAULT NULL,
  `seo_titulo` varchar(200) DEFAULT 'IPIKK - Instituto Politécnico Industrial',
  `seo_descricao` text DEFAULT NULL,
  `seo_keywords` text DEFAULT NULL,
  `seo_url` varchar(255) DEFAULT 'https://www.ipikk.ao',
  `modo_manutencao` tinyint(1) DEFAULT 0,
  `manutencao_inicio` datetime DEFAULT NULL,
  `manutencao_fim` datetime DEFAULT NULL,
  `manutencao_titulo` varchar(100) DEFAULT 'Site em Manutenção',
  `manutencao_mensagem_principal` varchar(255) DEFAULT 'Estamos realizando melhorias para lhe servir melhor.',
  `manutencao_detalhes` text DEFAULT NULL,
  `manutencao_previsao` varchar(100) DEFAULT 'em breve',
  `manutencao_telefone` varchar(20) DEFAULT NULL,
  `manutencao_whatsapp` varchar(20) DEFAULT NULL,
  `manutencao_email` varchar(100) DEFAULT NULL,
  `backup_frequencia` enum('diario','semanal','mensal') DEFAULT 'semanal',
  `backup_horario` time DEFAULT '02:00:00',
  `backup_manter` int(11) DEFAULT 4,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `matricula_titulo` varchar(200) DEFAULT 'Faça a sua matrícula no IPIKK',
  `matricula_descricao` text DEFAULT NULL,
  `matricula_imagem_url` varchar(255) DEFAULT NULL,
  `ano_lectivo_atual` varchar(20) DEFAULT NULL,
  `rodape_links_ipikk` text DEFAULT NULL,
  `rodape_links_rapidos` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `configuracoes`
--

INSERT INTO `configuracoes` (`id`, `instituicao_nome`, `instituicao_acronimo`, `instituicao_slogan`, `endereco_completo`, `cidade`, `provincia`, `telefone`, `telefone_alternativo`, `email_geral`, `email_inscricoes`, `whatsapp_numero`, `horario_funcionamento`, `horario_inscricoes`, `logo_url`, `logo_rodape_url`, `favicon_url`, `rede_social_facebook`, `rede_social_instagram`, `rede_social_linkedin`, `rede_social_youtube`, `rede_social_twitter`, `rede_social_tiktok`, `mostrar_social_header`, `mostrar_social_footer`, `social_nova_janela`, `cor_primaria`, `cor_azul_claro`, `cor_azul_escuro`, `cor_verde_acento`, `cor_verde_claro`, `cor_texto`, `cor_fundo`, `fonte_principal`, `fonte_secundaria`, `tamanho_fonte`, `altura_linha`, `efeito_animacoes`, `efeito_transicoes`, `efeito_hover`, `efeito_sombras`, `intensidade_sombra`, `borda_arredondada`, `css_personalizado`, `smtp_host`, `smtp_porta`, `smtp_seguranca`, `smtp_email`, `smtp_senha`, `seo_titulo`, `seo_descricao`, `seo_keywords`, `seo_url`, `modo_manutencao`, `manutencao_inicio`, `manutencao_fim`, `manutencao_titulo`, `manutencao_mensagem_principal`, `manutencao_detalhes`, `manutencao_previsao`, `manutencao_telefone`, `manutencao_whatsapp`, `manutencao_email`, `backup_frequencia`, `backup_horario`, `backup_manter`, `created_at`, `updated_at`, `matricula_titulo`, `matricula_descricao`, `matricula_imagem_url`, `ano_lectivo_atual`, `rodape_links_ipikk`, `rodape_links_rapidos`) VALUES
(1, 'Instituto Médio Politécnico Industrial do Kilamba Kiaxi', 'IPIKK', 'Um diferencial para a sua formação', 'Distrito Urbano da Nova-Vida, Rua 130, Kilamba Kiaxi', 'Luanda', 'Luanda', '933 096 705', '', 'geral@ipikk.ao', 'inscricoes@ipikk.ao', '244933096705', 'Segunda a Sexta: 7:00 - 17:40', 'Segunda a Sexta, das 8h às 16h', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778942349/ipikk/configuracoes/ybwvxsfyfqcz66wy8nzu.png', '', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778942359/ipikk/configuracoes/utk6ps0l3unlaxqqvkw8.png', '', '', '', '', '', '', 1, 1, 1, '#003072', '#2e86c1', '#001a40', '#0a9396', '#94d2bd', '#212529', '#f8f9fa', 'Poppins', 'Montserrat', '16px', '1.6', 1, 1, 1, 1, '0.1', '12px', '', 'smtp.gmail.com', 587, 'tls', 'emailtester2901@gmail.com', 'mmycglpzojvtzsra', 'IPIKK - Instituto Politécnico Industrial', 'Formacao tecnica especializada', 'IPIKK, formacao tecnica', 'https://www.ipikk.ao', 0, '0000-00-00 00:00:00', '0000-00-00 00:00:00', 'Site em Manutenção', 'Estamos realizando melhorias para lhe servir melhor.', 'O site estara disponivel em breve.\nEstamos atualizando nossos sistemas.\nAgradecemos pela paciencia.', 'em breve', '933 096 705', '244933096705', 'geral@ipikk.ao', 'semanal', '02:00:00', 4, '2026-03-27 22:05:18', '2026-05-16 14:39:24', 'Faça a sua matrícula no IPIKK', '', '', '2028/2029', 'Sobre Nós|sobre-nos.php\nInscrição|inscricoes.php\nContactos|contatos.php\nÁrea Restrita|area-restrita.php\nPolíticas de Privacidade|politica-privacidade.php', 'Governo de Angola|https://governo.gov.ao/\nGoverno Provincial de Luanda|https://luanda.gov.ao/\nMinistério da Educação|https://med.gov.ao/\nInstituto de Telecomunicações|https://itel.gov.ao/\nWebmail IPIKK|https://webmail.ipikk.ao/');

-- --------------------------------------------------------

--
-- Estrutura da tabela `conteudo_inscricoes`
--

CREATE TABLE `conteudo_inscricoes` (
  `id` int(11) NOT NULL,
  `titulo_disponivel` varchar(200) DEFAULT NULL,
  `msg_abertura` text DEFAULT NULL,
  `documentos` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`documentos`)),
  `passos_inscricao` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`passos_inscricao`)),
  `texto_teste` text DEFAULT NULL,
  `data_teste` datetime DEFAULT NULL,
  `horario_teste` varchar(100) DEFAULT NULL,
  `titulo_indisponivel` varchar(200) DEFAULT NULL,
  `msg_indisponivel` text DEFAULT NULL,
  `texto_info_indisponivel` text DEFAULT NULL,
  `proximo_periodo` varchar(200) DEFAULT NULL,
  `titulo_matricula` varchar(200) DEFAULT NULL,
  `descricao_matricula` text DEFAULT NULL,
  `passos_matricula` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`passos_matricula`)),
  `cards_matricula` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cards_matricula`)),
  `info_importantes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`info_importantes`)),
  `texto_cartao_estudante` text DEFAULT NULL,
  `mensagem_cartao_destaque` text DEFAULT NULL,
  `vagas_curso` longtext DEFAULT NULL CHECK (json_valid(`vagas_curso`)),
  `data_resultados` datetime DEFAULT NULL,
  `resultados_disponiveis` tinyint(1) DEFAULT 0,
  `contacto_telefone` varchar(20) DEFAULT NULL,
  `contacto_email` varchar(100) DEFAULT NULL,
  `contacto_horario` varchar(200) DEFAULT NULL,
  `contacto_endereco` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `conteudo_paginas`
--

CREATE TABLE `conteudo_paginas` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL COMMENT 'identificador unico da pagina',
  `titulo` varchar(200) DEFAULT NULL,
  `subtitulo` varchar(500) DEFAULT NULL,
  `conteudo` longtext DEFAULT NULL COMMENT 'JSON com todos os dados da pagina',
  `imagem_hero` varchar(255) DEFAULT NULL,
  `meta_descricao` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `status` enum('publicado','rascunho') DEFAULT 'publicado',
  `ultima_edicao_por` int(11) DEFAULT NULL,
  `ultima_edicao_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `conteudo_paginas`
--

INSERT INTO `conteudo_paginas` (`id`, `slug`, `titulo`, `subtitulo`, `conteudo`, `imagem_hero`, `meta_descricao`, `meta_keywords`, `status`, `ultima_edicao_por`, `ultima_edicao_em`, `created_at`) VALUES
(15, 'inicio', 'Inicio', '', '{\"parceiros\":[{\"nome\":\"ndombwa\",\"link\":\"\",\"logo\":\"https:\\/\\/res.cloudinary.com\\/diebqhzub\\/image\\/upload\\/v1778600216\\/ipikk\\/inicio\\/parceiros\\/je61kwnvmrfwaoxibol2.png\"}],\"slider\":[{\"titulo\":\"Inicio\",\"subtitulo\":\"Escola\",\"botao\":\"Saiba mais\",\"link\":\"sobre-nos.php\",\"imagem\":\"https:\\/\\/res.cloudinary.com\\/diebqhzub\\/image\\/upload\\/v1778599804\\/ipikk\\/inicio\\/slider\\/jvdg1v0clamw8wnfxud4.jpg\"}],\"mensagem_director\":{\"nome\":\"Ferreira Manuel Fragoso\",\"cargo\":\"Director do IPIKK\",\"mensagem\":\"\",\"assinatura\":\"Ferreira Manuel Fragoso\",\"foto\":\"https:\\/\\/res.cloudinary.com\\/diebqhzub\\/image\\/upload\\/v1778678649\\/ipikk\\/inicio\\/director\\/cut5xirch7x9ebnffnfe.jpg\"},\"matricula\":{\"titulo\":\"Faça a sua matrícula no IPIKK\",\"descricao\":\"Junte-se a nós, usufrua do que temos a oferecer para a sua capacitação profissional.\",\"imagem\":\"https:\\/\\/res.cloudinary.com\\/diebqhzub\\/image\\/upload\\/v1778600061\\/ipikk\\/inicio\\/matricula\\/viiqqpfa8jl4yoqte3nf.jpg\"}}', '', '', '', 'publicado', 1, '2026-05-13 13:24:29', '2026-05-09 18:51:14'),
(16, 'sobre', 'Sobre', '', '{\"hero\":{\"titulo\":\"Quem Somos?\",\"subtitulo\":\"Conheça a história, missão e valores do Instituto Politécnico Industrial do Kilamba Kiaxi nº 8056 \\\"Nova Vida\\\"\"},\"historia\":{\"titulo\":\"Nossa História\",\"conteudo\":\"\",\"imagem\":\"https:\\/\\/res.cloudinary.com\\/diebqhzub\\/image\\/upload\\/v1778678787\\/ipikk\\/sobre\\/depf18pdpop25cg7ijzh.jpg\",\"legenda\":\"IPIKK — Símbolo de excelência no ensino técnico-profissional angolano\"},\"missao\":\"\",\"visao\":\"\",\"valores\":\"\",\"lema\":\"\\\"Um diferencial para a sua formação\\\"\",\"lema_descricao\":\"Mais do que uma frase, nosso compromisso diário com cada estudante\"}', '', '', '', 'publicado', 1, '2026-05-13 13:26:27', '2026-05-12 20:55:37'),
(17, 'director', 'Perfil do Director', '', '{\"nome\":\"Ferreira Manuel Fragoso\",\"cargo\":\"Director Geral do IPIKK\",\"foto\":\"https://res.cloudinary.com/diebqhzub/image/upload/v1778619615/ipikk/director/wqgdq0szazhusftprhol.jpg\",\"data_nascimento\":\"27 de Julho de 1971\",\"naturalidade\":\"Cacongo, Cabinda\",\"experiencia\":\"30+ Anos na Educação\",\"inicio_cargo\":\"17 de Outubro de 2018\",\"resumo\":\"\",\"citacao\":\"A educação é a base para a construção de uma sociedade sólida e o pilar para o desenvolvimento de cada indivíduo.\",\"formacoes\":[],\"experiencias\":[],\"realizacoes\":[],\"idiomas\":[\"Português (Nativo)\",\"Espanhol (Intermédio)\",\"Francês (Noções)\"]}', '', '', '', 'publicado', 1, '2026-05-12 21:00:17', '2026-05-12 21:00:17'),
(18, 'quadro-honra', 'Quadro de Honra', '', '{\"citacao\":{\"texto\":\"Deus te ama\",\"referencia\":\"Lucas 2:40\"}}', '', '', '', 'publicado', 1, '2026-05-13 11:56:48', '2026-05-12 21:29:13');

-- --------------------------------------------------------

--
-- Estrutura da tabela `controle_inscricoes`
--

CREATE TABLE `controle_inscricoes` (
  `id` int(11) NOT NULL,
  `status` enum('abertas','fechadas','agendadas') DEFAULT 'fechadas',
  `modo` enum('manual','agendado') DEFAULT 'manual',
  `data_abertura` datetime DEFAULT NULL,
  `data_encerramento` datetime DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `controle_inscricoes`
--

INSERT INTO `controle_inscricoes` (`id`, `status`, `modo`, `data_abertura`, `data_encerramento`, `updated_at`) VALUES
(1, 'abertas', 'manual', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '2026-05-14 21:38:28');

-- --------------------------------------------------------

--
-- Estrutura da tabela `cursos`
--

CREATE TABLE `cursos` (
  `id` int(11) NOT NULL,
  `area_id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `duracao` varchar(50) DEFAULT NULL,
  `nivel` varchar(50) DEFAULT NULL,
  `vagas` int(11) DEFAULT 0,
  `estado` enum('ativo','pausado','arquivado') DEFAULT 'ativo',
  `destaque` tinyint(1) DEFAULT 0,
  `icone_classe` varchar(50) DEFAULT NULL,
  `cor` varchar(7) DEFAULT NULL,
  `imagem_hero` varchar(255) DEFAULT NULL,
  `subtitulo_hero` varchar(500) DEFAULT NULL,
  `descricao_curta` text DEFAULT NULL,
  `descricao_completa` text DEFAULT NULL,
  `sobre_descricao` text DEFAULT NULL,
  `objetivo` text DEFAULT NULL,
  `competencias_descricao` text DEFAULT NULL,
  `competencias_card` text DEFAULT NULL,
  `certificacao_descricao` text DEFAULT NULL,
  `programa_pdf_url` varchar(255) DEFAULT NULL,
  `competencias` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`competencias`)),
  `ordem` int(11) DEFAULT 0,
  `visualizacoes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `cursos`
--

INSERT INTO `cursos` (`id`, `area_id`, `nome`, `slug`, `duracao`, `nivel`, `vagas`, `estado`, `destaque`, `icone_classe`, `cor`, `imagem_hero`, `subtitulo_hero`, `descricao_curta`, `descricao_completa`, `sobre_descricao`, `objetivo`, `competencias_descricao`, `competencias_card`, `certificacao_descricao`, `programa_pdf_url`, `competencias`, `ordem`, `visualizacoes`, `created_at`, `updated_at`) VALUES
(10, 10, 'Tecnico de Obras', 'tecnico-de-obras', '4 anos', 'Tecnico Medio', 0, 'ativo', 0, 'fa-helmet-safety', '#8c8c8c', NULL, NULL, '', NULL, '', '', '', NULL, '', NULL, NULL, 0, 3, '2026-05-09 15:20:54', '2026-05-09 17:39:23'),
(11, 10, 'Desenhador projectista', 'desenhador-projectista', '4 anos', 'Tecnico Medio', 0, 'ativo', 0, 'fa-drafting-compass', '#8e9806', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778605591/ipikk/cursos/hero/fqfnqf0trz0hdtgiryvt.jpg', NULL, '', NULL, '', '', '', '', '', NULL, NULL, 0, 25, '2026-05-09 15:42:34', '2026-05-16 14:25:31');

-- --------------------------------------------------------

--
-- Estrutura da tabela `depoimentos`
--

CREATE TABLE `depoimentos` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `tipo_depoimento` enum('atual','ex_aluno') NOT NULL DEFAULT 'ex_aluno',
  `curso_nome` varchar(100) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `turma` varchar(10) NOT NULL,
  `ano_atual` varchar(20) DEFAULT NULL,
  `empresa` varchar(200) NOT NULL,
  `texto` text NOT NULL,
  `foto_url` varchar(255) DEFAULT 'foto/sem_foto.png',
  `destaque` tinyint(1) DEFAULT 0,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `depoimentos`
--

INSERT INTO `depoimentos` (`id`, `curso_id`, `tipo_depoimento`, `curso_nome`, `nome`, `turma`, `ano_atual`, `empresa`, `texto`, `foto_url`, `destaque`, `ordem`, `ativo`, `created_at`, `updated_at`) VALUES
(4, 10, 'ex_aluno', 'Tecnico de Obras', 'Renato Monana', '2025', '', 'Sonangol', 'Boa escola', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778618259/ipikk/depoimentos/onyetxnywrx11ns0kxhh.jpg', 1, 1, 1, '2026-05-09 17:13:45', '2026-05-12 20:37:41'),
(5, 11, 'atual', 'Desenhador projectista', 'Felix Zangue', 'GSI10AM', '2026', 'Estudante', 'Boa e grande escola.', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778618289/ipikk/depoimentos/t7dp4sot9wduik6kbu90.jpg', 1, 2, 1, '2026-05-09 17:36:02', '2026-05-12 20:38:11');

-- --------------------------------------------------------

--
-- Estrutura da tabela `documentos`
--

CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `descricao` text DEFAULT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `categoria` varchar(50) DEFAULT 'normativos',
  `pdf_url` varchar(255) NOT NULL,
  `data_publicacao` date DEFAULT NULL,
  `tamanho_kb` int(11) DEFAULT NULL,
  `downloads` int(11) DEFAULT 0,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `documentos`
--

INSERT INTO `documentos` (`id`, `titulo`, `descricao`, `imagem_url`, `categoria`, `pdf_url`, `data_publicacao`, `tamanho_kb`, `downloads`, `ordem`, `ativo`, `created_at`) VALUES
(3, 'lei do ipikk', 'boa lei', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778705272/ipikk/documentos/imagens/s0wrlilnkinrf1fmvg8d.jpg', 'normativos', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778705277/ipikk/documentos/pdfs/zuosb7dzi70r9u8n8xgd.pdf', '2026-05-13', 106, 0, 0, 1, '2026-05-13 20:47:56'),
(4, 'Lei da escola', 'lei de rigor', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778713311/ipikk/documentos/imagens/yo1druwgpub3p8lhec5x.jpg', 'normativos', 'https://res.cloudinary.com/diebqhzub/raw/upload/v1778713321/ipikk/documentos/pdfs/yntjv3d04pethznq2t7n.pdf', '0000-00-00', 226, 0, 0, 1, '2026-05-13 23:02:00');

-- --------------------------------------------------------

--
-- Estrutura da tabela `documentos_inscricao`
--

CREATE TABLE `documentos_inscricao` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `equipe`
--

CREATE TABLE `equipe` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cargo` varchar(100) NOT NULL,
  `curso` varchar(100) DEFAULT NULL,
  `disciplina` varchar(100) DEFAULT NULL,
  `categoria` enum('direcao_executiva','coordenador_curso','coordenador_disciplina','chefe_area','outros') NOT NULL,
  `tipo_card` enum('grande','pequeno') DEFAULT 'pequeno',
  `foto_url` varchar(255) DEFAULT 'foto/sem_foto.png',
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `equipe`
--

INSERT INTO `equipe` (`id`, `nome`, `cargo`, `curso`, `disciplina`, `categoria`, `tipo_card`, `foto_url`, `ordem`, `ativo`, `created_at`) VALUES
(5, 'Ferreira Manuel Fragoso', 'Director Geral do IPIKK', '', '', 'direcao_executiva', 'grande', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778619923/ipikk/equipe/fngf67d3udh8nzdglobz.jpg', 0, 1, '2026-05-12 21:05:24');

-- --------------------------------------------------------

--
-- Estrutura da tabela `escolas_afiliadas`
--

CREATE TABLE `escolas_afiliadas` (
  `id` int(11) NOT NULL,
  `nome` varchar(200) NOT NULL,
  `tipo` varchar(50) DEFAULT 'Privado',
  `logo_url` varchar(255) DEFAULT 'foto/sem_logo.png',
  `email` varchar(100) DEFAULT NULL,
  `telefone1` varchar(20) DEFAULT NULL,
  `telefone2` varchar(20) DEFAULT NULL,
  `endereco` text DEFAULT NULL,
  `site_url` varchar(255) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `escolas_afiliadas`
--

INSERT INTO `escolas_afiliadas` (`id`, `nome`, `tipo`, `logo_url`, `email`, `telefone1`, `telefone2`, `endereco`, `site_url`, `ordem`, `ativo`, `created_at`) VALUES
(8, 'ndombwa', 'Privado', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778621805/ipikk/escolas/jaubiegkv4okpbhrbfuf.jpg', '', '', '', '', '', 1, 1, '2026-05-12 21:36:46');

-- --------------------------------------------------------

--
-- Estrutura da tabela `estatisticas`
--

CREATE TABLE `estatisticas` (
  `id` int(11) NOT NULL,
  `tipo` enum('visitante','curso','noticia','area') NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `contador` int(11) DEFAULT 1,
  `data_referencia` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `estatisticas`
--

INSERT INTO `estatisticas` (`id`, `tipo`, `referencia_id`, `contador`, `data_referencia`, `created_at`) VALUES
(1, 'visitante', 0, 3, '2026-05-09', '2026-05-09 14:13:25'),
(2, 'area', 10, 1, '2026-05-09', '2026-05-09 15:22:56'),
(3, 'area', 10, 1, '2026-05-09', '2026-05-09 15:23:03'),
(4, 'curso', 10, 1, '2026-05-09', '2026-05-09 15:23:46'),
(5, 'area', 10, 1, '2026-05-09', '2026-05-09 15:24:37'),
(6, 'area', 10, 1, '2026-05-09', '2026-05-09 15:40:13'),
(7, 'area', 10, 1, '2026-05-09', '2026-05-09 15:42:48'),
(8, 'area', 10, 1, '2026-05-09', '2026-05-09 16:19:33'),
(9, 'area', 10, 1, '2026-05-09', '2026-05-09 16:21:30'),
(10, 'curso', 11, 1, '2026-05-09', '2026-05-09 16:21:44'),
(11, 'curso', 11, 1, '2026-05-09', '2026-05-09 16:33:24'),
(12, 'curso', 10, 1, '2026-05-09', '2026-05-09 16:33:56'),
(13, 'curso', 11, 1, '2026-05-09', '2026-05-09 17:39:03'),
(14, 'curso', 10, 1, '2026-05-09', '2026-05-09 17:39:23'),
(15, 'visitante', 0, 2, '2026-05-10', '2026-05-09 23:07:06'),
(16, 'visitante', 0, 4, '2026-05-12', '2026-05-12 12:08:04'),
(17, 'area', 10, 1, '2026-05-12', '2026-05-12 17:01:44'),
(18, 'area', 10, 1, '2026-05-12', '2026-05-12 17:06:56'),
(19, 'curso', 11, 1, '2026-05-12', '2026-05-12 17:07:08'),
(20, 'curso', 11, 1, '2026-05-12', '2026-05-12 17:08:26'),
(21, 'curso', 11, 1, '2026-05-12', '2026-05-12 17:12:49'),
(22, 'curso', 11, 1, '2026-05-12', '2026-05-12 18:47:55'),
(23, 'curso', 11, 1, '2026-05-12', '2026-05-12 19:24:20'),
(24, 'curso', 11, 1, '2026-05-12', '2026-05-12 20:17:53'),
(25, 'curso', 11, 1, '2026-05-12', '2026-05-12 20:26:54'),
(26, 'curso', 11, 1, '2026-05-12', '2026-05-12 20:34:19'),
(27, 'curso', 11, 1, '2026-05-12', '2026-05-12 20:38:30'),
(28, 'curso', 11, 1, '2026-05-12', '2026-05-12 22:10:41'),
(29, 'curso', 11, 1, '2026-05-13', '2026-05-13 10:22:21'),
(30, 'curso', 11, 1, '2026-05-13', '2026-05-13 10:31:29'),
(31, 'curso', 11, 1, '2026-05-13', '2026-05-13 10:32:01'),
(32, 'curso', 11, 1, '2026-05-13', '2026-05-13 15:07:56'),
(33, 'curso', 11, 1, '2026-05-13', '2026-05-13 22:58:15'),
(34, 'area', 10, 1, '2026-05-14', '2026-05-13 23:04:46'),
(35, 'area', 10, 1, '2026-05-14', '2026-05-14 00:17:12'),
(36, 'visitante', 0, 1, '2026-05-14', '2026-05-14 22:43:54'),
(37, 'curso', 11, 1, '2026-05-16', '2026-05-16 10:22:08'),
(38, 'curso', 11, 1, '2026-05-16', '2026-05-16 10:41:56'),
(39, 'curso', 11, 1, '2026-05-16', '2026-05-16 10:48:03'),
(40, 'curso', 11, 1, '2026-05-16', '2026-05-16 11:35:55'),
(41, 'curso', 11, 1, '2026-05-16', '2026-05-16 11:39:44'),
(42, 'curso', 11, 1, '2026-05-16', '2026-05-16 11:41:16'),
(43, 'visitante', NULL, 1, '2026-05-17', '2026-05-16 23:46:38');

-- --------------------------------------------------------

--
-- Estrutura da tabela `ex_diretores`
--

CREATE TABLE `ex_diretores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cargo` varchar(100) DEFAULT 'Director Geral',
  `foto_url` varchar(255) DEFAULT 'foto/sem_foto.png',
  `periodo_inicio` varchar(10) DEFAULT NULL,
  `periodo_fim` varchar(10) DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `ex_diretores`
--

INSERT INTO `ex_diretores` (`id`, `nome`, `cargo`, `foto_url`, `periodo_inicio`, `periodo_fim`, `biografia`, `ordem`, `ativo`, `created_at`) VALUES
(3, 'João Domingos', 'Director Geral', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778620351/ipikk/ex-directores/or5lil1fmkwmhttuwlhf.jpg', '2009', '2018', 'bom pai', 0, 1, '2026-05-12 21:12:32');

-- --------------------------------------------------------

--
-- Estrutura da tabela `funcionarios_destaque`
--

CREATE TABLE `funcionarios_destaque` (
  `id` int(11) NOT NULL,
  `ano_lectivo` varchar(20) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cargo` varchar(200) NOT NULL,
  `foto_url` varchar(255) DEFAULT 'foto/sem_foto.png',
  `grupo` int(11) DEFAULT 1,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `funcionarios_destaque`
--

INSERT INTO `funcionarios_destaque` (`id`, `ano_lectivo`, `nome`, `cargo`, `foto_url`, `grupo`, `ordem`, `ativo`, `created_at`) VALUES
(5, '2026/2027', 'Renato Monana', 'Estudante', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778621618/ipikk/funcionarios/qrzh1e43fvssb251nqqk.jpg', 1, 1, 1, '2026-05-12 21:33:41');

-- --------------------------------------------------------

--
-- Estrutura da tabela `galeria`
--

CREATE TABLE `galeria` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `legenda` text DEFAULT NULL,
  `tipo` enum('imagem','video') NOT NULL DEFAULT 'imagem',
  `url` varchar(500) NOT NULL,
  `categoria_id` int(11) NOT NULL,
  `ordem` int(11) DEFAULT 0,
  `destaque` tinyint(1) DEFAULT 0,
  `visualizacoes` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `galeria`
--

INSERT INTO `galeria` (`id`, `titulo`, `legenda`, `tipo`, `url`, `categoria_id`, `ordem`, `destaque`, `visualizacoes`, `ativo`, `created_at`, `updated_at`) VALUES
(1, '', '', 'imagem', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778622011/ipikk/galeria/imagens/nlvi3oybdlxyfbeurx2t.jpg', 20, 0, 0, 0, 1, '2026-05-12 21:40:12', '2026-05-12 21:40:12'),
(2, '', '', 'video', 'https://res.cloudinary.com/diebqhzub/video/upload/v1778943308/ipikk/galeria/videos/hdi5v6prx7l0lnm7nmnl.mp4', 20, 1, 0, 0, 1, '2026-05-16 14:55:08', '2026-05-16 14:55:08');

-- --------------------------------------------------------

--
-- Estrutura da tabela `informacoes_matricula`
--

CREATE TABLE `informacoes_matricula` (
  `id` int(11) NOT NULL,
  `descricao` text NOT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `linha_tempo`
--

CREATE TABLE `linha_tempo` (
  `id` int(11) NOT NULL,
  `ano` varchar(10) NOT NULL,
  `descricao` text NOT NULL,
  `ativo` tinyint(1) DEFAULT 0,
  `ordem` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `lixeira`
--

CREATE TABLE `lixeira` (
  `id` int(11) NOT NULL,
  `tipo` enum('imagem','video') NOT NULL,
  `nome_original` varchar(255) NOT NULL,
  `caminho_original` varchar(500) NOT NULL,
  `caminho_lixeira` varchar(500) NOT NULL,
  `noticia_id` int(11) DEFAULT NULL,
  `noticia_titulo` varchar(200) DEFAULT NULL,
  `data_movimento` timestamp NOT NULL DEFAULT current_timestamp(),
  `restaurado` tinyint(1) DEFAULT 0,
  `data_expiracao` timestamp NULL DEFAULT NULL,
  `tamanho_bytes` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `logs`
--

CREATE TABLE `logs` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `acao` varchar(100) NOT NULL,
  `tabela` varchar(50) DEFAULT NULL,
  `registro_id` int(11) DEFAULT NULL,
  `detalhes` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `data_hora` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `logs`
--

INSERT INTO `logs` (`id`, `utilizador_id`, `acao`, `tabela`, `registro_id`, `detalhes`, `ip_address`, `user_agent`, `data_hora`) VALUES
(7, 3, 'redefiniu senha', 'utilizadores', 0, 'Senha redefinida via link de recuperação', '', '', '2026-05-09 22:36:59'),
(11, 3, 'redefiniu senha', 'utilizadores', 0, 'Senha redefinida via link de recuperação', '', '', '2026-05-09 23:08:39'),
(23, 3, 'redefiniu senha', 'utilizadores', 0, 'Senha redefinida via link de recuperação', '', '', '2026-05-12 20:10:51'),
(32, 1, 'login', 'utilizadores', NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36 Edg/148.0.0.0', '2026-05-16 23:49:28');

-- --------------------------------------------------------

--
-- Estrutura da tabela `mensagens`
--

CREATE TABLE `mensagens` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `assunto` varchar(200) NOT NULL,
  `mensagem` text NOT NULL,
  `curso_interesse` varchar(100) DEFAULT NULL,
  `anexo_url` varchar(255) DEFAULT NULL,
  `anexo_nome` varchar(100) DEFAULT NULL,
  `anexo_tamanho` int(11) DEFAULT NULL,
  `data_envio` timestamp NOT NULL DEFAULT current_timestamp(),
  `lida` tinyint(1) DEFAULT 0,
  `respondida` tinyint(1) DEFAULT 0,
  `favorita` tinyint(1) DEFAULT 0,
  `arquivada` tinyint(1) DEFAULT 0,
  `lixeira` tinyint(1) DEFAULT 0,
  `resposta` text DEFAULT NULL,
  `resposta_texto` text DEFAULT NULL,
  `data_resposta` timestamp NULL DEFAULT NULL,
  `respondido_por` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `noticias`
--

CREATE TABLE `noticias` (
  `id` int(11) NOT NULL,
  `titulo` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `resumo` text DEFAULT NULL,
  `conteudo` text NOT NULL,
  `categoria` enum('DESTAQUE','CURSOS','EVENTOS','PARCERIA','INSTITUCIONAL') DEFAULT 'INSTITUCIONAL',
  `tipo_midia` enum('imagem','video') DEFAULT 'imagem',
  `imagem_url` varchar(255) DEFAULT NULL,
  `video_file` varchar(255) DEFAULT NULL,
  `alt_text` varchar(200) DEFAULT NULL,
  `autor` varchar(100) NOT NULL,
  `tags` text DEFAULT NULL,
  `data_publicacao` date NOT NULL,
  `estado` enum('publicada','rascunho','arquivada') DEFAULT 'rascunho',
  `destaque_principal` tinyint(1) DEFAULT 0,
  `visualizacoes` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `noticias`
--

INSERT INTO `noticias` (`id`, `titulo`, `slug`, `resumo`, `conteudo`, `categoria`, `tipo_midia`, `imagem_url`, `video_file`, `alt_text`, `autor`, `tags`, `data_publicacao`, `estado`, `destaque_principal`, `visualizacoes`, `created_at`, `updated_at`) VALUES
(53, 'Ipikk abre inscrições', 'ipikk-abre-inscri-es', 'A3ZERRTUY', 'UUIHHVYT', 'DESTAQUE', 'imagem', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778588447/ipikk/noticias/i7vgs76nzd6tw4rwcydx.jpg', '', '', 'Renato', '[\"Inscrições\",\"Ano\",\"2025-26\"]', '2026-05-12', 'publicada', 1, 0, '2026-05-12 12:20:49', '2026-05-12 12:20:49');

-- --------------------------------------------------------

--
-- Estrutura da tabela `notificacoes`
--

CREATE TABLE `notificacoes` (
  `id` int(11) NOT NULL,
  `tipo` enum('sistema','contacto','noticia','curso','usuario') NOT NULL,
  `prioridade` enum('alta','media','baixa') DEFAULT 'media',
  `titulo` varchar(200) NOT NULL,
  `mensagem` text NOT NULL,
  `referencia_id` int(11) DEFAULT NULL,
  `referencia_tabela` varchar(50) DEFAULT NULL,
  `acao_link` varchar(255) DEFAULT NULL,
  `para_utilizador_id` int(11) DEFAULT NULL,
  `lida` tinyint(1) DEFAULT 0,
  `data_criacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `paginas_estaticas`
--

CREATE TABLE `paginas_estaticas` (
  `id` int(11) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `subtitulo` varchar(500) DEFAULT NULL,
  `conteudo` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`conteudo`)),
  `imagem_hero` varchar(255) DEFAULT NULL,
  `meta_descricao` text DEFAULT NULL,
  `meta_keywords` text DEFAULT NULL,
  `ultima_edicao_por` int(11) DEFAULT NULL,
  `ultima_edicao_em` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `passos_processo`
--

CREATE TABLE `passos_processo` (
  `id` int(11) NOT NULL,
  `tipo` enum('inscricao','matricula') NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `icone` varchar(50) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `planos_curriculares`
--

CREATE TABLE `planos_curriculares` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `disciplina` varchar(150) NOT NULL,
  `componente` enum('sociocultural','cientifica','tecnica') DEFAULT 'tecnica',
  `ordem` int(11) DEFAULT 0,
  `ativo` tinyint(4) DEFAULT 1,
  `horas_10a` int(11) DEFAULT 0,
  `horas_11a` int(11) DEFAULT 0,
  `horas_12a` int(11) DEFAULT 0,
  `horas_13a` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `plano_curricular`
--

CREATE TABLE `plano_curricular` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `classe` int(11) NOT NULL,
  `pdf_url` varchar(255) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `plano_curricular`
--

INSERT INTO `plano_curricular` (`id`, `curso_id`, `classe`, `pdf_url`, `ordem`, `created_at`, `updated_at`) VALUES
(2, 11, 10, 'https://res.cloudinary.com/diebqhzub/raw/upload/v1778713079/ipikk/planos-curriculares/wnqyvrucmccvlknozclz.pdf', 0, '2026-05-13 22:57:57', '2026-05-13 22:57:57');

-- --------------------------------------------------------

--
-- Estrutura da tabela `projetos`
--

CREATE TABLE `projetos` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `ano` varchar(4) DEFAULT NULL,
  `autor` varchar(100) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `projetos`
--

INSERT INTO `projetos` (`id`, `curso_id`, `titulo`, `descricao`, `categoria`, `imagem_url`, `ano`, `autor`, `ordem`, `created_at`, `updated_at`) VALUES
(11, 11, 'Planta de casa', 'boa residencia', 'Estruturas', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778618030/ipikk/cursos/projetos/defyfm6xn5i4blbl21dp.jpg', '2026', 'Aluno IPIKK', 0, '2026-05-16 11:41:07', '2026-05-16 11:41:07');

-- --------------------------------------------------------

--
-- Estrutura da tabela `push_fila`
--

CREATE TABLE `push_fila` (
  `id` int(11) NOT NULL,
  `noticia_id` int(11) NOT NULL,
  `status` enum('pendente','enviado','erro') NOT NULL DEFAULT 'pendente',
  `tentativas` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `push_noticias_enviadas`
--

CREATE TABLE `push_noticias_enviadas` (
  `id` int(11) NOT NULL,
  `noticia_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `push_subscricoes`
--

CREATE TABLE `push_subscricoes` (
  `id` int(11) NOT NULL,
  `endpoint` text NOT NULL,
  `endpoint_hash` char(64) GENERATED ALWAYS AS (sha2(`endpoint`,256)) STORED,
  `p256dh` varchar(255) NOT NULL,
  `auth` varchar(255) NOT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura da tabela `quadro_honra`
--

CREATE TABLE `quadro_honra` (
  `id` int(11) NOT NULL,
  `ano_lectivo` varchar(20) NOT NULL,
  `ativo` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `quadro_honra`
--

INSERT INTO `quadro_honra` (`id`, `ano_lectivo`, `ativo`, `created_at`, `updated_at`) VALUES
(2, '2024/2025', 1, '2026-05-12 21:24:31', '2026-05-12 21:24:31');

-- --------------------------------------------------------

--
-- Estrutura da tabela `quadro_honra_classe`
--

CREATE TABLE `quadro_honra_classe` (
  `id` int(11) NOT NULL,
  `quadro_honra_id` int(11) NOT NULL,
  `classe` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `media` varchar(20) NOT NULL,
  `curso` varchar(100) NOT NULL,
  `foto_url` varchar(255) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Extraindo dados da tabela `quadro_honra_classe`
--

INSERT INTO `quadro_honra_classe` (`id`, `quadro_honra_id`, `classe`, `nome`, `media`, `curso`, `foto_url`, `ordem`) VALUES
(28, 2, 10, 'renato Monana', '18', 'Desenhador projectista', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778621346/ipikk/quadro-honra/n3uttmgapmza03klcfxm.jpg', 0),
(29, 2, 11, 'Felix Zangue', '15', 'Tecnico de Obras', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778621349/ipikk/quadro-honra/dayifkzjlzl1h3k7glkl.jpg', 1),
(30, 2, 12, 'Manuel', '15', 'Desenhador projectista', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778621351/ipikk/quadro-honra/dn2prhnzoohxqxspvoum.jpg', 2);

-- --------------------------------------------------------

--
-- Estrutura da tabela `saidas_profissionais`
--

CREATE TABLE `saidas_profissionais` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `titulo` varchar(100) NOT NULL,
  `descricao` text DEFAULT NULL,
  `competencias` longtext DEFAULT NULL,
  `imagem_url` varchar(255) DEFAULT NULL,
  `icone_fallback` varchar(50) DEFAULT NULL,
  `ordem` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `saidas_profissionais`
--

INSERT INTO `saidas_profissionais` (`id`, `curso_id`, `titulo`, `descricao`, `competencias`, `imagem_url`, `icone_fallback`, `ordem`, `created_at`, `updated_at`) VALUES
(64, 11, 'Desenhador', 'desenhar é bom', '[\"cad\",\"bim\"]', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778617543/ipikk/cursos/saidas/dssbdqhs4ztvyewyqvdq.jpg', '', 0, '2026-05-16 11:41:07', '2026-05-16 11:41:07');

-- --------------------------------------------------------

--
-- Estrutura da tabela `tokens_recuperacao`
--

CREATE TABLE `tokens_recuperacao` (
  `id` int(11) NOT NULL,
  `utilizador_id` int(11) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiracao` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `usado` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `tokens_recuperacao`
--

INSERT INTO `tokens_recuperacao` (`id`, `utilizador_id`, `token`, `expiracao`, `usado`, `created_at`) VALUES
(1, 3, 'b2ff52a226979325ede3817075062e8333978c115c85eeb072633745fee9f6ed', '2026-05-09 22:22:12', 1, '2026-05-09 22:01:16'),
(2, 3, '745cbca6def319015439f74d602899b6f1096c81c172b89080a886ba93c42ca7', '2026-05-09 22:36:59', 1, '2026-05-09 22:22:12'),
(3, 3, 'b840f839d16de8241f08527bc8db3a526bc4974fb2d4eaff967c8963c443969c', '2026-05-09 23:08:39', 1, '2026-05-09 23:07:30'),
(4, 3, '436538fa1307b5679d1efa3fdc5a1b8204bd40766f82e113f43c877358501309', '2026-05-12 19:36:15', 1, '2026-05-12 19:35:34'),
(5, 3, '1b99f529be0eecb91a288b2ca7e7f3c465dbd9fde0c9af35d43b3a4a6ccca60f', '2026-05-12 19:36:43', 1, '2026-05-12 19:36:15'),
(6, 3, '9a675e6acdf562c335aa626da5ff3cb69f31e14407504ae83d59c4b89b66b096', '2026-05-12 19:37:20', 1, '2026-05-12 19:36:43'),
(7, 3, '3ebcd4e4b91969d64822a51ff20307ea88bd1bd95381a81e90aca3729c6b3c75', '2026-05-12 19:57:56', 1, '2026-05-12 19:37:21'),
(8, 3, '2c7533e4d922d721af05a4b260b66fbc986c8e1ef70137f35279be2344668bcf', '2026-05-12 20:07:20', 1, '2026-05-12 19:57:57'),
(9, 3, '85999d742f74cb5e311f7a8c62afd55f7a00dee9eb43366296c749c574c7ff03', '2026-05-12 20:10:50', 1, '2026-05-12 20:07:21');

-- --------------------------------------------------------

--
-- Estrutura da tabela `utilizadores`
--

CREATE TABLE `utilizadores` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `foto_url` varchar(255) DEFAULT 'foto/sem_foto.png',
  `avatar_icone` varchar(50) DEFAULT 'fa-user',
  `telefone` varchar(20) DEFAULT NULL,
  `departamento` varchar(100) DEFAULT NULL,
  `cargo` varchar(100) DEFAULT NULL,
  `biografia` text DEFAULT NULL,
  `nivel` enum('admin','editor') DEFAULT 'editor',
  `permissoes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`permissoes`)),
  `ativo` tinyint(1) DEFAULT 1,
  `forcar_alteracao_senha` tinyint(1) DEFAULT 1,
  `ultimo_login` timestamp NULL DEFAULT NULL,
  `idioma` varchar(10) DEFAULT 'pt',
  `fuso_horario` varchar(50) DEFAULT 'Africa/Maputo',
  `tema` varchar(10) DEFAULT 'claro',
  `tamanho_fonte` varchar(10) DEFAULT 'medio',
  `notificacoes_contacto` tinyint(1) DEFAULT 1,
  `notificacoes_sistema` tinyint(1) DEFAULT 1,
  `notificacoes_relatorios` tinyint(1) DEFAULT 0,
  `notificacoes_mensagens` tinyint(1) DEFAULT 1,
  `notificacoes_comentarios` tinyint(1) DEFAULT 1,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `two_factor_secret` varchar(100) DEFAULT NULL,
  `recovery_codes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recovery_codes`)),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `utilizadores`
--

INSERT INTO `utilizadores` (`id`, `nome`, `email`, `senha`, `foto_url`, `avatar_icone`, `telefone`, `departamento`, `cargo`, `biografia`, `nivel`, `permissoes`, `ativo`, `forcar_alteracao_senha`, `ultimo_login`, `idioma`, `fuso_horario`, `tema`, `tamanho_fonte`, `notificacoes_contacto`, `notificacoes_sistema`, `notificacoes_relatorios`, `notificacoes_mensagens`, `notificacoes_comentarios`, `two_factor_enabled`, `two_factor_secret`, `recovery_codes`, `created_at`, `updated_at`) VALUES
(1, 'Administrador IPIKK', 'admin@ipikk.ao', '$2y$10$fKFoY2LKoJCPpHYH9kWapePyIb9KndhdjbqPSkruuTX6LxTzRTdaa', 'https://res.cloudinary.com/diebqhzub/image/upload/v1778599694/ipikk/perfis/ghzhkp5pzhieomvzeq4d.jpg', 'fa-user', NULL, NULL, NULL, NULL, 'admin', '[\"dashboard\",\"conteudo_site\",\"oferta_formativa\",\"noticias\",\"galeria\",\"inscricoes\",\"contactos\",\"utilizadores\",\"configuracoes\",\"notificacoes\",\"logs\",\"lixeira\"]', 1, 1, '2026-05-16 23:49:28', 'pt', 'Africa/Maputo', 'claro', 'medio', 1, 1, 0, 1, 1, 0, NULL, NULL, '2026-03-27 22:05:18', '2026-05-16 23:49:28'),
(4, 'Renato Monana', '2008Monana@gmail.com', '$2y$10$SmH/jTYLY2Dfmxys0yNmL.SOV2Ldeimk2oAznUWeKqOdKmot9Vy3C', 'foto/sem_foto.png', 'fa-user', '952995369', 'Estudante', 'Editor', NULL, 'editor', '[\"conteudo_site\",\"oferta_formativa\",\"noticias\"]', 1, 1, NULL, 'pt', 'Africa/Maputo', 'claro', 'medio', 1, 1, 0, 1, 1, 0, NULL, NULL, '2026-05-14 23:19:26', '2026-05-14 23:19:26');

-- --------------------------------------------------------

--
-- Estrutura da tabela `vagas_curso`
--

CREATE TABLE `vagas_curso` (
  `id` int(11) NOT NULL,
  `curso_id` int(11) NOT NULL,
  `ano_lectivo` varchar(20) NOT NULL,
  `vagas_disponiveis` int(11) NOT NULL,
  `vagas_preenchidas` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Extraindo dados da tabela `vagas_curso`
--

INSERT INTO `vagas_curso` (`id`, `curso_id`, `ano_lectivo`, `vagas_disponiveis`, `vagas_preenchidas`) VALUES
(1, 11, '2026/2027', 23, 0),
(3, 11, '2025/2026', 4, 0),
(4, 10, '2025/2026', 5, 0),
(5, 11, '2028/2029', 4, 0),
(6, 10, '2028/2029', 5, 0);

--
-- Índices para tabelas despejadas
--

--
-- Índices para tabela `alumni`
--
ALTER TABLE `alumni`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_curso_id` (`curso_id`),
  ADD KEY `idx_destaque` (`destaque`);

--
-- Índices para tabela `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_ordem` (`ordem`);

--
-- Índices para tabela `categorias_galeria`
--
ALTER TABLE `categorias_galeria`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Índices para tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `conteudo_inscricoes`
--
ALTER TABLE `conteudo_inscricoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `conteudo_paginas`
--
ALTER TABLE `conteudo_paginas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `ultima_edicao_por` (`ultima_edicao_por`);

--
-- Índices para tabela `controle_inscricoes`
--
ALTER TABLE `controle_inscricoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_slug` (`slug`),
  ADD KEY `idx_area` (`area_id`),
  ADD KEY `idx_estado` (`estado`);

--
-- Índices para tabela `depoimentos`
--
ALTER TABLE `depoimentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_curso` (`curso_id`),
  ADD KEY `idx_destaque` (`destaque`);

--
-- Índices para tabela `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `documentos_inscricao`
--
ALTER TABLE `documentos_inscricao`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `equipe`
--
ALTER TABLE `equipe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categoria` (`categoria`);

--
-- Índices para tabela `escolas_afiliadas`
--
ALTER TABLE `escolas_afiliadas`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `estatisticas`
--
ALTER TABLE `estatisticas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tipo` (`tipo`),
  ADD KEY `idx_data` (`data_referencia`);

--
-- Índices para tabela `ex_diretores`
--
ALTER TABLE `ex_diretores`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `funcionarios_destaque`
--
ALTER TABLE `funcionarios_destaque`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ano` (`ano_lectivo`);

--
-- Índices para tabela `galeria`
--
ALTER TABLE `galeria`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_categoria` (`categoria_id`);

--
-- Índices para tabela `informacoes_matricula`
--
ALTER TABLE `informacoes_matricula`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `linha_tempo`
--
ALTER TABLE `linha_tempo`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `lixeira`
--
ALTER TABLE `lixeira`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_noticia` (`noticia_id`),
  ADD KEY `idx_restaurado` (`restaurado`);

--
-- Índices para tabela `logs`
--
ALTER TABLE `logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_utilizador` (`utilizador_id`),
  ADD KEY `idx_data` (`data_hora`);

--
-- Índices para tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD PRIMARY KEY (`id`),
  ADD KEY `respondido_por` (`respondido_por`),
  ADD KEY `idx_lida` (`lida`),
  ADD KEY `idx_data` (`data_envio`),
  ADD KEY `idx_email_data` (`email`,`data_envio`),
  ADD KEY `idx_respondida` (`respondida`);

--
-- Índices para tabela `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Índices para tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `para_utilizador_id` (`para_utilizador_id`),
  ADD KEY `idx_lida` (`lida`),
  ADD KEY `idx_tipo` (`tipo`);

--
-- Índices para tabela `paginas_estaticas`
--
ALTER TABLE `paginas_estaticas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `ultima_edicao_por` (`ultima_edicao_por`),
  ADD KEY `idx_slug` (`slug`);

--
-- Índices para tabela `passos_processo`
--
ALTER TABLE `passos_processo`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `planos_curriculares`
--
ALTER TABLE `planos_curriculares`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_curso_disciplina` (`curso_id`,`disciplina`);

--
-- Índices para tabela `plano_curricular`
--
ALTER TABLE `plano_curricular`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_curso_classe` (`curso_id`,`classe`),
  ADD KEY `idx_curso` (`curso_id`);

--
-- Índices para tabela `projetos`
--
ALTER TABLE `projetos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_curso` (`curso_id`);

--
-- Índices para tabela `push_fila`
--
ALTER TABLE `push_fila`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_push_fila_status` (`status`),
  ADD KEY `idx_push_fila_noticia` (`noticia_id`);

--
-- Índices para tabela `push_noticias_enviadas`
--
ALTER TABLE `push_noticias_enviadas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_push_noticia` (`noticia_id`);

--
-- Índices para tabela `push_subscricoes`
--
ALTER TABLE `push_subscricoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_push_endpoint_hash` (`endpoint_hash`),
  ADD KEY `idx_push_ativo` (`ativo`);

--
-- Índices para tabela `quadro_honra`
--
ALTER TABLE `quadro_honra`
  ADD PRIMARY KEY (`id`);

--
-- Índices para tabela `quadro_honra_classe`
--
ALTER TABLE `quadro_honra_classe`
  ADD PRIMARY KEY (`id`),
  ADD KEY `quadro_honra_id` (`quadro_honra_id`);

--
-- Índices para tabela `saidas_profissionais`
--
ALTER TABLE `saidas_profissionais`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_curso` (`curso_id`);

--
-- Índices para tabela `tokens_recuperacao`
--
ALTER TABLE `tokens_recuperacao`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `utilizador_id` (`utilizador_id`),
  ADD KEY `idx_token` (`token`);

--
-- Índices para tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Índices para tabela `vagas_curso`
--
ALTER TABLE `vagas_curso`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `curso_id` (`curso_id`,`ano_lectivo`);

--
-- AUTO_INCREMENT de tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `alumni`
--
ALTER TABLE `alumni`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `areas`
--
ALTER TABLE `areas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `categorias_galeria`
--
ALTER TABLE `categorias_galeria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `conteudo_inscricoes`
--
ALTER TABLE `conteudo_inscricoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `conteudo_paginas`
--
ALTER TABLE `conteudo_paginas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `controle_inscricoes`
--
ALTER TABLE `controle_inscricoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `depoimentos`
--
ALTER TABLE `depoimentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `documentos_inscricao`
--
ALTER TABLE `documentos_inscricao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `equipe`
--
ALTER TABLE `equipe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `escolas_afiliadas`
--
ALTER TABLE `escolas_afiliadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de tabela `estatisticas`
--
ALTER TABLE `estatisticas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT de tabela `ex_diretores`
--
ALTER TABLE `ex_diretores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `funcionarios_destaque`
--
ALTER TABLE `funcionarios_destaque`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `galeria`
--
ALTER TABLE `galeria`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `informacoes_matricula`
--
ALTER TABLE `informacoes_matricula`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `linha_tempo`
--
ALTER TABLE `linha_tempo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `lixeira`
--
ALTER TABLE `lixeira`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `logs`
--
ALTER TABLE `logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT de tabela `mensagens`
--
ALTER TABLE `mensagens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `noticias`
--
ALTER TABLE `noticias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT de tabela `notificacoes`
--
ALTER TABLE `notificacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `paginas_estaticas`
--
ALTER TABLE `paginas_estaticas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `passos_processo`
--
ALTER TABLE `passos_processo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `planos_curriculares`
--
ALTER TABLE `planos_curriculares`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT de tabela `plano_curricular`
--
ALTER TABLE `plano_curricular`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `projetos`
--
ALTER TABLE `projetos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `push_fila`
--
ALTER TABLE `push_fila`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `push_noticias_enviadas`
--
ALTER TABLE `push_noticias_enviadas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `push_subscricoes`
--
ALTER TABLE `push_subscricoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `quadro_honra`
--
ALTER TABLE `quadro_honra`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `quadro_honra_classe`
--
ALTER TABLE `quadro_honra_classe`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `saidas_profissionais`
--
ALTER TABLE `saidas_profissionais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT de tabela `tokens_recuperacao`
--
ALTER TABLE `tokens_recuperacao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `utilizadores`
--
ALTER TABLE `utilizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `vagas_curso`
--
ALTER TABLE `vagas_curso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restrições para despejos de tabelas
--

--
-- Limitadores para a tabela `mensagens`
--
ALTER TABLE `mensagens`
  ADD CONSTRAINT `mensagens_respondido_por_fk` FOREIGN KEY (`respondido_por`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `conteudo_paginas`
--
ALTER TABLE `conteudo_paginas`
  ADD CONSTRAINT `conteudo_paginas_ibfk_1` FOREIGN KEY (`ultima_edicao_por`) REFERENCES `utilizadores` (`id`) ON DELETE SET NULL;

--
-- Limitadores para a tabela `planos_curriculares`
--
ALTER TABLE `planos_curriculares`
  ADD CONSTRAINT `planos_curriculares_ibfk_1` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `projetos`
--
ALTER TABLE `projetos`
  ADD CONSTRAINT `projetos_fk` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `quadro_honra_classe`
--
ALTER TABLE `quadro_honra_classe`
  ADD CONSTRAINT `quadro_honra_classe_ibfk_1` FOREIGN KEY (`quadro_honra_id`) REFERENCES `quadro_honra` (`id`) ON DELETE CASCADE;

--
-- Limitadores para a tabela `saidas_profissionais`
--
ALTER TABLE `saidas_profissionais`
  ADD CONSTRAINT `saidas_fk` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
