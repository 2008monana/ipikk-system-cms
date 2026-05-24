-- Base de cursos coerente com o editor de "Competências em Destaque (Card)" do admin-cursos.php
-- Data: 2026-05-24
--
-- IMPORTANTE:
-- 1) O DELETE abaixo é opcional e destrutivo para os IDs indicados.
-- 2) Recomenda-se executar backup antes de aplicar em produção.

START TRANSACTION;

-- Remover cursos existentes (opcional - cuidado!)
-- DELETE FROM cursos WHERE id IN (1,2,3,4,5,6,8);

INSERT INTO cursos (
    id, area_id, nome, slug, duracao, nivel, vagas, estado, destaque,
    icone_classe, cor, descricao_curta, sobre_descricao, objetivo,
    competencias_descricao, competencias_card, certificacao_descricao,
    ordem, visualizacoes, created_at
) VALUES
(1, 1, 'Técnico de Obras', 'tecnico-de-obras', '4 anos', 'Técnico Médio', 40, 'ativo', 1, 'fa-helmet-safety', '#6c757d',
 'Executa, planeia e fiscaliza obras de construção civil.',
 'O Curso de Técnico de Obras forma profissionais capacitados para atuar na execução, planeamento e fiscalização de obras de construção civil.',
 'Formar técnicos capazes de planear, executar e fiscalizar obras de construção civil.',
 'Interpretar projetos arquitetónicos, estruturais e de instalações; Planear e controlar o cronograma de obras; Fiscalizar a qualidade dos materiais.',
 'Interpretação de projetos arquitetónicos\nPlaneamento e controlo de cronograma\nFiscalização de qualidade em obra',
 'Diploma de Técnico Médio reconhecido pelo Ministério da Educação de Angola.',
 1, 0, NOW()),

(2, 1, 'Desenhador Projectista', 'desenhador-projectista', '4 anos', 'Técnico Médio', 35, 'ativo', 1, 'fa-edit', '#b46e00',
 'Elabora projetos arquitetónicos e desenhos técnicos em CAD e BIM.',
 'O Curso de Desenhador Projectista forma profissionais especializados na criação de projetos arquitetónicos.',
 'Capacitar profissionais para desenvolver projetos arquitetónicos utilizando ferramentas CAD e BIM.',
 'Utilizar softwares AutoCAD, SketchUp, Revit; Elaborar plantas baixas, cortes, fachadas e perspetivas 3D.',
 'AutoCAD, SketchUp e Revit\nPlantas, cortes e fachadas técnicas\nPerspetivas 3D para projetos',
 'Diploma de Técnico Médio reconhecido pelo Ministério da Educação de Angola.',
 2, 0, NOW()),

(3, 2, 'Energia e Instalações Eléctricas', 'energia-e-instalacoes-electricas', '4 anos', 'Técnico Médio', 45, 'ativo', 1, 'fa-bolt', '#2e86c1',
 'Projecta, instala e mantém sistemas elétricos residenciais e industriais.',
 'O Curso de Energia e Instalações Elétricas forma técnicos aptos a projectar, instalar e manter sistemas eléctricos.',
 'Preparar técnicos para projectar, instalar e manter sistemas elétricos.',
 'Projetar instalações elétricas; Instalar e manter quadros elétricos; Diagnosticar falhas.',
 'Projeto de instalações elétricas\nMontagem de quadros e circuitos\nDiagnóstico e correção de falhas',
 'Diploma de Técnico Médio reconhecido pelo Ministério da Educação de Angola.',
 3, 0, NOW()),

(4, 3, 'Frio e Climatização', 'frio-e-climatizacao', '4 anos', 'Técnico Médio', 38, 'ativo', 1, 'fa-snowflake', '#e07b2a',
 'Instala e mantém sistemas de refrigeração, climatização e ventilação.',
 'O Curso de Frio e Climatização forma especialistas em sistemas de refrigeração, ar condicionado.',
 'Formar especialistas em sistemas de refrigeração e climatização.',
 'Dimensionar sistemas de refrigeração; Instalar equipamentos de ar condicionado; Diagnosticar avarias.',
 'Dimensionamento de sistemas de frio\nInstalação de ar condicionado\nDiagnóstico de avarias térmicas',
 'Diploma de Técnico Médio reconhecido pelo Ministério da Educação de Angola.',
 4, 0, NOW()),

(5, 4, 'Gestão de Sistemas Informáticos', 'gestao-de-sistemas-informaticos', '4 anos', 'Técnico Médio', 25, 'ativo', 1, 'fa-server', '#1a5a8c',
 'Administra servidores, redes, bases de dados e segurança da informação.',
 'O Curso de Gestão de Sistemas Informáticos forma profissionais capacitados para administrar servidores.',
 'Capacitar profissionais para administrar servidores, gerir redes de computadores.',
 'Administrar servidores Windows Server e Linux; Configurar serviços de rede; Implementar segurança.',
 'Administração Windows Server e Linux\nConfiguração de serviços de rede\nBoas práticas de cibersegurança',
 'Diploma de Técnico Médio reconhecido pelo Ministério da Educação de Angola.',
 5, 0, NOW()),

(6, 4, 'Técnico de Informática', 'tecnico-de-informatica', '4 anos', 'Técnico Médio', 28, 'ativo', 1, 'fa-laptop-code', '#2d7a3a',
 'Monta, configura, repara e presta suporte em computadores e redes.',
 'O Curso de Técnico de Informática forma profissionais especializados em hardware e software.',
 'Formar técnicos aptos a montar, configurar e reparar equipamentos informáticos.',
 'Montar e configurar computadores; Diagnosticar falhas; Configurar redes locais.',
 'Montagem e configuração de PCs\nDiagnóstico e reparação de falhas\nConfiguração de redes locais',
 'Diploma de Técnico Médio reconhecido pelo Ministério da Educação de Angola.',
 6, 0, NOW()),

(8, 6, 'Tecnologias de Móveis', 'tecnologias-de-moveis', '4 anos', 'Técnico Médio', 32, 'ativo', 1, 'fa-couch', '#c0392b',
 'Projeta e produz móveis com tecnologia, design e gestão industrial.',
 'O Curso de Tecnologias de Móveis forma profissionais capacitados para atuar na indústria moveleira.',
 'Desenvolver competências para projetar, produzir e gerir a produção de móveis.',
 'Desenhar móveis em softwares 3D; Operar máquinas de corte; Gerir produção.',
 'Modelação 3D de mobiliário\nOperação de máquinas de corte\nGestão de produção moveleira',
 'Diploma de Técnico Médio reconhecido pelo Ministério da Educação de Angola.',
 7, 0, NOW())
ON DUPLICATE KEY UPDATE
    area_id = VALUES(area_id),
    nome = VALUES(nome),
    slug = VALUES(slug),
    duracao = VALUES(duracao),
    nivel = VALUES(nivel),
    vagas = VALUES(vagas),
    estado = VALUES(estado),
    destaque = VALUES(destaque),
    icone_classe = VALUES(icone_classe),
    cor = VALUES(cor),
    descricao_curta = VALUES(descricao_curta),
    sobre_descricao = VALUES(sobre_descricao),
    objetivo = VALUES(objetivo),
    competencias_descricao = VALUES(competencias_descricao),
    competencias_card = VALUES(competencias_card),
    certificacao_descricao = VALUES(certificacao_descricao),
    ordem = VALUES(ordem);

COMMIT;
