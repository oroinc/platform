<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration;

use Oro\Bundle\EntityConfigBundle\Tests\Unit\EntityConfig\Mock\ConfigurationHandlerMock;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Component\Testing\ReflectionUtil;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ExtendOptionsManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ExtendOptionsManager */
    private $manager;

    protected function setUp(): void
    {
        $this->manager = new ExtendOptionsManager(ConfigurationHandlerMock::getInstance());
    }

    public function testSetTableMode()
    {
        $this->manager->setTableMode('test_table', 'default');
        $this->assertEquals(
            ['test_table' => [ExtendOptionsManager::MODE_OPTION => 'default']],
            $this->manager->getExtendOptions()
        );
    }

    public function testSetColumnMode()
    {
        $this->manager->setColumnMode('test_table', 'test_clmn', 'default');
        $this->assertEquals(
            ['test_table!test_clmn' => [ExtendOptionsManager::MODE_OPTION => 'default']],
            $this->manager->getExtendOptions()
        );
    }

    public function testSetColumnType()
    {
        $this->manager->setColumnType('test_table', 'test_clmn', 'int');
        $this->assertEquals(
            ['test_table!test_clmn' => [ExtendOptionsManager::TYPE_OPTION => 'int']],
            $this->manager->getExtendOptions()
        );
    }

    /**
     * @dataProvider setTableOptionsProvider
     */
    public function testSetTableOptions(
        $tableName,
        array $options,
        array $prevValues,
        array $expected
    ) {
        if (!empty($prevValues)) {
            ReflectionUtil::setPropertyValue($this->manager, 'options', $prevValues);
        }
        $this->manager->setTableOptions($tableName, $options);
        $this->assertEquals($expected, $this->manager->getExtendOptions());
    }

    /**
     * @dataProvider setColumnOptionsProvider
     */
    public function testSetColumnOptions(
        $tableName,
        $columnName,
        array $options,
        array $prevValues,
        array $expected
    ) {
        if (!empty($prevValues)) {
            ReflectionUtil::setPropertyValue($this->manager, 'options', $prevValues);
        }
        $this->manager->setColumnOptions($tableName, $columnName, $options);
        $this->assertEquals($expected, $this->manager->getExtendOptions());
    }

    public function testMergeColumnOptionsWhenThereIsNoExisting()
    {
        $options = ['scope' => ['new_option' => true]];
        $this->manager->mergeColumnOptions('test_table', 'test_column', $options);
        $objectKey = sprintf(ExtendOptionsManager::COLUMN_OPTION_FORMAT, 'test_table', 'test_column');
        $expectedOptions = [$objectKey => $options];
        $this->assertEquals($expectedOptions, $this->manager->getExtendOptions());
    }

    /**
     * @dataProvider dataProviderForMergeColumnOptions
     */
    public function testMergeColumnOptions(array $existingOptions, array $newOptions, array $expectedOptions)
    {
        $objectKey = sprintf(ExtendOptionsManager::COLUMN_OPTION_FORMAT, 'test_table', 'test_column');
        ReflectionUtil::setPropertyValue($this->manager, 'options', [$objectKey => $existingOptions]);

        $this->manager->mergeColumnOptions('test_table', 'test_column', $newOptions);
        $this->assertEquals([$objectKey => $expectedOptions], $this->manager->getExtendOptions());
    }

    public function dataProviderForMergeColumnOptions(): array
    {
        return [
            [
                'existing' => ['scope' => ['new_option' => true]],
                'new' => ['scope' => ['new_option' => false]],
                'expected' => ['scope' => ['new_option' => false]]
            ],
            [
                'existing' => ['scope' => ['array_case' => ['op1' => 1, 'op2' => 2]]],
                'new' => ['scope' => ['array_case' => ['op2' => 3]]],
                'expected' => ['scope' => ['array_case' => ['op1' => 1, 'op2' => 3]]],
            ],
        ];
    }

    public function setTableOptionsProvider(): array
    {
        return $this->getSetOptionsData('test_table');
    }

    public function setColumnOptionsProvider(): array
    {
        return $this->getSetOptionsData('test_table', 'test_clmn');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getSetOptionsData(string $tableName, ?string $columnName = null): array
    {
        $result = [];

        $this->addSetOptionsDataItem(
            'empty',
            [
                [],
                [],
                []
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'empty scope',
            [
                ['scope' => []],
                [],
                ['scope' => []]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'set new',
            [
                ['scope' => ['attr' => 'test']],
                [],
                ['scope' => ['attr' => 'test']]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'set new (array)',
            [
                ['scope' => ['attr' => ['test']]],
                [],
                ['scope' => ['attr' => ['test']]]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'replace existing attr',
            [
                ['scope' => ['attr' => 'test']],
                ['scope' => ['attr' => 'old', 'other_attr' => 'other']],
                ['scope' => ['attr' => 'test', 'other_attr' => 'other']]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'replace existing attr (array)',
            [
                ['scope' => ['attr' => ['test2']]],
                ['scope' => ['attr' => ['test1']]],
                ['scope' => ['attr' => ['test2']]]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'replace existing attr (null -> array)',
            [
                ['scope' => ['attr' => null]],
                ['scope' => ['attr' => ['test1']]],
                ['scope' => ['attr' => null]]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'append new',
            [
                $this->getAppendedOption('scope', 'attr', 'test'),
                [],
                [
                    'scope'                              => ['attr' => ['test']],
                    ExtendOptionsManager::APPEND_SECTION => ['scope' => ['attr']]
                ]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'append new (with existing another attr in the same scope)',
            [
                $this->getAppendedOption('scope', 'attr', 'test'),
                [
                    'scope' => ['another_attr' => 'test'],
                ],
                [
                    'scope'                              => ['attr' => ['test'], 'another_attr' => 'test'],
                    ExtendOptionsManager::APPEND_SECTION => ['scope' => ['attr']]
                ]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'append existing',
            [
                $this->getAppendedOption('scope', 'attr', 'test2'),
                ['scope' => ['attr' => ['test1']]],
                [
                    'scope'                              => ['attr' => ['test1', 'test2']],
                    ExtendOptionsManager::APPEND_SECTION => ['scope' => ['attr']]
                ]
            ],
            $result,
            $tableName,
            $columnName
        );
        $this->addSetOptionsDataItem(
            'append existing empty',
            [
                $this->getAppendedOption('scope', 'attr', []),
                ['scope' => ['attr' => ['test1']]],
                [
                    'scope'                              => ['attr' => ['test1']],
                    ExtendOptionsManager::APPEND_SECTION => ['scope' => ['attr']]
                ]
            ],
            $result,
            $tableName,
            $columnName
        );

        return $result;
    }

    public function testRemoveTableOptions()
    {
        $tableName = 'test_table';

        $this->manager->setTableOptions(
            $tableName,
            ['key' => ['value' => 1], '_append' => ['appended' => ['data']]]
        );
        $this->assertEquals(
            [
                $tableName => ['key' => ['value' => 1]],
                '_append' => [$tableName => ['appended' => ['data']]]
            ],
            $this->manager->getExtendOptions()
        );

        $this->manager->removeTableOptions($tableName);
        $this->assertEquals(['_append' => []], $this->manager->getExtendOptions());
    }

    public function testRemoveColumnOptions()
    {
        $tableName = 'test_table';
        $columnName = 'test_column';
        $combinedName = sprintf('%s!%s', $tableName, $columnName);

        $this->manager->setColumnOptions(
            $tableName,
            $columnName,
            ['key' => ['value' => 1], '_append' => ['appended' => ['data']]]
        );
        $this->assertEquals(
            [
                $combinedName => ['key' => ['value' => 1]],
                '_append' => [$combinedName => ['appended' => ['data']]]
            ],
            $this->manager->getExtendOptions()
        );

        $this->manager->removeColumnOptions($tableName, $columnName);
        $this->assertEquals(['_append' => []], $this->manager->getExtendOptions());
    }

    private function addSetOptionsDataItem(
        string $testName,
        array $data,
        array &$result,
        string $tableName,
        ?string $columnName = null
    ): void {
        $key = $tableName;
        if (null !== $columnName) {
            $key .= '!' . $columnName;
        }

        $options      = $data[0];
        $prevData     = $this->processSetOptionsData($key, $data[1]);
        $expectedData = $this->processSetOptionsData($key, $data[2]);

        if (null === $columnName) {
            $result[$testName] = [$tableName, $options, $prevData, $expectedData];
        } else {
            $result[$testName] = [$tableName, $columnName, $options, $prevData, $expectedData];
        }
    }

    private function processSetOptionsData(string $key, array $data): array
    {
        $result = [$key => $data];

        if (isset($result[$key][ExtendOptionsManager::APPEND_SECTION])) {
            $tmp = [$key => $result[$key][ExtendOptionsManager::APPEND_SECTION]];
            unset($result[$key][ExtendOptionsManager::APPEND_SECTION]);
            $result[ExtendOptionsManager::APPEND_SECTION] = $tmp;
        }

        return $result;
    }

    private function getAppendedOption(string $scope, string $code, mixed $val): array
    {
        $options = new OroOptions();
        $options->append($scope, $code, $val);

        return $options->toArray();
    }

    public function testHasColumnOptionsWhenNoOptionsExist()
    {
        $this->assertFalse($this->manager->hasColumnOptions('some_table', 'some_column'));
    }

    public function testHasColumnOptionsWhenOptionsExist()
    {
        $this->manager->setColumnOptions('some_table', 'some_column', ['some' => ['options']]);
        $this->assertTrue($this->manager->hasColumnOptions('some_table', 'some_column'));
    }

    public function testGetColumnOptionsWhenNoOptionsExist()
    {
        $this->assertEquals([], $this->manager->getColumnOptions('some_table', 'some_column'));
    }

    public function testGetColumnOptionsWhenOptionsExist()
    {
        $this->manager->setColumnOptions('some_table', 'some_column', ['some' => ['options']]);
        $this->assertEquals(['some' => ['options']], $this->manager->getColumnOptions('some_table', 'some_column'));
    }
}
