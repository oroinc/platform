<?php

declare(strict_types=1);

namespace Oro\Bundle\EntityBundle\Tests\Unit\Twig\Analyzer;

use Oro\Bundle\EntityBundle\Twig\Analyzer\ScopeTracker;
use PHPUnit\Framework\TestCase;

final class ScopeTrackerTest extends TestCase
{
    public function testConstructorSetsInitialVariablesInRootScope(): void
    {
        $tracker = new ScopeTracker(['user' => 'App\Entity\User', 'order' => 'App\Entity\Order']);

        self::assertSame('App\Entity\User', $tracker->getVariableType('user'));
        self::assertSame('App\Entity\Order', $tracker->getVariableType('order'));
    }

    public function testConstructorWithEmptyVariablesCreatesEmptyRootScope(): void
    {
        $tracker = new ScopeTracker([]);

        self::assertNull($tracker->getVariableType('user'));
        self::assertFalse($tracker->hasVariable('user'));
    }

    public function testReturnsAbsentIndicatorsForUnknownVariable(): void
    {
        $tracker = new ScopeTracker(['product' => 'App\Entity\Product']);

        self::assertNull($tracker->getVariableType('nonexistent'));
        self::assertFalse($tracker->hasVariable('nonexistent'));
    }

    public function testHasVariableReturnsTrueForVariableInRootScope(): void
    {
        $tracker = new ScopeTracker(['product' => 'App\Entity\Product']);

        self::assertTrue($tracker->hasVariable('product'));
    }

    public function testSetVariableSetsVariableInRootScopeWhenNoScopePushed(): void
    {
        $tracker = new ScopeTracker([]);
        $tracker->setVariable('item', 'App\Entity\Item');

        self::assertSame('App\Entity\Item', $tracker->getVariableType('item'));
    }

    public function testSetVariableOverwritesExistingVariableInCurrentScope(): void
    {
        $tracker = new ScopeTracker(['entity' => 'App\Entity\OldClass']);
        $tracker->setVariable('entity', 'App\Entity\NewClass');

        self::assertSame('App\Entity\NewClass', $tracker->getVariableType('entity'));
    }

    public function testSetVariableAfterPushScopeSetsVariableInInnerScope(): void
    {
        $tracker = new ScopeTracker([]);
        $tracker->pushScope();
        $tracker->setVariable('item', 'App\Entity\Item');

        self::assertSame('App\Entity\Item', $tracker->getVariableType('item'));
        self::assertTrue($tracker->hasVariable('item'));
    }

    public function testInnerScopeVariableShadowsOuterScopeVariable(): void
    {
        $tracker = new ScopeTracker(['entity' => 'App\Entity\Outer']);
        $tracker->pushScope();
        $tracker->setVariable('entity', 'App\Entity\Inner');

        self::assertSame('App\Entity\Inner', $tracker->getVariableType('entity'));
    }

    public function testPopScopeRemovesInnerScopeAndRestoresOuterScopeVariable(): void
    {
        $tracker = new ScopeTracker(['entity' => 'App\Entity\Outer']);
        $tracker->pushScope();
        $tracker->setVariable('entity', 'App\Entity\Inner');

        $tracker->popScope();

        self::assertSame('App\Entity\Outer', $tracker->getVariableType('entity'));
    }

    public function testPopScopeRemovesVariablesDefinedOnlyInInnerScope(): void
    {
        $tracker = new ScopeTracker([]);
        $tracker->pushScope();
        $tracker->setVariable('innerOnly', 'App\Entity\Item');

        $tracker->popScope();

        self::assertNull($tracker->getVariableType('innerOnly'));
        self::assertFalse($tracker->hasVariable('innerOnly'));
    }

    public function testPopScopeThrowsLogicExceptionWhenOnlyRootScopeRemains(): void
    {
        $tracker = new ScopeTracker([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot pop the root scope from ScopeTracker.');

        $tracker->popScope();
    }

    public function testPopScopeThrowsLogicExceptionAfterAllPushedScopesArePopped(): void
    {
        $tracker = new ScopeTracker([]);
        $tracker->pushScope();
        $tracker->popScope();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot pop the root scope from ScopeTracker.');

        $tracker->popScope();
    }

    public function testGetVariableTypeSearchesFromInnermostScopeOutward(): void
    {
        $tracker = new ScopeTracker(['x' => 'App\Entity\Root']);
        $tracker->pushScope();
        $tracker->setVariable('x', 'App\Entity\Level1');
        $tracker->pushScope();
        $tracker->setVariable('x', 'App\Entity\Level2');

        self::assertSame('App\Entity\Level2', $tracker->getVariableType('x'));
    }

    public function testGetVariableTypeFallsThroughToOuterScopeWhenNotSetInInnerScope(): void
    {
        $tracker = new ScopeTracker(['rootVar' => 'App\Entity\Root']);
        $tracker->pushScope();
        $tracker->pushScope();

        self::assertSame('App\Entity\Root', $tracker->getVariableType('rootVar'));
    }

    public function testVariablesFromAllOuterScopesAreVisibleInDeepNestedScope(): void
    {
        $tracker = new ScopeTracker(['root' => 'App\Entity\Root']);

        $tracker->pushScope();
        $tracker->setVariable('level1', 'App\Entity\Level1');

        $tracker->pushScope();
        $tracker->setVariable('level2', 'App\Entity\Level2');

        self::assertSame('App\Entity\Root', $tracker->getVariableType('root'));
        self::assertSame('App\Entity\Level1', $tracker->getVariableType('level1'));
        self::assertSame('App\Entity\Level2', $tracker->getVariableType('level2'));
    }

    public function testPushAndPopScopeCanBeCalledMultipleTimesSymmetrically(): void
    {
        $tracker = new ScopeTracker(['root' => 'App\Entity\Root']);

        $tracker->pushScope();
        $tracker->setVariable('a', 'App\Entity\A');
        $tracker->popScope();

        $tracker->pushScope();
        $tracker->setVariable('b', 'App\Entity\B');
        $tracker->popScope();

        self::assertSame('App\Entity\Root', $tracker->getVariableType('root'));
        self::assertNull($tracker->getVariableType('a'));
        self::assertNull($tracker->getVariableType('b'));
    }

    public function testHasVariableReturnsTrueForOuterScopeVariableWhenInsideInnerScope(): void
    {
        $tracker = new ScopeTracker(['root' => 'App\Entity\Root']);
        $tracker->pushScope();

        self::assertTrue($tracker->hasVariable('root'));
    }
}
