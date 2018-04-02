<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handle form configuration for config field
 */
class ConfigFieldHandler
{
    /** @var ConfigHelperHandler */
    private $configHelperHandler;

    /** @var RequestStack */
    private $requestStack;

    /**
     * @param ConfigHelperHandler $configHelperHandler
     * @param RequestStack $requestStack
     */
    public function __construct(
        ConfigHelperHandler $configHelperHandler,
        RequestStack $requestStack
    ) {
        $this->configHelperHandler = $configHelperHandler;
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
        $request = $this->requestStack->getCurrentRequest();

        if ($this->configHelperHandler->isFormValidAfterSubmit($request, $form)) {
            return $this
                ->configHelperHandler->showClearCacheMessage()
                ->showSuccessMessageAndRedirect($fieldConfigModel, $successMessage);
        }

        return $this->configHelperHandler->constructConfigResponse($fieldConfigModel, $form, $formAction);
    }
}
