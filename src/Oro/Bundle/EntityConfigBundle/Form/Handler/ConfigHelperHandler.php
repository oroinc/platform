<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\FormBundle\Form\Handler\RequestHandlerTrait;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Encapsulate a logic for config form handlers
 */
class ConfigHelperHandler
{
    use RequestHandlerTrait;

    public function __construct(
        private FormFactoryInterface $formFactory,
        private RequestStack $requestStack,
        private Router $router,
        private ConfigHelper $configHelper,
    ) {
    }

    /**
     * @param Request $request
     * @param FormInterface $form
     * @return bool
     */
    public function isFormValidAfterSubmit(Request $request, FormInterface $form)
    {
        if ($request->isMethod('POST')) {
            $isPartialSubmit = !empty($request->get($form->getName())[ConfigType::PARTIAL_SUBMIT]);
            $this->submitPostPutRequest($form, $request, !$isPartialSubmit);

            if ($form->isSubmitted() && $form->isValid()) {
                if ($isPartialSubmit) {
                    // Form is submitted partially, so it cannot be fully valid.
                    return false;
                }

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
            FieldType::class,
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
            ConfigType::class,
            null,
            ['config_model' => $fieldConfigModel]
        );
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param string $successMessage
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function showSuccessMessageAndRedirect(FieldConfigModel $fieldConfigModel, $successMessage)
    {
        $this->requestStack->getSession()->getFlashBag()->add('success', $successMessage);

        return $this->redirect($fieldConfigModel);
    }

    /**
     * @param ConfigModel|string $entityOrUrl
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirect($entityOrUrl)
    {
        return $this->router->redirect($entityOrUrl);
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param FormInterface $form
     * @param string $formAction
     * @return array
     */
    public function constructConfigResponse(FieldConfigModel $fieldConfigModel, FormInterface $form, $formAction)
    {
        return [
            'entity_config' => $this->configHelper->getEntityConfigByField($fieldConfigModel, 'entity'),
            'non_extended_entities_classes' => $this->configHelper->getNonExtendedEntitiesClasses(),
            'field_config' => $this->configHelper->getFieldConfig($fieldConfigModel, 'entity'),
            'field' => $fieldConfigModel,
            'form' => $form->createView(),
            'formAction' => $formAction,
            'jsmodules' => $this->configHelper->getExtendJsModules()
        ];
    }
}
