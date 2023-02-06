<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ImportExportBundle\Form\Type\ImportType;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class ImportTypeTest extends FormIntegrationTestCase
{
    /** @var ProcessorRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $processorRegistry;

    /** @var ImportType */
    private $type;

    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->type = new ImportType($this->processorRegistry);
        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submitData, ImportData $formData, array $formOptions)
    {
        $this->processorRegistry->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->willReturnCallback(function ($type, $entityName) {
                self::assertEquals(ProcessorRegistry::TYPE_IMPORT, $type);

                return [$type . $entityName];
            });

        $form = $this->factory->create(ImportType::class, null, $formOptions);

        $this->assertTrue($form->has('file'));
        $this->assertInstanceOf(FileType::class, $form->get('file')->getConfig()->getType()->getInnerType());
        $this->assertTrue($form->get('file')->getConfig()->getOption('required'));

        $this->assertTrue($form->has('processorAlias'));
        $this->assertInstanceOf(
            ChoiceType::class,
            $form->get('processorAlias')->getConfig()->getType()->getInnerType()
        );
        $this->assertTrue($form->get('processorAlias')->getConfig()->getOption('required'));
        $key = ProcessorRegistry::TYPE_IMPORT . $formOptions['entityName'];
        $this->assertEquals(
            [new ChoiceView($key, $key, 'oro.importexport.import.' . $key)],
            $form->createView()->offsetGet('processorAlias')->vars['choices']
        );

        $form->submit($submitData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData());
    }

    public function submitDataProvider(): array
    {
        $data = new ImportData();
        $data->setProcessorAlias('importname');

        return [
            'empty data' => [
                'submitData' => [],
                'formData' => $data,
                'formOptions' => [
                    'entityName' => 'name'
                ]
            ],
            'alias options' => [
                'submitData' => [],
                'formData' => $data,
                'formOptions' => [
                    'entityName' => 'name',
                    'processorAliasOptions' => [
                        'expanded' => true,
                        'multiple' => false,
                    ],
                ]
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->type], []),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}
