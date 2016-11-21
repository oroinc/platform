<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Model\ButtonSearchContext;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;

class ButtonSearchContextProviderTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextHelper|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextHelper;

    /** @var ButtonSearchContextProvider */
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

        $this->assertButtonContext($context, $this->provider->getButtonSearchContext());
    }

    /**
     * @dataProvider contextProvider
     *
     * @param array $context
     */
    public function testBuildFromContext(array $context)
    {
        $this->assertButtonContext($context, $this->provider->buildFromContext($context));
    }

    /**
     * @param array $context
     * @param mixed $buttonSearchContext
     *
     * @throws \PHPUnit_Framework_Exception
     */
    protected function assertButtonContext(array $context, $buttonSearchContext)
    {
        $this->assertInstanceOf(ButtonSearchContext::class, $buttonSearchContext);

        if (isset($context[ContextHelper::GROUP_PARAM])) {
            $this->assertSame($context[ContextHelper::GROUP_PARAM], $buttonSearchContext->getGroup());
        } else {
            $this->assertNull($buttonSearchContext->getGridName());
        }

        if (isset($context[ContextHelper::ENTITY_CLASS_PARAM])) {
            $this->assertSame($context[ContextHelper::ENTITY_CLASS_PARAM], $buttonSearchContext->getEntityClass());

            if (isset($context[ContextHelper::ENTITY_ID_PARAM])) {
                $this->assertSame($context[ContextHelper::ENTITY_ID_PARAM], $buttonSearchContext->getEntityId());
            } else {
                $this->assertNull($buttonSearchContext->getEntityId());
            }
        } else {
            $this->assertNull($buttonSearchContext->getEntityClass());
            $this->assertNull($buttonSearchContext->getEntityId());
        }

        if (isset($context[ContextHelper::FROM_URL_PARAM])) {
            $this->assertSame($context[ContextHelper::FROM_URL_PARAM], $buttonSearchContext->getReferrer());
        } else {
            $this->assertNull($buttonSearchContext->getReferrer());
        }

        if (isset($context[ContextHelper::ROUTE_PARAM])) {
            $this->assertSame($context[ContextHelper::ROUTE_PARAM], $buttonSearchContext->getRouteName());
        } else {
            $this->assertNull($buttonSearchContext->getRouteName());
        }
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
            'empty' => [
                [

                ]
            ],
            'empty_class_name' => [
                [
                    ContextHelper::ENTITY_ID_PARAM => [1, uniqid()],
                ]
            ],
        ];
    }
}
