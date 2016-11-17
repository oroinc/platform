<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async;

use Oro\Bundle\ImportExportBundle\Async\HttpImportMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class HttpImportMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_importexport.async.http_import');

        $this->assertInstanceOf(HttpImportMessageProcessor::class, $instance);
    }
}
