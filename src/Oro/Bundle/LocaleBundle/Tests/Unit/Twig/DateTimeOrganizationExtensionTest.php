<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatterInterface;
use Oro\Bundle\LocaleBundle\Manager\LocalizationManager;
use Oro\Bundle\LocaleBundle\Twig\DateTimeOrganizationExtension;
use Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DateTimeOrganizationExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var DateTimeFormatterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formatter;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var LocalizationManager|\PHPUnit\Framework\MockObject\MockObject */
    private $localizationManager;

    /** @var DateTimeOrganizationExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->formatter = $this->createMock(DateTimeFormatterInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->localizationManager = $this->createMock(LocalizationManager::class);

        $container = self::getContainerBuilder()
            ->add('oro_locale.formatter.date_time', $this->formatter)
            ->add('oro_config.global', $this->configManager)
            ->add('oro_locale.manager.localization', $this->localizationManager)
            ->getContainer($this);

        $this->extension = new DateTimeOrganizationExtension($container);
    }

    public function testFormatDateTimeOrganizationShouldUseTimezoneFromConfigurationIfOrganizationProvided()
    {
        $date = new \DateTime('2016-05-31 00:00:00');
        $expected = 'May 30, 2016, 4:00 PM';

        $organization = $this->createMock(OrganizationInterface::class);

        $organizationLocale = 'en_US';
        $organizationTimezone = 'America/Los_Angeles';
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['oro_locale.default_localization', false, false, null, 42],
                ['oro_locale.timezone', false, false, null, $organizationTimezone],
            ]);
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($date, null, null, $organizationLocale, $organizationTimezone)
            ->willReturn($expected);

        $this->localizationManager->expects($this->any())
            ->method('getLocalizationData')
            ->with(42)
            ->willReturn(['formattingCode' => $organizationLocale]);

        $options = [
            'locale'       => 'fr_FR',
            'timeZone'     => 'Europe/Athens',
            'organization' => $organization
        ];
        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_datetime_organization', [$date, $options])
        );
    }

    public function testFormatDateTimeOrganizationShouldUseTimezonePassedInOptionsIfOrganizationNotProvided()
    {
        $date = new \DateTime('2016-05-31 00:00:00');
        $expected = 'May 30, 2016, 4:00 PM';

        $this->configManager->expects($this->never())
            ->method('get');

        $locale = 'en_US';
        $timezone = 'America/Los_Angeles';
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($date, null, null, $locale, $timezone)
            ->willReturn($expected);

        $options = [
            'locale'   => $locale,
            'timeZone' => $timezone
        ];
        $this->assertEquals(
            $expected,
            self::callTwigFilter($this->extension, 'oro_format_datetime_organization', [$date, $options])
        );
    }
}
