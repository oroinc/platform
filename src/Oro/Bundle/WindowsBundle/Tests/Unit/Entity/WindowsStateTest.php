<?php

namespace Oro\Bundle\WindowsBundle\Tests\Unit\Entity;

use Oro\Bundle\WindowsBundle\Entity\WindowsState;
use Symfony\Component\Security\Core\User\UserInterface;

class WindowsStateTest extends \PHPUnit\Framework\TestCase
{
    private WindowsState $windowState;

    protected function setUp(): void
    {
        $this->windowState = new WindowsState();
    }

    /**
     * @dataProvider propertiesDataProvider
     */
    public function testGetSet(string $property, mixed $value)
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

    public function propertiesDataProvider(): array
    {
        $userMock = $this->createMock(UserInterface::class);

        return [
            'user' => ['user', $userMock],
            'data' => ['data', ['test' => true]],
            'createdAt' => ['createdAt', new \DateTime('2022-02-22 22:22:22')],
            'updatedAt' => ['updatedAt', new \DateTime('2022-02-22 22:22:22')],
        ];
    }

    public function testGetData()
    {
        $data = ['test' => true];
        $this->windowState->setData($data);
        $this->assertEquals($data, $this->windowState->getData());
    }
}
