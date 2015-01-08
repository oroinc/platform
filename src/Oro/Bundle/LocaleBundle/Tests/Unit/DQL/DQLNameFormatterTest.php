<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DQL;

use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;

class DQLNameFormatterTest extends \PHPUnit_Framework_TestCase
{
    /** @var NameFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $nameFormatter;

    /** @var DQLNameFormatter */
    protected $formatter;

    public function setUp()
    {
        $this->nameFormatter = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Formatter\NameFormatter')
            ->disableOriginalConstructor()->getMock();

        $this->formatter = new DQLNameFormatter($this->nameFormatter);
    }

    public function tearDown()
    {
        unset($this->formatter, $this->nameFormatter);
    }

    /**
     * @dataProvider metadataProvider
     *
     * @param string $expectedDQL
     * @param string $nameFormat
     * @param string $className
     */
    public function testGetFormattedNameDQL($expectedDQL, $nameFormat, $className)
    {
        $this->nameFormatter->expects($this->once())->method('getNameFormat')
            ->will($this->returnValue($nameFormat));

        $this->assertEquals($expectedDQL, $this->formatter->getFormattedNameDQL('a', $className));
    }

    /**
     * @return array
     */
    public function metadataProvider()
    {
        return [
            'first and last name exists'                                 => [
                "CONCAT(CASE WHEN a.lastName IS NOT NULL THEN a.lastName ELSE '' END, " .
                "CONCAT(' ', CONCAT(CASE WHEN a.firstName IS NOT NULL THEN a.firstName ELSE '' END, ' ')))",
                '%last_name% %first_name% %suffix%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'first and last name exists, first name should be uppercase' => [
                "CONCAT(CASE WHEN a.lastName IS NOT NULL THEN a.lastName ELSE '' END, " .
                "CONCAT(' ', CONCAT(UPPER(CASE WHEN a.firstName IS NOT NULL THEN a.firstName ELSE '' END), ' ')))",
                '%last_name% %FIRST_NAME% %suffix%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'full name format, and entity contains all parts'            => [
                "CONCAT(CASE WHEN a.namePrefix IS NOT NULL THEN a.namePrefix ELSE '' END, " .
                "CONCAT(' ', CONCAT(CASE WHEN a.lastName IS NOT NULL THEN a.lastName ELSE '' END, " .
                "CONCAT(' ', CONCAT(CASE WHEN a.firstName IS NOT NULL THEN a.firstName ELSE '' END, " .
                "CONCAT(' - ', CASE WHEN a.nameSuffix IS NOT NULL THEN a.nameSuffix ELSE '' END))))))",
                '%prefix% %last_name% %first_name% - %suffix%',
                $this->getMock('Oro\Bundle\LocaleBundle\Model\FullNameInterface')
            ]
        ];
    }
}
