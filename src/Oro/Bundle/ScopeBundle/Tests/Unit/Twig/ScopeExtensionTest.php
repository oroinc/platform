<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Twig\ScopeExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ScopeExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private PropertyAccessorInterface|MockObject $propertyAccessor;

    private ScopeManager|MockObject $scopeManager;

    private ScopeExtension $extension;

    protected function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $this->extension = new ScopeExtension($this->propertyAccessor);
        $this->extension->setScopeManager($this->scopeManager);
    }

    public function testIsScopesEmptyWithMoreThanOneScope(): void
    {
        $scopeEntities = [];
        $scopes = $this->createMock(Collection::class);
        $scopes->expects(self::once())
            ->method('count')
            ->willReturn(2);

        $this->propertyAccessor->expects(self::never())
            ->method('getValue');

        self::assertFalse(
            self::callTwigFunction($this->extension, 'oro_scope_is_empty', [$scopeEntities, $scopes])
        );
    }

    public function testIsScopesEmptyWithOneNotEmptyScope(): void
    {
        $scopeEntities = ['firstField' => 'FirstClass', 'secondField' => 'SecondClass'];
        $scopes = $this->createMock(Collection::class);
        $scopes->expects(self::once())
            ->method('count')
            ->willReturn(1);

        $scope = new Scope();
        $scopes->expects(self::once())
            ->method('first')
            ->willReturn($scope);

        $this->propertyAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->withConsecutive([$scope, 'firstField'], [$scope, 'secondField'])
            ->willReturnOnConsecutiveCalls(null, new \stdClass());

        self::assertFalse(
            self::callTwigFunction($this->extension, 'oro_scope_is_empty', [$scopeEntities, $scopes])
        );
    }

    public function testIsScopesEmptyWithOneEmptyScope(): void
    {
        $scopeEntities = ['firstField' => 'FirstClass', 'secondField' => 'SecondClass'];
        $scopes = $this->createMock(Collection::class);
        $scopes->expects(self::once())
            ->method('count')
            ->willReturn(1);

        $scope = new Scope();
        $scopes->expects(self::once())
            ->method('first')
            ->willReturn($scope);

        $this->propertyAccessor->expects(self::exactly(2))
            ->method('getValue')
            ->withConsecutive([$scope, 'firstField'], [$scope, 'secondField'])
            ->willReturn(null);

        self::assertTrue(
            self::callTwigFunction($this->extension, 'oro_scope_is_empty', [$scopeEntities, $scopes])
        );
    }

    public function testGetScopeEntities(): void
    {
        $scopeEntities = ['firstField' => 'FirstClass', 'secondField' => 'SecondClass'];
        $scopeType = 'sample_type';

        $this->scopeManager
            ->expects(self::once())
            ->method('getScopeEntities')
            ->with($scopeType)
            ->willReturn($scopeEntities);

        self::assertSame(
            $scopeEntities,
            self::callTwigFunction($this->extension, 'oro_scope_entities', [$scopeType])
        );
    }
}
