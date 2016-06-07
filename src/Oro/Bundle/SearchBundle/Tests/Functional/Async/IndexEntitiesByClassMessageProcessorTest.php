<?php
namespace Oro\Bundle\SearchBundle\Tests\Functional\Async;

use Oro\Bundle\SearchBundle\Async\IndexEntitiesByClassMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class IndexEntitiesByClassMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_search.async.message_processor.index_entities_by_class');

        $this->assertInstanceOf(IndexEntitiesByClassMessageProcessor::class, $instance);
    }
}
