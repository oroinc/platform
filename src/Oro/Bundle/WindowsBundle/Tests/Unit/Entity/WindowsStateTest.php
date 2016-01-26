<?php

namespace Oro\Bundle\WindowsBundle\Tests\Entity;

use Oro\Bundle\WindowsBundle\Entity\WindowsState;

class WindowsStateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var WindowsState
     */
    protected $windowState;

    protected function setUp()
    {
        $this->windowState = new WindowsState();
    }

    /**
     * Test getters and setters
     *
     * @dataProvider propertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGetSet($property, $value)
    {
        $setMethod = 'set' . ucfirst($property);
        $getMethod = 'get' . ucfirst($property);
        $this->windowState->$setMethod($value);
        $this->assertEquals($value, $this->windowState->$getMethod());
    }

    public function testIsRenderedSuccessfully()
    {
        $this->assertFalse($this->windowState->isRenderedSuccessfully());
        $this->windowState->setRenderedSuccessfully(true);
        $this->assertTrue($this->windowState->isRenderedSuccessfully());
    }

    /**
     * @return array
     */
    public function propertiesDataProvider()
    {
        $userMock = $this->getMockBuilder('Symfony\Component\Security\Core\User\UserInterface')
            ->disableOriginalConstructor()
            ->getMock();
        return [
            'user' => ['user', $userMock],
            'data' => ['data', ['test' => true]],
            'createdAt' => ['createdAt', new \DateTime('2022-02-22 22:22:22')],
            'updatedAt' => ['updatedAt', new \DateTime('2022-02-22 22:22:22')],
        ];
    }

    public function testGetJsonData()
    {
        $data = ['test' => true];
        $this->windowState->setData($data);
        $this->assertEquals($data, $this->windowState->getData());
        $this->assertEquals(json_encode($data), $this->windowState->getJsonData());
    }
}
