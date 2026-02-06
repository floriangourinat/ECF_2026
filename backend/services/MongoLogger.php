<?php
/**
 * Service de journalisation MongoDB
 */

require_once __DIR__ . '/../vendor/autoload.php';

class MongoLogger {
    private $collection;
    private $enabled = true;

    public function __construct() {
        try {
            // Utiliser 'mongo' (nom du service Docker) au lieu de localhost
            $client = new MongoDB\Client("mongodb://root:root@mongo:27017");
            $database = $client->selectDatabase('innovevents_logs');
            $this->collection = $database->selectCollection('activity_logs');
        } catch (Exception $e) {
            $this->enabled = false;
            error_log("MongoDB connection failed: " . $e->getMessage());
        }
    }

    /**
     * Enregistrer une action
     */
    public function log(string $action, string $entity, ?int $entityId = null, ?int $userId = null, ?array $details = null): bool {
        if (!$this->enabled) {
            return false;
        }

        try {
            $document = [
                'action' => $action,
                'entity' => $entity,
                'entity_id' => $entityId,
                'user_id' => $userId,
                'details' => $details,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'created_at' => new MongoDB\BSON\UTCDateTime()
            ];

            $this->collection->insertOne($document);
            return true;
        } catch (Exception $e) {
            error_log("MongoDB log failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer les logs
     */
    public function getLogs(array $filters = [], int $limit = 100, int $skip = 0): array {
        if (!$this->enabled) {
            return [];
        }

        try {
            $query = [];

            if (!empty($filters['action'])) {
                $query['action'] = $filters['action'];
            }
            if (!empty($filters['entity'])) {
                $query['entity'] = $filters['entity'];
            }
            if (!empty($filters['user_id'])) {
                $query['user_id'] = (int)$filters['user_id'];
            }
            if (!empty($filters['date_from'])) {
                $query['created_at']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($filters['date_from']) * 1000);
            }
            if (!empty($filters['date_to'])) {
                $query['created_at']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($filters['date_to']) * 1000);
            }

            $options = [
                'sort' => ['created_at' => -1],
                'limit' => $limit,
                'skip' => $skip
            ];

            $cursor = $this->collection->find($query, $options);
            $logs = [];

            foreach ($cursor as $document) {
                $logs[] = [
                    'id' => (string)$document['_id'],
                    'action' => $document['action'],
                    'entity' => $document['entity'],
                    'entity_id' => $document['entity_id'],
                    'user_id' => $document['user_id'],
                    'details' => $document['details'] ? (array)$document['details'] : null,
                    'ip_address' => $document['ip_address'],
                    'created_at' => $document['created_at']->toDateTime()->format('Y-m-d H:i:s')
                ];
            }

            return $logs;
        } catch (Exception $e) {
            error_log("MongoDB getLogs failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Compter les logs
     */
    public function countLogs(array $filters = []): int {
        if (!$this->enabled) {
            return 0;
        }

        try {
            $query = [];

            if (!empty($filters['action'])) {
                $query['action'] = $filters['action'];
            }
            if (!empty($filters['entity'])) {
                $query['entity'] = $filters['entity'];
            }

            return $this->collection->countDocuments($query);
        } catch (Exception $e) {
            return 0;
        }
    }
}