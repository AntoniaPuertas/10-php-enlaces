<?php
// preview.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

class LinkPreview {
    private $url;
    private $timeout = 10;
    
    //sanitiza  y valida la  url 
    public function __construct($url) {
        $this->url = filter_var($url, FILTER_SANITIZE_URL);
        if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
            throw new Exception("URL inválida");
        }
    }
    
    /**
     * Configura una petición HTTP con un User-Agent específico
     * Obtiene el HTML de la página
     * Parsea el HTML usando DOMDocument
     * Extrae los metadatos usando diferentes métodos
     * Maneja cualquier error que pueda ocurrir
     */
    public function getMetadata() {
        try {
            // Verificar que podemos hacer peticiones externas
            if (!ini_get('allow_url_fopen')) {
                throw new Exception("allow_url_fopen debe estar habilitado en PHP");
            }
            //Configura una petición HTTP con un User-Agent específico
            $context = stream_context_create([
                'http' => [
                    'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n",
                    'timeout' => $this->timeout,
                    'follow_location' => true
                ]
            ]);
            
            // Intentar obtener el contenido con manejo de errores Obtiene el HTML de la página
            $html = @file_get_contents($this->url, false, $context);
            
            if ($html === false) {
                $error = error_get_last();
                throw new Exception("Error al obtener el contenido: " . ($error['message'] ?? 'Error desconocido'));
            }
            
            // Verificar que tenemos las extensiones necesarias
            if (!class_exists('DOMDocument')) {
                throw new Exception("La extensión DOM es requerida");
            }
            
            // Crear documento DOM con manejo de errores
            $doc = new DOMDocument();
            libxml_use_internal_errors(true); // Suprime errores de parsing HTML
            $doc->loadHTML($html, LIBXML_NOWARNING | LIBXML_NOERROR);
            libxml_clear_errors();
            
            $xpath = new DOMXPath($doc);
            
            // Obtener metadatos
            $metadata = [
                'title' => $this->getTitle($xpath),
                'description' => $this->getDescription($xpath),
                'image' => $this->getImage($xpath),
                'favicon' => $this->getFavicon($xpath),
                'url' => $this->url,
                'success' => true
            ];
            
            return $metadata;
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'title' => $this->url,
                'description' => 'No se pudo obtener la descripción',
                'image' => null,
                'favicon' => null,
                'url' => $this->url
            ];
        }
    }
    
    private function getTitle($xpath) {
        // Intentar obtener título de Open Graph
        $ogTitle = $xpath->query("//meta[@property='og:title']/@content");
        if ($ogTitle->length > 0) {
            return trim($ogTitle->item(0)->nodeValue);
        }
        
        // Intentar obtener título normal
        $title = $xpath->query("//title");
        if ($title->length > 0) {
            return trim($title->item(0)->nodeValue);
        }
        
        return $this->url;
    }
    
    private function getDescription($xpath) {
        // Intentar obtener descripción de Open Graph
        $ogDesc = $xpath->query("//meta[@property='og:description']/@content");
        if ($ogDesc->length > 0) {
            return trim($ogDesc->item(0)->nodeValue);
        }
        
        // Intentar obtener descripción normal
        $desc = $xpath->query("//meta[@name='description']/@content");
        if ($desc->length > 0) {
            return trim($desc->item(0)->nodeValue);
        }
        
        return '';
    }
    
    private function getImage($xpath) {
        // Intentar obtener imagen de Open Graph
        $ogImage = $xpath->query("//meta[@property='og:image']/@content");
        if ($ogImage->length > 0) {
            return $this->makeAbsoluteUrl($ogImage->item(0)->nodeValue);
        }
        
        return null;
    }
    
    private function getFavicon($xpath) {
        // Buscar favicon en diferentes ubicaciones
        $icons = $xpath->query("//link[contains(@rel, 'icon')]/@href");
        if ($icons->length > 0) {
            return $this->makeAbsoluteUrl($icons->item(0)->nodeValue);
        }
        
        // Intentar con favicon.ico en la raíz
        return parse_url($this->url, PHP_URL_SCHEME) . '://' . 
               parse_url($this->url, PHP_URL_HOST) . '/favicon.ico';
    }
    
    private function makeAbsoluteUrl($url) {
        if (empty($url)) return null;
        
        if (parse_url($url, PHP_URL_SCHEME) === null) {
            $baseUrl = parse_url($this->url);
            $base = $baseUrl['scheme'] . '://' . $baseUrl['host'];
            return $base . '/' . ltrim($url, '/');
        }
        return $url;
    }
}

// Asegurarse de que enviamos headers correctos
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Verificar que tenemos una URL
if (!isset($_GET['url']) || empty($_GET['url'])) {
    echo json_encode([
        'success' => false,
        'error' => 'No se proporcionó URL',
        'title' => null,
        'description' => null,
        'image' => null,
        'favicon' => null,
        'url' => null
    ]);
    exit;
}

try {
    $preview = new LinkPreview($_GET['url']);
    $metadata = $preview->getMetadata();
    echo json_encode($metadata);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'title' => $_GET['url'],
        'description' => null,
        'image' => null,
        'favicon' => null,
        'url' => $_GET['url']
    ]);
}
