<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\DataMapper;

use Oro\Bundle\TranslationBundle\Form\DataMapper\GedmoTranslationMapper;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

class GedmoTranslationMapperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GedmoTranslationMapper
     */
    private $mapper;

    /**
     * @var FormInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $form;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->mapper = new GedmoTranslationMapper();
        $this->form = $this->createMock(FormInterface::class);
    }

    /**
     * @dataProvider mapDataToFormsEmptyDataProvider
     * @param $data
     */
    public function testMapDataToFormsEmptyData($data)
    {
        $this->form
            ->expects($this->never())
            ->method('getConfig');

        $this->mapper->mapDataToForms($data, [$this->form]);
    }

    /**
     * @return array
     */
    public function mapDataToFormsEmptyDataProvider()
    {
        return [
            [null],
            [[]]
        ];
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\UnexpectedTypeException
     * @expectedExceptionMessage object, array or empty
     */
    public function testMapDataToFormsException()
    {
        $this->mapper->mapDataToForms('', [$this->form]);
    }

    public function testMapDataToForms()
    {
        $formConfig = $this->createMock(FormConfigInterface::class);

        $this->form
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($formConfig);

        $formConfig->expects($this->once())
            ->method('getName')
            ->willReturn('en');

        $translation = (new TranslationStub())
            ->setLocale('en')
            ->setField('foo_field')
            ->setContent('bar_content');

        $this->form
            ->expects($this->once())
            ->method('setData')
            ->with(['foo_field' => 'bar_content']);

        $this->mapper->mapDataToForms([$translation], [$this->form]);
    }
}
