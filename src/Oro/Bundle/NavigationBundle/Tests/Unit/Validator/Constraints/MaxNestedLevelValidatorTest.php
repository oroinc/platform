<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\MenuUpdateApplier;
use Oro\Bundle\NavigationBundle\MenuUpdate\Applier\MenuUpdateApplierInterface;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuItem\MenuUpdateToMenuItemPropagatorInterface;
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

    private BuilderChainProvider|\PHPUnit\Framework\MockObject\MockObject $builderChainProvider;

    private MenuUpdateApplierInterface $menuUpdateApplier;

    protected function setUp(): void
    {
        $this->builderChainProvider = $this->createMock(BuilderChainProvider::class);

        $localizationHelper = $this->createMock(LocalizationHelper::class);
        $localizationHelper
            ->expects(self::any())
            ->method('getLocalizedValue')
            ->willReturnCallback(static fn ($collection) => $collection[0] ?? null);

        $this->menuUpdateApplier = new MenuUpdateApplier(
            $this->createMock(MenuUpdateToMenuItemPropagatorInterface::class)
        );

        parent::setUp();
    }

    protected function createValidator(): MaxNestedLevelValidator
    {
        return new MaxNestedLevelValidator($this->builderChainProvider, $this->menuUpdateApplier);
    }

    public function testWhenNotValid(): void
    {
        $menu = $this->getMenu();
        $maxNestingLevel = 2;
        $menu->setExtra('max_nesting_level', $maxNestingLevel);

        $scope = $this->createMock(Scope::class);

        $update = new MenuUpdateStub();
        $update->setScope($scope);
        $update->setMenu('menu');
        $update->setKey('item-1-1-1');
        $update->setParentKey('item-1-1');
        $update->setUri('#');

        $this->builderChainProvider
            ->expects(self::once())
            ->method('get')
            ->with('menu', ['ignoreCache' => true, 'scopeContext' => $scope])
            ->willReturn($menu);

        $constraint = new MaxNestedLevel();
        $this->validator->validate($update, $constraint);

        $this
            ->buildViolation('oro.navigation.validator.menu_update.max_nested_level.message')
            ->setParameter('{{ label }}', '"item-1-1-1"')
            ->setParameter('{{ max }}', $maxNestingLevel)
            ->setCode(MaxNestedLevel::MAX_NESTING_LEVEL_ERROR)
            ->assertRaised();

        $item = $menu
            ->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        self::assertNotNull($item);
    }

    public function testWhenNotValidAndNew(): void
    {
        $menu = $this->getMenu();
        $maxNestingLevel = 3;
        $menu->setExtra('max_nesting_level', $maxNestingLevel);

        $scope = $this->createMock(Scope::class);

        $update = new MenuUpdateStub();
        $update->setScope($scope);
        $update->setMenu('menu');
        $update->setKey('item-1-1-1-1');
        $update->setParentKey('item-1-1-1');
        $update->setUri('#');
        $update->setCustom(true);

        $this->builderChainProvider
            ->expects(self::once())
            ->method('get')
            ->with('menu', ['ignoreCache' => true, 'scopeContext' => $scope])
            ->willReturn($menu);

        $constraint = new MaxNestedLevel();
        $this->validator->validate($update, $constraint);

        $this
            ->buildViolation('oro.navigation.validator.menu_update.max_nested_level.message')
            ->setParameter('{{ label }}', '"item-1-1-1-1"')
            ->setParameter('{{ max }}', $maxNestingLevel)
            ->setCode(MaxNestedLevel::MAX_NESTING_LEVEL_ERROR)
            ->assertRaised();

        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1')
            ->getChild('item-1-1-1-1');

        self::assertNull($item);
    }

    public function testWhenIsValid(): void
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

        $this->builderChainProvider->expects(self::once())
            ->method('get')
            ->with('menu', ['ignoreCache' => true, 'scopeContext' => $scope])
            ->willReturn($menu);

        $constraint = new MaxNestedLevel();
        $this->validator->validate($update, $constraint);

        $this->assertNoViolation();
    }

    public function testWhenUnlimitedAndValid(): void
    {
        $menu = $this->getMenu();
        $menu->setExtra('max_nesting_level', 0);

        $update = new MenuUpdateStub();

        $scope = $this->createMock(Scope::class);

        $update->setScope($scope);
        $update->setMenu('menu');
        $update->setKey('item-1-1-1-1');
        $update->setParentKey('item-1-1-1');
        $update->setUri('#');

        $this->builderChainProvider->expects(self::once())
            ->method('get')
            ->with('menu', ['ignoreCache' => true, 'scopeContext' => $scope])
            ->willReturn($menu);

        $constraint = new MaxNestedLevel();
        $this->validator->validate($update, $constraint);

        $this->assertNoViolation();
    }
}
