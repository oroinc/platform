<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Oro\Bundle\ApiBundle\Model\Label;

class LabelTest extends \PHPUnit_Framework_TestCase
{
    public function testName()
    {
        $label = new Label('test');
        $this->assertEquals('test', $label->getName());

        $label->setName('test1');
        $this->assertEquals('test1', $label->getName());

        $this->assertEquals('Label: test1', (string)$label);
    }

    public function testTrans()
    {
        $label = new Label('test');

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->once())
            ->method('trans')
            ->with('test')
            ->willReturn('translated');

        $this->assertEquals('translated', $label->trans($translator));
    }

    public function testTransWhenNoTranslation()
    {
        $label = new Label('test');

        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->once())
            ->method('trans')
            ->with('test')
            ->willReturn('test');

        $this->assertEquals('', $label->trans($translator));
    }
}
