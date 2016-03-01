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
                'CONCAT('
                . 'CASE WHEN NULLIF(a.lastName, \'\') IS NULL THEN \'\' ELSE CONCAT(a.lastName, \' \') END, '
                . 'CASE WHEN NULLIF(a.firstName, \'\') IS NULL THEN \'\' ELSE CONCAT(a.firstName, \' \') END'
                . ')',
                '%last_name% %first_name% %suffix%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'first and last name exists, has unknown placeholders'       => [
                'CONCAT('
                . 'CASE WHEN NULLIF(a.lastName, \'\') IS NULL THEN \'\' ELSE CONCAT(a.lastName, \' \') END, '
                . 'CASE WHEN NULLIF(a.firstName, \'\') IS NULL THEN \'\' ELSE CONCAT(a.firstName, \' \') END'
                . ')',
                '%unknown_data_one% %last_name% %first_name% %suffix% %unknown_data_two%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'has both prepend and append separators'                     => [
                'CONCAT('
                . '\'(\', '
                . 'CONCAT('
                . 'CASE WHEN NULLIF(a.firstName, \'\') IS NULL THEN \'\' ELSE CONCAT(a.firstName, \' \') END, '
                . 'CASE WHEN NULLIF(a.lastName, \'\') IS NULL THEN \'\' ELSE CONCAT(a.lastName, \') - \') END'
                . ')'
                . ')',
                '(%first_name% %last_name%) - %suffix%!',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'first and last name exists, first name should be uppercase' => [
                'CONCAT('
                . 'CASE WHEN NULLIF(a.lastName, \'\') IS NULL THEN \'\' ELSE CONCAT(a.lastName, \' \') END, '
                . 'CASE WHEN NULLIF(UPPER(a.firstName), \'\') IS NULL'
                . ' THEN \'\' ELSE CONCAT(UPPER(a.firstName), \' \') END'
                . ')',
                '%last_name% %FIRST_NAME% %suffix%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'full name format, and entity contains all parts'            => [
                'CONCAT('
                . 'CASE WHEN NULLIF(a.namePrefix, \'\') IS NULL THEN \'\' ELSE CONCAT(a.namePrefix, \' \') END, '
                . 'CONCAT('
                . 'CASE WHEN NULLIF(a.lastName, \'\') IS NULL THEN \'\' ELSE CONCAT(a.lastName, \' \') END, '
                . 'CONCAT('
                . 'CASE WHEN NULLIF(a.firstName, \'\') IS NULL THEN \'\' ELSE CONCAT(a.firstName, \' - \') END, '
                . 'CASE WHEN NULLIF(a.nameSuffix, \'\') IS NULL THEN \'\' ELSE a.nameSuffix END'
                . ')'
                . ')'
                . ')',
                '%prefix% %last_name% %first_name% - %suffix%',
                $this->getMock('Oro\Bundle\LocaleBundle\Model\FullNameInterface')
            ],
            'without separators'                                         => [
                'CONCAT('
                . 'CASE WHEN NULLIF(a.lastName, \'\') IS NULL THEN \'\' ELSE a.lastName END, '
                . 'CASE WHEN NULLIF(a.firstName, \'\') IS NULL THEN \'\' ELSE a.firstName END'
                . ')',
                '%last_name%%first_name%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'one item and prefix'                                        => [
                'CONCAT('
                . '\' - \', '
                . 'CASE WHEN NULLIF(a.lastName, \'\') IS NULL THEN \'\' ELSE a.lastName END'
                . ')',
                ' - %last_name%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
        ];
    }
}
