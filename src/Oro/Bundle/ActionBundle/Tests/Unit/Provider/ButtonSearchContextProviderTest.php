<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;

class ButtonSearchContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextHelper|\PHPUnit\Framework\MockObject\MockObject */
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
        $context = $this->normalizeContext($context);

        $this->contextHelper->expects($this->once())->method('getContext')->willReturn($context);

        $buttonSearchContext = $this->provider->getButtonSearchContext($context);

        $this->assertSame($context[ContextHelper::GROUP_PARAM], $buttonSearchContext->getGroup());
        $this->assertSame($context[ContextHelper::DATAGRID_PARAM], $buttonSearchContext->getDatagrid());
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
                    ContextHelper::ENTITY_ID_PARAM => 'test_string',
                    ContextHelper::ENTITY_CLASS_PARAM => 'Class',
                    ContextHelper::DATAGRID_PARAM => 'datagrid',
                    ContextHelper::GROUP_PARAM => 'group'
                ]
            ],
            'correct_array' => [
                [
                    ContextHelper::ROUTE_PARAM => 'route',
                    ContextHelper::FROM_URL_PARAM => 'ref',
                    ContextHelper::ENTITY_ID_PARAM => [1, 'test_string'],
                    ContextHelper::ENTITY_CLASS_PARAM => 'Class',
                    ContextHelper::DATAGRID_PARAM => 'datagrid',
                    ContextHelper::GROUP_PARAM => 'group'
                ]
            ],
            'empty' => [
                []
            ],
            'empty_class_name' => [
                [
                    ContextHelper::ENTITY_ID_PARAM => [1, 'test_string'],
                ]
            ],
        ];
    }

    /**
     * @param array $context
     * @return array
     */
    private function normalizeContext(array $context)
    {
        return array_merge(
            [
                ContextHelper::ROUTE_PARAM => null,
                ContextHelper::ENTITY_ID_PARAM => null,
                ContextHelper::ENTITY_CLASS_PARAM => null,
                ContextHelper::DATAGRID_PARAM => null,
                ContextHelper::GROUP_PARAM => null,
                ContextHelper::FROM_URL_PARAM => null,
            ],
            $context
        );
    }
}
