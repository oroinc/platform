<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Translation;

use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\TranslationBundle\Translation\DatabasePersister;

class DatabasePersisterTest extends \PHPUnit_Framework_TestCase
{
    /** @var NativeQueryExecutorHelper */
    private $nativeQueryExecutorHelper;

    /** @var array */
    protected $testData = [
        'messages'   => [
            'key_1' => 'value_1',
            'key_2' => 'value_2',
            'key_3' => 'value_3',
        ],
        'validators' => [
            'key_1' => 'value_1',
            'key_2' => 'value_2',
        ]
    ];

    /** @var string */
    protected $testLocale = 'en';

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->nativeQueryExecutorHelper =
            $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper')
                ->disableOriginalConstructor()
                ->getMock();

        $this->nativeQueryExecutorHelper->expects($this->once())
            ->method('getTableName')
            ->willReturn('oro_test_table');
    }

    protected function tearDown()
    {
        unset($this->nativeQueryExecutorHelper);
    }

    public function testAllNewTranslationsInserted()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->exactly(2))->method('fetchAll')->willReturn([]);
        $connection->expects($this->exactly(5))->method('insert');
        $connection->expects($this->never())->method('update');
        $connection->expects($this->once())->method('commit');
        $connection->expects($this->never())->method('rollback');

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $metadataCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataCache->expects($this->once())->method('updateTimestamp')->with($this->testLocale);

        $persister = new DatabasePersister($doctrine, $this->nativeQueryExecutorHelper, $metadataCache);
        $persister->persist($this->testLocale, $this->testData);
    }

    public function testInsertAndUpdateScenario()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->exactly(2))->method('fetchAll')->willReturnOnConsecutiveCalls(
            [
                ['id' => 1, 'key' => 'key_1', 'value' => 'value_1'], //existing translation, to be skipped
                ['id' => 2, 'key' => 'key_2', 'value' => 'value_02'], //existing with different value, to be updated
            ],
            [
                ['id' => 4, 'key' => 'key_1', 'value' => 'value_1'], //existing translation, to be skipped
                ['id' => 5, 'key' => 'key_2', 'value' => 'value_02'], //existing with different value, to be updated
            ]
        );
        $connection->expects($this->exactly(1))->method('insert');
        $connection->expects($this->exactly(2))->method('update');

        $connection->expects($this->once())->method('commit');
        $connection->expects($this->never())->method('rollback');

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $metadataCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataCache->expects($this->once())->method('updateTimestamp')->with($this->testLocale);

        $persister = new DatabasePersister($doctrine, $this->nativeQueryExecutorHelper, $metadataCache);
        $persister->persist($this->testLocale, $this->testData);
    }

    public function testExceptionScenario()
    {
        $exceptionClass = '\LogicException';
        $this->setExpectedException($exceptionClass);
        $exception = new $exceptionClass();

        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->once())->method('beginTransaction');
        $connection->expects($this->any())->method('fetchAll')->willReturn([]);
        $connection->expects($this->exactly(5))->method('insert');
        $connection->expects($this->never())->method('update');
        $connection->expects($this->once())->method('commit')->will($this->throwException($exception));
        $connection->expects($this->once())->method('rollback');

        $doctrine = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrine->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection);

        $metadataCache = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\DynamicTranslationMetadataCache')
            ->disableOriginalConstructor()
            ->getMock();
        $metadataCache->expects($this->never())->method('updateTimestamp');

        $persister = new DatabasePersister($doctrine, $this->nativeQueryExecutorHelper, $metadataCache);
        $persister->persist($this->testLocale, $this->testData);
    }
}
