<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\UIBundle\Route\Router;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

class ConfigHelperHandler
{
    /** @var FormFactoryInterface */
    private $formFactory;

    /** @var Session */
    private $session;

    /** @var Router */
    private $router;

    /** @var ConfigHelper */
    private $configHelper;

    /**
     * @param FormFactoryInterface $formFactory
     * @param Session $session
     * @param Router $router
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        Session $session,
        Router $router,
        ConfigHelper $configHelper
    ) {
        $this->formFactory = $formFactory;
        $this->session = $session;
        $this->router = $router;
        $this->configHelper = $configHelper;
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

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param string $successMessage
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function showSuccessMessageAndRedirect(FieldConfigModel $fieldConfigModel, $successMessage)
    {
        $this->session->getFlashBag()->add('success', $successMessage);

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
            'require_js' => $this->configHelper->getExtendRequireJsModules()
        ];
    }
}
