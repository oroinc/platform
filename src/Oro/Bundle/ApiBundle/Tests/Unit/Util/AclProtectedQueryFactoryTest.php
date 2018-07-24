<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\AclProtectedQueryFactory;
use Oro\Bundle\ApiBundle\Util\AclProtectedQueryResolver;
use Oro\Bundle\ApiBundle\Util\QueryModifierRegistry;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Component\EntitySerializer\QueryResolver;

class AclProtectedQueryFactoryTest extends OrmRelatedTestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryResolver */
    private $queryResolver;

    /** @var \PHPUnit\Framework\MockObject\MockObject|QueryModifierRegistry */
    private $queryModifier;

    /** @var AclProtectedQueryFactory */
    private $queryFactory;

    protected function setUp()
    {
        parent::setUp();

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->queryResolver = $this->createMock(QueryResolver::class);
        $this->queryModifier = $this->createMock(QueryModifierRegistry::class);

        $this->queryFactory = new AclProtectedQueryFactory(
            $doctrineHelper,
            $this->queryResolver,
            $this->queryModifier
        );
    }

    public function testGetQuery()
    {
        $requestType = new RequestType(['rest']);
        $qb = $this->createMock(QueryBuilder::class);
        $query = new Query($this->em);

        $config = new EntityConfig();

        $qb->expects(self::once())
            ->method('getRootAliases');
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $this->queryModifier->expects(self::once())
            ->method('modifyQuery')
            ->with(self::identicalTo($qb), false, $requestType);

        $this->queryResolver->expects(self::once())
            ->method('resolveQuery')
            ->with(self::identicalTo($query), self::identicalTo($config));

        $this->queryFactory->setRequestType($requestType);
        self::assertSame(
            $query,
            $this->queryFactory->getQuery($qb, $config)
        );
    }

    public function testGetQueryWhenRequestTypeIsNotSet()
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = new Query($this->em);

        $config = new EntityConfig();

        $qb->expects(self::never())
            ->method('getRootAliases');
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $this->queryModifier->expects(self::never())
            ->method('modifyQuery');

        $this->queryResolver->expects(self::once())
            ->method('resolveQuery')
            ->with(self::identicalTo($query), self::identicalTo($config));

        self::assertSame(
            $query,
            $this->queryFactory->getQuery($qb, $config)
        );
    }

    public function testGetQueryWhenAclForRootEntityShouldBeSkipped()
    {
        $requestType = new RequestType(['rest']);
        $qb = $this->createMock(QueryBuilder::class);
        $query = new Query($this->em);

        $config = new EntityConfig();
        $config->set(AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY, true);

        $qb->expects(self::once())
            ->method('getRootAliases');
        $qb->expects(self::once())
            ->method('getQuery')
            ->willReturn($query);
        $this->queryModifier->expects(self::once())
            ->method('modifyQuery')
            ->with(self::identicalTo($qb), true, $requestType);

        $this->queryResolver->expects(self::once())
            ->method('resolveQuery')
            ->with(self::identicalTo($query), self::identicalTo($config));

        $this->queryFactory->setRequestType($requestType);
        self::assertSame(
            $query,
            $this->queryFactory->getQuery($qb, $config)
        );
    }
}
