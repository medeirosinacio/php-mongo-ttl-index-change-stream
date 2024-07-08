<?php

use Medeirosinacio\MongoTtlIndexChangeStream\MongoStreamListenerCommand;
use MongoDB\ChangeStream;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Model\BSONDocument;
use Psr\SimpleCache\CacheInterface;

beforeEach(function () {
    $this->mongo = Mockery::mock(Client::class);
    $this->mongoDatabase = Mockery::mock(Database::class);
    $this->cache = Mockery::mock(CacheInterface::class);
    $this->listener = new MongoStreamListenerCommand($this->mongo, $this->cache);
});

afterEach(function () {
    Mockery::close();
});

test('it listens and processes changes', function () {
    $collection = Mockery::mock(Collection::class);
    $changeStream = Mockery::mock(ChangeStream::class);
    $resumeToken = (object) ['_data' => 'resume_token_data'];
    $event = new BSONDocument([
        'ns' => ['db' => 'default', 'coll' => 'records'],
        'documentKey' => ['_id' => 'some_id'],
        'operationType' => 'insert',
        'fullDocument' => ['_id' => 'some_id', 'field' => 'value'],
    ]);

    $this->mongo->expects('selectDatabase')
        ->with('default')
        ->andReturn($this->mongoDatabase);

    $this->mongoDatabase->expects('selectCollection')
        ->with('records')
        ->andReturn($collection);

    $collection->expects('getDatabaseName')
        ->andReturn('default');

    $collection->expects('getCollectionName')
        ->andReturn('records');

    $this->cache->expects('get')
        ->with('mongo_resume_token')
        ->andReturn($resumeToken);

    $collection->expects('watch')
        ->andReturn($changeStream);

    $changeStream->expects('rewind');
    $changeStream->expects('next')->andReturnUsing(function () use ($changeStream) {
        static $count = 0;
        if ($count < 2) {
            $count++;
        } else {
            $changeStream->valid = false;
        }
    });

    $changeStream->expects('current')
        ->andReturn($event, null);

    $changeStream->expects('getResumeToken')
        ->andReturn($resumeToken);

    $this->cache->expects('set')
        ->with('mongo_resume_token', $resumeToken)
        ->andReturn(true);

    $this->listener->run();
});
