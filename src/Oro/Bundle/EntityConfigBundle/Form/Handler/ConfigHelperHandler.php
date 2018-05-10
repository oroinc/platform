<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\UIBundle\Route\Router;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    /** @var TranslatorInterface */
    private $translator;

    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

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
     * @param TranslatorInterface $translator
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function setUrlGenerator(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
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
     * @return $this
     */
    public function showClearCacheMessage()
    {
        $message = $this->translator->trans(
            'oro.translation.translation.rebuild_cache_required',
            [
                '%path%' => $this->generateUrl('oro_translation_translation_index')
            ]
        );

        $this->session->getFlashBag()->add('warning', $message);

        return $this;
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

    /**
     * Generates a URL from the given parameters.
     *
     * @param string $route         The name of the route
     * @param mixed  $parameters    An array of parameters
     * @param int    $referenceType The type of reference (one of the constants in UrlGeneratorInterface)
     *
     * @return string The generated URL
     *
     * @see UrlGeneratorInterface
     */
    private function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->urlGenerator->generate($route, $parameters, $referenceType);
    }
}
