<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixture;

class AbstractEnumFixtureTest extends \PHPUnit_Framework_TestCase
{
    const ID_1 = 'id1';
    const ID_2 = 'id2';
    const VALUE_1 = 'value1';
    const VALUE_2 = 'value2';

    public function testLoadData()
    {
        $stub = $this->getMockForAbstractClass(
            'Oro\Bundle\EntityExtendBundle\Fixture\AbstractEnumFixture',
            [],
            '',
            true,
            true,
            true,
            ['getDefaultValue']
        );

        $stub
            ->expects($this->once())
            ->method('getData')
            ->willReturn($this->getData());
        $stub
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn('ClassName');
        $stub
            ->expects($this->any())
            ->method('getDefaultValue')
            ->willReturn(self::ID_1);

        $stub->load($this->mockObjectManager());
    }

    /**
     * @return array
     */
    private function getData()
    {
        return [
            self::ID_1 => self::VALUE_1,
            self::ID_2 => self::VALUE_2,
        ];
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function mockObjectManager()
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

        $objectManagerMock = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $objectManagerMock
            ->expects($this->any())
            ->method('getRepository')
            ->with('ClassName')
            ->willReturn($enumRepositoryMock);

        return $objectManagerMock;
    }
}
