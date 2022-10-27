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
    private $nameFormatter;

    /** @var DQLNameFormatter */
    private $formatter;

    protected function setUp(): void
    {
        $this->nameFormatter = $this->createMock(NameFormatter::class);

        $this->formatter = new DQLNameFormatter($this->nameFormatter);
    }

    /**
     * @dataProvider metadataProvider
     */
    public function testGetFormattedNameDQL(string $expectedDQL, string $nameFormat, object $className)
    {
        $this->nameFormatter->expects($this->once())
            ->method('getNameFormat')
            ->willReturn($nameFormat);

        $this->assertEquals($expectedDQL, $this->formatter->getFormattedNameDQL('a', $className));
    }

    public function metadataProvider(): array
    {
        return [
            'first and last name exists'                                 => [
                'TRIM(CONCAT('
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' \'), \'\')'
                . '))',
                '%last_name% %first_name% %suffix%',
                $this->createMock(FirstLastNameAwareInterface::class)
            ],
            'first and last name exists, has unknown placeholders'       => [
                'TRIM(CONCAT('
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' \'), \'\')'
                . '))',
                '%unknown_data_one% %last_name% %first_name% %suffix% %unknown_data_two%',
                $this->createMock(FirstLastNameAwareInterface::class)
            ],
            'has both prepend and append separators'                     => [
                'TRIM(CONCAT('
                . '\'(\', '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \') - \'), \'\')'
                . '))',
                '(%first_name% %last_name%) - %suffix%!',
                $this->createMock(FirstLastNameAwareInterface::class)
            ],
            'first and last name exists, first name should be uppercase' => [
                'TRIM(CONCAT('
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(UPPER(a.firstName) as string), \' \'), \'\')'
                . '))',
                '%last_name% %FIRST_NAME% %suffix%',
                $this->createMock(FirstLastNameAwareInterface::class)
            ],
            'full name format, and entity contains all parts'            => [
                'TRIM(CONCAT('
                . 'COALESCE(CONCAT(CAST(a.namePrefix as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.lastName as string), \' \'), \'\'), '
                . 'COALESCE(CONCAT(CAST(a.firstName as string), \' - \'), \'\'), '
                . 'COALESCE(CAST(a.nameSuffix as string), \'\')'
                . '))',
                '%prefix% %last_name% %first_name% - %suffix%',
                $this->createMock(FullNameInterface::class)
            ],
            'without separators'                                         => [
                'TRIM(CONCAT('
                . 'COALESCE(CAST(a.lastName as string), \'\'), '
                . 'COALESCE(CAST(a.firstName as string), \'\')'
                . '))',
                '%last_name%%first_name%',
                $this->createMock(FirstLastNameAwareInterface::class)
            ],
            'one item and prefix'                                        => [
                'TRIM(CONCAT('
                . '\' - \', '
                . 'COALESCE(CAST(a.lastName as string), \'\')'
                . '))',
                ' - %last_name%',
                $this->createMock(FirstLastNameAwareInterface::class)
            ],
        ];
    }

    /**
     * @dataProvider suggestedFieldNamesDataProvider
     */
    public function testGetSuggestedFieldNames(string|object $class, array $expected)
    {
        $this->assertEquals($expected, $this->formatter->getSuggestedFieldNames($class));
    }

    public function suggestedFieldNamesDataProvider(): array
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
