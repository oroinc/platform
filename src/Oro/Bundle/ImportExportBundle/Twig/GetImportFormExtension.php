<?php

namespace Oro\Bundle\ImportExportBundle\Twig;

use Oro\Bundle\ImportExportBundle\Form\Type\ImportType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class GetImportFormExtension extends \Twig_Extension
{
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_import_form', [$this, 'getImportForm'])
        ];
    }

    /**
     * @param string $entityName
     *
     * @return FormInterface
     */
    public function getImportForm(string $entityName): FormInterface
    {
        return $this->formFactory->create(ImportType::NAME, null, ['entityName' => $entityName]);
    }
}
