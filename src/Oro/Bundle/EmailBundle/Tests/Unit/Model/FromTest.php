<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Model;

use Oro\Bundle\EmailBundle\Model\From;

class FromTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider gettersDataProvider
     */
    public function testGetters(
        string $address,
        string $name,
        string $expectedAddress,
        string $expectedName,
        string $expectedString
    ): void {
        $from = From::emailAddress($address, $name);
        self::assertEquals($expectedAddress, $from->getAddress());
        self::assertEquals($expectedName, $from->getName());
        self::assertEquals([$expectedAddress, $expectedName], $from->toArray());
        self::assertEquals($expectedString, $from->toString());
    }

    public function gettersDataProvider(): array
    {
        return [
            'address only' => [
                'address' => 'sample@example.org',
                'name' => '',
                'expectedAddress' => 'sample@example.org',
                'expectedName' => '',
                'expectedString' => 'sample@example.org',
            ],
            'address with spaces' => [
                'address' => '  sample@example.org  ',
                'name' => '',
                'expectedAddress' => 'sample@example.org',
                'expectedName' => '',
                'expectedString' => 'sample@example.org',
            ],
            'address and name' => [
                'address' => ' sample@example.org ',
                'name' => 'sample name',
                'expectedAddress' => 'sample@example.org',
                'expectedName' => 'sample name',
                'expectedString' => '"sample name" <sample@example.org>',
            ],
            'address and name with spaces and new lines' => [
                'address' => ' sample@example.org ',
                'name' => "  sam\nple \r\nname  ",
                'expectedAddress' => 'sample@example.org',
                'expectedName' => 'sample name',
                'expectedString' => '"sample name" <sample@example.org>',
            ],
            'address with name' => [
                'address' => '"sample name" <sample@example.org>',
                'name' => "",
                'expectedAddress' => 'sample@example.org',
                'expectedName' => 'sample name',
                'expectedString' => '"sample name" <sample@example.org>',
            ],
            'address with name and custom name' => [
                'address' => '"sample name" <sample@example.org>',
                'name' => "another name",
                'expectedAddress' => 'sample@example.org',
                'expectedName' => 'another name',
                'expectedString' => '"another name" <sample@example.org>',
            ],
        ];
    }

    public function testGettersWhenEmailAddressFromSelf(): void
    {
        $address = From::emailAddress('sample@example.org', 'sample name');

        $from = From::emailAddress($address);
        self::assertEquals('sample@example.org', $from->getAddress());
        self::assertEquals('sample name', $from->getName());
        self::assertEquals(['sample@example.org', 'sample name'], $from->toArray());
        self::assertEquals('"sample name" <sample@example.org>', $from->toString());
    }

    public function testGettersWhenEmailAddressFromSelfWithCustomName(): void
    {
        $address = From::emailAddress('sample@example.org', 'sample name');

        $from = From::emailAddress($address, 'another name');
        self::assertEquals('sample@example.org', $from->getAddress());
        self::assertEquals('another name', $from->getName());
        self::assertEquals(['sample@example.org', 'another name'], $from->toArray());
        self::assertEquals('"another name" <sample@example.org>', $from->toString());
    }

    public function testGettersWithoutName(): void
    {
        $from = From::emailAddress('sample@example.org');
        self::assertEquals('sample@example.org', $from->getAddress());
        self::assertEquals('', $from->getName());
        self::assertEquals(['sample@example.org', ''], $from->toArray());
        self::assertEquals('sample@example.org', $from->toString());
    }
}
