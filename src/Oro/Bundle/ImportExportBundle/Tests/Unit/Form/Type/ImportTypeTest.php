<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ImportExportBundle\Form\Type\ImportType;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class ImportTypeTest extends FormIntegrationTestCase
{
    private ProcessorRegistry&MockObject $processorRegistry;
    private ImportType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->processorRegistry = $this->createMock(ProcessorRegistry::class);
        $this->type = new ImportType($this->processorRegistry);
        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->type], []),
            new ValidatorExtension(Validation::createValidator())
        ];
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(array $submitData, ImportData $formData, array $formOptions): void
    {
        $this->processorRegistry->expects(self::any())
            ->method('getProcessorAliasesByEntity')
            ->willReturnCallback(function ($type, $entityName) {
                self::assertEquals(ProcessorRegistry::TYPE_IMPORT, $type);

                return [$type . $entityName];
            });

        $form = $this->factory->create(ImportType::class, null, $formOptions);

        self::assertTrue($form->has('file'));
        self::assertInstanceOf(FileType::class, $form->get('file')->getConfig()->getType()->getInnerType());
        self::assertTrue($form->get('file')->getConfig()->getOption('required'));

        self::assertTrue($form->has('processorAlias'));
        self::assertInstanceOf(
            ChoiceType::class,
            $form->get('processorAlias')->getConfig()->getType()->getInnerType()
        );
        self::assertTrue($form->get('processorAlias')->getConfig()->getOption('required'));
        $key = ProcessorRegistry::TYPE_IMPORT . $formOptions['entityName'];
        self::assertEquals(
            [new ChoiceView($key, $key, 'oro.importexport.import.' . $key)],
            $form->createView()->offsetGet('processorAlias')->vars['choices']
        );

        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($formData, $form->getData());
    }

    public static function submitDataProvider(): array
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
                        'multiple' => false
                    ]
                ]
            ]
        ];
    }
}
