<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Twig;

use Oro\Bundle\AddressBundle\Twig\PhoneExtension;
use Oro\Bundle\AddressBundle\Provider\PhoneProvider;

class PhoneExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|PhoneProvider
     */
    protected $provider;

    /**
     * @var PhoneExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder('Oro\Bundle\AddressBundle\Provider\PhoneProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new PhoneExtension($this->provider);
    }

    public function testGetFunctions()
    {
        $expectedFunctions = [
            [$this->extension, 'getPhoneNumber'],
            [$this->extension, 'getPhoneNumbers'],
        ];

        $actualFunctions = $this->extension->getFunctions();

        $this->assertSameSize($expectedFunctions, $actualFunctions);

        foreach ($expectedFunctions as $index => $expectedCallable) {
            $this->assertArrayHasKey($index, $actualFunctions);
            /** @var \Twig_SimpleFunction $actualFunction */
            $actualFunction = $actualFunctions[$index];
            $this->assertInstanceOf('\Twig_SimpleFunction', $actualFunction);
            $this->assertEquals($expectedCallable, $actualFunction->getCallable());
        }
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

        $actualPhone = $this->extension->getPhoneNumber($object);
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

        $actualPhones = $this->extension->getPhoneNumbers($object);
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
