<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async;

use Oro\Bundle\ImportExportBundle\Async\CliImportValidationMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CliImportValidationMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_importexport.async.cli_import_validation');

        $this->assertInstanceOf(CliImportValidationMessageProcessor::class, $instance);
    }
}
