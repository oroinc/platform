<?php

namespace Oro\Bundle\EmbeddedFormBundle\Tests\Unit\DataGrid\Formatter;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\EmbeddedFormBundle\DataGrid\Formatter\EmbeddedFormTypeProperty;
use Oro\Bundle\EmbeddedFormBundle\Manager\EmbeddedFormManager;
use Symfony\Contracts\Translation\TranslatorInterface;

class EmbeddedFormTypePropertyTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldReturnValue()
    {
        $manager = $this->createMock(EmbeddedFormManager::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $record = $this->createMock(ResultRecordInterface::class);

        $formatter = new EmbeddedFormTypeProperty($manager, $translator);

        $formType = 'Test\Type';
        $label = 'test_label';
        $translatedValue = 'test_translated_label';

        $record->expects($this->once())
            ->method('getValue')
            ->with('formType')
            ->willReturn($formType);

        $manager->expects($this->once())
            ->method('getLabelByType')
            ->with($formType)
            ->willReturn($label);

        $translator->expects($this->once())
            ->method('trans')
            ->with($label)
            ->willReturn($translatedValue);

        $this->assertEquals($translatedValue, $formatter->getValue($record));
    }
}
