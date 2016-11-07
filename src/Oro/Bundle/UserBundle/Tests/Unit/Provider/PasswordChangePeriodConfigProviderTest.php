<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Provider;

use Oro\Bundle\UserBundle\Provider\PasswordChangePeriodConfigProvider;

class PasswordChangePeriodConfigProviderTest extends \PHPUnit_Framework_TestCase
{
    protected $configManager;

    public function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @dataProvider getConfigSettings
     */
    public function testGetPasswordExpiryDateFromNow($valueMap, $expectedInterval)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap($valueMap));

        $provider = new PasswordChangePeriodConfigProvider($this->configManager);
        $format = 'Y-m-d H:i';
        $now = new \DateTime('now', new \DateTimeZone('UTC'));
        $expectedDate = $now->add($expectedInterval);

        $this->assertSame($provider->getPasswordExpiryDateFromNow()->format($format), $expectedDate->format($format));
    }

    public function getConfigSettings()
    {
        return [
            '3 days' => [
                'valueMap' => [
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_ENABLED_KEY, false, false, null, true],
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_PERIOD, false, false, null, 3],
                ],
                'expectedInterval' => new \DateInterval('P3D')
            ],
            '30 days' => [
                'valueMap' => [
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_ENABLED_KEY, false, false, null, true],
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_PERIOD, false, false, null, 30],
                ],
                'expectedInterval' => new \DateInterval('P30D')
            ],
            '90 days' => [
                'valueMap' => [
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_ENABLED_KEY, false, false, null, true],
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_PERIOD, false, false, null, 90],
                ],
                'expectedInterval' => new \DateInterval('P90D')
            ],
        ];
    }
}
