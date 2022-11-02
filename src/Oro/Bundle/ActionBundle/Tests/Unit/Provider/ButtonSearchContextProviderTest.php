<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Provider;

use Oro\Bundle\ActionBundle\Helper\ContextHelper;
use Oro\Bundle\ActionBundle\Provider\ButtonSearchContextProvider;

class ButtonSearchContextProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $contextHelper;

    /** @var ButtonSearchContextProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->contextHelper = $this->createMock(ContextHelper::class);

        $this->provider = new ButtonSearchContextProvider($this->contextHelper);
    }

    /**
     * @dataProvider contextProvider
     */
    public function testGetButtonSearchContext(array $context)
    {
        $context = $this->normalizeContext($context);

        $this->contextHelper->expects($this->once())
            ->method('getContext')
            ->willReturn($context);

        $buttonSearchContext = $this->provider->getButtonSearchContext($context);

        $this->assertSame($context[ContextHelper::GROUP_PARAM], $buttonSearchContext->getGroup());
        $this->assertSame($context[ContextHelper::DATAGRID_PARAM], $buttonSearchContext->getDatagrid());
        $this->assertSame($context[ContextHelper::ENTITY_CLASS_PARAM], $buttonSearchContext->getEntityClass());
        $this->assertSame($context[ContextHelper::ENTITY_ID_PARAM], $buttonSearchContext->getEntityId());
        $this->assertSame($context[ContextHelper::FROM_URL_PARAM], $buttonSearchContext->getReferrer());
        $this->assertSame($context[ContextHelper::ROUTE_PARAM], $buttonSearchContext->getRouteName());
    }

    public function contextProvider(): array
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

    private function normalizeContext(array $context): array
    {
        return array_merge(
            [
                ContextHelper::ROUTE_PARAM => '',
                ContextHelper::ENTITY_ID_PARAM => null,
                ContextHelper::ENTITY_CLASS_PARAM => null,
                ContextHelper::DATAGRID_PARAM => '',
                ContextHelper::GROUP_PARAM => '',
                ContextHelper::FROM_URL_PARAM => '',
            ],
            $context
        );
    }
}
