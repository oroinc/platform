<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\LoadDictionaries;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;

class LoadDictionariesTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ChainDictionaryValueListProvider */
    private $dictionaryProvider;

    /** @var LoadDictionaries */
    private $processor;

    protected function setUp()
    {
        $this->dictionaryProvider = $this->createMock(ChainDictionaryValueListProvider::class);

        $this->processor = new LoadDictionaries($this->dictionaryProvider);
    }

    public function testProcess()
    {
        $context = new CollectResourcesContext();

        $this->dictionaryProvider->expects(self::once())
            ->method('getSupportedEntityClasses')
            ->willReturn(['Test\Entity1', 'Test\Entity2']);

        $this->processor->process($context);

        self::assertEquals(
            [
                'Test\Entity1' => new ApiResource('Test\Entity1'),
                'Test\Entity2' => new ApiResource('Test\Entity2')
            ],
            $context->getResult()->toArray()
        );
    }
}
