<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Twig\ScopeExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ScopeExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var PropertyAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyAccessor;

    /** @var ScopeExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);

        $this->extension = new ScopeExtension($this->propertyAccessor);
    }

    public function testIsScopesEmptyWithMoreThanOneScope()
    {
        $scopeEntities = [];
        $scopes = $this->createMock(Collection::class);
        $scopes->expects($this->once())
            ->method('count')
            ->willReturn(2);

        $this->propertyAccessor->expects($this->never())
            ->method('getValue');

        $this->assertFalse(
            self::callTwigFunction($this->extension, 'oro_scope_is_empty', [$scopeEntities, $scopes])
        );
    }

    public function testIsScopesEmptyWithOneNotEmptyScope()
    {
        $scopeEntities = ['firstField' => 'FirstClass', 'secondField' => 'SecondClass'];
        $scopes = $this->createMock(Collection::class);
        $scopes->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $scope = new Scope();
        $scopes->expects($this->once())
            ->method('first')
            ->willReturn($scope);

        $this->propertyAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive([$scope, 'firstField'], [$scope, 'secondField'])
            ->willReturnOnConsecutiveCalls(null, new \stdClass());

        $this->assertFalse(
            self::callTwigFunction($this->extension, 'oro_scope_is_empty', [$scopeEntities, $scopes])
        );
    }

    public function testIsScopesEmptyWithOneEmptyScope()
    {
        $scopeEntities = ['firstField' => 'FirstClass', 'secondField' => 'SecondClass'];
        $scopes = $this->createMock(Collection::class);
        $scopes->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $scope = new Scope();
        $scopes->expects($this->once())
            ->method('first')
            ->willReturn($scope);

        $this->propertyAccessor->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive([$scope, 'firstField'], [$scope, 'secondField'])
            ->willReturn(null);

        $this->assertTrue(
            self::callTwigFunction($this->extension, 'oro_scope_is_empty', [$scopeEntities, $scopes])
        );
    }
}
