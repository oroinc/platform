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
        $this->configManager->expects($this->exactly(3))
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
            'config with days' => [
                'valueMap' => [
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_ENABLED_KEY, false, false, null, true],
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_PERIOD_KEY, false, false, null, 7],
                    [
                        PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_PERIOD_UNIT_KEY, false, false, null,
                        PasswordChangePeriodConfigProvider::DAYS
                    ]
                ],
                'expectedInterval' => new \DateInterval('P7D')
            ],
            'config with weeks' => [
                'valueMap' => [
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_ENABLED_KEY, false, false, null, true],
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_PERIOD_KEY, false, false, null, 2],
                    [
                        PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_PERIOD_UNIT_KEY, false, false, null,
                        PasswordChangePeriodConfigProvider::WEEKS
                    ]
                ],
                'expectedInterval' => new \DateInterval('P14D')
            ],
            'config with months' => [
                'valueMap' => [
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_ENABLED_KEY, false, false, null, true],
                    [PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_PERIOD_KEY, false, false, null, 1],
                    [
                        PasswordChangePeriodConfigProvider::PASSWORD_EXPIRY_PERIOD_UNIT_KEY, false, false, null,
                        PasswordChangePeriodConfigProvider::MONTHS
                    ]
                ],
                'expectedInterval' => new \DateInterval('P1M')
            ],
        ];
    }
}
