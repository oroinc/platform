<?php

namespace Oro\Bundle\AsseticBundle\Tests\Unit\Command\Proxy;

use Oro\Bundle\AsseticBundle\Command\Proxy\ContainerProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContainerProxyTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $target;

    /**
     * @var ContainerProxy
     */
    protected $proxy;

    protected function setUp()
    {
        $this->target = $this->createMock(ContainerInterface::class);
        $this->proxy = new ContainerProxy($this->target);
    }

    public function testReplace()
    {
        $oldService = new \stdClass();
        $newService = new \stdClass();

        $this->target->expects($this->once())
            ->method('has')
            ->with('id')
            ->will($this->returnValue(true));
        $this->target->expects($this->once())
            ->method('get')
            ->with('id')
            ->will($this->returnValue($oldService));

        $this->assertSame($oldService, $this->proxy->get('id'));

        $this->proxy->replace('id', $newService);
        $this->assertSame($newService, $this->proxy->get('id'));
    }

    /**
     * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function testReplaceNonExistingService()
    {
        $this->target->expects($this->once())
            ->method('has')
            ->with('id')
            ->will($this->returnValue(false));
        $this->proxy->replace('id', new \stdClass());
    }

    /**
     * @param string $name
     * @param array  $params
     * @param mixed  $returnValue
     *
     * @dataProvider methodProvider
     */
    public function testMethod($name, $params, $returnValue)
    {
        $targetParams = null;
        $this->target->expects($this->once())
            ->method($name)
            ->will(
                $this->returnCallback(
                    function () use (&$targetParams, &$returnValue) {
                        $targetParams = func_get_args();
                        return $returnValue;
                    }
                )
            );

        $result = call_user_func_array([$this->proxy, $name], $params);

        $this->assertEquals($params, $targetParams);
        $this->assertEquals($returnValue, $result);
    }

    /**
     * @return array
     */
    public function methodProvider()
    {
        return [
            ['set', ['id', new \stdClass()], null],
            ['get', ['id', ContainerInterface::IGNORE_ON_INVALID_REFERENCE], new \stdClass()],
            ['has', ['id'], true],
            ['getParameter', ['name'], 'test'],
            ['hasParameter', ['name'], true],
            ['setParameter', ['name', 'test'], null],
        ];
    }

    public function testInitialized()
    {
        $id = 'acme.service_id';

        $this->target->expects($this->once())
            ->method('initialized')
            ->with($id)
            ->willReturn(true);

        $this->assertTrue($this->proxy->initialized($id));
    }
}
