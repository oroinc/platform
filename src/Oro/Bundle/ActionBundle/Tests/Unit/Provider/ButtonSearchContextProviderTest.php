<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;

class ButtonSearchContextProviderTest extends \PHPUnit_Framework_TestCase
{
    public function testGetButtonSearchContext()
    {
        $context = $this->getContext();
        $contextHelper = $this->getMockBuilder('Oro\Bundle\ActionBundle\Helper\ContextHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $contextHelper->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $buttonSearchContextProvider = new ButtonSearchContextProvider($contextHelper);
        $buttonSearchContext = $buttonSearchContextProvider->getButtonSearchContext();

        $this->assertInstanceOf('Oro\Bundle\ActionBundle\Model\ButtonSearchContext', $buttonSearchContext);
        $this->assertSame($context[ContextHelper::GROUP_PARAM], $buttonSearchContext->getGroup());
        $this->assertSame($context[ContextHelper::DATAGRID_PARAM], $buttonSearchContext->getGridName());
        $this->assertSame($context[ContextHelper::ENTITY_CLASS_PARAM], $buttonSearchContext->getEntityClass());
        $this->assertSame($context[ContextHelper::ENTITY_ID_PARAM], $buttonSearchContext->getEntityId());
        $this->assertSame($context[ContextHelper::FROM_URL_PARAM], $buttonSearchContext->getReferrer());
        $this->assertSame($context[ContextHelper::ROUTE_PARAM], $buttonSearchContext->getRouteName());
    }

    /**
     * @return array
     */
    private function getContext()
    {
        return [
            ContextHelper::ROUTE_PARAM => 'route',
            ContextHelper::FROM_URL_PARAM => 'ref',
            ContextHelper::ENTITY_ID_PARAM => 1,
            ContextHelper::ENTITY_CLASS_PARAM => 'Class',
            ContextHelper::DATAGRID_PARAM => 'datagrid',
            ContextHelper::GROUP_PARAM => 'group'
        ];
    }
}
