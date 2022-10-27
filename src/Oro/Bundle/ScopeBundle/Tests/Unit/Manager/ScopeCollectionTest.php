<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Manager;

use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeCollection;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeCollectionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ScopeCollection */
    private $collection;

    protected function setUp(): void
    {
        $this->collection = new ScopeCollection();
    }

    public function testAddAndRemove()
    {
        $scope1 = new Scope();
        $scope2 = new Scope();
        $scope3 = new Scope();
        $scopeCriteria1 = new ScopeCriteria(['test1' => 1], $this->createMock(ClassMetadataFactory::class));
        $scopeCriteria2 = new ScopeCriteria(['test2' => 1], $this->createMock(ClassMetadataFactory::class));

        $this->collection->add($scope1, $scopeCriteria1);
        $this->assertSame($scope1, $this->collection->get($scopeCriteria1));
        $this->assertSame([$scope1], $this->collection->getAll());

        $this->collection->add($scope1, $scopeCriteria1);
        $this->assertSame($scope1, $this->collection->get($scopeCriteria1));
        $this->assertSame([$scope1], $this->collection->getAll());

        $this->collection->add($scope2, $scopeCriteria2);
        $this->assertSame($scope2, $this->collection->get($scopeCriteria2));
        $this->assertSame([$scope1, $scope2], $this->collection->getAll());

        $this->collection->add($scope3, $scopeCriteria1);
        $this->assertSame($scope3, $this->collection->get($scopeCriteria1));
        $this->assertSame([$scope3, $scope2], $this->collection->getAll());

        $this->collection->remove($scopeCriteria1);
        $this->assertNull($this->collection->get($scopeCriteria1));
        $this->assertSame([$scope2], $this->collection->getAll());
    }

    public function testIsEmpty()
    {
        $scope = new Scope();
        $scopeCriteria = new ScopeCriteria(['test' => 1], $this->createMock(ClassMetadataFactory::class));

        $this->assertTrue($this->collection->isEmpty());

        $this->collection->add($scope, $scopeCriteria);
        $this->assertFalse($this->collection->isEmpty());
    }

    public function testClear()
    {
        $scope = new Scope();
        $scopeCriteria = new ScopeCriteria(['test' => 1], $this->createMock(ClassMetadataFactory::class));

        $this->collection->add($scope, $scopeCriteria);
        $this->assertSame([$scope], $this->collection->getAll());

        $this->collection->clear();
        $this->assertSame([], $this->collection->getAll());
    }
}
