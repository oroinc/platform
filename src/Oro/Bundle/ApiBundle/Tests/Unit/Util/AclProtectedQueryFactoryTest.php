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

    protected function setUp(): void
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

    public function testRequestTypeGetterAndSetter(): void
    {
        self::assertNull($this->queryFactory->getRequestType());

        $requestType = new RequestType([]);
        $this->queryFactory->setRequestType($requestType);
        self::assertSame($requestType, $this->queryFactory->getRequestType());

        $this->queryFactory->setRequestType(null);
        self::assertNull($this->queryFactory->getRequestType());
    }

    public function testOptionsGetterAndSetter(): void
    {
        self::assertNull($this->queryFactory->getOptions());

        $options = ['option_1' => 'option_1_val'];
        $this->queryFactory->setOptions($options);
        self::assertSame($options, $this->queryFactory->getOptions());

        $this->queryFactory->setOptions(null);
        self::assertNull($this->queryFactory->getOptions());
    }

    public function testGetQuery(): void
    {
        $requestType = new RequestType(['rest']);
        $qb = $this->createMock(QueryBuilder::class);
        $query = new Query($this->em);

        $config = new EntityConfig();
        $config->set('option_1', 'option_1_initial_val');

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
            ->with(self::identicalTo($query), self::identicalTo($config))
            ->willReturnCallback(function (Query $query, EntityConfig $config) {
                self::assertEquals('option_1_val', $config->get('option_1'));
                self::assertEquals('option_2_val', $config->get('option_2'));
            });

        $this->queryFactory->setRequestType($requestType);
        $this->queryFactory->setOptions(['option_1' => 'option_1_val', 'option_2' => 'option_2_val']);
        self::assertSame(
            $query,
            $this->queryFactory->getQuery($qb, $config)
        );
        self::assertTrue($config->has('option_1'));
        self::assertEquals('option_1_initial_val', $config->get('option_1'));
        self::assertFalse($config->has('option_2'));
    }

    public function testGetQueryWhenRequestTypeIsNotSet(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The query factory was not initialized.');

        $this->queryFactory->getQuery(
            $this->createMock(QueryBuilder::class),
            new EntityConfig()
        );
    }

    public function testGetQueryWhenAclForRootEntityShouldBeSkipped(): void
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

    public function testGetQueryWhenAclForRootEntityShouldBeSkippedDueToOptions(): void
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
            ->with(self::identicalTo($qb), true, $requestType);

        $this->queryResolver->expects(self::once())
            ->method('resolveQuery')
            ->with(self::identicalTo($query), self::identicalTo($config));

        $this->queryFactory->setRequestType($requestType);
        $this->queryFactory->setOptions([AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY => true]);
        self::assertSame(
            $query,
            $this->queryFactory->getQuery($qb, $config)
        );
        self::assertFalse($config->has(AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY));
    }
}
