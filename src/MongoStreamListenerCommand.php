<?php

declare(strict_types=1);

namespace Medeirosinacio\MongoTtlIndexChangeStream;

use Exception;
use InvalidArgumentException;
use Medeirosinacio\MongoTtlIndexChangeStream\Factories\CacheFactory;
use Medeirosinacio\MongoTtlIndexChangeStream\Factories\MongoFactory;
use MongoDB\ChangeStream;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use MongoException;
use Psr\SimpleCache\CacheInterface;

final readonly class MongoStreamListenerCommand
{
    public function __construct(
        private Client $mongo,
        private CacheInterface $cache,
    ) {}

    public static function listen(): void
    {
        (new self(
            mongo: MongoFactory::make(),
            cache: CacheFactory::make()
        )
        )->run();
    }

    public function run(): void
    {
        $collection = $this->getCollection('default', 'records');
        $this->printListeningMessage($collection);

        $resumeToken = $this->getResumeToken();

        try {
            $changeStream = $this->watchCollection($collection, $resumeToken);

            for ($changeStream->rewind(); true; $changeStream->next()) {
                $event = $changeStream->current();
                if (!empty($event) && $event instanceof BSONDocument) {
                    $this->processEvent($event);
                }

                $resumeToken = $changeStream->getResumeToken();
                if (!is_object($resumeToken)) {
                    throw new Exception('Resume token was not found');
                }
                $this->cacheResumeToken($resumeToken);
            }
        } catch (MongoException $e) {
            printf("MongoDB Error: %s\n", $e->getMessage());
        } catch (InvalidArgumentException $e) {
            printf("Cache Error: %s\n", $e->getMessage());
        } catch (Exception $e) {
            printf("Error: %s\n", $e->getMessage());
        }
    }

    private function getCollection(string $database, string $collection): Collection
    {
        return $this->mongo->selectDatabase($database)->selectCollection($collection);
    }

    private function printListeningMessage(Collection $collection): void
    {
        printf("Listening for changes in %s.%s\n", $collection->getDatabaseName(), $collection->getCollectionName());
    }

    private function getResumeToken(): ?object
    {
        $resumeToken = $this->cache->get('mongo_resume_token');
        if (is_object($resumeToken) && !empty($resumeToken->_data)) {
            printf("Resume token: %s\n", $resumeToken->_data ?? '(null)');

            return $resumeToken;
        }

        return null;
    }

    private function watchCollection(Collection $collection, ?object $resumeToken = null): ChangeStream
    {
        return $collection->watch(
            [],
            [
                'fullDocument' => 'updateLookup',
                'fullDocumentBeforeChange' => 'whenAvailable',
                ...$resumeToken ? ['resumeAfter' => $resumeToken] : [],
            ]
        );
    }

    private function processEvent(BSONDocument $event): void
    {
        $ns = sprintf('%s.%s', $event['ns']['db'], $event['ns']['coll']);         // @phpstan-ignore-line
        $id = json_encode($event['documentKey']['_id'], JSON_THROW_ON_ERROR);        // @phpstan-ignore-line
        switch ($event['operationType']) {
            case 'delete':
                printf("Deleted document in %s with _id: %s\n\n", $ns, $id);
                echo json_encode($event, JSON_THROW_ON_ERROR), "\n\n";
                break;
            case 'insert':
                printf("Inserted new document in %s\n", $ns);
                echo json_encode($event, JSON_THROW_ON_ERROR), "\n\n";
                break;
            case 'replace':
                printf("Replaced new document in %s with _id: %s\n", $ns, $id);
                echo json_encode($event, JSON_THROW_ON_ERROR), "\n\n";
                break;
            case 'update':
                printf("Updated document in %s with _id: %s\n", $ns, $id);
                echo json_encode($event, JSON_THROW_ON_ERROR), "\n\n";
                break;
        }
    }

    private function cacheResumeToken(object $resumeToken): void
    {
        $this->cache->set('mongo_resume_token', $resumeToken);
    }
}
