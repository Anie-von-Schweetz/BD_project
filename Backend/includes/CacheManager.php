<?php
// includes/CacheManager.php
class CacheManager {
    private $cacheDir;
    
    public function __construct($cacheDir = null) {
        $this->cacheDir = $cacheDir ?? __DIR__ . '/../cache/';
        
        // Создаём папку кэша если нет
        if (!file_exists($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }
    
    /**
     * Получить данные из кэша
     */
    public function get($key, $ttl = 3600) {
        $filename = $this->getFilename($key);
        
        if (!file_exists($filename)) {
            return null;
        }
        
        // Проверяем срок годности
        if (time() - filemtime($filename) > $ttl) {
            unlink($filename); // Удаляем просроченный кэш
            return null;
        }
        
        $content = file_get_contents($filename);
        return json_decode($content, true);
    }
    
    /**
     * Сохранить данные в кэш
     */
    public function set($key, $data) {
        $filename = $this->getFilename($key);
        $content = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        return file_put_contents($filename, $content) !== false;
    }
    
    /**
     * Очистить кэш
     */
    public function clear($key = null) {
        if ($key) {
            // Удалить конкретный файл
            $filename = $this->getFilename($key);
            if (file_exists($filename)) {
                unlink($filename);
            }
        } else {
            // Удалить все файлы кэша
            $files = glob($this->cacheDir . '*.json');
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }
    
    /**
     * Сгенерировать ключ кэша на основе фильтров
     */
    public function generateKey($filters) {
        // Убираем limit и offset из ключа (они не влияют на данные)
        $keyFilters = $filters;
        unset($keyFilters['limit'], $keyFilters['offset']);
        
        return 'events_' . md5(json_encode($keyFilters));
    }
    
    private function getFilename($key) {
        return $this->cacheDir . $key . '.json';
    }
}
?>