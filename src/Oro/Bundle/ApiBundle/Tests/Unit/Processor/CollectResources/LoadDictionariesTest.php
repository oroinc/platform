<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\LoadDictionaries;
use Oro\Bundle\ApiBundle\Request\ApiResource;
use Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class LoadDictionariesTest extends TestCase
{
    private ChainDictionaryValueListProvider&MockObject $dictionaryProvider;
    private LoadDictionaries $processor;

    #[\Override]
    protected function setUp(): void
    {
        $this->dictionaryProvider = $this->createMock(ChainDictionaryValueListProvider::class);

        $this->processor = new LoadDictionaries($this->dictionaryProvider);
    }

    public function testProcess(): void
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
