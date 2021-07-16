<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Splitter;

use JsonStreamingParser\Exception\ParsingException;
use Oro\Bundle\ApiBundle\Batch\Splitter\JsonPartialFileSplitter;

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JsonApiPartialFileSplitterTest extends FileSplitterTestCase
{
    public function testSplitWithoutTimeout()
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
]}
JSON;
        $result = [
            [
                'data' => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']],
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']]
                ]
            ],
            [
                'data' => [
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonPartialFileSplitter();
        $splitter->setChunkSize(2);
        // guard
        self::assertSame(-1, $splitter->getTimeout());

        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertTrue($splitter->isCompleted(), 'Completed');
        self::assertEquals(
            [
                'offset'                      => 242,
                'lineNumber'                  => 5,
                'charNumber'                  => 3,
                'state'                       => 14 /* STATE_END_DOCUMENT */,
                'stack'                       => [],
                'sectionName'                 => 'data',
                'targetFileIndex'             => 2,
                'targetFileFirstRecordOffset' => 4,
                'listener'                    => [
                    'level'       => 0,
                    'objectLevel' => 0,
                    'objectKeys'  => [],
                    'stack'       => []
                ]
            ],
            $splitter->getState(),
            'State'
        );

        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 2, 'data', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);
    }

    public function testSplitWithoutTimeoutAndWithHeaderSection()
    {
        $inputJson = <<<JSON
{"jsonapi": {"version": "1.0"},
"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
]}
JSON;
        $result = [
            [
                'jsonapi' => ['version' => '1.0'],
                'data'    => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']],
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']]
                ]
            ],
            [
                'jsonapi' => ['version' => '1.0'],
                'data'    => [
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonPartialFileSplitter();
        $splitter->setChunkSize(2);
        $splitter->setHeaderSectionName('jsonapi');
        // guard
        self::assertSame(-1, $splitter->getTimeout());

        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertTrue($splitter->isCompleted(), 'Completed');
        self::assertEquals(
            [
                'offset'                      => 273,
                'lineNumber'                  => 6,
                'charNumber'                  => 3,
                'state'                       => 14 /* STATE_END_DOCUMENT */,
                'stack'                       => [],
                'sectionName'                 => 'data',
                'headerSection'               => ['jsonapi' => ['version' => '1.0']],
                'targetFileIndex'             => 2,
                'targetFileFirstRecordOffset' => 4,
                'listener'                    => [
                    'level'       => 0,
                    'objectLevel' => 0,
                    'objectKeys'  => [],
                    'stack'       => []
                ]
            ],
            $splitter->getState(),
            'State'
        );

        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 2, 'data', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);
    }

    public function testSplitWithTimeoutButWhenItIsNotExceeded()
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
]}
JSON;
        $result = [
            [
                'data' => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']],
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']]
                ]
            ],
            [
                'data' => [
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonPartialFileSplitter();
        $splitter->setChunkSize(2);
        $splitter->setTimeout(1000);

        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertTrue($splitter->isCompleted(), 'Completed');
        self::assertEquals(
            [
                'offset'                      => 242,
                'lineNumber'                  => 5,
                'charNumber'                  => 3,
                'state'                       => 14 /* STATE_END_DOCUMENT */,
                'stack'                       => [],
                'sectionName'                 => 'data',
                'targetFileIndex'             => 2,
                'targetFileFirstRecordOffset' => 4,
                'listener'                    => [
                    'level'       => 0,
                    'objectLevel' => 0,
                    'objectKeys'  => [],
                    'stack'       => []
                ]
            ],
            $splitter->getState(),
            'State'
        );

        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 2, 'data', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);
    }

    public function testSplitWhenTimeoutExceededAfterEachObjectAndDifferentInstancesOfSplitterAreUsed()
    {
        $this->runTestSplitWhenTimeoutExceededAfterEachObject(function (JsonPartialFileSplitterStub $splitter) {
            $state = $splitter->getState();
            $newSplitter = new JsonPartialFileSplitterStub(50);
            $newSplitter->setChunkSize(1);
            $newSplitter->setTimeout(80);
            $newSplitter->setState($state);

            return $newSplitter;
        });
    }

    public function testSplitWhenTimeoutExceededAfterEachObjectAndTheSameInstanceOfSplitterIsUsed()
    {
        $splitter = new JsonPartialFileSplitterStub(50);
        $splitter->setChunkSize(1);
        $splitter->setTimeout(80);

        $this->runTestSplitWhenTimeoutExceededAfterEachObject(function (JsonPartialFileSplitterStub $splitter) {
            return $splitter;
        });
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function runTestSplitWhenTimeoutExceededAfterEachObject(callable $getNextSplitter)
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
]}
JSON;
        $result = [
            [
                'data' => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']]
                ]
            ],
            [
                'data' => [
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']]
                ]
            ],
            [
                'data' => [
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ]
        ];

        // first iteration
        $splitter = new JsonPartialFileSplitterStub(50);
        $splitter->setChunkSize(1);
        $splitter->setTimeout(80);
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertFalse($splitter->isCompleted(), 'Completed (first iteration)');
        self::assertEquals(
            [
                'offset'                      => 85,
                'lineNumber'                  => 2,
                'charNumber'                  => 75,
                'state'                       => 12 /* STATE_AFTER_VALUE */,
                'stack'                       => [0, 1],
                'sectionName'                 => 'data',
                'targetFileIndex'             => 1,
                'targetFileFirstRecordOffset' => 1,
                'listener'                    => [
                    'level'       => 2,
                    'objectLevel' => 1,
                    'objectKeys'  => [1 => null, 2 => 'data'],
                    'stack'       => [[], []]
                ]
            ],
            $splitter->getState(),
            'State (first iteration)'
        );

        self::assertCount(1, $files);
        self::assertCount(1, $resultFileNames);
        self::assertCount(1, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);

        // second iteration
        /** @var JsonPartialFileSplitterStub $splitter */
        $splitter = $getNextSplitter($splitter);
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertFalse($splitter->isCompleted(), 'Completed (second iteration)');
        self::assertEquals(
            [
                'offset'                      => 162,
                'lineNumber'                  => 3,
                'charNumber'                  => 75,
                'state'                       => 12 /* STATE_AFTER_VALUE */,
                'stack'                       => [0, 1],
                'sectionName'                 => 'data',
                'targetFileIndex'             => 2,
                'targetFileFirstRecordOffset' => 2,
                'listener'                    => [
                    'level'       => 2,
                    'objectLevel' => 1,
                    'objectKeys'  => [1 => null, 2 => 'data'],
                    'stack'       => [[], []]
                ]
            ],
            $splitter->getState(),
            'State (second iteration)'
        );

        self::assertCount(1, $files);
        self::assertCount(1, $resultFileNames);
        self::assertCount(1, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 1, 1, 'data', $files[0]);
        $this->assertChunkContent($result[1], $resultFileContents[0]);

        // third iteration
        /** @var JsonPartialFileSplitterStub $splitter */
        $splitter = $getNextSplitter($splitter);
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertFalse($splitter->isCompleted(), 'Completed (third iteration)');
        self::assertEquals(
            [
                'offset'                      => 239,
                'lineNumber'                  => 4,
                'charNumber'                  => 75,
                'state'                       => 12 /* STATE_AFTER_VALUE */,
                'stack'                       => [0, 1],
                'sectionName'                 => 'data',
                'targetFileIndex'             => 3,
                'targetFileFirstRecordOffset' => 3,
                'listener'                    => [
                    'level'       => 2,
                    'objectLevel' => 1,
                    'objectKeys'  => [1 => null, 2 => 'data'],
                    'stack'       => [[], []]
                ]
            ],
            $splitter->getState(),
            'State (third iteration)'
        );

        self::assertCount(1, $files);
        self::assertCount(1, $resultFileNames);
        self::assertCount(1, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 2, 2, 'data', $files[0]);
        $this->assertChunkContent($result[2], $resultFileContents[0]);

        // last iteration
        /** @var JsonPartialFileSplitterStub $splitter */
        $splitter = $getNextSplitter($splitter);
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertTrue($splitter->isCompleted(), 'Completed (last iteration)');
        self::assertEquals(
            [
                'offset'                      => 242,
                'lineNumber'                  => 5,
                'charNumber'                  => 3,
                'state'                       => 14 /* STATE_END_DOCUMENT */,
                'stack'                       => [],
                'sectionName'                 => 'data',
                'targetFileIndex'             => 3,
                'targetFileFirstRecordOffset' => 3,
                'listener'                    => [
                    'level'       => 0,
                    'objectLevel' => 0,
                    'objectKeys'  => [],
                    'stack'       => []
                ]
            ],
            $splitter->getState(),
            'State (last iteration)'
        );

        self::assertCount(0, $files);
        self::assertCount(0, $resultFileNames);
        self::assertCount(0, $resultFileContents);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSplitWhenTimeoutExceededAfterFewObjectsAndDifferentInstancesOfSplittersAreUsed()
    {
        $this->runTestSplitWhenTimeoutExceededAfterFewObjects(function (JsonPartialFileSplitterStub $splitter) {
            $state = $splitter->getState();
            $newSplitter = new JsonPartialFileSplitterStub(50);
            $newSplitter->setChunkSize(1);
            $newSplitter->setTimeout(140);
            $newSplitter->setState($state);

            return $newSplitter;
        });
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSplitWhenTimeoutExceededAfterFewObjectsAndTheSameInstanceOfSplitterIsUsed()
    {
        $this->runTestSplitWhenTimeoutExceededAfterFewObjects(function (JsonPartialFileSplitterStub $splitter) {
            return $splitter;
        });
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function runTestSplitWhenTimeoutExceededAfterFewObjects(callable $getNextSplitter)
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
]}
JSON;
        $result = [
            [
                'data' => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']]
                ]
            ],
            [
                'data' => [
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']]
                ]
            ],
            [
                'data' => [
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ]
        ];

        // first iteration
        $splitter = new JsonPartialFileSplitterStub(50);
        $splitter->setChunkSize(1);
        $splitter->setTimeout(140);
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertFalse($splitter->isCompleted(), 'Completed (first iteration)');
        self::assertEquals(
            [
                'offset'                      => 162,
                'lineNumber'                  => 3,
                'charNumber'                  => 75,
                'state'                       => 12 /* STATE_AFTER_VALUE */,
                'stack'                       => [0, 1],
                'sectionName'                 => 'data',
                'targetFileIndex'             => 2,
                'targetFileFirstRecordOffset' => 2,
                'listener'                    => [
                    'level'       => 2,
                    'objectLevel' => 1,
                    'objectKeys'  => [1 => null, 2 => 'data'],
                    'stack'       => [[], []]
                ]
            ],
            $splitter->getState(),
            'State (first iteration)'
        );

        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 1, 'data', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);

        // second (last) iteration
        /** @var JsonPartialFileSplitterStub $splitter */
        $splitter = $getNextSplitter($splitter);
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertTrue($splitter->isCompleted(), 'Completed (second iteration)');
        self::assertEquals(
            [
                'offset'                      => 242,
                'lineNumber'                  => 5,
                'charNumber'                  => 3,
                'state'                       => 14 /* STATE_END_DOCUMENT */,
                'stack'                       => [],
                'sectionName'                 => 'data',
                'targetFileIndex'             => 3,
                'targetFileFirstRecordOffset' => 3,
                'listener'                    => [
                    'level'       => 0,
                    'objectLevel' => 0,
                    'objectKeys'  => [],
                    'stack'       => []
                ]
            ],
            $splitter->getState(),
            'State (second iteration)'
        );

        self::assertCount(1, $files);
        self::assertCount(1, $resultFileNames);
        self::assertCount(1, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 2, 2, 'data', $files[0]);
        $this->assertChunkContent($result[2], $resultFileContents[0]);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSplitWhenTimeoutExceededAndDifferentInstancesOfSplittersAreUsedAndWithHeaderSection()
    {
        $inputJson = <<<JSON
{"jsonapi": {"version": "1.0"},
"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
]}
JSON;
        $result = [
            [
                'jsonapi' => ['version' => '1.0'],
                'data'    => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']]
                ]
            ],
            [
                'jsonapi' => ['version' => '1.0'],
                'data'    => [
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']]
                ]
            ],
            [
                'jsonapi' => ['version' => '1.0'],
                'data'    => [
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ]
        ];

        // first iteration
        $splitter = new JsonPartialFileSplitterStub(50);
        $splitter->setChunkSize(1);
        $splitter->setHeaderSectionName('jsonapi');
        $splitter->setTimeout(140);
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertFalse($splitter->isCompleted(), 'Completed (first iteration)');
        self::assertEquals(
            [
                'offset'                      => 193,
                'lineNumber'                  => 4,
                'charNumber'                  => 75,
                'state'                       => 12 /* STATE_AFTER_VALUE */,
                'stack'                       => [0, 1],
                'sectionName'                 => 'data',
                'headerSection'               => ['jsonapi' => ['version' => '1.0']],
                'targetFileIndex'             => 2,
                'targetFileFirstRecordOffset' => 2,
                'listener'                    => [
                    'level'       => 2,
                    'objectLevel' => 1,
                    'objectKeys'  => [1 => null, 2 => 'data'],
                    'stack'       => [[], []]
                ]
            ],
            $splitter->getState(),
            'State (first iteration)'
        );

        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 1, 'data', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);

        // second (last) iteration
        $state = $splitter->getState();
        $splitter = new JsonPartialFileSplitterStub(50);
        $splitter->setChunkSize(1);
        $splitter->setHeaderSectionName('jsonapi');
        $splitter->setTimeout(140);
        $splitter->setState($state);
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertTrue($splitter->isCompleted(), 'Completed (second iteration)');
        self::assertEquals(
            [
                'offset'                      => 273,
                'lineNumber'                  => 6,
                'charNumber'                  => 3,
                'state'                       => 14 /* STATE_END_DOCUMENT */,
                'stack'                       => [],
                'sectionName'                 => 'data',
                'headerSection'               => ['jsonapi' => ['version' => '1.0']],
                'targetFileIndex'             => 3,
                'targetFileFirstRecordOffset' => 3,
                'listener'                    => [
                    'level'       => 0,
                    'objectLevel' => 0,
                    'objectKeys'  => [],
                    'stack'       => []
                ]
            ],
            $splitter->getState(),
            'State (second iteration)'
        );

        self::assertCount(1, $files);
        self::assertCount(1, $resultFileNames);
        self::assertCount(1, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 2, 2, 'data', $files[0]);
        $this->assertChunkContent($result[2], $resultFileContents[0]);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSplitWhenTimeoutExceededAndFileContainsMultibyteSymbols()
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName ä"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName ž"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName Ā"}}
]}
JSON;
        $result = [
            [
                'data' => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName ä']]
                ]
            ],
            [
                'data' => [
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName ž']]
                ]
            ],
            [
                'data' => [
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName Ā']]
                ]
            ]
        ];

        $splitter = new JsonPartialFileSplitterStub(50);
        $splitter->setChunkSize(1);
        $splitter->setTimeout(140);

        // first iteration
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertFalse($splitter->isCompleted(), 'Completed (first iteration)');
        self::assertEquals(
            [
                'offset'                      => 164,
                'lineNumber'                  => 3,
                'charNumber'                  => 76,
                'state'                       => 12 /* STATE_AFTER_VALUE */,
                'stack'                       => [0, 1],
                'sectionName'                 => 'data',
                'targetFileIndex'             => 2,
                'targetFileFirstRecordOffset' => 2,
                'listener'                    => [
                    'level'       => 2,
                    'objectLevel' => 1,
                    'objectKeys'  => [1 => null, 2 => 'data'],
                    'stack'       => [[], []]
                ]
            ],
            $splitter->getState(),
            'State (first iteration)'
        );

        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 1, 'data', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);

        // second (last) iteration
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertTrue($splitter->isCompleted(), 'Completed (second iteration)');
        self::assertEquals(
            [
                'offset'                      => 245,
                'lineNumber'                  => 5,
                'charNumber'                  => 3,
                'state'                       => 14 /* STATE_END_DOCUMENT */,
                'stack'                       => [],
                'sectionName'                 => 'data',
                'targetFileIndex'             => 3,
                'targetFileFirstRecordOffset' => 3,
                'listener'                    => [
                    'level'       => 0,
                    'objectLevel' => 0,
                    'objectKeys'  => [],
                    'stack'       => []
                ]
            ],
            $splitter->getState(),
            'State (second iteration)'
        );

        self::assertCount(1, $files);
        self::assertCount(1, $resultFileNames);
        self::assertCount(1, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 2, 2, 'data', $files[0]);
        $this->assertChunkContent($result[2], $resultFileContents[0]);
    }

    public function testSplitWhenTimeoutExceededAndFileHasErrorInFirstIteration()
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
]}
JSON;

        $splitter = new JsonPartialFileSplitterStub(50);
        $splitter->setChunkSize(1);
        $splitter->setTimeout(140);

        $this->splitWithException(
            $splitter,
            $inputJson,
            ParsingException::class,
            'Parsing error in [3:5]. Start of string expected for object key. Instead got: {'
        );
    }

    public function testSplitWhenTimeoutExceededAndFileHasErrorInSecondIteration()
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}
]}
JSON;

        $splitter = new JsonPartialFileSplitterStub(50);
        $splitter->setChunkSize(1);
        $splitter->setTimeout(140);

        // first iteration
        $resultFileNames = [];
        $resultFileContents = [];
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertFalse($splitter->isCompleted(), 'Completed (first iteration)');
        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);

        // second (last) iteration
        $this->splitWithException(
            $splitter,
            $inputJson,
            ParsingException::class,
            "Parsing error in [5:1]. Expected ',' or '}' while parsing object. Got: ]"
        );
    }
}
