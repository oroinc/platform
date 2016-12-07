<?php
namespace Oro\Bundle\DataGridBundle\Tests\Functional\Async;

use Oro\Bundle\DataGridBundle\Async\ExportCsvMessageProcessor;
use Oro\Bundle\DataGridBundle\Async\ExportXlsxMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ExportXlsxMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_datagrid.async.export.xlsx');

        $this->assertInstanceOf(ExportXlsxMessageProcessor::class, $instance);
    }
}
