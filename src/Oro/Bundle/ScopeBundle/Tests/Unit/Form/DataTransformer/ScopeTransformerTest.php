<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ScopeBundle\Form\DataTransformer\ScopeTransformer;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Component\Testing\Unit\EntityTrait;

class ScopeTransformerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const SCOPE_TYPE = 'test_scope_type';

    /** @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $scopeManager;

    /** @var ScopeTransformer */
    private $transformer;

    protected function setUp(): void
    {
        $this->scopeManager = $this->createMock(ScopeManager::class);

        $this->transformer = new ScopeTransformer($this->scopeManager, self::SCOPE_TYPE);
    }

    public function testTransform()
    {
        $scopeValue = new \stdClass();
        $scope = new StubScope();
        $scope->setScopeField($scopeValue);

        $this->scopeManager->expects($this->once())
            ->method('getScopeEntities')
            ->with(self::SCOPE_TYPE)
            ->willReturn(['scopeField' => \stdClass::class]);

        $result = $this->transformer->transform($scope);

        $this->assertEquals(['scopeField' => $scopeValue], $result);
    }

    public function testTransformForNull()
    {
        $this->scopeManager->expects($this->never())
            ->method('getScopeEntities');

        $this->assertNull($this->transformer->transform(null));
    }

    public function testReverseTransform()
    {
        $scopeValue = new \stdClass();
        $scope = new StubScope();
        $scope->setScopeField($scopeValue);

        $value = ['scopeField' => $scopeValue];

        $this->scopeManager->expects($this->once())
            ->method('findOrCreate')
            ->with(self::SCOPE_TYPE, $value, false)
            ->willReturn($scope);

        $this->transformer = new ScopeTransformer($this->scopeManager, self::SCOPE_TYPE);

        $result = $this->transformer->reverseTransform($value);

        $this->assertEquals($scope, $result);
    }

    public function testReverseTransformForNull()
    {
        $this->scopeManager->expects($this->never())
            ->method('findOrCreate');

        $this->assertNull($this->transformer->reverseTransform(null));
    }
}
