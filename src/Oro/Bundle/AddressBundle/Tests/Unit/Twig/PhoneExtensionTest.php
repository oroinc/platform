<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Twig;

use Oro\Bundle\AddressBundle\Twig\PhoneExtension;
use Oro\Bundle\AddressBundle\Provider\PhoneProvider;

class PhoneExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|PhoneProvider */
    protected $provider;

    /** @var PhoneExtension */
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
        $tests = [
            'phone_number'  => '\Twig_Function_Method',
            'phone_numbers' => '\Twig_Function_Method',
        ];

        $functions = $this->extension->getFunctions();

        $this->assertCount(count($tests), $functions);

        foreach ($tests as $test => $func) {
            $this->assertArrayHasKey($test, $functions);
            $this->assertInstanceOf($func, $functions[$test]);
        }
    }

    /**
     * @param int   $expected
     * @param mixed $expected
     * @param mixed $obj
     *
     * @dataProvider getPhoneNumberProvider
     */
    public function testGetPhoneNumber($expectedCalls, $expected, $obj)
    {
        $this->provider->expects($this->exactly($expectedCalls))
            ->method('getPhoneNumber')
            ->with($obj)
            ->willReturn('phoneByProvider');

        $this->assertEquals($expected, $this->extension->getPhoneNumber($obj));
    }

    /**
     * @param int   $expected
     * @param mixed $expected
     * @param mixed $obj
     *
     * @dataProvider getPhoneNumberProvider
     */
    public function testGetPhoneNumbers($expectedCalls, $expected, $obj)
    {
        $this->provider->expects($this->exactly($expectedCalls))
            ->method('getPhoneNumbers')
            ->with($obj)
            ->willReturn('phoneByProvider');

        $this->assertEquals($expected, $this->extension->getPhoneNumbers($obj));
    }

    /**
     * @return array
     */
    public function getPhoneNumberProvider()
    {
        $tests = [];

        $tests['empty object'] = [
            0,
            null,
            null,
        ];

        $tests['phoneByProvider'] = [
            1,
            'phoneByProvider',
            new \stdClass(),
        ];

        return $tests;
    }

    public function testGetName()
    {
        $this->assertEquals('oro_phone_extension', $this->extension->getName());
    }
}
