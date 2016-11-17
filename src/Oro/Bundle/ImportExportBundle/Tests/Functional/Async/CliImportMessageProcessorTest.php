<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async;

use Oro\Bundle\ImportExportBundle\Async\CliImportMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CliImportMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_importexport.async.cli_import');

        $this->assertInstanceOf(CliImportMessageProcessor::class, $instance);
    }
}
