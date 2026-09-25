<?php

namespace Oro\Bundle\DataAuditBundle\Tests\Unit\Provider;

use Oro\Bundle\DataAuditBundle\Provider\MenuAuditLevelProvider;
use Oro\Bundle\DataAuditBundle\Provider\MenuAuditObjectProvider;
use Oro\Bundle\NavigationBundle\Entity\MenuUpdate;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MenuAuditObjectProviderTest extends TestCase
{
    private const string OBJECT_CLASS = 'Oro\Bundle\NavigationBundle\GlobalBackOfficeMenu';

    private MenuAuditLevelProvider&MockObject $levelProvider;
    private MenuAuditObjectProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->levelProvider = $this->createMock(MenuAuditLevelProvider::class);
        $this->levelProvider->expects(self::any())
            ->method('getClassForScope')
            ->willReturn(self::OBJECT_CLASS);

        $this->provider = new MenuAuditObjectProvider($this->levelProvider, MenuUpdate::class);
    }

    public function testSaysWhatAMenuItemIsRecordedUnder(): void
    {
        $auditObject = $this->provider->getAuditObject($this->createMenuUpdate($this->createScope(5)));

        self::assertNotNull($auditObject);
        self::assertSame(self::OBJECT_CLASS, $auditObject->getObjectClass());
        self::assertSame('5_application_menu_contact_us', $auditObject->getObjectId());
    }

    public function testKnowsNothingAboutAMenuOfAnotherKind(): void
    {
        $provider = new MenuAuditObjectProvider($this->levelProvider, TestActivity::class);

        self::assertNull($provider->getAuditObject($this->createMenuUpdate($this->createScope(5))));
    }

    public function testKnowsNothingAboutAnItemThatIsCustomizedNowhereYet(): void
    {
        self::assertNull($this->provider->getAuditObject($this->createMenuUpdate(null)));
    }

    private function createMenuUpdate(?Scope $scope): MenuUpdate
    {
        $menuUpdate = new MenuUpdate();
        $menuUpdate->setMenu('application_menu');
        $menuUpdate->setKey('contact_us');
        if (null !== $scope) {
            $menuUpdate->setScope($scope);
        }

        return $menuUpdate;
    }

    private function createScope(int $id): Scope
    {
        $scope = new Scope();
        ReflectionUtil::setId($scope, $id);

        return $scope;
    }
}
