<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Autocomplete;

use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class SearchRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var SearchHandlerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $searchHandler;

    /** @var SearchRegistry */
    private $searchRegistry;

    protected function setUp(): void
    {
        $this->searchHandler = $this->createMock(SearchHandlerInterface::class);

        $container = TestContainerBuilder::create()
            ->add('test', $this->searchHandler)
            ->getContainer($this);

        $this->searchRegistry = new SearchRegistry($container);
    }

    public function testGetAndHasSearchHandler()
    {
        $this->assertTrue($this->searchRegistry->hasSearchHandler('test'));
        $this->assertFalse($this->searchRegistry->hasSearchHandler('testNotExists'));

        $this->assertSame($this->searchHandler, $this->searchRegistry->getSearchHandler('test'));
    }

    public function testGetSearchHandlerFails()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Search handler "testNotExists" is not registered.');

        $this->searchRegistry->getSearchHandler('testNotExists');
    }
}
