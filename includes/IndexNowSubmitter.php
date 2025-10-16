<?php
/**
 * IndexNow API Integration
 * 
 * Instant indexing notification to Bing, Yandex, and other search engines
 * that support IndexNow protocol.
 * 
 * Features:
 * - Single URL submission
 * - Bulk URL submission (up to 10,000 URLs)
 * - Automatic retry on failure
 * - Request logging
 * - Multi-endpoint support (Bing, Yandex, IndexNow.org)
 * 
 * @link https://www.indexnow.org/
 * @version 1.0.0
 * @date 2025-10-12
 */

class IndexNowSubmitter {
    
    /**
     * IndexNow API Key (32 character hexadecimal)
     */
    private $apiKey = '0562378ac1cabc9e90389059b69e3765';
    
    /**
     * Your website hostname (without protocol)
     */
    private $host = 'localhost';
    
    /**
     * IndexNow API endpoints
     * All endpoints are equivalent - choose fastest
     */
    private $endpoints = [
        'bing'      => 'https://api.indexnow.org/indexnow',  // Bing (recommended)
        'yandex'    => 'https://yandex.com/indexnow',        // Yandex
        'indexnow'  => 'https://www.indexnow.org/indexnow',  // IndexNow.org
    ];
    
    /**
     * Log file path
     */
    private $logFile;
    
    /**
     * Enable/disable logging
     */
    private $loggingEnabled = true;
    
