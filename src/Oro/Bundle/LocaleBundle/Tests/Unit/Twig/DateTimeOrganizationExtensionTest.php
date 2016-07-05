<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Twig\DateTimeOrganizationExtension;

class DateTimeOrganizationExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateTimeOrganizationExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formatter;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    protected function setUp()
    {
        $this->formatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new DateTimeOrganizationExtension($this->formatter);
        $this->extension->setConfigManager($this->configManager);
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(5, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[4]);
        $this->assertEquals('oro_format_datetime_organization', $filters[4]->getName());
    }

    public function testFormatDateTimeOrganizationShouldUseTimezoneFromConfigurationIfOrganizationProvided()
    {
        $date = new \DateTime('2016-05-31 00:00:00');
        $expected = 'May 30, 2016, 4:00 PM';

        $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface');

        $organizationLocale = 'en_US';
        $organizationTimezone = 'America/Los_Angeles';
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap(
                [
                    ['oro_locale.locale', false, false, $organizationLocale],
                    ['oro_locale.timezone', false, false, $organizationTimezone],
                ]
            );
        $this->formatter->expects($this->once())
            ->method('format')
            ->with($date, null, null, $organizationLocale, $organizationTimezone)
            ->willReturn($expected);

        $options = [
            'locale'       => 'fr_FR',
            'timeZone'     => 'Europe/Athens',
            'organization' => $organization
        ];
        $actual = $this->extension->formatDateTimeOrganization($date, $options);

        $this->assertEquals($expected, $actual);
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
        $actual = $this->extension->formatDateTimeOrganization($date, $options);

        $this->assertEquals($expected, $actual);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_datetime_organization', $this->extension->getName());
    }
}
