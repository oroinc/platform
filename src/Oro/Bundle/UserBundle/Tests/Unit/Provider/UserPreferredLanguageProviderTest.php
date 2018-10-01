<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\UserPreferredLanguageProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class UserPreferredLanguageProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;
    private const LANGUAGE = 'fr_FR';
    private const USER_SCOPE_ID = 723;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $userConfigManager;

    /**
     * @var LocaleSettings|\PHPUnit\Framework\MockObject\MockObject
     */
    private $localeSettings;

    /**
     * @var UserPreferredLanguageProvider
     */
    private $provider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->userConfigManager = $this->createMock(ConfigManager::class);
        $this->localeSettings = $this->createMock(LocaleSettings::class);

        $this->provider = new UserPreferredLanguageProvider($this->userConfigManager, $this->localeSettings);
    }

    public function testSupports(): void
    {
        self::assertTrue($this->provider->supports(new User()));
    }

    public function testSupportsFail(): void
    {
        self::assertFalse($this->provider->supports(new \stdClass()));
    }

    public function testGetPreferredLanguageWithNotSupportedEntity(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageRegExp('/entity class is not supported/');

        $this->provider->getPreferredLanguage(new \stdClass());
    }

    public function testGetPreferredLanguageForNotSupportedEntity(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessageRegExp('/entity class is not supported/');

        $this->provider->getPreferredLanguage(new Organization());
    }

    public function testGetPreferredLanguageWithUser(): void
    {
        $organization = new Organization();
        $user = $this->getEntity(User::class, ['id' => 1, 'organization' => $organization]);
        $userConfigScopeId = self::USER_SCOPE_ID;

        $this->userConfigManager
            ->expects($this->once())
            ->method('getScopeId')
            ->willReturn($userConfigScopeId);

        $this->userConfigManager
            ->expects($this->once())
            ->method('setScopeIdFromEntity')
            ->with($user)
            ->willReturnCallback(function ($user) use (&$userConfigScopeId) {
                $userConfigScopeId = spl_object_id($user);

                return $userConfigScopeId;
            });

        $this->userConfigManager
            ->expects($this->once())
            ->method('setScopeId')
            ->willReturnCallback(function ($scopeId) use (&$userConfigScopeId) {
                $userConfigScopeId = $scopeId;
            });

        $this->localeSettings
            ->expects($this->once())
            ->method('getActualLanguage')
            ->willReturn(self::LANGUAGE);

        self::assertEquals(self::LANGUAGE, $this->provider->getPreferredLanguage($user));
        self::assertEquals(self::USER_SCOPE_ID, $userConfigScopeId);
    }
}
