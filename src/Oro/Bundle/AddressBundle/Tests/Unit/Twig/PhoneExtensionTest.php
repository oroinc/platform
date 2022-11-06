<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Twig;

use Oro\Bundle\AddressBundle\Provider\PhoneProvider;
use Oro\Bundle\AddressBundle\Twig\PhoneExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class PhoneExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PhoneProvider */
    private $provider;

    /** @var PhoneExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(PhoneProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_address.provider.phone', $this->provider)
            ->getContainer($this);

        $this->extension = new PhoneExtension($container);
    }

    /**
     * @dataProvider phoneSourceProvider
     */
    public function testGetPhoneNumber(?object $object)
    {
        $expectedPhone = '123-456-789';

        $this->provider->expects($object ? $this->once() : $this->never())
            ->method('getPhoneNumber')
            ->with($object)
            ->willReturn($object ? $expectedPhone : null);

        $actualPhone = self::callTwigFunction($this->extension, 'phone_number', [$object]);
        if ($object) {
            $this->assertEquals($expectedPhone, $actualPhone);
        } else {
            $this->assertNull($actualPhone);
        }
    }

    /**
     * @dataProvider phoneSourceProvider
     */
    public function testGetPhoneNumbers(?object $object)
    {
        $sourcePhones = [
            ['123-456-789', new \stdClass],
            ['987-654-321', new \stdClass],
        ];
        $expectedPhones = [
            ['phone' => '123-456-789', 'object' => new \stdClass],
            ['phone' => '987-654-321', 'object' => new \stdClass],
        ];

        $this->provider->expects($object ? $this->once() : $this->never())
            ->method('getPhoneNumbers')
            ->with($object)
            ->willReturn($object ? $sourcePhones : null);

        $actualPhones = self::callTwigFunction($this->extension, 'phone_numbers', [$object]);
        if ($object) {
            $this->assertEquals($expectedPhones, $actualPhones);
        } else {
            $this->assertEquals([], $actualPhones);
        }
    }

    public function phoneSourceProvider(): array
    {
        return [
            'no object' => [null],
            'valid object' => [new \stdClass()]
        ];
    }
}
