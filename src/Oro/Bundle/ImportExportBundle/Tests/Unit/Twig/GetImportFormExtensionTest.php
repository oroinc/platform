<?php

namespace Oro\Bundle\ImportExportBundle\Tests\Unit\Twig;

use Oro\Bundle\ImportExportBundle\Form\Type\ImportType;
use Oro\Bundle\ImportExportBundle\Twig\GetImportFormExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class GetImportFormExtensionTest extends TestCase
{
    /**
     * @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $formFactory;

    /**
     * @var GetImportFormExtension
     */
    private $extension;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->extension = new GetImportFormExtension($this->formFactory);
    }

    public function testGetFunctions()
    {
        static::assertEquals(
            [
                new \Twig_SimpleFunction('get_import_form', [$this->extension, 'getImportForm'])
            ],
            $this->extension->getFunctions()
        );
    }

    public function testGetImportForm()
    {
        $entityName = 'name';
        $form = $this->createMock(FormInterface::class);

        $this->formFactory
            ->expects(static::once())
            ->method('create')
            ->with(
                ImportType::NAME,
                null,
                [
                    'entityName' => $entityName,
                    'processorAliasOptions' => [
                        'expanded' => true,
                        'multiple' => false,
                    ]
                ]
            )
            ->willReturn($form);

        static::assertSame($form, $this->extension->getImportForm($entityName));
    }
}
