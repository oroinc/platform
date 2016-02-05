<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Processor\CollectPublicResources;

use Oro\Bundle\ApiBundle\Processor\CollectPublicResources\CollectPublicResourcesContext;
use Oro\Bundle\ApiBundle\Processor\CollectPublicResources\LoadDictionaries;
use Oro\Bundle\ApiBundle\Request\PublicResource;

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
        $context = new CollectPublicResourcesContext();

        $this->dictionaryProvider->expects($this->once())
            ->method('getSupportedEntityClasses')
            ->willReturn(['Test\Entity1', 'Test\Entity2']);

        $this->processor->process($context);

        $this->assertEquals(
            [
                new PublicResource('Test\Entity1'),
                new PublicResource('Test\Entity2'),
            ],
            $context->getResult()->toArray()
        );
    }
}
