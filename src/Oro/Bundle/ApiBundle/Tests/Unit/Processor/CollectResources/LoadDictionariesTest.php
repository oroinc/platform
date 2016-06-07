<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectResources;

use Oro\Bundle\ApiBundle\Processor\CollectResources\CollectResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectResources\LoadDictionaries;
use Oro\Bundle\ApiBundle\Request\ApiResource;

class LoadDictionariesTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $dictionaryProvider;

    /** @var LoadDictionaries */
    protected $processor;

    protected function setUp()
    {
        $this->dictionaryProvider = $this
            ->getMockBuilder('Oro\Bundle\EntityBundle\Provider\ChainDictionaryValueListProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new LoadDictionaries($this->dictionaryProvider);
    }

    public function testProcess()
    {
        $context = new CollectResourcesContext();

        $this->dictionaryProvider->expects($this->once())
            ->method('getSupportedEntityClasses')
            ->willReturn(['Test\Entity1', 'Test\Entity2']);

        $this->processor->process($context);

        $this->assertEquals(
            [
                'Test\Entity1' => new ApiResource('Test\Entity1'),
                'Test\Entity2' => new ApiResource('Test\Entity2'),
            ],
            $context->getResult()->toArray()
        );
    }
}
