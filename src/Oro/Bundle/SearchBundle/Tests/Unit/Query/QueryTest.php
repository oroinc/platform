<?php

namespace Oro\Bundle\SearchBundle\Tests\Unit\Query;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Query;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    private $config = array(
        'Oro\Bundle\DataBundle\Entity\Product' => array(
            'alias' => 'test_alias',
            'fields' => array(
                array(
                    'name' => 'name',
                    'target_type' => 'string',
                    'target_fields' => array('name', 'all_data')
                ),
                array(
                    'name' => 'description',
                    'target_type' => 'string',
                    'target_fields' => array('description', 'all_data')
                ),
                array(
                    'name' => 'price',
                    'target_type' => 'decimal',
                    'target_fields' => array('price')
                ),
                array(
                    'name' => 'count',
                    'target_type' => 'integer',
                    'target_fields' => array('count')
                ),
                array(
                    'name' => 'createDate',
                    'target_type' => 'datetime',
                    'target_fields' => array('create_date')
                ),
                array(
                    'name' => 'manufacturer',
                    'relation_type' => 'to',
                    'relation_fields' => array(
                        array(
                            'name' => 'name',
                            'target_type' => 'string',
                            'target_fields' => array('manufacturer', 'all_data')
                        )
                    )
                ),
            )
        )
    );

    /**
     * Set mapping config parameters
     */
    public function testSetMappingConfig()
    {
        $query = new Query();
        $query->setMappingConfig($this->config);

        $this->assertArrayHasKey('Oro\Bundle\DataBundle\Entity\Product', $query->getMappingConfig());
        $this->assertArrayHasKey('all_data', $query->getFields());
    }

    public function testAndWhere()
    {
        $query = new Query();
        $query->setMappingConfig($this->config);
        $query->from('Oro\Bundle\DataBundle\Entity\Product');
        $query->andWhere('all_data', '=', 'test', 'string');

        $whereExpression = $query->getCriteria()->getWhereExpression();
        $this->assertEquals('string.all_data', $whereExpression->getField());
        $this->assertEquals(Comparison::EQ, $whereExpression->getOperator());
        $this->assertEquals('test', $whereExpression->getValue()->getValue());
    }

    public function testGetMaxResults()
    {
        $query = new Query();
        $query->setMaxResults(10);
        $this->assertEquals(10, $query->getMaxResults());
    }

    public function testOrWhere()
    {
        $query = new Query();
        $query->setMappingConfig($this->config);
        $query->from('Oro\Bundle\DataBundle\Entity\Product');
        $query->orWhere('all_data', '=', 'test', 'string');

        $whereExpression = $query->getCriteria()->getWhereExpression();
        $this->assertEquals('string.all_data', $whereExpression->getField());
        $this->assertEquals(Comparison::EQ, $whereExpression->getOperator());
        $this->assertEquals('test', $whereExpression->getValue()->getValue());
    }

    public function testWhere()
    {
        $query = new Query();
        $query->setMappingConfig($this->config);
        $query->from('Oro\Bundle\DataBundle\Entity\Product');
        $query->where('or', 'all_data', '=', 'test', 'string');

        $whereExpression = $query->getCriteria()->getWhereExpression();
        $this->assertEquals('string.all_data', $whereExpression->getField());
        $this->assertEquals(Comparison::EQ, $whereExpression->getOperator());
        $this->assertEquals('test', $whereExpression->getValue()->getValue());
    }

    public function testGetEntityByAlias()
    {
        $query = new Query();
        $query->setMappingConfig($this->config);
        $result = $query->getEntityByAlias('test_alias');
        $this->assertEquals('Oro\Bundle\DataBundle\Entity\Product', $result);

        $this->assertFalse($query->getEntityByAlias('bad alias'));
    }

    public function testStringCleanup()
    {
        $testString = 'Re: FW: Test Sample - One äöü ßü abc 3 – Testing again';

        $clearedValue     = Query::clearString($testString);
        $textAllDataField = sprintf('%s %s', $testString, $clearedValue);

        $result = implode(
            Query::DELIMITER,
            array_unique(
                explode(
                    Query::DELIMITER,
                    $textAllDataField
                )
            )
        );

        $this->assertEquals($testString, $result);
    }

    public function testAddingSelect()
    {
        $query = new Query();
        $query->addSelect('name', Query::TYPE_TEXT);
        $query->addSelect('name', Query::TYPE_TEXT); // testing handing doubles
        $query->addSelect('datetime.date', Query::TYPE_DECIMAL); // enforced type
        $query->addSelect('integer.number'); // type guessing by prefix
        $query->addSelect('sku'); // default type should be `text`

        $this->assertCount(4, $query->getSelect());
        $this->assertEquals(
            [
                'text.name',
                'decimal.date',
                'integer.number',
                'text.sku'
            ],
            $query->getSelect()
        );

        $query->addSelect('');
        $this->assertCount(4, $query->getSelect());
    }

    public function testAddSelectArray()
    {
        $query = new Query();
        $query->addSelect([
            'name',
            'name', // testing handing doubles
            'integer.number', // type guessing by prefix
            'sku', // default type should be `text`
        ]);

        $this->assertEquals(
            [
                'text.name',
                'integer.number',
                'text.sku'
            ],
            $query->getSelect()
        );

        $query->addSelect('');
        $this->assertCount(3, $query->getSelect());
    }

    public function testStringQueryWithSelect()
    {
        $query = new Query();
        $this->assertEquals('', $query->getStringQuery());
        $query->from('*');
        $this->assertEquals('from *', $query->getStringQuery());
        $query->select('language');
        $this->assertEquals('select text.language from *', $query->getStringQuery());
        $query->addSelect('organization', 'integer');
        $this->assertEquals('select (text.language, integer.organization) from *', $query->getStringQuery());
    }

    public function testSelectingWithAliases()
    {
        $query = new Query();
        $query->addSelect('text.foo as bar');
        $query->addSelect('text.fooNoAlias');
        $query->addSelect('text.foo bar as  ');
        $query->addSelect('  as bar');

        $reflectionObject = new \ReflectionObject($query);

        $selectFieldsProperty = $reflectionObject->getProperty('select');
        $selectFieldsProperty->setAccessible(true);

        $aliasesProperty = $reflectionObject->getProperty('selectAliases');
        $aliasesProperty->setAccessible(true);

        $fields = $selectFieldsProperty->getValue($query);
        $aliases = $aliasesProperty->getValue($query);

        $this->assertContains('text.foo', $fields);
        $this->assertContains('text.fooNoAlias', $fields);
        $this->assertContains('bar', $aliases);

        $this->assertTrue(count($aliases) < 2);

        foreach ($selectFieldsProperty as $field) {
            $this->assertNotTrue(strpos($field, ' ') > 0);
        }
    }

    public function testGetSelectWithAliases()
    {
        $query = new Query();
        $query->addSelect('text.foo as bar');
        $query->addSelect('text.faa as bor');

        $select = $query->getSelect();

        $this->assertSame(['text.foo', 'text.faa'], $select);
    }

    public function testGetAliases()
    {
        $query = new Query();
        $query->addSelect('text.foo as bar');
        $query->addSelect('text.faa as bor');

        $aliases = $query->getSelectAliases();

        $this->assertSame(['text.foo' => 'bar', 'text.faa' => 'bor'], $aliases);
    }

    public function testGetSelectDataFields()
    {
        $query = new Query();
        $this->assertSame([], $query->getSelectDataFields());

        $query->addSelect('text.notes');
        $this->assertSame(['text.notes' => 'notes'], $query->getSelectDataFields());

        $query->addSelect('text.foo as name');
        $query->addSelect('text.faa as surname');

        $fields = $query->getSelectDataFields();

        $this->assertSame(['text.notes' => 'notes', 'text.foo' => 'name', 'text.faa' => 'surname'], $fields);
    }
}
