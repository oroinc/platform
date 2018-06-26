<?php

namespace Oro\Bundle\UserBundle\Tests\Unit\Util;

use Oro\Bundle\UserBundle\Tests\Unit\Stub\ObfuscatedEmailStub;

class ObfuscatedEmailTraitTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ObfuscatedEmailStub
     */
    private $obfuscatedEmailStub;

    public function setUp()
    {
        $this->obfuscatedEmailStub = new ObfuscatedEmailStub();
    }

    /**
     * @param string $emailAddress
     * @param string $expected
     * @dataProvider getObfuscatedEmailDataProvider
     */
    public function testGetObfuscatedEmail($emailAddress, $expected)
    {
        $this->assertEquals(
            $expected,
            $this->obfuscatedEmailStub->getObfuscatedEmail($emailAddress)
        );
    }

    /**
     * @return array
     */
    public function getObfuscatedEmailDataProvider()
    {
        return [
            'empty string' => [
                'emailAddress' => '',
                'expected' => ''
            ],
            'object' => [
                'emailAddress' => new \stdClass(),
                'expected' => null
            ],
            'not email address' => [
                'emailAddress' => 'test.demo.com',
                'expected' => 'test.demo.com'
            ],
            'email' => [
                'emailAddress' => 'test@demo.com',
                'expected' => '...@demo.com'
            ]
        ];
    }
}
