<?php

use Medeirosinacio\MongoTtlIndexChangeStream\MongoStreamListenerCommand;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use MongoDB\ChangeStream;
use MongoDB\Client;
use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Model\BSONDocument;
use Psr\SimpleCache\CacheInterface;

use function PHPUnit\Framework\assertStringContainsString;

uses(MockeryPHPUnitIntegration::class);

beforeEach(function () {
    $this->mongo = Mockery::mock(Client::class);
    $this->mongoDatabase = Mockery::mock(Database::class);
    $this->cache = Mockery::mock(CacheInterface::class);
    $this->listener = new MongoStreamListenerCommand($this->mongo, $this->cache);
});

test('it processes different types of events', function () {
    $collection = Mockery::mock(Collection::class);
    $changeStream = Mockery::mock(ChangeStream::class);
    $resumeToken = (object) ['_data' => 'resume_token_data'];

    $insertEvent = new BSONDocument([
        'operationType' => 'insert',
        'fullDocument' => ['_id' => '123', 'name' => 'Test'],
        'ns' => ['db' => 'default', 'coll' => 'records'],
        'documentKey' => ['_id' => '123'],
    ]);

    $this->mongo->expects('selectDatabase')->andReturn($this->mongoDatabase);
    $this->mongoDatabase->expects('selectCollection')->andReturn($collection);
    $this->cache->expects('get')->andReturn($resumeToken);
    $collection->expects('watch')->andReturn($changeStream);
    $collection->expects('getDatabaseName')->andReturn('default');
    $collection->expects('getCollectionName')->andReturn('records');

    $changeStream->expects('rewind');
    $changeStream->expects('next')->andThrow(new Exception('Test end of stream'));
    $changeStream->expects('current')->andReturn($insertEvent);
    $changeStream->expects('getResumeToken')->andReturn($resumeToken);

    $this->cache->expects('set')->with('mongo_resume_token', $resumeToken)->andReturn(true);

    ob_start();
    $this->listener->run();
    $output = ob_get_clean();

    assertStringContainsString('Listening for changes in default.records', $output);
    assertStringContainsString('Resume token: resume_token_data', $output);
    assertStringContainsString('Inserted new document in default.records', $output);
    assertStringContainsString('operationType":"insert","fullDocument":{"_id":"123","name":"Test"}', $output);

});
