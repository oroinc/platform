<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;

class ButtonSearchContextProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextHelper;

    /** @var  ButtonSearchContextProvider */
    protected $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contextHelper = $this->getMockBuilder(ContextHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->provider = new ButtonSearchContextProvider($this->contextHelper);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->provider, $this->contextHelper);
    }

    /**
     * @dataProvider contextProvider
     *
     * @param array $context
     */
    public function testGetButtonSearchContext(array $context)
    {
        $this->contextHelper->expects($this->atLeastOnce())
            ->method('getContext')
            ->willReturn($context);

        $buttonSearchContext = $this->provider->getButtonSearchContext();

        $this->assertInstanceOf(ButtonSearchContext::class, $buttonSearchContext);

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
    public function contextProvider()
    {
        return [
            'correct_int' => [
                [
                    ContextHelper::ROUTE_PARAM => 'route',
                    ContextHelper::FROM_URL_PARAM => 'ref',
                    ContextHelper::ENTITY_ID_PARAM => 1,
                    ContextHelper::ENTITY_CLASS_PARAM => 'Class',
                    ContextHelper::DATAGRID_PARAM => 'datagrid',
                    ContextHelper::GROUP_PARAM => 'group'
                ]
            ],
            'correct_string' => [
                [
                    ContextHelper::ROUTE_PARAM => 'route',
                    ContextHelper::FROM_URL_PARAM => 'ref',
                    ContextHelper::ENTITY_ID_PARAM => uniqid(),
                    ContextHelper::ENTITY_CLASS_PARAM => 'Class',
                    ContextHelper::DATAGRID_PARAM => 'datagrid',
                    ContextHelper::GROUP_PARAM => 'group'
                ]
            ],
            'correct_array' => [
                [
                    ContextHelper::ROUTE_PARAM => 'route',
                    ContextHelper::FROM_URL_PARAM => 'ref',
                    ContextHelper::ENTITY_ID_PARAM => [1, uniqid()],
                    ContextHelper::ENTITY_CLASS_PARAM => 'Class',
                    ContextHelper::DATAGRID_PARAM => 'datagrid',
                    ContextHelper::GROUP_PARAM => 'group'
                ]
            ],
        ];
    }
}
