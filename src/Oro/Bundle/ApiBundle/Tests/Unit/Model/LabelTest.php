<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Model;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ApiBundle\Model\Label;

class LabelTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $translator;

    public function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

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

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('test')
            ->willReturn('translated');

        $this->assertEquals('translated', $label->trans($this->translator));
    }

    public function testTransWhenNoTranslation()
    {
        $label = new Label('test');

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('test')
            ->willReturn('test');

        $this->assertEquals('', $label->trans($this->translator));
    }

    public function testReturnsResultEvenWhenNoTranslationExistIfTranslateDirectlyOptionIsSetToTrue()
    {
        $label = new Label('test');
        $label->setTranslateDirectly(true);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('test')
            ->willReturn('test');

        $this->assertEquals('test', $label->trans($this->translator));
    }
}
