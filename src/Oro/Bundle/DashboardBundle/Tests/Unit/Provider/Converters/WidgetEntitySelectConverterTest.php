<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetEntitySelectConverter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;

class WidgetEntitySelectConverterTest extends \PHPUnit\Framework\TestCase
{
    /** @var AbstractQuery|\PHPUnit\Framework\MockObject\MockObject */
    private $query;

    /** @var WidgetEntitySelectConverter */
    private $converter;

    protected function setUp(): void
    {
        $entityNameResolver = $this->createMock(EntityNameResolver::class);

        $entityNameResolver->expects($this->any())
            ->method('getName')
            ->willReturnCallback(function (User $object) {
                return $object->getFirstName() . ' ' . $object->getLastName();
            });

        $doctrineHelper = $this->createMock(DoctrineHelper::class);

        $entityManager = $this->createMock(EntityManager::class);

        $this->query = $this->createMock(AbstractQuery::class);

        $aclHelper = $this->createMock(AclHelper::class);

        $aclHelper->expects($this->any())
            ->method('apply')
            ->willReturn($this->query);

        $queryBuilder = $this->createMock(QueryBuilder::class);

        $expr = $this->createMock(Expr::class);
        $expr->expects($this->any())
            ->method('in')
            ->willReturnSelf();

        $queryBuilder->expects($this->any())
            ->method('expr')
            ->willReturn($expr);

        $repository = $this->createMock(EntityRepository::class);

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        $repository->expects($this->any())
            ->method('createQueryBuilder')
            ->with('e')
            ->willReturn($queryBuilder);

        $this->converter = new WidgetEntitySelectConverter(
            $aclHelper,
            $entityNameResolver,
            $doctrineHelper,
            $entityManager,
            User::class
        );
    }

    public function testGetViewValueWithoutEntities()
    {
        $this->assertNull($this->converter->getViewValue([]));
    }

    public function testGetViewValueWithOneEntity()
    {
        $user1 = new User();
        $user1->setFirstName('Joe');
        $user1->setLastName('Doe');

        $this->query->expects($this->any())
            ->method('getResult')
            ->willReturn([$user1]);

        $this->assertEquals('Joe Doe', $this->converter->getViewValue([1, 2]));
    }

    public function testGetViewValueWithSeveralEntities()
    {
        $user1 = new User();
        $user1->setFirstName('Joe');
        $user1->setLastName('Doe');

        $user2 = new User();
        $user2->setFirstName('Joyce');
        $user2->setLastName('Palmer');

        $this->query->expects($this->any())
            ->method('getResult')
            ->willReturn([$user1, $user2]);

        $this->assertEquals('Joe Doe; Joyce Palmer', $this->converter->getViewValue([1, 2]));
    }
}
