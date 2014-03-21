<?php

namespace Oro\Bundle\AsseticBundle\Tests\Unit\Command\Proxy;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Oro\Bundle\AsseticBundle\Command\Proxy\ContainerProxy;

class ContainerProxyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $target;

    /**
     * @var ContainerProxy
     */
    protected $proxy;

    protected function setUp()
    {
        $this->target = $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface');
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

        $result = call_user_func_array(array($this->proxy, $name), $params);

        $this->assertEquals($params, $targetParams);
        $this->assertEquals($returnValue, $result);
    }

    public function methodProvider()
    {
        return [
            ['set', ['id', new \stdClass(), ContainerInterface::SCOPE_PROTOTYPE], null],
            ['get', ['id', ContainerInterface::IGNORE_ON_INVALID_REFERENCE], new \stdClass()],
            ['has', ['id'], true],
            ['getParameter', ['name'], 'test'],
            ['hasParameter', ['name'], true],
            ['setParameter', ['name', 'test'], null],
            ['enterScope', ['name'], null],
            ['leaveScope', ['name'], null],
            ['addScope', [$this->getMock('Symfony\Component\DependencyInjection\ScopeInterface')], null],
            ['hasScope', ['name'], true],
            ['isScopeActive', ['name'], true],
        ];
    }
}
