<?php

namespace Oro\Bundle\FormBundle\Tests\Unit\Model;

use Oro\Bundle\FormBundle\Form\Handler\FormHandlerInterface;
use Oro\Bundle\FormBundle\Model\FormHandlerRegistry;

class FormHandlerRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FormHandlerRegistry
     */
    private $registry;

    protected function setUp()
    {
        $this->registry = new FormHandlerRegistry();
    }

    public function testRegisterAndGet()
    {
        $handler = $this->getHandlerMock('test');

        $this->registry->addHandler($handler);

        $this->assertSame($this->registry->get('test'), $handler);
    }

    public function testRegisterOverridesPreviousByAlias()
    {
        $handlerOne = $this->getHandlerMock('handler_name');
        $this->registry->addHandler($handlerOne);
        $handlerTwo = $this->getHandlerMock('handler_name');
        $this->registry->addHandler($handlerTwo);

        $this->assertSame($this->registry->get('handler_name'), $handlerTwo);
        $this->assertNotSame($this->registry->get('handler_name'), $handlerOne);
    }

    /**
     * @expectedException \Oro\Bundle\FormBundle\Exception\UnknownFormHandlerException
     * @expectedExceptionMessage Unknown form handler with alias `test`
     */
    public function testGetUnregisteredException()
    {
        $this->registry->get('test');
    }

    /**
     * @dataProvider hasDataProvider
     *
     * @param bool $expected
     * @param string $alias
     * @param FormHandlerInterface|null $handler
     */
    public function testHas($expected, $alias, FormHandlerInterface $handler = null)
    {
        if ($handler) {
            $this->registry->addHandler($handler);
        }
        $this->assertEquals($expected, $this->registry->has($alias));
    }

    /**
     * @return \Generator
     */
    public function hasDataProvider()
    {
        yield 'correct' => ['expected' => true, 'alias' => 'test', 'handler' => $this->getHandlerMock()];
        yield 'not registered' => ['expected' => false, 'alias' => uniqid(), 'handler' => $this->getHandlerMock()];
        yield 'incorrect' => ['expected' => false, 'alias' => 'test', 'handler' => null];
    }

    /**
     * @param string $alias
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|FormHandlerInterface
     */
    protected function getHandlerMock($alias = 'test')
    {
        $handler = $this->getMockBuilder(FormHandlerInterface::class)->disableOriginalConstructor()->getMock();
        $handler->expects($this->any())->method('getAlias')->willReturn($alias);

        return $handler;
    }
}
