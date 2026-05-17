<?php
/**
 * Configuração de Armazenamento em Nuvem - IPIKK
 * Suporte para Cloudflare R2, Backblaze B2 ou AWS S3
 */

// Escolha o provedor: 'r2', 'b2', 's3'
define('CLOUD_PROVIDER', 'r2');

// Configurações Cloudflare R2
define('R2_ACCOUNT_ID', 'seu_account_id');
define('R2_ACCESS_KEY_ID', 'sua_access_key');
define('R2_SECRET_ACCESS_KEY', 'sua_secret_key');
define('R2_BUCKET_NAME', 'ipikk-uploads');
define('R2_PUBLIC_URL', 'https://pub-xxx.r2.dev'); // URL pública do bucket

// Configurações Backblaze B2 (alternativa)
define('B2_APPLICATION_KEY_ID', 'sua_key_id');
define('B2_APPLICATION_KEY', 'sua_key');
define('B2_BUCKET_NAME', 'ipikk-uploads');
define('B2_PUBLIC_URL', 'https://f00x.backblazeb2.com/file/ipikk-uploads');

// Configurações AWS S3 (alternativa)
define('S3_KEY', 'sua_access_key');
define('S3_SECRET', 'sua_secret_key');
define('S3_REGION', 'eu-south-2'); // Milão (mais próximo de Angola)
define('S3_BUCKET', 'ipikk-uploads');
define('S3_PUBLIC_URL', 'https://ipikk-uploads.s3.eu-south-2.amazonaws.com');