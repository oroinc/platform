<?php

namespace Oro\Bundle\DashboardBundle\Tests\Unit\Provider\Converters;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\DashboardBundle\Provider\Converters\WidgetEntitySelectConverter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\UserBundle\Entity\User;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class WidgetEntitySelectConverterTest extends TestCase
{
    private AclHelper&MockObject $aclHelper;
    private DoctrineHelper&MockObject $doctrineHelper;
    private WidgetEntitySelectConverter $converter;

    #[\Override]
    protected function setUp(): void
    {
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $entityNameResolver->expects(self::any())
            ->method('getName')
            ->willReturnCallback(function (User $object) {
                return $object->getFirstName() . ' ' . $object->getLastName();
            });

        $this->converter = new WidgetEntitySelectConverter(
            $this->aclHelper,
            $entityNameResolver,
            $this->doctrineHelper,
            User::class
        );
    }

    public function testGetViewValueWithoutEntities(): void
    {
        self::assertNull($this->converter->getViewValue([]));
    }

    public function testGetViewValueWithOneEntity(): void
    {
        $user1 = new User();
        $user1->setFirstName('Joe');
        $user1->setLastName('Doe');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([$user1]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.id IN (:ids)')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('ids')
            ->willReturnSelf();

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->willReturn($query);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(User::class)
            ->willReturn('id');
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(User::class, 'e')
            ->willReturn($queryBuilder);

        self::assertEquals('Joe Doe', $this->converter->getViewValue([1, 2]));
    }

    public function testGetViewValueWithSeveralEntities(): void
    {
        $user1 = new User();
        $user1->setFirstName('Joe');
        $user1->setLastName('Doe');

        $user2 = new User();
        $user2->setFirstName('Joyce');
        $user2->setLastName('Palmer');

        $query = $this->createMock(AbstractQuery::class);
        $query->expects(self::once())
            ->method('getResult')
            ->willReturn([$user1, $user2]);

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $queryBuilder->expects(self::once())
            ->method('where')
            ->with('e.id IN (:ids)')
            ->willReturnSelf();
        $queryBuilder->expects(self::once())
            ->method('setParameter')
            ->with('ids')
            ->willReturnSelf();

        $this->aclHelper->expects(self::once())
            ->method('apply')
            ->willReturn($query);

        $this->doctrineHelper->expects(self::once())
            ->method('getSingleEntityIdentifierFieldName')
            ->with(User::class)
            ->willReturn('id');
        $this->doctrineHelper->expects(self::once())
            ->method('createQueryBuilder')
            ->with(User::class, 'e')
            ->willReturn($queryBuilder);

        self::assertEquals('Joe Doe; Joyce Palmer', $this->converter->getViewValue([1, 2]));
    }
}
