<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async\Import;

use Oro\Bundle\ImportExportBundle\Async\Import\AbstractPreparingHttpImportMessageProcessor;
use Oro\Bundle\ImportExportBundle\Async\Import\PreparingHttpImportValidationMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class PreparingHttpImportValidationMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_importexport.async.preparing_http_import_validation');

        $this->assertInstanceOf(PreparingHttpImportValidationMessageProcessor::class, $instance);
        $this->assertInstanceOf(AbstractPreparingHttpImportMessageProcessor::class, $instance);
    }
}