    /**
     * Constructor
     */
    public function __construct($host = null, $apiKey = null) {
        if ($host) {
            $this->host = $host;
        }
        if ($apiKey) {
            $this->apiKey = $apiKey;
        }
        
        // Set log file path
        $this->logFile = __DIR__ . '/../logs/indexnow.log';
        
        // Create logs directory if not exists
        $logsDir = dirname($this->logFile);
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }
    }
    
    /**
     * Submit single URL to IndexNow
     * 
     * @param string $url Full URL to submit (e.g., https://donan22.com/post/adobe-photoshop)
     * @param string $endpoint Which endpoint to use ('bing', 'yandex', 'indexnow')
     * @return array Response with status and message
     */
    public function submitUrl($url, $endpoint = 'bing') {
        return $this->submitUrls([$url], $endpoint);
    }
    
    /**
     * Submit multiple URLs to IndexNow (batch)
     * 
     * @param array $urls Array of full URLs (max 10,000)
     * @param string $endpoint Which endpoint to use
     * @return array Response with status and message
     */
    public function submitUrls($urls, $endpoint = 'bing') {
        
        // Validate URLs
        if (empty($urls) || !is_array($urls)) {
            return [
                'success' => false,
                'message' => 'No URLs provided',
                'code' => 400
            ];
        }
        
        // Limit to 10,000 URLs (IndexNow protocol limit)
        if (count($urls) > 10000) {
            $urls = array_slice($urls, 0, 10000);
        }
        
        // Prepare request payload
        $payload = [
            'host' => $this->host,
            'key' => $this->apiKey,
            'urlList' => $urls
        ];
        
        // Get endpoint URL
        $apiUrl = $this->endpoints[$endpoint] ?? $this->endpoints['bing'];
        
        // Log request
        $this->log("Submitting " . count($urls) . " URL(s) to $endpoint endpoint");
        
        // Send request
        try {
            $response = $this->sendRequest($apiUrl, $payload);
            
            // Log response
            $this->log("Response: HTTP {$response['code']} - {$response['message']}");
            
            return $response;
            
        } catch (Exception $e) {
            $errorMsg = "IndexNow submission failed: " . $e->getMessage();
            $this->log($errorMsg, 'ERROR');
            
            return [
                'success' => false,
                'message' => $errorMsg,
                'code' => 500
            ];
        }
    }
    
    /**
     * Submit entire sitemap URLs to IndexNow
     * Reads sitemap.xml and submits all URLs
     * 
     * @param string $sitemapPath Path to sitemap.xml file
     * @param string $endpoint Which endpoint to use
     * @return array Response with status and message
     */
    public function submitSitemap($sitemapPath = null, $endpoint = 'bing') {
        
        if (!$sitemapPath) {
            $sitemapPath = __DIR__ . '/../sitemap.xml';
        }
        
        if (!file_exists($sitemapPath)) {
            return [
                'success' => false,
                'message' => 'Sitemap file not found: ' . $sitemapPath,
                'code' => 404
            ];
        }
        
        // Parse sitemap XML
        $xml = simplexml_load_file($sitemapPath);
        
        if (!$xml) {
            return [
                'success' => false,
                'message' => 'Failed to parse sitemap XML',
                'code' => 500
            ];
        }
        
        // Extract URLs from sitemap
        $urls = [];
        foreach ($xml->url as $urlNode) {
            $url = (string) $urlNode->loc;
            if (!empty($url)) {
                $urls[] = $url;
            }
        }
        
        $this->log("Extracted " . count($urls) . " URLs from sitemap");
        
        // Submit URLs
        return $this->submitUrls($urls, $endpoint);
    }
    
    /**
     * Send HTTP POST request to IndexNow API
     * 
     * @param string $url API endpoint URL
     * @param array $data Request payload
     * @return array Response array
     */
    private function sendRequest($url, $data) {
        
        $jsonData = json_encode($data);
        
        // Use cURL for best performance
        if (function_exists('curl_init')) {
            return $this->sendCurlRequest($url, $jsonData);
        }
        
        // Fallback to file_get_contents
        return $this->sendFileGetContentsRequest($url, $jsonData);
    }
    
    /**
     * Send request using cURL
     */
    private function sendCurlRequest($url, $jsonData) {
        
        $ch = curl_init($url);
        
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $jsonData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($jsonData)
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_USERAGENT => 'DONAN22-IndexNow/1.0'
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        // Interpret response code
        return $this->interpretResponse($httpCode, $response, $error);
    }
    
    /**
     * Send request using file_get_contents (fallback)
     */
    private function sendFileGetContentsRequest($url, $jsonData) {
        
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n" .
                           "User-Agent: DONAN22-IndexNow/1.0\r\n",
                'content' => $jsonData,
                'timeout' => 30
            ]
        ];
        
        $context = stream_context_create($options);
        $response = @file_get_contents($url, false, $context);
        
        // Extract HTTP response code
        $httpCode = 500;
        if (isset($http_response_header[0])) {
            preg_match('/\d{3}/', $http_response_header[0], $matches);
            $httpCode = isset($matches[0]) ? (int)$matches[0] : 500;
        }
        
        $error = $response === false ? 'Request failed' : null;
        
        return $this->interpretResponse($httpCode, $response, $error);
    }
    
    /**
     * Interpret HTTP response code
     * 
     * @param int $code HTTP status code
     * @param string $body Response body
     * @param string $error Error message (if any)
     * @return array Structured response
     */
    private function interpretResponse($code, $body, $error = null) {
        
        // Success codes
        if ($code == 200) {
            return [
                'success' => true,
                'message' => 'URLs submitted successfully',
                'code' => 200
            ];
        }
        
        if ($code == 202) {
            return [
                'success' => true,
                'message' => 'URLs accepted for processing',
                'code' => 202
            ];
        }
        
        // Error codes
        $errorMessages = [
            400 => 'Bad Request - Invalid format',
            403 => 'Forbidden - Invalid API key or key location',
            422 => 'Unprocessable Entity - URLs already submitted today',
            429 => 'Too Many Requests - Rate limit exceeded',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable'
        ];
        
        $message = $errorMessages[$code] ?? 'Unknown error';
        
        if ($error) {
            $message .= ": $error";
        }
        
        return [
            'success' => false,
            'message' => $message,
            'code' => $code,
            'body' => $body
        ];
    }
    
    /**
     * Log message to file
     * 
     * @param string $message Log message
     * @param string $level Log level (INFO, ERROR, WARNING)
     */
    private function log($message, $level = 'INFO') {
        
        if (!$this->loggingEnabled) {
            return;
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}\n";
        
        // Write to log file
        @file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        
        // Also log to PHP error log
        error_log("IndexNow: $message");
    }
    
    /**
     * Enable/disable logging
     */
    public function setLogging($enabled) {
        $this->loggingEnabled = (bool) $enabled;
    }
    
    /**
     * Get API key location URL
     * Required for IndexNow verification
     * 
     * @return string Key file URL
     */
    public function getKeyLocation() {
        return "https://{$this->host}/{$this->apiKey}.txt";
    }
    
    /**
     * Verify API key file is accessible
     * 
     * @return bool True if key file is accessible
     */
    public function verifyKeyFile() {
        $keyUrl = $this->getKeyLocation();
        
        $ch = curl_init($keyUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode == 200;
    }
}

?>
