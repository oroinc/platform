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
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' \'), \'\')'
                . ')',
                '%last_name% %first_name% %suffix%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'first and last name exists, has unknown placeholders'       => [
                'CONCAT('
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' \'), \'\')'
                . ')',
                '%unknown_data_one% %last_name% %first_name% %suffix% %unknown_data_two%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'has both prepend and append separators'                     => [
                'CONCAT('
                . '\'(\', '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \') - \'), \'\')'
                . ')',
                '(%first_name% %last_name%) - %suffix%!',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'first and last name exists, first name should be uppercase' => [
                'CONCAT('
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(UPPER(a.firstName) as string), \' \'), \'\')'
                . ')',
                '%last_name% %FIRST_NAME% %suffix%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'full name format, and entity contains all parts'            => [
                'CONCAT('
                . 'COALESCE(CONCAT(CAST(a.namePrefix as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' - \'), \'\'), '
                . 'COALESCE(CAST(a.nameSuffix as string), \'\')'
                . ')',
                '%prefix% %last_name% %first_name% - %suffix%',
                $this->getMock('Oro\Bundle\LocaleBundle\Model\FullNameInterface')
            ],
            'without separators'                                         => [
                'CONCAT('
                . 'COALESCE(CAST(a.lastName as string), \'\'), '
                . 'COALESCE(CAST(a.firstName as string), \'\')'
                . ')',
                '%last_name%%first_name%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'one item and prefix'                                        => [
                'CONCAT('
                . '\' - \', '
                . 'COALESCE(CAST(a.lastName as string), \'\')'
                . ')',
                ' - %last_name%',
                $this->getMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
        ];
    }
}
