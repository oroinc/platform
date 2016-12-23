<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\EntityExtendBundle\Form\Util\FieldSessionStorage;
use Oro\Bundle\UIBundle\Route\Router;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class CreateUpdateConfigFieldHandler
{
    /** @var ConfigHelperHandler */
    private $configHelperHandler;

    /** @var ConfigManager */
    private $configManager;

    /** @var Router */
    private $router;

    /** @var ConfigHelper */
    private $configHelper;

    /** @var FieldSessionStorage */
    private $sessionStorage;

    /**
     * @param ConfigHelperHandler $configHelperHandler
     * @param ConfigManager $configManager
     * @param Router $router
     * @param ConfigHelper $configHelper
     * @param FieldSessionStorage $sessionStorage
     */
    public function __construct(
        ConfigHelperHandler $configHelperHandler,
        ConfigManager $configManager,
        Router $router,
        ConfigHelper $configHelper,
        FieldSessionStorage $sessionStorage
    ) {
        $this->configHelperHandler = $configHelperHandler;
        $this->configManager = $configManager;
        $this->router = $router;
        $this->configHelper = $configHelper;
        $this->sessionStorage = $sessionStorage;
    }

    /**
     * @param Request $request
     * @param FieldConfigModel $fieldConfigModel
     * @param string $formAction
     * @return array|RedirectResponse
     */
    public function handleCreate(Request $request, FieldConfigModel $fieldConfigModel, $formAction)
    {
        $entityConfigModel = $fieldConfigModel->getEntity();
        $form = $this->configHelperHandler->createFirstStepFieldForm($fieldConfigModel);

        if ($this->configHelperHandler->isFormValidAfterSubmit($request, $form)) {
            $fieldName = $fieldConfigModel->getFieldName();
            $originalFieldNames = $form->getConfig()->getAttribute(FieldType::ORIGINAL_FIELD_NAMES_ATTRIBUTE);
            if (isset($originalFieldNames[$fieldName])) {
                $fieldName = $originalFieldNames[$fieldName];
            }

            $this->sessionStorage->saveFieldInfo($entityConfigModel, $fieldName, $fieldConfigModel->getType());

            return $this->router->redirect($entityConfigModel);
        }

        return [
            'form' => $form->createView(),
            'formAction' => $formAction,
            'entity_id' => $entityConfigModel->getId(),
            'entity_config' => $this->configHelper->getEntityConfig($entityConfigModel, 'entity'),
        ];
    }

    /**
     * @param Request $request
     * @param EntityConfigModel $entityConfigModel
     * @param string $createActionRedirectUrl
     * @param string $formAction
     * @param string $successMessage
     * @param array $additionalFieldOptions
     * @return array|RedirectResponse
     */
    public function handleFieldSave(
        Request $request,
        EntityConfigModel $entityConfigModel,
        $createActionRedirectUrl,
        $formAction,
        $successMessage,
        array $additionalFieldOptions = []
    ) {
        if (!$this->sessionStorage->hasFieldInfo($entityConfigModel)) {
            return $this->router->redirect($createActionRedirectUrl);
        }

        list($fieldName, $fieldType) = $this->sessionStorage->getFieldInfo($entityConfigModel);

        $extendEntityConfig = $this->configHelper->getEntityConfig($entityConfigModel, 'extend');

        list($fieldType, $fieldOptions) = $this->configHelper->createFieldOptions(
            $extendEntityConfig,
            $fieldType,
            $additionalFieldOptions
        );

        $newFieldModel = $this->configManager->createConfigFieldModel(
            $entityConfigModel->getClassName(),
            $fieldName,
            $fieldType
        );

        $this->configHelper->updateFieldConfigs($newFieldModel, $fieldOptions);

        $form = $this->configHelperHandler->createSecondStepFieldForm($newFieldModel);

        if ($this->configHelperHandler->isFormValidAfterSubmit($request, $form)) {
            /** @var Session $session */
            $session = $request->getSession();
            $session->getFlashBag()->add('success', $successMessage);
            $extendEntityConfig->set('upgradeable', true);

            //persist data inside the form
            $this->configManager->persist($extendEntityConfig);
            $this->configManager->flush();

            return $this->router->redirect($newFieldModel);
        }

        return [
            'entity_config' => $this->configHelper->getEntityConfigByField($newFieldModel, 'entity'),
            'field_config' => $this->configHelper->getFieldConfig($newFieldModel, 'entity'),
            'field' => $newFieldModel,
            'form' => $form->createView(),
            'formAction' => $formAction,
            'require_js' => $this->configHelper->getExtendRequireJsModules()
        ];
    }
}
