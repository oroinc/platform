<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Twig;

use Oro\Bundle\DataAuditBundle\Model\MenuAuditObject;
use Oro\Bundle\DataAuditBundle\Provider\MenuAuditObjectProviderInterface;
use Oro\Bundle\DataAuditBundle\Twig\MenuAuditExtension;
use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MenuAuditExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private const string OBJECT_CLASS = 'Oro\Bundle\NavigationBundle\GlobalBackOfficeMenu';

    private MenuAuditObjectProviderInterface&MockObject $auditObjectProvider;
    private MenuAuditExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->auditObjectProvider = $this->createMock(MenuAuditObjectProviderInterface::class);

        $container = self::getContainerBuilder()
            ->add(MenuAuditObjectProviderInterface::class, $this->auditObjectProvider)
            ->add(
                EntityClassNameHelper::class,
                new EntityClassNameHelper($this->createMock(EntityAliasResolver::class))
            )
            ->getContainer($this);

        $this->extension = new MenuAuditExtension($container);
    }

    public function testTellsHowToAddressTheHistoryOfAMenuItem(): void
    {
        $this->givenAuditObject(new MenuAuditObject(self::OBJECT_CLASS, 'application_menu', 5, 'contact_us'));

        self::assertSame(
            [
                'class' => 'Oro_Bundle_NavigationBundle_GlobalBackOfficeMenu',
                'id' => '5_application_menu_contact_us',
            ],
            $this->getMenuItemAudit(new MenuUpdate())
        );
    }

    public function testTellsNothingAboutAnItemOfAMenuThatIsNotAudited(): void
    {
        $this->givenAuditObject(null);

        self::assertNull($this->getMenuItemAudit(new MenuUpdate()));
    }

    public function testTellsNothingWhenThereIsNoMenuItemToTellAbout(): void
    {
        $this->auditObjectProvider->expects(self::never())
            ->method('getAuditObject');

        self::assertNull($this->getMenuItemAudit(null));
    }

    public function testTellsNothingWhenTheHistoryOfTheItemCannotBeAddressed(): void
    {
        $this->givenAuditObject(new MenuAuditObject(self::OBJECT_CLASS, 'application_menu', 5, 'contact us.1'));

        self::assertNull($this->getMenuItemAudit(new MenuUpdate()));
    }

    private function givenAuditObject(?MenuAuditObject $auditObject): void
    {
        $this->auditObjectProvider->expects(self::any())
            ->method('getAuditObject')
            ->willReturn($auditObject);
    }

    private function getMenuItemAudit(?MenuUpdate $menuUpdate): ?array
    {
        return self::callTwigFunction($this->extension, 'oro_dataaudit_menu_item_audit', [$menuUpdate]);
    }
}
