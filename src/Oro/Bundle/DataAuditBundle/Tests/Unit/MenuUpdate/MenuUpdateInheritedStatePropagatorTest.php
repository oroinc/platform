<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\MenuUpdate;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Menu\ItemInterface;
use Oro\Bundle\DataAuditBundle\MenuUpdate\MenuUpdateInheritedStatePropagator;
use Oro\Bundle\DataAuditBundle\Model\MenuAuditValueNormalizer;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\NavigationBundle\MenuUpdate\Propagator\ToMenuUpdate\MenuItemToMenuUpdatePropagatorInterface;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccess;

class MenuUpdateInheritedStatePropagatorTest extends TestCase
{
    private const string STRATEGY = MenuItemToMenuUpdatePropagatorInterface::STRATEGY_FULL;

    private ItemInterface&MockObject $menuItem;
    private MenuUpdateInheritedStatePropagator $propagator;

    #[\Override]
    protected function setUp(): void
    {
        $this->menuItem = $this->createMock(ItemInterface::class);
        $this->propagator = $this->createPropagator(['uri', 'active', 'notAccessible'], ['titles']);
    }

    public function testAppliesToAMenuUpdateThatDoesNotExistYet(): void
    {
        self::assertTrue($this->propagator->isApplicable(new MenuUpdate(), $this->menuItem, self::STRATEGY));
    }

    public function testDoesNotApplyToAnExistingMenuUpdate(): void
    {
        $menuUpdate = new MenuUpdate();
        ReflectionUtil::setId($menuUpdate, 42);

        self::assertFalse($this->propagator->isApplicable($menuUpdate, $this->menuItem, self::STRATEGY));
    }

    public function testRemembersTheStateAsTheAuditStoresIt(): void
    {
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setUri('/products');
        $menuUpdate->setActive(true);
        $menuUpdate->addTitle((new LocalizedFallbackValue())->setString('Products'));
        $menuUpdate->addTitle($this->createLocalizedValue('Produkte', 'German'));

        $this->propagator->propagateFromMenuItem($menuUpdate, $this->menuItem, self::STRATEGY);

        self::assertSame(
            [
                'uri' => '/products',
                'active' => true,
                'titles' => ['' => 'Products', 'German' => 'Produkte'],
            ],
            $this->propagator->getInheritedState($menuUpdate)
        );
    }

    public function testKnowsNothingAboutAMenuUpdateItHasNotSeen(): void
    {
        self::assertNull($this->propagator->getInheritedState(new MenuUpdate()));
    }

    public function testRemembersNothingWithoutAManagerOfItsOwn(): void
    {
        $propagator = $this->createPropagator(['uri'], [], false);
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setUri('/products');

        $propagator->propagateFromMenuItem($menuUpdate, $this->menuItem, self::STRATEGY);

        self::assertSame([], $propagator->getInheritedState($menuUpdate));
    }

    public function testForgetsEverythingOnReset(): void
    {
        $menuUpdate = new MenuUpdate();
        $this->propagator->propagateFromMenuItem($menuUpdate, $this->menuItem, self::STRATEGY);

        $this->propagator->reset();

        self::assertNull($this->propagator->getInheritedState($menuUpdate));
    }

    private function createPropagator(
        array $fields,
        array $associations,
        bool $withManager = true
    ): MenuUpdateInheritedStatePropagator {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects(self::any())
            ->method('getFieldNames')
            ->willReturn($fields);
        $metadata->expects(self::any())
            ->method('getAssociationNames')
            ->willReturn($associations);

        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($metadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(MenuUpdate::class)
            ->willReturn($withManager ? $manager : null);

        return new MenuUpdateInheritedStatePropagator(
            $doctrine,
            PropertyAccess::createPropertyAccessor(),
            new MenuAuditValueNormalizer($this->createMock(EntityNameResolver::class))
        );
    }

    private function createLocalizedValue(string $text, string $localization): LocalizedFallbackValue
    {
        $value = new LocalizedFallbackValue();
        $value->setString($text);
        $value->setLocalization((new Localization())->setName($localization));

        return $value;
    }
}
