<?php
namespace Oro\Bundle\DataGridBundle\Tests\Functional\Async;

use Oro\Bundle\DataGridBundle\Async\ExportMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ExportCsvMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_datagrid.async.export');

        $this->assertInstanceOf(ExportMessageProcessor::class, $instance);
    }
}
