<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Engine\Orm;

use Carbon\Carbon;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\SearchBundle\Engine\Orm\DbalStorer;
use Oro\Bundle\SearchBundle\Entity\IndexDatetime;
use Oro\Bundle\SearchBundle\Entity\IndexDecimal;
use Oro\Bundle\SearchBundle\Entity\IndexInteger;
use Oro\Bundle\SearchBundle\Entity\IndexText;
use Oro\Bundle\SearchBundle\Tests\Unit\Fixture\Entity\Item;

class DbalStorerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DbalStorer */
    protected $dbalStorer;

    /** @var Connection|\PHPUnit_Framework_MockObject_MockObject */
    protected $connection;

    public function setUp()
    {
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection->expects($this->any())
            ->method('quoteIdentifier')
            ->will($this->returnCallback(function ($identifier) {
                return $identifier;
            }));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $em->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connection));

        $doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with('OroSearchBundle:Item')
            ->will($this->returnValue($em));

        $this->dbalStorer = new DbalStorer($doctrineHelper);
    }

    public function tearDown()
    {
        Carbon::setTestNow();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testStore()
    {
        $item1 = (new Item())
            ->setAlias('alias1')
            ->setChanged(true)
            ->setEntity('StdClass')
            ->setRecordId(1)
            ->addIntegerField(
                (new IndexInteger())
                    ->setField('intField')
                    ->setValue(3)
            )
            ->addIntegerField(
                (new IndexInteger())
                    ->setField('intField2')
                    ->setValue(5)
            )
            ->setTitle('item1');

        $item2 = (new Item())
            ->setAlias('alias2')
            ->setChanged(true)
            ->setEntity('StdClass')
            ->setRecordId(1)
            ->setTitle('item2')
            ->addDatetimeField(
                (new IndexDatetime())
                    ->setField('dateTimeField')
                    ->setValue(new \DateTime('2016-01-01 13:55:16'))
            )
            ->addTextField(
                (new IndexText())
                    ->setField('text')
                    ->setValue('val')
            );

        $item3 = (new Item())
            ->setId(3)
            ->setAlias('alias3')
            ->setChanged(true)
            ->setEntity('StdClass')
            ->setRecordId(1)
            ->setTitle('item3')
            ->setCreatedAt(new \DateTime('2016-03-01 13:52:12'))
            ->addDecimalField(
                (new IndexDecimal())
                    ->setField('decimalField')
                    ->setValue(0.3)
            );

        $this->dbalStorer->addItem($item1);
        $this->dbalStorer->addItem($item2);
        $this->dbalStorer->addItem($item3);

        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->connection->expects($this->exactly(2))
            ->method('insert')
            ->withConsecutive(
                [
                    'oro_search_item',
                    $this->itemInsertData($item1),
                    $this->itemInsertTypes(),
                ],
                [
                    'oro_search_item',
                    $this->itemInsertData($item2),
                    $this->itemInsertTypes(),
                ]
            );

        $this->connection->expects($this->once())
            ->method('update')
            ->with(
                'oro_search_item',
                $this->itemUpdateData($item3),
                ['id' => $item3->getId()],
                $this->itemUpdateTypes()
            );

        $this->connection->expects($this->exactly(2))
            ->method('lastInsertId')
            ->will($this->onConsecutiveCalls(1, 2));

        $this->connection->expects($this->exactly(4))
            ->method('executeQuery')
            ->withConsecutive(
                [
                    'INSERT INTO oro_search_index_integer (field, value, item_id) VALUES (?, ?, ?), (?, ?, ?)',
                    ['intField', 3, 1, 'intField2', 5, 1],
                    $this->indexInsertTypes(Type::INTEGER, 2),
                ],
                [
                    'INSERT INTO oro_search_index_text (field, value, item_id) VALUES (?, ?, ?)',
                    ['text', 'val', 2],
                    $this->indexInsertTypes(Type::TEXT),
                ],
                [
                    'INSERT INTO oro_search_index_datetime (field, value, item_id) VALUES (?, ?, ?)',
                    ['dateTimeField', new \DateTime('2016-01-01 13:55:16'), 2],
                    $this->indexInsertTypes(Type::DATETIME),
                ],
                [
                    'INSERT INTO oro_search_index_decimal (field, value, item_id) VALUES (?, ?, ?)',
                    ['decimalField', 0.3, 3],
                    $this->indexInsertTypes(Type::DECIMAL),
                ]
            );

        $this->connection->expects($this->exactly(1))
            ->method('update')
            ->with('oro_search_item', $this->itemUpdateData($item3), ['id' => 3], $this->itemUpdateTypes());

        $this->dbalStorer->store();
    }

    /**
     * @param Item $item
     *
     * @return array
     */
    protected function itemInsertData(Item $item)
    {
        return [
            'entity'     => $item->getEntity(),
            'alias'      => $item->getAlias(),
            'record_id'  => $item->getRecordId(),
            'title'      => $item->getTitle(),
            'changed'    => $item->getChanged(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }

    /**
     * @return array
     */
    protected function itemInsertTypes()
    {
        return [
            Type::STRING,
            Type::STRING,
            Type::INTEGER,
            Type::STRING,
            Type::BOOLEAN,
            Type::DATETIME,
            Type::DATETIME,
        ];
    }

    /**
     * @param Item $item
     *
     * @return array
     */
    protected function itemUpdateData(Item $item)
    {
        return [
            'id'         => $item->getId(),
            'entity'     => $item->getEntity(),
            'alias'      => $item->getAlias(),
            'record_id'  => $item->getRecordId(),
            'title'      => $item->getTitle(),
            'changed'    => $item->getChanged(),
            'created_at' => $item->getCreatedAt(),
            'updated_at' => Carbon::now(),
        ];
    }

    /**
     * @return array
     */
    protected function itemUpdateTypes()
    {
        return [
            Type::INTEGER,
            Type::STRING,
            Type::STRING,
            Type::INTEGER,
            Type::STRING,
            Type::BOOLEAN,
            Type::DATETIME,
            Type::DATETIME,
        ];
    }

    /**
     * @param string $valueType
     * @param int $n
     *
     * @return array
     */
    protected function indexInsertTypes($valueType, $n = 1)
    {
        return call_user_func_array(
            'array_merge',
            array_fill(
                0,
                $n,
                [
                    Type::STRING,
                    $valueType,
                    Type::INTEGER,
                ]
            )
        );
    }
}
