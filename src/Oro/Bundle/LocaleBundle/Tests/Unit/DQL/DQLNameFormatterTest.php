<?php

namespace Oro\Bundle\LocaleBundle\Tests\Unit\DQL;

use Oro\Bundle\LocaleBundle\DQL\DQLNameFormatter;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\LocaleBundle\Model\FirstNameInterface;
use Oro\Bundle\LocaleBundle\Model\FullNameInterface;
use Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface;

class DQLNameFormatterTest extends \PHPUnit\Framework\TestCase
{
    /** @var NameFormatter|\PHPUnit\Framework\MockObject\MockObject */
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
                'TRIM(CONCAT('
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' \'), \'\')'
                . '))',
                '%last_name% %first_name% %suffix%',
                $this->createMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'first and last name exists, has unknown placeholders'       => [
                'TRIM(CONCAT('
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' \'), \'\')'
                . '))',
                '%unknown_data_one% %last_name% %first_name% %suffix% %unknown_data_two%',
                $this->createMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'has both prepend and append separators'                     => [
                'TRIM(CONCAT('
                . '\'(\', '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \') - \'), \'\')'
                . '))',
                '(%first_name% %last_name%) - %suffix%!',
                $this->createMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'first and last name exists, first name should be uppercase' => [
                'TRIM(CONCAT('
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(UPPER(a.firstName) as string), \' \'), \'\')'
                . '))',
                '%last_name% %FIRST_NAME% %suffix%',
                $this->createMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'full name format, and entity contains all parts'            => [
                'TRIM(CONCAT('
                . 'COALESCE(CONCAT(CAST(a.namePrefix as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' - \'), \'\'), '
                . 'COALESCE(CAST(a.nameSuffix as string), \'\')'
                . '))',
                '%prefix% %last_name% %first_name% - %suffix%',
                $this->createMock('Oro\Bundle\LocaleBundle\Model\FullNameInterface')
            ],
            'without separators'                                         => [
                'TRIM(CONCAT('
                . 'COALESCE(CAST(a.lastName as string), \'\'), '
                . 'COALESCE(CAST(a.firstName as string), \'\')'
                . '))',
                '%last_name%%first_name%',
                $this->createMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
            'one item and prefix'                                        => [
                'TRIM(CONCAT('
                . '\' - \', '
                . 'COALESCE(CAST(a.lastName as string), \'\')'
                . '))',
                ' - %last_name%',
                $this->createMock('Oro\Bundle\LocaleBundle\Tests\Unit\Fixtures\FirstLastNameAwareInterface')
            ],
        ];
    }

    /**
     * @dataProvider suggestedFieldNamesDataProvider
     *
     * @param string|object $class
     * @param array $expected
     */
    public function testGetSuggestedFieldNames($class, array $expected)
    {
        $this->assertEquals($expected, $this->formatter->getSuggestedFieldNames($class));
    }

    public function suggestedFieldNamesDataProvider()
    {
        return [

            [
                'class' => FirstNameInterface::class,
                'expected' => ['first_name' => 'firstName']
            ],
            [
                'class' => FirstLastNameAwareInterface::class,
                'expected' => ['first_name' => 'firstName', 'last_name' => 'lastName']
            ],
            [
                'class' => FullNameInterface::class,
                'expected' => [
                    'first_name' => 'firstName',
                    'middle_name' => 'middleName',
                    'last_name' => 'lastName',
                    'prefix' => 'namePrefix',
                    'suffix' => 'nameSuffix',
                ],
            ],
            [
                'class' => $this->createMock(FullNameInterface::class),
                'expected' => [
                    'first_name' => 'firstName',
                    'middle_name' => 'middleName',
                    'last_name' => 'lastName',
                    'prefix' => 'namePrefix',
                    'suffix' => 'nameSuffix',
                ],
            ],
        ];
    }
}
