<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Entity\Type;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\EntityBundle\Entity\Type\CurrencyType;
use Doctrine\DBAL\Types\Type;

class CurrencyTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CurrencyType
     */
    protected $currencyType;

    public function setUp()
    {
        if (!Type::hasType(CurrencyType::TYPE)) {
            Type::addType(CurrencyType::TYPE, 'Oro\Bundle\EntityBundle\Entity\Type\CurrencyType');
        }
        $this->currencyType = Type::getType(CurrencyType::TYPE);
    }

    public function testGetName()
    {
        $this->assertEquals('currency', $this->currencyType->getName());
    }

    public function testGetSQLDeclaration()
    {
        $platform = new MySqlPlatform();
        $output = $this->currencyType->getSQLDeclaration([], $platform);

        $this->assertEquals('NUMERIC(19, 4)', $output);
    }

    public function testConvertToPHPValue()
    {
        $platform = new MySqlPlatform();
        $this->assertNull($this->currencyType->convertToPHPValue(null, $platform));
        $this->assertEquals(12.1, $this->currencyType->convertToPHPValue(12.1, $platform));
    }
}
