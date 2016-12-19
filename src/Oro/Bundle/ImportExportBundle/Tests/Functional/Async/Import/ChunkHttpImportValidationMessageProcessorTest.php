<?php
namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Import\AbstractChunkImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Import\ChunkHttpImportValidationMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ChunkHttpImportValidationMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_importexport.async.chunck_http_import_validation');

        $this->assertInstanceOf(ChunkHttpImportValidationMessageProcessor::class, $instance);
        $this->assertInstanceOf(AbstractChunkImportMessageProcessor::class, $instance);
    }
}
