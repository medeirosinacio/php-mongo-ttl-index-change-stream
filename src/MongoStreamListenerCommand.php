<?php

declare(strict_types=1);

namespace Medeirosinacio\MongoTtlIndexChangeStream;

final class MongoStreamListenerCommand
{
    public static function listen()
    {
        $cache = make(CacheInterface::class);

        $collection = (new Client(
            'mongodb://mongo:27017',
            [
                'username' => 'root',
                'password' => 'root',
                'ssl' => false,
                'replicaSet' => 'rs0',
            ]))
            ->selectDatabase('sentinela')
            ->selectCollection('transaction_timeouts');

        printf("Listening for changes in %s.%s\n", $collection->getDatabaseName(), $collection->getCollectionName());

        $resumeToken = $cache->get('mongo_resume_token');
        printf("Resume token: %s\n", $resumeToken?->_data ?? '(null)');

        $changeStream = $collection->watch(
            [],
            [
                'fullDocument' => 'updateLookup',
                'fullDocumentBeforeChange' => 'required',
                ... $resumeToken ? ['resumeAfter' => $resumeToken] : []
            ]
        );

        for ($changeStream->rewind(); true; $changeStream->next()) {
            if (! $changeStream->valid()) {
                continue;
            }
            $event = $changeStream->current();
            $ns = sprintf('%s.%s', $event['ns']['db'], $event['ns']['coll']);
            $id = json_encode($event['documentKey']['_id']);
            switch ($event['operationType']) {
                case 'delete':
                    printf("Deleted document in %s with _id: %s\n\n", $ns, $id);
                    echo json_encode($event), "\n\n";
                    break;
                case 'insert':
                    printf("Inserted new document in %s\n", $ns);
                    echo json_encode($event['fullDocument']), "\n\n";
                    break;
                case 'replace':
                    printf("Replaced new document in %s with _id: %s\n", $ns, $id);
                    echo json_encode($event['fullDocument']), "\n\n";
                    break;
                case 'update':
                    printf("Updated document in %s with _id: %s\n", $ns, $id);
                    echo json_encode($event['updateDescription']), "\n\n";
                    break;
            }

            // token
            $resumeToken = $changeStream->getResumeToken();
            if ($resumeToken === null) {
                throw new \Exception('Resume token was not found');
            }
            $cache->set('mongo_resume_token', $resumeToken);
        }
    }
}
