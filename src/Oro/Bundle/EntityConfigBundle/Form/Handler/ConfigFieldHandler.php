<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\FormBundle\Model\UpdateHandler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class ConfigFieldHandler
{
    /** @var ConfigHelperHandler */
    private $configHelperHandler;

    /** @var ConfigHelper */
    private $configHelper;

    /** @var UpdateHandler */
    private $updateHandler;

    /** @var RequestStack */
    private $requestStack;

    /**
     * @param ConfigHelperHandler $configHelperHandler
     * @param ConfigHelper $configHelper
     * @param UpdateHandler $updateHandler
     * @param RequestStack $requestStack
     */
    public function __construct(
        ConfigHelperHandler $configHelperHandler,
        ConfigHelper $configHelper,
        UpdateHandler $updateHandler,
        RequestStack $requestStack
    ) {
        $this->configHelperHandler = $configHelperHandler;
        $this->configHelper = $configHelper;
        $this->updateHandler = $updateHandler;
        $this->requestStack = $requestStack;
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param string $formAction
     * @param string $successMessage
     * @return array|RedirectResponse
     */
    public function handleUpdate(
        FieldConfigModel $fieldConfigModel,
        $formAction,
        $successMessage
    ) {
        $form = $this->configHelperHandler->createSecondStepFieldForm($fieldConfigModel);

        return $this->updateHandler->update(
            $fieldConfigModel,
            $form,
            $successMessage,
            $this,
            function ($fieldConfigModel, FormInterface $form) use ($formAction) {
                return [
                    'entity_config' => $this->configHelper->getEntityConfigByField($fieldConfigModel, 'entity'),
                    'field_config'  => $this->configHelper->getFieldConfig($fieldConfigModel, 'entity'),
                    'field'         => $fieldConfigModel,
                    'form'          => $form->createView(),
                    'formAction'    => $formAction,
                    'require_js'    => $this->configHelper->getExtendRequireJsModules()
                ];
            }
        );
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @return bool
     */
    public function process(FieldConfigModel $fieldConfigModel)
    {
        $form = $this->configHelperHandler->createSecondStepFieldForm($fieldConfigModel);
        $request = $this->requestStack->getCurrentRequest();

        return $this->configHelperHandler->isFormValidAfterSubmit($request, $form);
    }
}
