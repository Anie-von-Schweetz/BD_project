<?php


class Database {
    private $connection;
    
    public function __construct($host, $username, $password, $database) {
        $this->connection = new mysqli($host, $username, $password, $database);
        
        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        
        $this->connection->set_charset("utf8mb4");
    }
    
    //Сохраняем или обновляем событие в БД
    public function saveEvent($eventData) {
        // ДЕБАГ: Сохраняем сырые данные для анализа
        if (empty($eventData)) {
            error_log("Empty event data received");
            return false;
        }
        
        // Извлекаем данные из структуры API
        $general = $eventData['general'] ?? [];
        
        if (empty($general)) {
            error_log("No 'general' section in event data");
            // Сохраняем для отладки
            file_put_contents('debug_no_general.json', 
                json_encode($eventData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return false;
        }
        
        // ВАЖНО: Проверяем наличие обязательных полей
        $externalId = $general['id'] ?? 0;
        if (empty($externalId) || $externalId == 0) {
            error_log("Invalid or empty external_id: " . $externalId);
            return false;
        }
        
        // Извлекаем вложенные данные с проверками
        $category = $general['category'] ?? [];
        $organization = $general['organization'] ?? [];
        $places = $general['places'] ?? [];
        $firstPlace = $places[0] ?? [];
        $address = $firstPlace['address'] ?? [];
        $locale = $firstPlace['locale'] ?? [];
        
        // Подготовка данных
        $title = $this->escape($general['name'] ?? '');
        
        // Проверяем даты
        $startDatetime = null;
        $endDatetime = null;
        
        if (!empty($general['start'])) {
            $timestamp = strtotime($general['start']);
            if ($timestamp !== false) {
                $startDatetime = date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        if (!empty($general['end'])) {
            $timestamp = strtotime($general['end']);
            if ($timestamp !== false) {
                $endDatetime = date('Y-m-d H:i:s', $timestamp);
            }
        }
        
        $city = $locale['name'] ?? '';
        $ageRestriction = $general['ageRestriction'] ?? 0;
        
        // Проверяем, существует ли уже событие
        $existingId = $this->getEventIdByExternalId($externalId);
        
        // ДЕБАГ: логируем процесс
        error_log("Processing event: ID={$externalId}, Title={$title}, City={$city}, ExistingID=" . ($existingId ?? 'none'));
        
        if ($existingId) {
            // Обновляем существующее
            $updateData = [
                'title' => $title,
                'age_restriction' => $ageRestriction,
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'city' => $city,
                'source_data' => json_encode($eventData, JSON_UNESCAPED_UNICODE),
                'category_name' => $this->escape($category['name'] ?? ''),
                'place_name' => $this->escape($firstPlace['name'] ?? ''),
                'place_address' => $this->escape($address['fullAddress'] ?? ''),
                'organizer_name' => $this->escape($organization['name'] ?? ''),
                'is_free' => $general['isFree'] ?? false,
                'price_min' => $general['price'] ?? null,
                'price_max' => $general['maxPrice'] ?? null,
                'last_updated' => date('Y-m-d H:i:s')
            ];
            
            $success = $this->updateEvent($existingId, $updateData);
            if ($success) {
                error_log("Updated event ID={$externalId}");
            } else {
                error_log("Failed to update event ID={$externalId}");
            }
            return $success;
        } else {
            // Вставляем новое
            $insertData = [
                'external_id' => $externalId,
                'title' => $title,
                'short_description' => $this->escape($general['shortDescription'] ?? ''),
                'full_description' => $this->escape($general['description'] ?? $general['fullDescription'] ?? ''),
                'age_restriction' => $ageRestriction,
                'start_datetime' => $startDatetime,
                'end_datetime' => $endDatetime,
                'is_free' => $general['isFree'] ?? false,
                'price_min' => $general['price'] ?? null,
                'price_max' => $general['maxPrice'] ?? null,
                'category_name' => $this->escape($category['name'] ?? ''),
                'place_name' => $this->escape($firstPlace['name'] ?? ''),
                'place_address' => $this->escape($address['fullAddress'] ?? ''),
                'place_city' => $city,
                'organizer_name' => $this->escape($organization['name'] ?? ''),
                'source_data' => json_encode($eventData, JSON_UNESCAPED_UNICODE)
            ];
            
            $success = $this->insertEvent($insertData);
            if ($success) {
                error_log("Inserted new event ID={$externalId}, Title={$title}");
            } else {
                error_log("Failed to insert event ID={$externalId}");
                // Сохраняем проблемные данные для отладки
                file_put_contents('debug_insert_failed.json', 
                    json_encode(['event' => $eventData, 'insertData' => $insertData], 
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
            return $success;
        }
    }

    private function getEventIdByExternalId($externalId) {
        // ВАЖНО: Проверяем, что externalId не пустой
        if (empty($externalId) || $externalId == 0) {
            return null;
        }
        
        $stmt = $this->connection->prepare(
            "SELECT id FROM events WHERE external_id = ?"
        );
        
        if (!$stmt) {
            error_log("Prepare failed: " . $this->connection->error);
            return null;
        }
        
        $stmt->bind_param("i", $externalId);
        
        if (!$stmt->execute()) {
            error_log("Execute failed: " . $stmt->error);
            $stmt->close();
            return null;
        }
        
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            return null;
        }
        
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['id'] ?? null;
    }

    private function insertEvent($data) {
        $sql = "INSERT INTO events (
            external_id, title, short_description, full_description, 
            age_restriction, start_datetime, end_datetime, is_free,
            price_min, price_max, category_name, place_name, place_address,
            place_city, organizer_name, source_data
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed for INSERT: " . $this->connection->error);
            return false;
        }
        
        // Приводим типы для bind_param
        $isFree = !empty($data['is_free']) ? 1 : 0;
        
        // Обрабатываем NULL значения
        $priceMin = $data['price_min'] !== null ? $data['price_min'] : null;
        $priceMax = $data['price_max'] !== null ? $data['price_max'] : null;
        
        $stmt->bind_param(
            "isssiissiissssss",
            $data['external_id'],
            $data['title'],
            $data['short_description'],
            $data['full_description'],
            $data['age_restriction'],
            $data['start_datetime'],
            $data['end_datetime'],
            $isFree,
            $priceMin,
            $priceMax,
            $data['category_name'],
            $data['place_name'],
            $data['place_address'],
            $data['place_city'],
            $data['organizer_name'],
            $data['source_data']
        );
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Insert failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    }

    private function updateEvent($id, $data) {
        $sql = "UPDATE events SET 
            title = ?, 
            age_restriction = ?,
            start_datetime = ?,
            end_datetime = ?,
            place_city = ?,
            source_data = ?,
            category_name = ?,
            place_name = ?,
            place_address = ?,
            organizer_name = ?,
            is_free = ?,
            price_min = ?,
            price_max = ?,
            last_updated = ?
            WHERE id = ?";
        
        $stmt = $this->connection->prepare($sql);
        
        if (!$stmt) {
            error_log("Prepare failed for UPDATE: " . $this->connection->error);
            return false;
        }
        
        $isFree = !empty($data['is_free']) ? 1 : 0;
        $priceMin = $data['price_min'] !== null ? $data['price_min'] : null;
        $priceMax = $data['price_max'] !== null ? $data['price_max'] : null;
        
        $stmt->bind_param(
            "sississsssiissi",
            $data['title'],
            $data['age_restriction'],
            $data['start_datetime'],
            $data['end_datetime'],
            $data['city'],
            $data['source_data'],
            $data['category_name'],
            $data['place_name'],
            $data['place_address'],
            $data['organizer_name'],
            $isFree,
            $priceMin,
            $priceMax,
            $data['last_updated'],
            $id
        );
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Update failed: " . $stmt->error);
        }
        
        $stmt->close();
        return $success;
    }

    private function escape($value) {
        if ($value === null || $value === '') {
            return '';
        }
        return $this->connection->real_escape_string($value);
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function close() {
        $this->connection->close();
    }
}
?>