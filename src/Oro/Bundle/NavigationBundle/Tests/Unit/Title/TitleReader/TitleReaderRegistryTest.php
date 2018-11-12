<?php

namespace Oro\Bundle\NavigationBundle\Title\TitleReader;

use Oro\Bundle\NavigationBundle\Tests\Unit\Title\TitleReader\Stub\TitleReaderStub;

class TitleReaderRegistryTest extends \PHPUnit\Framework\TestCase
{
    /** @var TitleReaderRegistry */
    private $registry = [];

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->registry = new TitleReaderRegistry();
    }

    public function testGetTitleReaders()
    {
        $this->assertEquals([], $this->registry->getTitleReaders());

        $reader = new TitleReaderStub(['route_name' => 'Route Name']);

        $this->registry->addTitleReader($reader);

        $this->assertSame([$reader], $this->registry->getTitleReaders());
    }

    /**
     * @dataProvider titleReaderProvider
     *
     * @param TitleReaderStub[] $readers
     * @param string            $routeName
     * @param string            $expectedTitle
     */
    public function testGetTitleByRoute(array $readers, $routeName, $expectedTitle)
    {
        foreach ($readers as $reader) {
            $this->registry->addTitleReader($reader);
        }

        $this->assertEquals($expectedTitle, $this->registry->getTitleByRoute($routeName));
    }

    /**
     * @return array
     */
    public function titleReaderProvider()
    {
        return [
            'no readers' => [
                'readers' => [],
                'routeName' => 'route_name',
                'expectedTitle' => null,
            ],
            'one reader' => [
                'readers' => [
                    new TitleReaderStub(['route_name' => 'Route Name'])
                ],
                'routeName' => 'route_name',
                'expectedTitle' => 'Route Name',
            ],
            'two readers with different route names' => [
                'readers' => [
                    new TitleReaderStub(['route_name' => 'Route Name']),
                    new TitleReaderStub(['new_route_name' => 'New Route Name'])
                ],
                'routeName' => 'route_name',
                'expectedTitle' => 'Route Name',
            ],
            'two readers with same route names' => [
                'readers' => [
                    new TitleReaderStub(['route_name' => 'Route Name']),
                    new TitleReaderStub(['route_name' => 'New Route Name'])
                ],
                'routeName' => 'route_name',
                'expectedTitle' => 'Route Name'
            ],
            'two readers without coincidence ' => [
                'readers' => [
                    new TitleReaderStub(['route_name' => 'Route Name']),
                    new TitleReaderStub(['route_name' => 'New Route Name'])
                ],
                'routeName' => 'route_name_another',
                'expectedTitle' => null
            ],
        ];
    }
}
