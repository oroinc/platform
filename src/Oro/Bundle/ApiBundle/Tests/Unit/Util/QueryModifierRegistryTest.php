<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Util\QueryModifierInterface;
use Oro\Bundle\ApiBundle\Util\QueryModifierRegistry;
use Oro\Bundle\ApiBundle\Util\RequestExpressionMatcher;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QueryModifierRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|ContainerInterface */
    private $container;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryModifierInterface */
    private $queryModifier1;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryModifierInterface */
    private $queryModifier2;

    /** @var QueryModifierRegistry */
    private $registry;

    protected function setUp()
    {
        $this->queryModifier1 = $this->createMock(QueryModifierInterface::class);
        $this->queryModifier2 = $this->createMock(QueryModifierInterface::class);

        $this->container = TestContainerBuilder::create()
            ->add('query_modifier1', $this->queryModifier1)
            ->add('query_modifier2', $this->queryModifier2)
            ->getContainer($this);

        $this->registry = new QueryModifierRegistry(
            [
                ['query_modifier1', 'json_api'],
                ['query_modifier2', null]
            ],
            $this->container,
            new RequestExpressionMatcher()
        );
    }

    public function testFieldsShouldExecuteAllSuitableQueryModifiers()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $skipRootEntity = true;

        $this->queryModifier1->expects(self::once())
            ->method('modify')
            ->with(self::identicalTo($qb), $skipRootEntity);
        $this->queryModifier2->expects(self::once())
            ->method('modify')
            ->with(self::identicalTo($qb), $skipRootEntity);

        $this->registry->modifyQuery($qb, $skipRootEntity, new RequestType(['rest', 'json_api']));
    }

    public function testFieldsShouldSkipNotSuitableQueryModifiers()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $skipRootEntity = true;

        $this->queryModifier1->expects(self::never())
            ->method('modify');
        $this->queryModifier2->expects(self::once())
            ->method('modify')
            ->with(self::identicalTo($qb), $skipRootEntity);

        $this->registry->modifyQuery($qb, $skipRootEntity, new RequestType(['rest']));
    }
}
