<?php

namespace Oro\Bundle\EntityBundle\Tests\Unit\Entity\Type;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\EntityBundle\Entity\Type\PercentType;
use Doctrine\DBAL\Types\Type;

class PercentTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PercentType
     */
    protected $percentType;

    public function setUp()
    {
        if (!Type::hasType(PercentType::TYPE)) {
            Type::addType(PercentType::TYPE, 'Oro\Bundle\EntityBundle\Entity\Type\PercentType');
        }
        $this->percentType = Type::getType(PercentType::TYPE);
    }

    public function testGetName()
    {
        $this->assertEquals('percent', $this->percentType->getName());
    }

    public function testGetSQLDeclaration()
    {
        $platform = new MySqlPlatform();
        $output = $this->percentType->getSQLDeclaration([], $platform);

        $this->assertEquals('DOUBLE PRECISION', $output);
    }

    public function testConvertToPHPValue()
    {
        $platform = new MySqlPlatform();
        $this->assertNull($this->percentType->convertToPHPValue(null, $platform));
        $this->assertEquals(12.4, $this->percentType->convertToPHPValue(12.4, $platform));
    }
}
