<?php
/**
 * Header da Área Restrita - IPIKK
 */
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Área Restrita - <?= htmlspecialchars($titulo_pagina) ?> | IPIKK</title>
    
    <!-- Fontes -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Favicon -->
    <link href="<?= $config['favicon_url'] ?? '../area-publica/foto/ipikk_new_logo.png' ?>" rel="icon">
    
    <!-- CSS -->
    <link rel="stylesheet" href="css/admin-sidebar-header.css">
    <link rel="stylesheet" href="css/<?= $css_especifico ?>">
</head>
<body>