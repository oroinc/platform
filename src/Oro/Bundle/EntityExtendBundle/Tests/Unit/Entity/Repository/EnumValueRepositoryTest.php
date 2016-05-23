<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Entity\Repository;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueRepositoryTest extends \PHPUnit_Framework_TestCase
{
    const ENUM_VALUE_CLASS_NAME = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue';

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $em;

    /** @var EnumValueRepository */
    protected $repo;

    protected function setUp()
    {
        $this->em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repo = new EnumValueRepository(
            $this->em,
            new ClassMetadata(self::ENUM_VALUE_CLASS_NAME)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $name must not be empty.
     */
    public function testCreateEnumValueWithNullName()
    {
        $this->repo->createEnumValue(null, 1, false);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $name must not be empty.
     */
    public function testCreateEnumValueWithEmptyName()
    {
        $this->repo->createEnumValue('', 1, false);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage $id length must be less or equal 32 characters. id: 123456789012345678901234567890123.
     */
    public function testCreateEnumValueWithTooLongId()
    {
        $this->repo->createEnumValue(
            'Test Value 1',
            1,
            false,
            '123456789012345678901234567890123'
        );
    }

    public function testCreateEnumValue()
    {
        $result = $this->repo->createEnumValue('Test Value 1', 1, false, 'val1');

        $this->assertInstanceOf(self::ENUM_VALUE_CLASS_NAME, $result);
        $this->assertEquals('val1', $result->getId());
        $this->assertEquals('Test Value 1', $result->getName());
        $this->assertEquals(1, $result->getPriority());
        $this->assertFalse($result->isDefault());
    }

    public function testCreateEnumValueWithoutId()
    {
        $result = $this->repo->createEnumValue('Test Value 1', 1, true);

        $this->assertInstanceOf(self::ENUM_VALUE_CLASS_NAME, $result);
        $this->assertEquals(ExtendHelper::buildEnumValueId('Test Value 1'), $result->getId());
        $this->assertEquals('Test Value 1', $result->getName());
        $this->assertEquals(1, $result->getPriority());
        $this->assertTrue($result->isDefault());
    }

    public function testCreateEnumValueWithZeroAsKey()
    {
        $result = $this->repo->createEnumValue('Test Value 1', 1, true, '0');

        $this->assertInstanceOf(self::ENUM_VALUE_CLASS_NAME, $result);
        $this->assertSame('0', $result->getId());
        $this->assertEquals('Test Value 1', $result->getName());
        $this->assertEquals(1, $result->getPriority());
        $this->assertTrue($result->isDefault());
    }

    public function testCreateEnumValueWithEmptyString()
    {
        $result = $this->repo->createEnumValue('Test Value 1', 1, true, '');

        $this->assertInstanceOf(self::ENUM_VALUE_CLASS_NAME, $result);
        $this->assertEquals('test_value_1', $result->getId());
        $this->assertEquals('Test Value 1', $result->getName());
        $this->assertEquals(1, $result->getPriority());
        $this->assertTrue($result->isDefault());
    }
}
