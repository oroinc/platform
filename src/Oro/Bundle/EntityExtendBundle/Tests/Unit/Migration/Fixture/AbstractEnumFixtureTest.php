<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Migration\Fixture;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

class AbstractEnumFixtureTest extends \PHPUnit\Framework\TestCase
{
    const ID_1 = 'id1';
    const ID_2 = 'id2';
    const VALUE_1 = 'value1';
    const VALUE_2 = 'value2';
    const ENUM_CODE = 'enum_code';
    const EXTEND_ENTITY_NAME = 'Extend\Entity\EV_Enum_Code';

    public function testLoadData()
    {
        /** @var AbstractEnumFixture|\PHPUnit\Framework\MockObject\MockObject $stub */
        $stub = $this
            ->getMockBuilder('Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture')
            ->setMethods(['getDefaultValue'])
            ->getMockForAbstractClass();
        $stub
            ->expects($this->once())
            ->method('getData')
            ->willReturn($this->getData());
        $stub
            ->expects($this->once())
            ->method('getEnumCode')
            ->willReturn(self::ENUM_CODE);
        $stub
            ->expects($this->any())
            ->method('getDefaultValue')
            ->willReturn(self::ID_1);

        $stub->load($this->mockObjectManager());
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            self::ID_1 => self::VALUE_1,
            self::ID_2 => self::VALUE_2
        ];
    }

    /**
     * @return ObjectManager|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function mockObjectManager()
    {
        $enumRepositoryMock = $this
            ->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $enumRepositoryMock
            ->expects($this->at(0))
            ->method('createEnumValue')
            ->with(self::VALUE_1, 1, true, self::ID_1);
        $enumRepositoryMock
            ->expects($this->at(1))
            ->method('createEnumValue')
            ->with(self::VALUE_2, 2, false, self::ID_2);

        $objectManagerMock = $this->createMock('Doctrine\Common\Persistence\ObjectManager');
        $objectManagerMock
            ->expects($this->any())
            ->method('getRepository')
            ->with(self::EXTEND_ENTITY_NAME)
            ->willReturn($enumRepositoryMock);

        return $objectManagerMock;
    }
}
