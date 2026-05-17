<?php
/**
 * Classe para upload de arquivos para a nuvem
 * Suporta: Cloudflare R2, Backblaze B2, AWS S3
 */

class CloudUpload {
    private $provider;
    private $client;
    private $bucket;
    private $publicUrl;

    public function __construct() {
        $this->provider = CLOUD_PROVIDER;
        $this->bucket = constant(strtoupper($this->provider) . '_BUCKET_NAME');
        $this->publicUrl = constant(strtoupper($this->provider) . '_PUBLIC_URL');
        
        $this->initializeClient();
    }

    private function initializeClient() {
        switch($this->provider) {
            case 'r2':
                $this->client = new Aws\S3\S3Client([
                    'version' => 'latest',
                    'region'  => 'auto',
                    'endpoint' => 'https://' . R2_ACCOUNT_ID . '.r2.cloudflarestorage.com',
                    'credentials' => [
                        'key'    => R2_ACCESS_KEY_ID,
                        'secret' => R2_SECRET_ACCESS_KEY,
                    ],
                ]);
                break;
                
            case 'b2':
                // Backblaze B2 via S3 compatible API
                $this->client = new Aws\S3\S3Client([
                    'version' => 'latest',
                    'region'  => 'us-west-002',
                    'endpoint' => 'https://s3.us-west-002.backblazeb2.com',
                    'credentials' => [
                        'key'    => B2_APPLICATION_KEY_ID,
                        'secret' => B2_APPLICATION_KEY,
                    ],
                ]);
                break;
                
            case 's3':
                $this->client = new Aws\S3\S3Client([
                    'version' => 'latest',
                    'region'  => S3_REGION,
                    'credentials' => [
                        'key'    => S3_KEY,
                        'secret' => S3_SECRET,
                    ],
                ]);
                break;
        }
    }

    /**
     * Upload de arquivo para a nuvem
     * @param string $localPath Caminho local do arquivo
     * @param string $remotePath Caminho remoto (ex: 'noticias/imagem.jpg')
     * @param string $contentType Tipo de conteúdo (ex: 'image/jpeg')
     * @return string|false URL pública do arquivo ou false
     */
    public function upload($localPath, $remotePath, $contentType = null) {
        try {
            if (!$contentType) {
                $contentType = mime_content_type($localPath);
            }
            
            $result = $this->client->putObject([
                'Bucket' => $this->bucket,
                'Key'    => $remotePath,
                'Body'   => fopen($localPath, 'rb'),
                'ContentType' => $contentType,
                'ACL'    => 'public-read',
            ]);
            
            return $this->publicUrl . '/' . $remotePath;
            
        } catch (Exception $e) {
            error_log("Erro no upload para nuvem: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Upload a partir de um arquivo enviado via POST
     * @param array $file $_FILES['campo']
     * @param string $subfolder Subpasta (ex: 'noticias', 'perfis')
     * @return array|null ['url' => '...', 'path' => '...', 'success' => bool]
     */
    public function uploadFromPost($file, $subfolder = 'geral') {
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'Erro no upload do arquivo'];
        }
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'mp4', 'webm', 'pdf'];
        
        if (!in_array($ext, $allowed)) {
            return ['success' => false, 'message' => 'Formato de arquivo não permitido'];
        }
        
        $maxSize = in_array($ext, ['mp4', 'webm']) ? 50 * 1024 * 1024 : 5 * 1024 * 1024;
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'Arquivo muito grande'];
        }
        
        $nomeArquivo = uniqid() . '.' . $ext;
        $remotePath = $subfolder . '/' . date('Y/m') . '/' . $nomeArquivo;
        
        $url = $this->upload($file['tmp_name'], $remotePath, $file['type']);
        
        if ($url) {
            return [
                'success' => true,
                'url' => $url,
                'path' => $remotePath,
                'message' => 'Upload realizado com sucesso'
            ];
        }
        
        return ['success' => false, 'message' => 'Erro ao enviar para a nuvem'];
    }

    /**
     * Deletar arquivo da nuvem
     */
    public function delete($remotePath) {
        try {
            $this->client->deleteObject([
                'Bucket' => $this->bucket,
                'Key'    => $remotePath,
            ]);
            return true;
        } catch (Exception $e) {
            error_log("Erro ao deletar da nuvem: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar se arquivo existe na nuvem
     */
    public function exists($remotePath) {
        try {
            $this->client->headObject([
                'Bucket' => $this->bucket,
                'Key'    => $remotePath,
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}