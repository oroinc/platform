<?php

namespace Oro\Bundle\TranslationBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Constraints\File;

use Oro\Bundle\ImportExportBundle\Form\Type\ImportType;
use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Oro\Bundle\TranslationBundle\Form\Type\TranslationImportType;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class TranslationImportTypeTest extends FormIntegrationTestCase
{
    /** @var TranslationImportType */
    protected $type;

    protected function setUp()
    {
        parent::setUp();

        $this->type = new TranslationImportType();
    }

    public function testSubmit()
    {
        $form = $this->factory->create($this->type, null, ['entityName' => '\stdClass']);

        $this->assertTrue($form->has('file'));

        $config = $form->get('file')->getConfig();

        $this->assertEquals('file', $config->getType()->getName());
        $this->assertTrue($config->getOption('required'));
        $this->assertEquals(
            [
                new File(
                    [
                        'mimeTypes' => ['application/zip'],
                        'mimeTypesMessage' => 'This file type is not allowed.'
                    ]
                )
            ],
            $config->getOption('constraints')
        );

        $form->submit([]);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals(new ImportData(), $form->getData());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $processorRegistry = $this->getMockBuilder(ProcessorRegistry::class)->disableOriginalConstructor()->getMock();
        $processorRegistry->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->will(
                $this->returnCallback(
                    function ($type, $entityName) {
                        $this->assertEquals(ProcessorRegistry::TYPE_IMPORT, $type);

                        return [$type . $entityName];
                    }
                )
            );

        return [
            new PreloadedExtension(
                [
                    ImportType::NAME => new ImportType($processorRegistry)
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
