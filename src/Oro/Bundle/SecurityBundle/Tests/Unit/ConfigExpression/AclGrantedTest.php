<?php

namespace Oro\Bundle\SecurityBundle\Tests\Unit\ConfigExpression;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ConfigExpression\AclGranted;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Security\Acl\Util\ClassUtils;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class AclGrantedTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrine;

    /** @var AclGranted */
    private $condition;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);

        $this->condition = new AclGranted(
            $this->authorizationChecker,
            $this->tokenAccessor,
            $this->doctrine
        );
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testEvaluateByAclAnnotationId(): void
    {
        $options = ['acme_product_view'];
        $context = [];
        $expectedResult = true;

        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn(new \stdClass());

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($options[0], null)
            ->willReturn($expectedResult);

        self::assertSame($this->condition, $this->condition->initialize($options));
        self::assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    /**
     * @dataProvider getEvaluateByObjectIdentityDescriptorDataProvider
     */
    public function testEvaluateByObjectIdentityDescriptor(
        array $options,
        array $isGrantedCalls,
        bool $expectedResult
    ): void {
        $context = [];

        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn(new \stdClass());

        $this->authorizationChecker->expects(self::exactly(count($isGrantedCalls)))
            ->method('isGranted')
            ->willReturnMap($isGrantedCalls);

        self::assertSame($this->condition, $this->condition->initialize($options));
        self::assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function getEvaluateByObjectIdentityDescriptorDataProvider(): array
    {
        return [
            [
                'options' => ['VIEW', 'entity:Acme/DemoBundle/Entity/AcmeEntity'],
                'isGrantedCalls' => [
                    ['VIEW', 'entity:Acme/DemoBundle/Entity/AcmeEntity', true],
                ],
                'expectedResult' => true,
            ],
            [
                'options' => [['VIEW', 'EDIT'], 'entity:Acme/DemoBundle/Entity/AcmeEntity'],
                'isGrantedCalls' => [
                    ['VIEW', 'entity:Acme/DemoBundle/Entity/AcmeEntity', true],
                    ['EDIT', 'entity:Acme/DemoBundle/Entity/AcmeEntity', true]
                ],
                'expectedResult' => true,
            ],
            [
                'options' => [['VIEW', 'EDIT'], 'entity:Acme/DemoBundle/Entity/AcmeEntity'],
                'isGrantedCalls' => [
                    ['VIEW', 'entity:Acme/DemoBundle/Entity/AcmeEntity', true],
                    ['EDIT', 'entity:Acme/DemoBundle/Entity/AcmeEntity', false]
                ],
                'expectedResult' => false,
            ],
        ];
    }

    public function testEvaluateForNotEntityObject(): void
    {
        $options = ['VIEW', new \stdClass()];
        $context = [];
        $expectedResult = true;

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($options[1]))
            ->willReturn(null);

        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn(new \stdClass());

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($options[0], $this->identicalTo($options[1]))
            ->willReturn($expectedResult);

        self::assertSame($this->condition, $this->condition->initialize($options));
        self::assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function testEvaluateForExistingEntity(): void
    {
        $options = ['VIEW', new \stdClass()];
        $context = [];
        $expectedResult = true;

        $em = $this->createMock(EntityManager::class);
        $uow = $this->createMock(UnitOfWork::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('isScheduledForInsert')
            ->with($options[1])
            ->willReturn(false);
        $uow->expects(self::once())
            ->method('isInIdentityMap')
            ->with($options[1])
            ->willReturn(true);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($options[1]))
            ->willReturn($em);

        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn(new \stdClass());

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($options[0], $this->identicalTo($options[1]))
            ->willReturn($expectedResult);

        self::assertSame($this->condition, $this->condition->initialize($options));
        self::assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function testEvaluateForNewEntity(): void
    {
        $options = ['VIEW', new \stdClass()];
        $context = [];
        $expectedResult = true;

        $em = $this->createMock(EntityManager::class);
        $uow = $this->createMock(UnitOfWork::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('isScheduledForInsert')
            ->with($options[1])
            ->willReturn(true);
        $uow->expects(self::never())
            ->method('isInIdentityMap');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($options[1]))
            ->willReturn($em);

        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn(new \stdClass());

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($options[0], 'entity:' . ClassUtils::getRealClass($options[1]))
            ->willReturn($expectedResult);

        self::assertSame($this->condition, $this->condition->initialize($options));
        self::assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function testEvaluateForEntityWhichIsNotInUowYet(): void
    {
        $options = ['VIEW', new \stdClass()];
        $context = [];
        $expectedResult = true;

        $em = $this->createMock(EntityManager::class);
        $uow = $this->createMock(UnitOfWork::class);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('isScheduledForInsert')
            ->with($options[1])
            ->willReturn(false);
        $uow->expects(self::once())
            ->method('isInIdentityMap')
            ->with($options[1])
            ->willReturn(false);

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(ClassUtils::getRealClass($options[1]))
            ->willReturn($em);

        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn(new \stdClass());

        $this->authorizationChecker->expects(self::once())
            ->method('isGranted')
            ->with($options[0], 'entity:' . ClassUtils::getRealClass($options[1]))
            ->willReturn($expectedResult);

        self::assertSame($this->condition, $this->condition->initialize($options));
        self::assertEquals($expectedResult, $this->condition->evaluate($context));
    }

    public function testEvaluateHasNoUser(): void
    {
        $options = ['acme_product_view'];
        $context = [];

        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn(null);

        $this->authorizationChecker->expects(self::never())
            ->method('isGranted');

        self::assertSame($this->condition, $this->condition->initialize($options));
        self::assertFalse($this->condition->evaluate($context));
    }

    public function testInitializeFailsWhenEmptyOptions(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Options must have 1 or 2 elements, but 0 given.');

        $this->condition->initialize([]);
    }

    public function testInitializeFailsWhenEmptyAttributes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ACL attributes must not be empty.');

        $this->condition->initialize(['']);
    }

    public function testInitializeFailsWhenEmptyObject(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ACL object must not be empty.');

        $this->condition->initialize(['VIEW', '']);
    }

    /**
     * @dataProvider toArrayDataProvider
     */
    public function testToArray(array $options, ?string $message, array $expected): void
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->toArray();
        self::assertEquals($expected, $actual);
    }

    public function toArrayDataProvider(): array
    {
        return [
            [
                'options'  => ['acme_product_view'],
                'message'  => null,
                'expected' => [
                    '@acl' => [
                        'parameters' => [
                            'acme_product_view'
                        ]
                    ]
                ]
            ],
            [
                'options'  => ['VIEW', new PropertyPath('entity')],
                'message'  => 'Test',
                'expected' => [
                    '@acl' => [
                        'message'    => 'Test',
                        'parameters' => [
                            'VIEW',
                            '$entity'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider compileDataProvider
     */
    public function testCompile(array $options, ?string $message, string $expected): void
    {
        $this->condition->initialize($options);
        if ($message !== null) {
            $this->condition->setMessage($message);
        }
        $actual = $this->condition->compile('$factory');
        self::assertEquals($expected, $actual);
    }

    public function compileDataProvider(): array
    {
        return [
            [
                'options'  => ['acme_product_view'],
                'message'  => null,
                'expected' => '$factory->create(\'acl\', [\'acme_product_view\'])'
            ],
            [
                'options'  => ['VIEW', new PropertyPath('entity')],
                'message'  => 'Test',
                'expected' => '$factory->create(\'acl\', [\'VIEW\', '
                    . 'new \Oro\Component\ConfigExpression\CompiledPropertyPath(\'entity\', [\'entity\'], [false])'
                    . '])->setMessage(\'Test\')'
            ]
        ];
    }
}
