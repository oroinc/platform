<?php

namespace Oro\Bundle\NotificationBundle\Tests\Unit\Provider;

use Oro\Bundle\EmailBundle\Form\Model\Email;
use Oro\Bundle\LocaleBundle\Provider\PreferredLanguageProviderInterface;
use Oro\Bundle\NotificationBundle\Model\EmailAddressWithContext;
use Oro\Bundle\NotificationBundle\Provider\EmailAddressWithContextPreferredLanguageProvider;

class EmailAddressWithContextPreferredLanguageProviderTest extends \PHPUnit\Framework\TestCase
{
    private const LANGUAGE = 'fr_FR';

    /**
     * @var PreferredLanguageProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $chainLanguageProvider;

    /**
     * @var EmailAddressWithContextPreferredLanguageProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->chainLanguageProvider = $this->createMock(PreferredLanguageProviderInterface::class);
        $this->provider = new EmailAddressWithContextPreferredLanguageProvider(
            $this->chainLanguageProvider
        );
    }

    public function testSupports(): void
    {
        self::assertTrue($this->provider->supports(
            new EmailAddressWithContext('some@mail.com')
        ));
    }

    public function testSupportsFail(): void
    {
        self::assertFalse($this->provider->supports(new \stdClass()));
    }

    public function testGetPreferredLanguageWhenNotSupports(): void
    {
        $this->expectException(\LogicException::class);

        $this->provider->getPreferredLanguage(new \stdClass());
    }

    public function testGetPreferredLanguage(): void
    {
        $context = new Email();

        $this->chainLanguageProvider
            ->expects($this->once())
            ->method('getPreferredLanguage')
            ->with($context)
            ->willReturn(self::LANGUAGE);

        self::assertEquals(
            self::LANGUAGE,
            $this->provider->getPreferredLanguage(new EmailAddressWithContext('some@mail.com', $context))
        );
    }
}
