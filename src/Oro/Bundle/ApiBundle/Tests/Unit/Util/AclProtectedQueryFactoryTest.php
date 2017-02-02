<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Doctrine\ORM\Query;

use Oro\Component\DoctrineUtils\ORM\QueryHintResolverInterface;
use Oro\Component\EntitySerializer\DoctrineHelper;
use Oro\Component\EntitySerializer\EntityConfig;
use Oro\Bundle\ApiBundle\Tests\Unit\OrmRelatedTestCase;
use Oro\Bundle\ApiBundle\Util\AclProtectedQueryFactory;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class AclProtectedQueryFactoryTest extends OrmRelatedTestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $queryHintResolver;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $aclHelper;

    /** @var AclProtectedQueryFactory */
    protected $queryFactory;

    protected function setUp()
    {
        parent::setUp();

        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->queryHintResolver = $this->createMock(QueryHintResolverInterface::class);
        $this->aclHelper = $this->getMockBuilder(AclHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->queryFactory = new AclProtectedQueryFactory(
            $doctrineHelper,
            $this->queryHintResolver
        );
        $this->queryFactory->setAclHelper($this->aclHelper);
    }

    public function testGetQuery()
    {
        $qb = $this->getQueryBuilderMock();
        $query = new Query($this->em);

        $config = new EntityConfig();
        $config->addHint('testHint');

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
        $qb = $this->getQueryBuilderMock();
        $query = new Query($this->em);

        $config = new EntityConfig();
        $config->set(AclProtectedQueryFactory::SKIP_ACL_FOR_ROOT_ENTITY, true);

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

        self::assertSame(
            $query,
            $this->queryFactory->getQuery($qb, $config)
        );
    }
}
