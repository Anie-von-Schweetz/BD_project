<?php
class CacheManager {
    private $cacheDir;
    private $cacheTime;
    
    public function __construct($dir = null, $time = null) {
        $this->cacheDir = $dir ?: CACHE_DIR;
        $this->cacheTime = $time ?: CACHE_TIME;
        
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0777, true);
        }
    }
    
    public function get($key) {
        $file = $this->cacheDir . md5($key) . '.json';
        
        if (file_exists($file) && (time() - filemtime($file)) < $this->cacheTime) {
            $data = file_get_contents($file);
            return json_decode($data, true);
        }
        
        return null;
    }
    
    public function set($key, $data) {
        $file = $this->cacheDir . md5($key) . '.json';
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    public function clearOld() {
        $files = glob($this->cacheDir . '*.json');
        foreach ($files as $file) {
            if ((time() - filemtime($file)) > $this->cacheTime) {
                unlink($file);
            }
        }
    }
}
?>