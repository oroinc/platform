<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\NavigationBundle\Validator\Constraints\MaxNestedLevel;
use Oro\Bundle\NavigationBundle\Validator\Constraints\MaxNestedLevelValidator;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MaxNestedLevelValidatorTest extends ConstraintValidatorTestCase
{
    use MenuItemTestTrait;

    /** @var BuilderChainProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $builderChainProvider;

    /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationHelper;

    protected function setUp(): void
    {
        $this->builderChainProvider = $this->createMock(BuilderChainProvider::class);
        $this->localizationHelper = $this->createMock(LocalizationHelper::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new MaxNestedLevelValidator($this->builderChainProvider, $this->localizationHelper);
    }

    public function testValidateNotValid()
    {
        $menu = $this->getMenu();
        $menu->setExtra('max_nesting_level', 2);

        $scope = $this->createMock(Scope::class);

        $update = new MenuUpdateStub();
        $update->setScope($scope);
        $update->setMenu('menu');
        $update->setKey('item-1-1-1');
        $update->setParentKey('item-1-1');
        $update->setUri('#');

        $this->builderChainProvider->expects($this->once())
            ->method('get')
            ->with('menu', ['ignoreCache' => true, 'scopeContext' => $scope])
            ->willReturn($menu);

        $constraint = new MaxNestedLevel();
        $this->validator->validate($update, $constraint);

        $this->buildViolation('Item "item-1-1-1" can\'t be saved. Max nesting level is reached.')
            ->assertRaised();

        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $this->assertNotNull($item);
    }

    public function testValidateNotValidNew()
    {
        $menu = $this->getMenu();
        $menu->setExtra('max_nesting_level', 3);

        $scope = $this->createMock(Scope::class);

        $update = new MenuUpdateStub();
        $update->setScope($scope);
        $update->setMenu('menu');
        $update->setKey('item-1-1-1-1');
        $update->setParentKey('item-1-1-1');
        $update->setUri('#');
        $update->setCustom(true);

        $this->builderChainProvider->expects($this->once())
            ->method('get')
            ->with('menu', ['ignoreCache' => true, 'scopeContext' => $scope])
            ->willReturn($menu);

        $constraint = new MaxNestedLevel();
        $this->validator->validate($update, $constraint);

        $this->buildViolation('Item "item-1-1-1-1" can\'t be saved. Max nesting level is reached.')
            ->assertRaised();

        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1')
            ->getChild('item-1-1-1-1');

        $this->assertNull($item);
    }

    public function testValidateIsValid()
    {
        $menu = $this->getMenu();
        $menu->setExtra('max_nesting_level', 4);

        $update = new MenuUpdateStub();

        $scope = $this->createMock(Scope::class);

        $update->setScope($scope);
        $update->setMenu('menu');
        $update->setKey('item-1-1-1-1');
        $update->setParentKey('item-1-1-1');
        $update->setUri('#');

        $this->builderChainProvider->expects($this->once())
            ->method('get')
            ->with('menu', ['ignoreCache' => true, 'scopeContext' => $scope])
            ->willReturn($menu);

        $constraint = new MaxNestedLevel();
        $this->validator->validate($update, $constraint);

        $this->assertNoViolation();
    }
}
