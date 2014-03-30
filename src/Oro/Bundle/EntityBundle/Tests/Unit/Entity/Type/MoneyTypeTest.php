<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Entity\Type;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\EntityBundle\Entity\Type\MoneyType;
use Doctrine\DBAL\Types\Type;

class MoneyTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MoneyType
     */
    protected $type;

    public function setUp()
    {
        if (!Type::hasType(MoneyType::TYPE)) {
            Type::addType(MoneyType::TYPE, 'Oro\Bundle\EntityBundle\Entity\Type\MoneyType');
        }
        $this->type = Type::getType(MoneyType::TYPE);
    }

    public function testGetName()
    {
        $this->assertEquals('money', $this->type->getName());
    }

    public function testGetSQLDeclaration()
    {
        $platform = new MySqlPlatform();
        $output = $this->type->getSQLDeclaration([], $platform);

        $this->assertEquals('NUMERIC(19, 4)', $output);
    }

    public function testConvertToPHPValue()
    {
        $platform = new MySqlPlatform();
        $this->assertNull($this->type->convertToPHPValue(null, $platform));
        $this->assertEquals(12.1, $this->type->convertToPHPValue(12.1, $platform));
    }
}
