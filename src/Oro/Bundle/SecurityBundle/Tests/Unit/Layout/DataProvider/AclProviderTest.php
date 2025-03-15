<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\Layout\DataProvider;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Layout\DataProvider\AclProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AclProviderTest extends TestCase
{
    private AuthorizationCheckerInterface&MockObject $authorizationChecker;
    private ManagerRegistry&MockObject $doctrine;
    private AclProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->provider = new AclProvider(
            $this->authorizationChecker,
            $this->doctrine
        );
    }

    public function testIsGrantedByAclAttributeId(): void
    {
        $attributes = 'acme_product_view';
        $expectedResult = true;

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, null)
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->provider->isGranted($attributes));
    }

    /**
     * @dataProvider getIsGrantedByObjectIdentityDescriptorDataProvider
     */
    public function testIsGrantedByObjectIdentityDescriptor(
        array|string $attributes,
        string $entity,
        array $isGrantedCalls,
        bool $expectedResult
    ): void {
        $this->authorizationChecker->expects(self::exactly(count($isGrantedCalls)))
            ->method('isGranted')
            ->willReturnMap($isGrantedCalls);

        self::assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function getIsGrantedByObjectIdentityDescriptorDataProvider(): array
    {
        return [
            [
                'attributes' => 'VIEW',
                'entity' => 'entity:Acme/DemoBundle/Entity/AcmeEntity',
                'isGrantedCalls' => [
                    ['VIEW', 'entity:Acme/DemoBundle/Entity/AcmeEntity', true],
                ],
                'expectedResult' => true,
            ],
            [
                'attributes' => ['VIEW', 'EDIT'],
                'entity' => 'entity:Acme/DemoBundle/Entity/AcmeEntity',
                'isGrantedCalls' => [
                    ['VIEW', 'entity:Acme/DemoBundle/Entity/AcmeEntity', true],
                    ['EDIT', 'entity:Acme/DemoBundle/Entity/AcmeEntity', true]
                ],
                'expectedResult' => true,
            ],
            [
                'attributes' => ['VIEW', 'EDIT'],
                'entity' => 'entity:Acme/DemoBundle/Entity/AcmeEntity',
                'isGrantedCalls' => [
                    ['VIEW', 'entity:Acme/DemoBundle/Entity/AcmeEntity', true],
                    ['EDIT', 'entity:Acme/DemoBundle/Entity/AcmeEntity', false]
                ],
                'expectedResult' => false,
            ],
        ];
    }

    public function testIsGrantedForNotEntityObject(): void
    {
        $attributes = 'VIEW';
        $entity = new \stdClass();
        $expectedResult = true;

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($entity))
            ->willReturn(null);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, $this->identicalTo($entity))
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function testIsGrantedForExistingEntity(): void
    {
        $attributes = 'VIEW';
        $entity = new \stdClass();
        $expectedResult = true;

        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('isScheduledForInsert')
            ->with($entity)
            ->willReturn(false);
        $uow->expects(self::once())
            ->method('isInIdentityMap')
            ->with($entity)
            ->willReturn(true);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($entity))
            ->willReturn($em);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, $this->identicalTo($entity))
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function testIsGrantedForNewEntity(): void
    {
        $attributes = 'VIEW';
        $entity = new \stdClass();
        $expectedResult = true;

        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('isScheduledForInsert')
            ->with($entity)
            ->willReturn(true);
        $uow->expects(self::never())
            ->method('isInIdentityMap');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($entity))
            ->willReturn($em);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, 'entity:' . ClassUtils::getRealClass($entity))
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function testIsGrantedForEntityWhichIsNotInUowYet(): void
    {
        $attributes = 'VIEW';
        $entity = new \stdClass();
        $expectedResult = true;

        $em = $this->createMock(EntityManagerInterface::class);
        $uow = $this->createMock(UnitOfWork::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('isScheduledForInsert')
            ->with($entity)
            ->willReturn(false);
        $uow->expects(self::once())
            ->method('isInIdentityMap')
            ->with($entity)
            ->willReturn(false);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($entity))
            ->willReturn($em);

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, 'entity:' . ClassUtils::getRealClass($entity))
            ->willReturn($expectedResult);

        self::assertEquals($expectedResult, $this->provider->isGranted($attributes, $entity));
    }

    public function testIsGrantedHasNoUser(): void
    {
        $attributes = 'acme_product_view';
        $expectedResult = false;

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($attributes, null)
            ->willReturn($expectedResult);

        self::assertFalse($this->provider->isGranted($attributes));
    }
}
