<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\FormBundle\Captcha\CaptchaSettingsProviderInterface;
use Oro\Bundle\FormBundle\Layout\DataProvider\CaptchaProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CaptchaProviderTest extends TestCase
{
    private CaptchaSettingsProviderInterface&MockObject $captchaSettingsProvider;
    private CaptchaProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->captchaSettingsProvider = $this->createMock(CaptchaSettingsProviderInterface::class);
        $this->provider = new CaptchaProvider($this->captchaSettingsProvider);
    }

    /**
     * @dataProvider trueFalseDataProvider
     */
    public function testIsProtectionAvailable(bool $data): void
    {
        $this->captchaSettingsProvider->expects($this->once())
            ->method('isProtectionAvailable')
            ->willReturn($data);

        $this->assertSame($data, $this->provider->isProtectionAvailable());
    }

    /**
     * @dataProvider trueFalseDataProvider
     */
    public function testIsFormProtected(bool $data): void
    {
        $formName = 'test_form';

        $this->captchaSettingsProvider->expects($this->once())
            ->method('isFormProtected')
            ->with($formName)
            ->willReturn($data);

        $this->assertSame($data, $this->provider->isFormProtected($formName));
    }

    public static function trueFalseDataProvider(): array
    {
        return [
            [true],
            [false]
        ];
    }
}
