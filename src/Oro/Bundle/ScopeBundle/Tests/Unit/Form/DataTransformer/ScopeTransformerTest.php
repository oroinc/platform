<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\ScopeBundle\Form\DataTransformer\ScopeTransformer;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Component\Testing\Unit\EntityTrait;

class ScopeTransformerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const SCOPE_TYPE = 'test_scope_type';

    /**
     * @var ScopeManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $scopeManager;

    /**
     * @var ScopeTransformer
     */
    protected $transformer;

    public function setUp()
    {
        $this->scopeManager = $this->getMockBuilder(ScopeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->transformer = new ScopeTransformer($this->scopeManager, self::SCOPE_TYPE);
    }

    public function testTransform()
    {
        $accountId = 42;

        $scope = new \stdClass();
        $scope->account = $accountId;

        $this->scopeManager
            ->expects($this->once())
            ->method('getScopeEntities')
            ->with(self::SCOPE_TYPE)
            ->willReturn([
                'account' => new \stdClass()
            ]);

        $result = $this->transformer->transform($scope);

        $this->assertEquals(['account' => $accountId], $result);
    }

    public function testTransformForNull()
    {
        $this->scopeManager
            ->expects($this->never())
            ->method('getScopeEntities');

        $this->assertNull($this->transformer->transform(null));
    }

    public function testReverseTransform()
    {
        $accountId = 42;
        $scope = new \stdClass();
        $scope->account = $accountId;

        $value = ['account' => $accountId];

        $this->scopeManager
            ->expects($this->once())
            ->method('findOrCreate')
            ->with(self::SCOPE_TYPE, $value, false)
            ->willReturn($scope);

        $this->transformer = new ScopeTransformer($this->scopeManager, self::SCOPE_TYPE);

        $result = $this->transformer->reverseTransform($value);

        $this->assertEquals($scope, $result);
    }

    public function testReverseTransformForNull()
    {
        $this->scopeManager
            ->expects($this->never())
            ->method('findOrCreate');

        $this->assertNull($this->transformer->reverseTransform(null));
    }
}
