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
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|ProcessorRegistry
     */
    protected $processorRegistry;

    /**
     * @var ImportType
     */
    protected $type;

    protected function setUp()
    {
        $this->processorRegistry = $this->getMockBuilder('Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->type = new ImportType($this->processorRegistry);
        parent::setUp();
    }

    /**
     * @dataProvider submitDataProvider
     * @param mixed $submitData
     * @param mixed $formData
     * @param array $formOptions
     */
    public function testSubmit($submitData, $formData, array $formOptions)
    {
        $this->processorRegistry->expects($this->any())
            ->method('getProcessorAliasesByEntity')
            ->will(
                $this->returnCallback(
                    function ($type, $entityName) {
                        \PHPUnit\Framework\Assert::assertEquals(ProcessorRegistry::TYPE_IMPORT, $type);
                        return array($type . $entityName);
                    }
                )
            );

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

    public function submitDataProvider()
    {
        $data = new ImportData();
        $data->setProcessorAlias('importname');

        return array(
            'empty data' => array(
                'submitData' => array(),
                'formData' => $data,
                'formOptions' => array(
                    'entityName' => 'name'
                )
            ),
            'alias options' => array(
                'submitData' => array(),
                'formData' => $data,
                'formOptions' => array(
                    'entityName' => 'name',
                    'processorAliasOptions' => [
                        'expanded' => true,
                        'multiple' => false,
                    ],
                )
            ),
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ImportType::class => $this->type
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator())
        ];
    }
}
