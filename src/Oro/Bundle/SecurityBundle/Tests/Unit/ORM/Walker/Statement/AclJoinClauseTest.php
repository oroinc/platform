<?php
namespace Oro\Bundle\SecurityBundle\Tests\Unit\ORM\Walker\Condition;

use Doctrine\ORM\Query\AST\Join;

use Oro\Bundle\SecurityBundle\ORM\Walker\Statement\AclJoinClause;

class AclJoinClauseTest extends \PHPUnit_Framework_TestCase
{
    /** @var AclJoinClause */
    protected $obj;

    protected function setUp()
    {
        $this->obj = new AclJoinClause('Oro\Bundle\SecurityBundle\Entity\AclEntry', 'entries', 'c1', 'entries1');
    }

    public function testConstruct()
    {
        $this->assertEquals('Oro\Bundle\SecurityBundle\Entity\AclEntry', $this->obj->getAbstractSchemaName());
        $this->assertEquals('entries', $this->obj->getAliasIdentificationVariable());
        $this->assertEquals('c1', $this->obj->getIdentificationVariable());
        $this->assertEquals('entries1', $this->obj->getAssociationField());
        $this->assertEquals(Join::JOIN_TYPE_INNER, $this->obj->getJoinType());
    }

    /**
     * @dataProvider flatPropertiesDataProvider
     */
    public function testGetSet($property, $value, $expected)
    {
        call_user_func_array([$this->obj, 'set' . ucfirst($property)], [$value]);
        $this->assertEquals($expected, call_user_func_array([$this->obj, 'get' . ucfirst($property)], []));
    }

    public function testIsAssociationJoin()
    {
        $this->assertTrue($this->obj->isAssociationJoin());
        $this->obj->setAssociationField(null);
        $this->assertFalse($this->obj->isAssociationJoin());
        $this->obj->setIdentificationVariable(null);
        $this->assertFalse($this->obj->isAssociationJoin());
        $this->obj->setAssociationField('id');
        $this->assertFalse($this->obj->isAssociationJoin());
    }

    public function flatPropertiesDataProvider()
    {
        return [
            'abstractSchemaName' => [
                'abstractSchemaName',
                'Oro\Bundle\SecurityBundle\Entity\AclEntry',
                'Oro\Bundle\SecurityBundle\Entity\AclEntry'
            ],
            'aliasIdentificationVariable' => ['aliasIdentificationVariable', 'entries', 'entries'],
            'identificationVariable' => ['identificationVariable', 'c', 'c'],
            'associationField' => ['associationField', 'entries', 'entries'],
            'joinType' => ['joinType', Join::JOIN_TYPE_LEFT, Join::JOIN_TYPE_LEFT],
        ];
    }
}
