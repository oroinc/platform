<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;

class ConfigHelperHandler
{
    /** @var FormFactoryInterface */
    private $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     */
    public function __construct(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param Request $request
     * @param FormInterface $form
     * @return bool
     */
    public function isFormValidAfterSubmit(Request $request, FormInterface $form)
    {
        if ($request->isMethod('POST')) {
            $form->submit($request);
            if ($form->isValid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @return FormInterface
     */
    public function createFirstStepFieldForm(FieldConfigModel $fieldConfigModel)
    {
        $entityConfigModel = $fieldConfigModel->getEntity();

        return $this->formFactory->create(
            'oro_entity_extend_field_type',
            $fieldConfigModel,
            ['class_name' => $entityConfigModel->getClassName()]
        );
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @return FormInterface
     */
    public function createSecondStepFieldForm(FieldConfigModel $fieldConfigModel)
    {
        return $this->formFactory->create(
            'oro_entity_config_type',
            null,
            ['config_model' => $fieldConfigModel]
        );
    }
}
