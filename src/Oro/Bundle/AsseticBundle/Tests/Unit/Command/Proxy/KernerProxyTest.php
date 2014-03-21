<?php

namespace Oro\Bundle\AsseticBundle\Tests\Unit\Command\Proxy;

use Symfony\Component\DependencyInjection\Scope;
use Oro\Bundle\AsseticBundle\Command\Proxy\KernelProxy;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class KernelProxyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $target;

    /**
     * @var KernelProxy
     */
    protected $proxy;

    protected function setUp()
    {
        $this->target = $this->getMock('Symfony\Component\HttpKernel\KernelInterface');
        $this->proxy = new KernelProxy($this->target);
    }

    public function testExclude()
    {
        $bundle1 = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle1->expects($this->any())->method('getName')->will($this->returnValue('bundle1'));
        $bundle2 = $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface');
        $bundle2->expects($this->any())->method('getName')->will($this->returnValue('bundle2'));

        $this->target->expects($this->any())
            ->method('getBundles')
            ->will($this->returnValue([$bundle1, $bundle2]));
        $this->target->expects($this->any())
            ->method('getBundle')
            ->with('bundle2')
            ->will($this->returnValue($bundle2));

        $this->assertEquals([$bundle1, $bundle2], $this->proxy->getBundles());
        $this->assertEquals($bundle2, $this->proxy->getBundle('bundle2'));

        $this->proxy->excludeBundle('bundle2');
        $this->assertEquals([$bundle1], $this->proxy->getBundles());

        $this->setExpectedException(
            '\InvalidArgumentException',
            'Bundle "bundle2" is in exclude list.'
        );
        $this->proxy->getBundle('bundle2');
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
            ['registerBundles', [], []],
            ['registerContainerConfiguration', [$this->getMock('Symfony\Component\Config\Loader\Loader')], null],
            ['boot', [], null],
            ['shutdown', [], null],
            ['getBundles', [], []],
            ['isClassInActiveBundle', ['class'], true],
            ['getBundle', ['name', false], $this->getMock('Symfony\Component\HttpKernel\Bundle\BundleInterface')],
            ['locateResource', ['name', 'dir', false], 'test'],
            ['getName', [], 'test'],
            ['getEnvironment', [], 'test'],
            ['isDebug', [], false],
            ['getRootDir', [], 'test'],
            ['getContainer', [], $this->getMock('Symfony\Component\DependencyInjection\ContainerInterface')],
            ['getStartTime', [], 111],
            ['getCacheDir', [], 'test'],
            ['getLogDir', [], 'test'],
            ['getCharset', [], 'test'],
            [
                'handle',
                [
                    $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
                        ->disableOriginalConstructor()
                        ->getMock(),
                    HttpKernelInterface::SUB_REQUEST,
                    false
                ],
                $this->getMockBuilder('Symfony\Component\HttpFoundation\Response')
                    ->disableOriginalConstructor()
                    ->getMock()
            ],
            ['serialize', [], 'test'],
            ['unserialize', ['test'], null],
        ];
    }
}
