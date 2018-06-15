<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\Query;
use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\AclProtectedQueryFactory;
use Oro\Bundle\ApiBundle\Util\QueryModifierRegistry;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\EntityConfig;

class AclProtectedQueryFactoryTest extends OrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|QueryHintResolverInterface */
    private $queryHintResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject|AclHelper */
    private $aclHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|QueryModifierRegistry */
    private $queryModifier;

    /** @var AclProtectedQueryFactory */
    private $queryFactory;

    protected function setUp()
    {
        parent::setUp();

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->queryHintResolver = $this->createMock(QueryHintResolverInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->queryModifier = $this->createMock(QueryModifierRegistry::class);

        $this->queryFactory = new AclProtectedQueryFactory(
            $doctrineHelper,
            $this->queryHintResolver
        );
        $this->queryFactory->setAclHelper($this->aclHelper);
        $this->queryFactory->setQueryModifier($this->queryModifier);
    }

    public function testGetQuery()
    {
        $requestType = new RequestType(['rest']);
        $qb = $this->getQueryBuilderMock();
        $query = new Query($this->em);

        $config = new EntityConfig();
        $config->addHint('testHint');

        $qb->expects(self::once())
            ->method('getRootAliases');
        $this->queryModifier->expects(self::once())
            ->method('modifyQuery')
            ->with(self::identicalTo($qb), false, $requestType);

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($qb))
            ->willReturn($query);

        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), $config->getHints());

        $this->queryFactory->setRequestType($requestType);
        self::assertSame(
            $query,
            $this->queryFactory->getQuery($qb, $config)
        );
    }

    public function testGetQueryWhenRequestTypeIsNotSet()
    {
        $qb = $this->getQueryBuilderMock();
        $query = new Query($this->em);

        $config = new EntityConfig();
        $config->addHint('testHint');

        $qb->expects(self::never())
            ->method('getRootAliases');
        $this->queryModifier->expects(self::never())
            ->method('modifyQuery');

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->with(self::identicalTo($qb))
            ->willReturn($query);

        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), $config->getHints());

        self::assertSame(
            $query,
            $this->queryFactory->getQuery($qb, $config)
        );
    }

    public function testGetQueryWhenAclForRootEntityShouldBeSkipped()
    {
        $requestType = new RequestType(['rest']);
        $qb = $this->getQueryBuilderMock();
        $query = new Query($this->em);

        $config = new EntityConfig();
        $config->set(AclProtectedQueryFactory::SKIP_ACL_FOR_ROOT_ENTITY, true);

        $qb->expects(self::once())
            ->method('getRootAliases');
        $this->queryModifier->expects(self::once())
            ->method('modifyQuery')
            ->with(self::identicalTo($qb), true, $requestType);

        $this->aclHelper->expects(self::at(0))
            ->method('setCheckRootEntity')
            ->with(false);
        $this->aclHelper->expects(self::at(1))
            ->method('apply')
            ->with(self::identicalTo($qb))
            ->willReturn($query);
        $this->aclHelper->expects(self::at(2))
            ->method('setCheckRootEntity')
            ->with(true);

        $this->queryHintResolver->expects(self::once())
            ->method('resolveHints')
            ->with(self::identicalTo($query), $config->getHints());

        $this->queryFactory->setRequestType($requestType);
        self::assertSame(
            $query,
            $this->queryFactory->getQuery($qb, $config)
        );
    }
}
