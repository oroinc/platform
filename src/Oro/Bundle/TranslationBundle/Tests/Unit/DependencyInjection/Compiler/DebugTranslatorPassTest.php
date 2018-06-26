<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\DependencyInjection\Compiler;

use Oro\Bundle\TranslationBundle\DependencyInjection\Compiler\DebugTranslatorPass;

class DebugTranslatorPassTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param bool $enabled
     * @dataProvider processDataProvider
     */
    public function testProcess($enabled)
    {
        $containerBuilder = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $containerBuilder->expects($this->once())
            ->method('getParameter')
            ->with(DebugTranslatorPass::DEBUG_TRANSLATOR_PARAMETER)
            ->will($this->returnValue($enabled));

        if ($enabled) {
            $containerBuilder->expects($this->once())
                ->method('setParameter')
                ->with('translator.class', DebugTranslatorPass::DEBUG_TRANSLATOR_CLASS);
        } else {
            $containerBuilder->expects($this->never())
                ->method('setParameter');
        }

        $compiler = new DebugTranslatorPass();
        $compiler->process($containerBuilder);
    }

    /**
     * @return array
     */
    public function processDataProvider()
    {
        return [
            'enabled'  => [true],
            'disabled' => [false],
        ];
    }
}
