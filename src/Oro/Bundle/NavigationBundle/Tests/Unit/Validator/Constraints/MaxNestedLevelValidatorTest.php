<?php

namespace Oro\Bundle\NavigationBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\NavigationBundle\Provider\BuilderChainProvider;
use Oro\Bundle\NavigationBundle\Tests\Unit\Entity\Stub\MenuUpdateStub;
use Oro\Bundle\NavigationBundle\Tests\Unit\MenuItemTestTrait;
use Oro\Bundle\NavigationBundle\Validator\Constraints\MaxNestedLevel;
use Oro\Bundle\NavigationBundle\Validator\Constraints\MaxNestedLevelValidator;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class MaxNestedLevelValidatorTest extends \PHPUnit\Framework\TestCase
{
    use MenuItemTestTrait;

    /** @var MaxNestedLevelValidator */
    protected $validator;

    /** @var BuilderChainProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $builderChainProvider;

    /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->builderChainProvider = $this->createMock(BuilderChainProvider::class);

        /** @var LocalizationHelper|\PHPUnit\Framework\MockObject\MockObject $localizationHelper */
        $localizationHelper = $this->createMock(LocalizationHelper::class);

        $this->validator = new MaxNestedLevelValidator($this->builderChainProvider, $localizationHelper);
        $this->validator->initialize($this->context);
    }

    public function testValidateNotValid()
    {
        $constraint = new MaxNestedLevel();

        $menu = $this->getMenu();
        $menu->setExtra('max_nesting_level', 2);

        $scope = $this->createPartialMock(Scope::class, ['getOrganization', 'getUser']);

        $update = new MenuUpdateStub();
        $update->setScope($scope);
        $update->setMenu('menu');
        $update->setKey('item-1-1-1');
        $update->setParentKey('item-1-1');
        $update->setUri('#');

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with(
                'menu',
                [
                    'ignoreCache' => true,
                    'scopeContext' => $scope
                ]
            )
            ->will($this->returnValue($menu));

        $this->context
            ->expects($this->once())
            ->method('addViolation')
            ->with("Item \"item-1-1-1\" can't be saved. Max nesting level is reached.");


        $this->validator->validate($update, $constraint);

        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1');

        $this->assertNotNull($item);
    }

    public function testValidateNotValidNew()
    {
        $constraint = new MaxNestedLevel();

        $menu = $this->getMenu();
        $menu->setExtra('max_nesting_level', 3);

        $scope = $this->createPartialMock(Scope::class, ['getOrganization', 'getUser']);

        $update = new MenuUpdateStub();
        $update->setScope($scope);
        $update->setMenu('menu');
        $update->setKey('item-1-1-1-1');
        $update->setParentKey('item-1-1-1');
        $update->setUri('#');
        $update->setCustom(true);

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with(
                'menu',
                [
                    'ignoreCache' => true,
                    'scopeContext' => $scope
                ]
            )
            ->will($this->returnValue($menu));

        $this->context
            ->expects($this->once())
            ->method('addViolation')
            ->with("Item \"item-1-1-1-1\" can't be saved. Max nesting level is reached.");

        $this->validator->validate($update, $constraint);

        $item = $menu->getChild('item-1')
            ->getChild('item-1-1')
            ->getChild('item-1-1-1')
            ->getChild('item-1-1-1-1');

        $this->assertNull($item);
    }

    public function testValidateIsValid()
    {
        $constraint = new MaxNestedLevel();

        $menu = $this->getMenu();
        $menu->setExtra('max_nesting_level', 4);

        $update = new MenuUpdateStub();

        $scope = $this->createPartialMock(Scope::class, ['getOrganization', 'getUser']);

        $update->setScope($scope);
        $update->setMenu('menu');
        $update->setKey('item-1-1-1-1');
        $update->setParentKey('item-1-1-1');
        $update->setUri('#');

        $this->builderChainProvider
            ->expects($this->once())
            ->method('get')
            ->with(
                'menu',
                [
                    'ignoreCache' => true,
                    'scopeContext' => $scope
                ]
            )
            ->will($this->returnValue($menu));

        $this->validator->validate($update, $constraint);
    }
}
