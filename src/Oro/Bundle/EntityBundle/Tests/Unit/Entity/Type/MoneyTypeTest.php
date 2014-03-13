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
    protected $moneyType;

    public function setUp()
    {
        if (!Type::hasType(MoneyType::MONEY_TYPE)) {
            Type::addType(MoneyType::MONEY_TYPE, 'Oro\Bundle\EntityBundle\Entity\Type\MoneyType');
        }
        $this->moneyType = Type::getType(MoneyType::MONEY_TYPE);
    }

    public function testGetName()
    {
        $this->assertEquals('money', $this->moneyType->getName());
    }

    public function testGetSQLDeclaration()
    {
        $platform = new MySqlPlatform();
        $output = $this->moneyType->getSQLDeclaration([], $platform);

        $this->assertEquals('NUMERIC(19, 4)', $output);
    }

    public function testConvertToPHPValue()
    {
        $platform = new MySqlPlatform();
        $this->assertNull($this->moneyType->convertToPHPValue(null, $platform));
        $this->assertEquals(12.1, $this->moneyType->convertToPHPValue(12.1, $platform));
    }
}
