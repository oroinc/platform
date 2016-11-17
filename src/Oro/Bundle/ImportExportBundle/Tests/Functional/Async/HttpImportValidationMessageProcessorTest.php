<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Functional\Async;

use Oro\Bundle\ImportExportBundle\Async\HttpImportValidationMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class HttpImportValidationMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_importexport.async.http_import_validation');

        $this->assertInstanceOf(HttpImportValidationMessageProcessor::class, $instance);
    }
}
