<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Twig;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Twig\ScopeExtension;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class ScopeExtensionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PropertyAccessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $propertyAccessor;

    /**
     * @var ScopeExtension
     */
    protected $scopeExtension;

    protected function setUp()
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->scopeExtension = new ScopeExtension($this->propertyAccessor);
    }

    public function testGetFunctions()
    {
        $functions = $this->scopeExtension->getFunctions();
        $this->assertCount(1, $functions);
        $this->assertInstanceOf(\Twig_SimpleFunction::class, reset($functions));
    }

    public function testIsScopesEmptyWithMoreThanOneScope()
    {
        $scopeEntities = [];
        /** @var Collection|\PHPUnit\Framework\MockObject\MockObject $scopes **/
        $scopes = $this->createMock(Collection::class);
        $scopes
            ->expects($this->once())
            ->method('count')
            ->willReturn(2);

        $this->propertyAccessor
            ->expects($this->never())
            ->method('getValue');

        $this->assertFalse($this->scopeExtension->isScopesEmpty($scopeEntities, $scopes));
    }

    public function testIsScopesEmptyWithOneNotEmptyScope()
    {
        $scopeEntities = ['firstField' => 'FirstClass', 'secondField' => 'SecondClass'];
        /** @var Collection|\PHPUnit\Framework\MockObject\MockObject $scopes **/
        $scopes = $this->createMock(Collection::class);
        $scopes
            ->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $scope = new Scope();
        $scopes
            ->expects($this->once())
            ->method('first')
            ->willReturn($scope);

        $this->propertyAccessor
            ->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive([$scope, 'firstField'], [$scope, 'secondField'])
            ->willReturnOnConsecutiveCalls(null, new \stdClass());

        $this->assertFalse($this->scopeExtension->isScopesEmpty($scopeEntities, $scopes));
    }

    public function testIsScopesEmptyWithOneEmptyScope()
    {
        $scopeEntities = ['firstField' => 'FirstClass', 'secondField' => 'SecondClass'];
        /** @var Collection|\PHPUnit\Framework\MockObject\MockObject $scopes * */
        $scopes = $this->createMock(Collection::class);
        $scopes
            ->expects($this->once())
            ->method('count')
            ->willReturn(1);

        $scope = new Scope();
        $scopes
            ->expects($this->once())
            ->method('first')
            ->willReturn($scope);

        $this->propertyAccessor
            ->expects($this->exactly(2))
            ->method('getValue')
            ->withConsecutive([$scope, 'firstField'], [$scope, 'secondField'])
            ->willReturn(null);

        $this->assertTrue($this->scopeExtension->isScopesEmpty($scopeEntities, $scopes));
    }

    public function testGetName()
    {
        $this->assertEquals('oro_scope', $this->scopeExtension->getName());
    }
}
