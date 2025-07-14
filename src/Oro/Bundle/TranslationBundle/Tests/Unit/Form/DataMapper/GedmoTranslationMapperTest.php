<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\DataMapper;

use Oro\Bundle\TranslationBundle\Form\DataMapper\GedmoTranslationMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

class GedmoTranslationMapperTest extends TestCase
{
    private FormInterface&MockObject $form;
    private GedmoTranslationMapper $mapper;

    #[\Override]
    protected function setUp(): void
    {
        $this->mapper = new GedmoTranslationMapper();
        $this->form = $this->createMock(FormInterface::class);
    }

    /**
     * @dataProvider mapDataToFormsEmptyDataProvider
     */
    public function testMapDataToFormsEmptyData(?array $data): void
    {
        $this->form->expects($this->never())
            ->method('getConfig');

        $this->mapper->mapDataToForms($data, [$this->form]);
    }

    public function mapDataToFormsEmptyDataProvider(): array
    {
        return [
            [null],
            [[]]
        ];
    }

    public function testMapDataToFormsException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('object, array or empty');

        $this->mapper->mapDataToForms('', [$this->form]);
    }

    public function testMapDataToForms(): void
    {
        $formConfig = $this->createMock(FormConfigInterface::class);

        $this->form->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig->expects($this->once())
            ->method('getName')
            ->willReturn('en');

        $translation = (new TranslationStub())
            ->setLocale('en')
            ->setField('foo_field')
            ->setContent('bar_content');

        $this->form->expects($this->once())
            ->method('setData')
            ->with(['foo_field' => 'bar_content']);

        $this->mapper->mapDataToForms([$translation], [$this->form]);
    }
}
