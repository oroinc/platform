<?php
namespace Oro\Bundle\SearchBundle\Tests\Functional\Async;

use Oro\Bundle\SearchBundle\Async\IndexEntitiesByIdMessageProcessor;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 * @group search
 */
class IndexEntitiesByIdMessageProcessorTest extends WebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->initClient();
    }

    public function testCouldBeConstructedByContainer()
    {
        $instance = $this->getContainer()->get('oro_search.async.message_processor.index_entities_by_id');

        $this->assertInstanceOf(IndexEntitiesByIdMessageProcessor::class, $instance);
    }
}
