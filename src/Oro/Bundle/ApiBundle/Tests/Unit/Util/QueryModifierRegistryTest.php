<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\Stub\QueryModifierWithOptionsStub;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\ApiBundle\Util\QueryModifierRegistry;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;

class QueryModifierRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var QueryModifierInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $queryModifier1;

    /** @var QueryModifierInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $queryModifier2;

    /** @var QueryModifierWithOptionsStub|\PHPUnit\Framework\MockObject\MockObject */
    private $queryModifier3;

    /** @var QueryModifierRegistry */
    private $registry;

    #[\Override]
    protected function setUp(): void
    {
        $this->queryModifier1 = $this->createMock(QueryModifierInterface::class);
        $this->queryModifier2 = $this->createMock(QueryModifierInterface::class);
        $this->queryModifier3 = $this->createMock(QueryModifierWithOptionsStub::class);

        $container = TestContainerBuilder::create()
            ->add('query_modifier1', $this->queryModifier1)
            ->add('query_modifier2', $this->queryModifier2)
            ->add('query_modifier3', $this->queryModifier3)
            ->getContainer($this);

        $this->registry = new QueryModifierRegistry(
            [
                ['query_modifier1', 'json_api'],
                ['query_modifier2', null],
                ['query_modifier3', 'json_api']
            ],
            $container,
            new RequestExpressionMatcher()
        );
    }

    public function testShouldExecuteAllSuitableQueryModifiers()
    {
        $options = ['key' => 'value'];

        $qb = $this->createMock(QueryBuilder::class);
        $skipRootEntity = true;

        $this->queryModifier1->expects(self::once())
            ->method('modify')
            ->with(self::identicalTo($qb), $skipRootEntity);
        $this->queryModifier2->expects(self::once())
            ->method('modify')
            ->with(self::identicalTo($qb), $skipRootEntity);
        $this->queryModifier3->expects(self::exactly(2))
            ->method('setOptions')
            ->withConsecutive([$options], [null]);
        $this->queryModifier3->expects(self::once())
            ->method('modify')
            ->with(self::identicalTo($qb), $skipRootEntity);

        $this->registry->modifyQuery($qb, $skipRootEntity, new RequestType(['rest', 'json_api']), $options);
    }

    public function testShouldSkipNotSuitableQueryModifiers()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $skipRootEntity = true;

        $this->queryModifier1->expects(self::never())
            ->method('modify');
        $this->queryModifier2->expects(self::once())
            ->method('modify')
            ->with(self::identicalTo($qb), $skipRootEntity);
        $this->queryModifier3->expects(self::never())
            ->method('modify');

        $this->registry->modifyQuery($qb, $skipRootEntity, new RequestType(['rest']));
    }
}
