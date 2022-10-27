<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\UserPreferredLocalizationProvider;

class UserPreferredLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $userConfigManager;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var UserPreferredLocalizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->userConfigManager = $this->createMock(ConfigManager::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $this->provider = new UserPreferredLocalizationProvider($this->userConfigManager, $this->localizationManager);
    }

    /**
     * @dataProvider supportsDataProvider
     */
    public function testSupports(object $entity, bool $isSupported): void
    {
        $this->assertSame($isSupported, $this->provider->supports($entity));

        if (!$isSupported) {
            $this->expectException(\LogicException::class);
            $this->provider->getPreferredLocalization($entity);
        }
    }

    public function supportsDataProvider(): array
    {
        return [
            'supported' => [
                'entity' => new User(),
                'isSupported' => true,
            ],
            'not supported' => [
                'entity' => new \stdClass(),
                'isSupported' => false,
            ],
        ];
    }

    public function testGetPreferredLocalization(): void
    {
        $entity = new User();

        $this->userConfigManager->expects($this->once())
            ->method('getScopeId')
            ->willReturn(42);

        $this->userConfigManager->expects($this->once())
            ->method('setScopeIdFromEntity')
            ->with($this->identicalTo($entity));

        $this->userConfigManager->expects($this->once())
            ->method('get')
            ->with('oro_locale.default_localization')
            ->willReturn(54);

        $localization = new Localization();
        $this->localizationManager->expects($this->once())
            ->method('getLocalization')
            ->with(54)
            ->willReturn($localization);

        $this->userConfigManager->expects($this->once())
            ->method('setScopeId')
            ->with(42);

        $this->assertSame($localization, $this->provider->getPreferredLocalization($entity));
    }
}
