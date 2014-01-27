<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\Grid\Formatter;


use Oro\Bundle\EmbeddedFormBundle\Grid\Formatter\EmbeddedFormTypeProperty;

class EmbeddedFormTypePropertyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function shouldBeConstructedWithEmbeddedFormManagerAndTranslator()
    {
        new EmbeddedFormTypeProperty($this->createEmbeddedFormManagerMock(), $this->createTranslatorMock());
    }

    /**
     * @test
     */
    public function shouldReturnValue()
    {
        $manager = $this->createEmbeddedFormManagerMock();
        $translator = $this->createTranslatorMock();
        $record = $this->getMock('Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface');

        $formatter = new EmbeddedFormTypeProperty($manager, $translator);

        $record->expects($this->once())
            ->method('getValue')
            ->with('formType')
            ->will($this->returnValue($formType = uniqid()));

        $manager->expects($this->once())
            ->method('getLabelByType')
            ->with($formType)
            ->will($this->returnValue($label = uniqid()));

        $translator->expects($this->once())
            ->method('trans')
            ->with($label)
            ->will($this->returnValue($translatedValue = uniqid()));

        $this->assertEquals($translatedValue, $formatter->getValue($record));
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createEmbeddedFormManagerMock()
    {
        return $this
            ->getMockBuilder(
                'Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager'
            )
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createTranslatorMock()
    {
        return $this->getMock('Symfony\Component\Translation\TranslatorInterface');
    }
}
 