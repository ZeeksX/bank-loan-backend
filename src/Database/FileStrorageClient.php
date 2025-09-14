<?php
// File: src/Database/FileStorageClient.php
namespace App\Database;

class FileStorageClient {
    private $storagePath;
    
    public function __construct($databaseName) {
        $this->storagePath = __DIR__ . '/../../storage/' . $databaseName;
        if (!file_exists($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
    
    public function ping() {
        return is_writable($this->storagePath);
    }
    
    public function insertOne($collection, $document) {
        $collectionPath = $this->storagePath . '/' . $collection;
        if (!file_exists($collectionPath)) {
            mkdir($collectionPath, 0755, true);
        }
        
        // Add timestamps
        $now = time();
        $document['created_at'] = $now;
        $document['updated_at'] = $now;
        
        // Generate ID
        $id = uniqid();
        $document['_id'] = $id;
        
        $filePath = $collectionPath . '/' . $id . '.json';
        file_put_contents($filePath, json_encode($document));
        
        return [
            'insertedId' => $id,
            'insertedCount' => 1
        ];
    }
    
    public function findOne($collection, $filter = []) {
        $results = $this->find($collection, $filter, ['limit' => 1]);
        return count($results) > 0 ? $results[0] : null;
    }
    
    public function find($collection, $filter = [], $options = []) {
        $collectionPath = $this->storagePath . '/' . $collection;
        if (!file_exists($collectionPath)) {
            return [];
        }
        
        $results = [];
        $limit = $options['limit'] ?? 0;
        $skip = $options['skip'] ?? 0;
        $count = 0;
        
        foreach (scandir($collectionPath) as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filePath = $collectionPath . '/' . $file;
            if (is_file($filePath) && pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                $content = file_get_contents($filePath);
                $document = json_decode($content, true);
                
                // Apply filter
                $matches = true;
                foreach ($filter as $key => $value) {
                    if (!isset($document[$key]) || $document[$key] !== $value) {
                        $matches = false;
                        break;
                    }
                }
                
                if ($matches) {
                    $count++;
                    if ($count <= $skip) continue;
                    if ($limit > 0 && count($results) >= $limit) break;
                    
                    $results[] = $document;
                }
            }
        }
        
        return $results;
    }
    
    public function updateOne($collection, $filter, $update, $options = []) {
        $document = $this->findOne($collection, $filter);
        if (!$document) {
            return ['matchedCount' => 0, 'modifiedCount' => 0];
        }
        
        // Apply update
        if (isset($update['$set'])) {
            foreach ($update['$set'] as $key => $value) {
                $document[$key] = $value;
            }
        }
        
        $document['updated_at'] = time();
        
        $filePath = $this->storagePath . '/' . $collection . '/' . $document['_id'] . '.json';
        file_put_contents($filePath, json_encode($document));
        
        return ['matchedCount' => 1, 'modifiedCount' => 1];
    }
    
    public function deleteOne($collection, $filter, $options = []) {
        $document = $this->findOne($collection, $filter);
        if (!$document) {
            return ['deletedCount' => 0];
        }
        
        $filePath = $this->storagePath . '/' . $collection . '/' . $document['_id'] . '.json';
        if (file_exists($filePath)) {
            unlink($filePath);
            return ['deletedCount' => 1];
        }
        
        return ['deletedCount' => 0];
    }
    
    public function deleteMany($collection, $filter, $options = []) {
        $documents = $this->find($collection, $filter);
        $deletedCount = 0;
        
        foreach ($documents as $document) {
            $filePath = $this->storagePath . '/' . $collection . '/' . $document['_id'] . '.json';
            if (file_exists($filePath)) {
                unlink($filePath);
                $deletedCount++;
            }
        }
        
        return ['deletedCount' => $deletedCount];
    }
    
    public function count($collection, $filter = [], $options = []) {
        return count($this->find($collection, $filter));
    }
}
?>