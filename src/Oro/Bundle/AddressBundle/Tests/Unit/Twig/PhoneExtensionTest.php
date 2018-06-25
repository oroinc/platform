<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Twig;

use Oro\Bundle\AddressBundle\Provider\PhoneProvider;
use Oro\Bundle\AddressBundle\Twig\PhoneExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class PhoneExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|PhoneProvider */
    protected $provider;

    /** @var PhoneExtension */
    protected $extension;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder(PhoneProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_address.provider.phone', $this->provider)
            ->getContainer($this);

        $this->extension = new PhoneExtension($container);
    }

    /**
     * @param object|null $object
     *
     * @dataProvider phoneSourceProvider
     */
    public function testGetPhoneNumber($object)
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
     * @param object|null $object
     *
     * @dataProvider phoneSourceProvider
     */
    public function testGetPhoneNumbers($object)
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

    /**
     * @return array
     */
    public function phoneSourceProvider()
    {
        return [
            'no object' => [null],
            'valid object' => [new \stdClass()]
        ];
    }

    public function testGetName()
    {
        $this->assertEquals('oro_phone_extension', $this->extension->getName());
    }
}
