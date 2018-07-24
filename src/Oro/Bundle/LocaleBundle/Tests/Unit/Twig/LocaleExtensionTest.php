<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\Twig;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\LocaleBundle\Twig\LocaleExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class LocaleExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    const TEST_TYPE = 'test_format_type';
    const TEST_FORMAT = 'MMM, d y t';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $localeSettings;

    /** @var LocaleExtension */
    protected $extension;

    protected function setUp()
    {
        $this->localeSettings =$this->getMockBuilder(LocaleSettings::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_locale.settings', $this->localeSettings)
            ->getContainer($this);

        $this->extension = new LocaleExtension($container);
    }

    protected function tearDown()
    {
        unset($this->localeSettings);
        unset($this->extension);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_locale', $this->extension->getName());
    }

    public function testGetTimeZoneOffset()
    {
        $timezoneString = 'UTC';
        $timezoneOffset = '+00:00';

        $this->localeSettings->expects($this->once())
            ->method('getTimeZone')
            ->will($this->returnValue($timezoneString));

        $this->assertEquals(
            $timezoneOffset,
            self::callTwigFunction($this->extension, 'oro_timezone_offset', [])
        );
    }
}
