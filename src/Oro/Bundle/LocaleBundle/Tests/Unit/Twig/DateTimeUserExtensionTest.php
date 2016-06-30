<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Formatter\DateTimeFormatter;
use Oro\Bundle\SecurityBundle\Tests\Unit\Acl\Domain\Fixtures\Entity\User;
use Oro\Bundle\OrganizationBundle\Tests\Unit\Fixture\Entity\Organization;
use Oro\Bundle\LocaleBundle\Twig\DateTimeUserExtension;

class DateTimeUserExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DateTimeUserExtension
     */
    protected $extension;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $localeSettings;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var DateTimeFormatter
     */
    protected $formatter;

    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();
        $translator = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new DateTimeFormatter($this->localeSettings, $translator);
        $this->extension = new DateTimeUserExtension($this->formatter);
        $this->extension->setConfigManager($this->configManager);
    }

    public function testGetFilters()
    {
        $filters = $this->extension->getFilters();

        $this->assertCount(5, $filters);

        $this->assertInstanceOf('Twig_SimpleFilter', $filters[4]);
        $this->assertEquals('oro_format_datetime_user', $filters[4]->getName());
    }

    /**
     * @param string $value
     * @param string $expected
     * @param array $options
     * @param string|null $locale
     * @param string|null $timeZone
     *
     * @dataProvider formatDateTimeUserDataProvider
     */
    public function testFormatDateTimeUser($value, $expected, array $options, $locale = null, $timeZone = null)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->will(
                $this->returnValueMap(
                    [
                        ['oro_locale.locale', false, false, $locale],
                        ['oro_locale.timezone', false, false, $timeZone],
                    ]
                )
            );

        $this->assertEquals($expected, $this->extension->formatDateTimeUser($value, $options));
    }

    /**
     * @return array
     */
    public function formatDateTimeUserDataProvider()
    {
        $organization = new Organization(1);
        $user = new User(1, null, $organization);

        return [
            'options without User negative shift' => [
                'value' => new \DateTime('2016-05-31 00:00:00', new \DateTimeZone('UTC')),
                'expected' => 'May 30, 2016, 5:00 PM',
                'options' => [
                    'locale' => 'en_US',
                    'timeZone' => 'America/Los_Angeles',
                ],
            ],
            'options without User positive shift' => [
                'value' => new \DateTime('2016-05-31 00:00:00', new \DateTimeZone('UTC')),
                'expected' => 'May 31, 2016, 3:00 AM',
                'options' => [
                    'locale' => 'en_US',
                    'timeZone' => 'Europe/Athens',
                ],
                'locale' => 'en_US',
                'timeZone' => 'Europe/Athens',
            ],
            'user scope timeZone positive shift' => [
                'value' => new \DateTime('2016-05-31 00:00:00', new \DateTimeZone('UTC')),
                'expected' => 'May 31, 2016, 3:00 AM',
                'options' => [
                    'user' => $user
                ],
                'locale' => 'en_US',
                'timeZone' => 'Europe/Athens',
            ],
            'user scope timeZone negative shift' => [
                'value' => new \DateTime('2016-05-31 00:00:00', new \DateTimeZone('UTC')),
                'expected' => 'May 30, 2016, 6:00 PM',
                'options' => [
                    'user' => $user
                ],
                'locale' => 'en_US',
                'timeZone' => 'Pacific/Easter',
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale_datetime_user', $this->extension->getName());
    }
}
