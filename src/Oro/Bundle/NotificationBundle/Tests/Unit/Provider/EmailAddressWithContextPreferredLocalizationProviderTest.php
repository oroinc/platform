<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\PreferredLocalizationProviderInterface;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\NotificationBundle\Provider\EmailAddressWithContextPreferredLocalizationProvider;
use Oro\Bundle\UserBundle\Entity\User;

class EmailAddressWithContextPreferredLocalizationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var PreferredLocalizationProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $innerProvider;

    /** @var EmailAddressWithContextPreferredLocalizationProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->innerProvider = $this->createMock(PreferredLocalizationProviderInterface::class);
        $this->provider = new EmailAddressWithContextPreferredLocalizationProvider(
            $this->innerProvider
        );
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
                'entity' => new EmailAddressWithContext('to@example.com'),
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
        $context = new User();
        $entity = new EmailAddressWithContext('to@example.com', $context);

        $localization = new Localization();
        $this->innerProvider->expects($this->once())
            ->method('getPreferredLocalization')
            ->with($this->identicalTo($context))
            ->willReturn($localization);

        $this->assertSame($localization, $this->provider->getPreferredLocalization($entity));
    }
}
