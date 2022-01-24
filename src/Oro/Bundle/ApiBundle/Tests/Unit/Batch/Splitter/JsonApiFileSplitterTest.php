<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Batch\Splitter;

use JsonStreamingParser\Exception\ParsingException;
use Oro\Bundle\ApiBundle\Batch\Splitter\JsonFileSplitter;
use Oro\Bundle\ApiBundle\Exception\ParsingErrorFileSplitterException;
use Oro\Bundle\GaufretteBundle\FileManager;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class JsonApiFileSplitterTest extends FileSplitterTestCase
{
    public function testChunkSize()
    {
        $splitter = new JsonFileSplitter();
        self::assertSame(100, $splitter->getChunkSize());
        $splitter->setChunkSize(1000);
        self::assertSame(1000, $splitter->getChunkSize());
    }

    public function testChunkSizePerSection()
    {
        $splitter = new JsonFileSplitter();
        self::assertSame([], $splitter->getChunkSizePerSection());
        $splitter->setChunkSizePerSection(['included' => 1000]);
        self::assertSame(['included' => 1000], $splitter->getChunkSizePerSection());
    }

    public function testChunkFileNameTemplate()
    {
        $splitter = new JsonFileSplitter();
        self::assertNull($splitter->getChunkFileNameTemplate());
        $splitter->setChunkFileNameTemplate('api_chunk_%s');
        self::assertEquals('api_chunk_%s', $splitter->getChunkFileNameTemplate());
    }

    public function testHeaderSectionName()
    {
        $splitter = new JsonFileSplitter();
        self::assertNull($splitter->getHeaderSectionName());
        $splitter->setHeaderSectionName('jsonapi');
        self::assertEquals('jsonapi', $splitter->getHeaderSectionName());
    }

    public function testSectionNamesToSplit()
    {
        $splitter = new JsonFileSplitter();
        self::assertSame([], $splitter->getSectionNamesToSplit());
        $splitter->setSectionNamesToSplit(['data', 'included']);
        self::assertSame(['data', 'included'], $splitter->getSectionNamesToSplit());
    }

    public function testSplitWithoutFile()
    {
        $fileName = 'notExistingFile';
        $expectedException = new \Exception('some error');

        $srcFileManager = $this->createMock(FileManager::class);
        $srcFileManager->expects(self::once())
            ->method('getStream')
            ->with($fileName)
            ->willThrowException($expectedException);
        $destFileManager = $this->createMock(FileManager::class);
        $srcFileManager->expects(self::never())
            ->method('writeToStorage');

        $actualException = null;
        $splitter = new JsonFileSplitter();
        try {
            $splitter->splitFile($fileName, $srcFileManager, $destFileManager);
        } catch (\Exception $e) {
            $actualException = $e;
        }

        $this->assertSplitterException(
            $actualException,
            get_class($expectedException),
            $expectedException->getMessage()
        );
    }

    public function streamTypeDataProvider(): array
    {
        return [
            'Local'          => [false],
            'InMemoryBuffer' => [true]
        ];
    }

    /**
     * @dataProvider streamTypeDataProvider
     */
    public function testSplitToOneChunk(bool $withInMemoryBuffer)
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
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']],
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonFileSplitter();
        $splitter->setChunkSize(10);
        $splitter->setChunkSizePerSection(['included' => 15]);
        $files = $this->splitFile(
            $splitter,
            'tmpFileName',
            $inputJson,
            $resultFileNames,
            $resultFileContents,
            $withInMemoryBuffer
        );

        self::assertCount(1, $files);
        self::assertCount(1, $resultFileNames);
        self::assertCount(1, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
    }

    /**
     * @dataProvider streamTypeDataProvider
     */
    public function testSplitToOneChunkPerSection(bool $withInMemoryBuffer)
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
],
"included":[
    {"type": "acme", "id": "4", "attributes": {"firstName": "FirstName 4"}},
    {"type": "acme", "id": "5", "attributes": {"firstName": "FirstName 5"}}
]}
JSON;
        $result = [
            [
                'data' => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']],
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']],
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ],
            [
                'included' => [
                    ['type' => 'acme', 'id' => '4', 'attributes' => ['firstName' => 'FirstName 4']],
                    ['type' => 'acme', 'id' => '5', 'attributes' => ['firstName' => 'FirstName 5']]
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonFileSplitter();
        $splitter->setChunkSize(10);
        $splitter->setChunkSizePerSection(['included' => 5]);
        $files = $this->splitFile(
            $splitter,
            'tmpFileName',
            $inputJson,
            $resultFileNames,
            $resultFileContents,
            $withInMemoryBuffer
        );

        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 0, 'included', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);
    }

    /**
     * @dataProvider streamTypeDataProvider
     */
    public function testSplitWithSpecifiedSectionsToSplit(bool $withInMemoryBuffer)
    {
        $inputJson = <<<JSON
{
"meta":{"authors": ["John Doo"]},
"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
],
"included":[
    {"type": "acme", "id": "4", "attributes": {"firstName": "FirstName 4"}},
    {"type": "acme", "id": "5", "attributes": {"firstName": "FirstName 5"}}
],
"links":[
    {"self": "http://example.com/acme"}
]}
JSON;
        $result = [
            [
                'data' => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']],
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']],
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ],
            [
                'included' => [
                    ['type' => 'acme', 'id' => '4', 'attributes' => ['firstName' => 'FirstName 4']],
                    ['type' => 'acme', 'id' => '5', 'attributes' => ['firstName' => 'FirstName 5']]
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonFileSplitter();
        $splitter->setChunkSize(10);
        $splitter->setChunkSizePerSection(['included' => 5]);
        $splitter->setSectionNamesToSplit(['data', 'included']);
        $files = $this->splitFile(
            $splitter,
            'tmpFileName',
            $inputJson,
            $resultFileNames,
            $resultFileContents,
            $withInMemoryBuffer
        );

        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 0, 'included', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);
    }

    /**
     * @dataProvider streamTypeDataProvider
     */
    public function testSplitWithSpecifiedSectionsToSplitAndWithHeaderSection(bool $withInMemoryBuffer)
    {
        $inputJson = <<<JSON
{
"jsonapi": {"version": "1.0"},
"meta":{"authors": ["John Doo"]},
"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
],
"included":[
    {"type": "acme", "id": "4", "attributes": {"firstName": "FirstName 4"}},
    {"type": "acme", "id": "5", "attributes": {"firstName": "FirstName 5"}}
],
"links":[
    {"self": "http://example.com/acme"}
]}
JSON;
        $result = [
            [
                'jsonapi' => ['version' => '1.0'],
                'data'    => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']],
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']],
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ],
            [
                'jsonapi'  => ['version' => '1.0'],
                'included' => [
                    ['type' => 'acme', 'id' => '4', 'attributes' => ['firstName' => 'FirstName 4']],
                    ['type' => 'acme', 'id' => '5', 'attributes' => ['firstName' => 'FirstName 5']]
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonFileSplitter();
        $splitter->setChunkSize(10);
        $splitter->setChunkSizePerSection(['included' => 5]);
        $splitter->setSectionNamesToSplit(['data', 'included']);
        $splitter->setHeaderSectionName('jsonapi');
        $files = $this->splitFile(
            $splitter,
            'tmpFileName',
            $inputJson,
            $resultFileNames,
            $resultFileContents,
            $withInMemoryBuffer
        );

        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 0, 'included', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);
    }

    /**
     * @dataProvider streamTypeDataProvider
     */
    public function testSplitToSeveralChunks(bool $withInMemoryBuffer)
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
],
"included":[
    {"type": "acme", "id": "4", "attributes": {"firstName": "FirstName 4"}},
    {"type": "acme", "id": "5", "attributes": {"firstName": "FirstName 5"}},
    {"type": "acme", "id": "6", "attributes": {"firstName": "FirstName 6"}},
    {"type": "acme", "id": "7", "attributes": {"firstName": "FirstName 7"}}
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
            ],
            [
                'included' => [
                    ['type' => 'acme', 'id' => '4', 'attributes' => ['firstName' => 'FirstName 4']],
                    ['type' => 'acme', 'id' => '5', 'attributes' => ['firstName' => 'FirstName 5']],
                    ['type' => 'acme', 'id' => '6', 'attributes' => ['firstName' => 'FirstName 6']]
                ]
            ],
            [
                'included' => [
                    ['type' => 'acme', 'id' => '7', 'attributes' => ['firstName' => 'FirstName 7']]
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonFileSplitter();
        $splitter->setChunkSize(2);
        $splitter->setChunkSizePerSection(['included' => 3]);
        $files = $this->splitFile(
            $splitter,
            'tmpFileName',
            $inputJson,
            $resultFileNames,
            $resultFileContents,
            $withInMemoryBuffer
        );

        self::assertCount(4, $files);
        self::assertCount(4, $resultFileNames);
        self::assertCount(4, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 2, 'data', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);
        $this->assertChunkFile($resultFileNames[2], 2, 0, 'included', $files[2]);
        $this->assertChunkContent($result[2], $resultFileContents[2]);
        $this->assertChunkFile($resultFileNames[3], 3, 3, 'included', $files[3]);
        $this->assertChunkContent($result[3], $resultFileContents[3]);
    }

    /**
     * @dataProvider streamTypeDataProvider
     */
    public function testSplitToSeveralChunksAndWithHeaderSection(bool $withInMemoryBuffer)
    {
        $inputJson = <<<JSON
{"jsonapi": {"version": "1.0"},
"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
],
"included":[
    {"type": "acme", "id": "4", "attributes": {"firstName": "FirstName 4"}},
    {"type": "acme", "id": "5", "attributes": {"firstName": "FirstName 5"}},
    {"type": "acme", "id": "6", "attributes": {"firstName": "FirstName 6"}},
    {"type": "acme", "id": "7", "attributes": {"firstName": "FirstName 7"}}
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
            ],
            [
                'jsonapi'  => ['version' => '1.0'],
                'included' => [
                    ['type' => 'acme', 'id' => '4', 'attributes' => ['firstName' => 'FirstName 4']],
                    ['type' => 'acme', 'id' => '5', 'attributes' => ['firstName' => 'FirstName 5']],
                    ['type' => 'acme', 'id' => '6', 'attributes' => ['firstName' => 'FirstName 6']]
                ]
            ],
            [
                'jsonapi'  => ['version' => '1.0'],
                'included' => [
                    ['type' => 'acme', 'id' => '7', 'attributes' => ['firstName' => 'FirstName 7']]
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonFileSplitter();
        $splitter->setChunkSize(2);
        $splitter->setChunkSizePerSection(['included' => 3]);
        $splitter->setHeaderSectionName('jsonapi');
        $files = $this->splitFile(
            $splitter,
            'tmpFileName',
            $inputJson,
            $resultFileNames,
            $resultFileContents,
            $withInMemoryBuffer
        );

        self::assertCount(4, $files);
        self::assertCount(4, $resultFileNames);
        self::assertCount(4, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 2, 'data', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);
        $this->assertChunkFile($resultFileNames[2], 2, 0, 'included', $files[2]);
        $this->assertChunkContent($result[2], $resultFileContents[2]);
        $this->assertChunkFile($resultFileNames[3], 3, 3, 'included', $files[3]);
        $this->assertChunkContent($result[3], $resultFileContents[3]);
    }

    public function testSplitToSeveralChunksWhenHeaderSectionIsNotFirstSectionInDocument()
    {
        $this->expectException(ParsingErrorFileSplitterException::class);
        $this->expectExceptionMessage(
            'Failed to split the file "tmpFileName". Reason: Parsing error in [0:0].'
            . ' The object with the key "jsonapi" should be the first object in the document.'
        );

        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
],
"jsonapi": {"version": "1.0"}}
JSON;

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonFileSplitter();
        $splitter->setChunkSize(2);
        $splitter->setChunkSizePerSection(['included' => 3]);
        $splitter->setHeaderSectionName('jsonapi');
        $this->splitFile(
            $splitter,
            'tmpFileName',
            $inputJson,
            $resultFileNames,
            $resultFileContents
        );
    }

    /**
     * @dataProvider streamTypeDataProvider
     */
    public function testSplitWithChunkFileNameTemplate(bool $withInMemoryBuffer)
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}},
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
        $splitter = new JsonFileSplitter();
        $splitter->setChunkSize(2);
        $splitter->setChunkSizePerSection(['included' => 3]);
        $splitter->setChunkFileNameTemplate('api_chunk_%s');
        $files = $this->splitFile(
            $splitter,
            'tmpFileName',
            $inputJson,
            $resultFileNames,
            $resultFileContents,
            $withInMemoryBuffer
        );

        self::assertCount(2, $files);
        self::assertCount(2, $resultFileNames);
        self::assertCount(2, $resultFileContents);
        self::assertStringStartsWith('api_chunk_', $resultFileNames[0]);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        self::assertStringStartsWith('api_chunk_', $resultFileNames[1]);
        $this->assertChunkFile($resultFileNames[1], 1, 2, 'data', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);
    }

    /**
     * @dataProvider streamTypeDataProvider
     */
    public function testSplitToSeveralChunkWithDifferentSections(bool $withInMemoryBuffer)
    {
        $inputJson = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}},
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}},
    {"type": "acme", "id": "4", "attributes": {"firstName": "FirstName 4"}},
    {"type": "acme", "id": "5", "attributes": {"firstName": "FirstName 5"}}
],
"included":[
    {"type": "acme", "id": "6", "attributes": {"firstName": "FirstName 6"}},
    {"type": "acme", "id": "7", "attributes": {"firstName": "FirstName 7"}}
]}
JSON;
        $result = [
            [
                'data' => [
                    ['type' => 'acme', 'id' => '1', 'attributes' => ['firstName' => 'FirstName 1']],
                    ['type' => 'acme', 'id' => '2', 'attributes' => ['firstName' => 'FirstName 2']],
                    ['type' => 'acme', 'id' => '3', 'attributes' => ['firstName' => 'FirstName 3']]
                ]
            ],
            [
                'data' => [
                    ['type' => 'acme', 'id' => '4', 'attributes' => ['firstName' => 'FirstName 4']],
                    ['type' => 'acme', 'id' => '5', 'attributes' => ['firstName' => 'FirstName 5']]
                ]
            ],
            [
                'included' => [
                    ['type' => 'acme', 'id' => '6', 'attributes' => ['firstName' => 'FirstName 6']],
                    ['type' => 'acme', 'id' => '7', 'attributes' => ['firstName' => 'FirstName 7']]
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonFileSplitter();
        $splitter->setChunkSize(3);
        $files = $this->splitFile(
            $splitter,
            'tmpFileName',
            $inputJson,
            $resultFileNames,
            $resultFileContents,
            $withInMemoryBuffer
        );

        self::assertCount(3, $files);
        self::assertCount(3, $resultFileNames);
        self::assertCount(3, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
        $this->assertChunkFile($resultFileNames[1], 1, 3, 'data', $files[1]);
        $this->assertChunkContent($result[1], $resultFileContents[1]);
        $this->assertChunkFile($resultFileNames[2], 2, 0, 'included', $files[2]);
        $this->assertChunkContent($result[2], $resultFileContents[2]);
    }

    /**
     * @dataProvider dataProviderWithExceptions
     */
    public function testSplitWithException(string $inputJson, string $exceptionClass, string $exceptionMessage)
    {
        $splitter = new JsonFileSplitter();
        $this->splitWithException($splitter, $inputJson, $exceptionClass, $exceptionMessage);
    }

    public function dataProviderWithExceptions(): array
    {
        $inputJson1 = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
],
"included":[
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}}
    {"type": "acme", "id": "3", "attributes": {"firstName": "FirstName 3"}}
]}
JSON;
        $inputJson2 = <<<JSON
{"data":[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}
]}
JSON;
        $inputJson3 = <<<JSON
{[
    {"type": "acme", "id": "1", "attributes": {"firstName": "FirstName 1"}},
    {"type": "acme", "id": "2", "attributes": {"firstName": "FirstName 2"}}
]}
JSON;

        return [
            'missed separator (comma) between items in "included" section' => [
                $inputJson1,
                ParsingException::class,
                "Parsing error in [6:5]. Expected ',' or ']' while parsing array. Got: {"
            ],
            'missed closing "}" in last collection item'                   => [
                $inputJson2,
                ParsingException::class,
                "Parsing error in [4:1]. Expected ',' or '}' while parsing object. Got: ]"
            ],
            'missed key before collection'                                 => [
                $inputJson3,
                ParsingException::class,
                'Parsing error in [1:2]. Start of string expected for object key. Instead got: ['
            ]
        ];
    }

    public function testSplitWithDifferentTypesOfItems()
    {
        $inputJson = <<<JSON
{"data":[
 {"type": "first"},
 null,
 0,
 123,
 "test",
 {},
 [],
 {"type": "last"}
]}
JSON;
        $result = [
            [
                'data' => [
                    ['type' => 'first'],
                    null,
                    0,
                    123,
                    'test',
                    [],
                    [],
                    ['type' => 'last']
                ]
            ]
        ];

        $resultFileNames = [];
        $resultFileContents = [];
        $splitter = new JsonFileSplitter();
        $splitter->setChunkSize(10);
        $splitter->setChunkSizePerSection(['included' => 15]);
        $files = $this->splitFile($splitter, 'tmpFileName', $inputJson, $resultFileNames, $resultFileContents);

        self::assertCount(1, $files);
        self::assertCount(1, $resultFileNames);
        self::assertCount(1, $resultFileContents);
        $this->assertChunkFile($resultFileNames[0], 0, 0, 'data', $files[0]);
        $this->assertChunkContent($result[0], $resultFileContents[0]);
    }
}
