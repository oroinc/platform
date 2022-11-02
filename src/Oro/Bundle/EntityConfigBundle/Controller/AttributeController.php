<?php

namespace Oro\Bundle\EntityConfigBundle\Controller;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Handler\ConfigFieldHandler;
use Oro\Bundle\EntityConfigBundle\Form\Handler\CreateUpdateConfigFieldHandler;
use Oro\Bundle\EntityConfigBundle\Form\Handler\RemoveRestoreConfigFieldHandler;
use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigProviderHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Entity Attribute Controller
 * @Route("/attribute")
 */
class AttributeController extends AbstractController
{
    /**
     * @Route("/create/{alias}", name="oro_attribute_create")
     * @Template("@OroEntityConfig/Attribute/create.html.twig")
     *
     * @param Request $request
     * @param string $alias
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction(Request $request, $alias)
    {
        $entityConfigModel = $this->getEntityByAlias($alias);
        $this->ensureEntityConfigSupported($entityConfigModel);

        $fieldConfigModel = new FieldConfigModel();
        $fieldConfigModel->setEntity($entityConfigModel);
        $fieldConfigModel->fromArray('attribute', ['is_attribute' => true], []);

        $formAction = $this->generateUrl('oro_attribute_create', ['alias' => $alias]);

        $response = $this->getCreateUpdateConfigFieldHandler()
            ->handleCreate($request, $fieldConfigModel, $formAction);

        return $this->addInfoToResponse($response, $alias);
    }

    /**
     * @Route("/save/{alias}", name="oro_attribute_save")
     * @Template("@OroEntityConfig/Attribute/update.html.twig")
     * @param Request $request
     * @param string $alias
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function saveAction(Request $request, $alias)
    {
        $entityConfigModel = $this->getEntityByAlias($alias);
        $this->ensureEntityConfigSupported($entityConfigModel);

        $redirectUrl = $this->generateUrl('oro_attribute_create', ['alias' => $alias]);
        $successMessage = $this->getTranslator()->trans('oro.entity_config.attribute.successfully_saved');
        $formAction = $this->generateUrl('oro_attribute_save', ['alias' => $alias]);

        $options['attribute'] = ['is_attribute' => true];

        $response = $this->getCreateUpdateConfigFieldHandler()
            ->handleFieldSave($request, $entityConfigModel, $redirectUrl, $formAction, $successMessage, $options);

        return $this->addInfoToResponse($response, $alias);
    }

    /**
     * @param array|RedirectResponse $response
     * @param string $alias
     * @return array|RedirectResponse
     */
    private function addInfoToResponse($response, $alias)
    {
        if (is_array($response)) {
            $response['entityAlias'] = $alias;
            $response['attributesLabel'] = sprintf('oro.%s.menu.%s_attributes', $alias, $alias);
        }

        return $response;
    }

    /**
     * @Route("/update/{id}", name="oro_attribute_update", requirements={"id"="\d+"})
     * @Template("@OroEntityConfig/Attribute/update.html.twig")
     * @param FieldConfigModel $fieldConfigModel
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateAction(FieldConfigModel $fieldConfigModel)
    {
        $entityConfigModel = $fieldConfigModel->getEntity();

        $this->ensureEntityConfigSupported($entityConfigModel);
        $this->ensureFieldConfigSupported($fieldConfigModel);

        $formAction = $this->generateUrl('oro_attribute_update', ['id' => $fieldConfigModel->getId()]);
        $successMessage = $this->getTranslator()
            ->trans('oro.entity_config.attribute.successfully_saved');

        $response = $this
            ->get(ConfigFieldHandler::class)
            ->handleUpdate($fieldConfigModel, $formAction, $successMessage);

        $aliasResolver = $this->getEntityAliasResolver();

        return $this->addInfoToResponse($response, $aliasResolver->getAlias($entityConfigModel->getClassName()));
    }

    /**
     * @throws BadRequestHttpException
     */
    private function ensureEntityConfigSupported(EntityConfigModel $entityConfigModel)
    {
        $extendConfigProvider = $this->getExtendConfigProvider();
        $extendConfig = $extendConfigProvider->getConfig($entityConfigModel->getClassName());
        $attributeConfigProvider = $this->getAttributeConfigProvider();
        $attributeConfig = $attributeConfigProvider->getConfig($entityConfigModel->getClassName());

        if (!$extendConfig->is('is_extend') || !$attributeConfig->is('has_attributes')) {
            throw new BadRequestHttpException(
                $this->getTranslator()->trans('oro.entity_config.attribute.entity_not_supported')
            );
        }
    }

    /**
     * @throws BadRequestHttpException
     */
    private function ensureFieldConfigSupported(FieldConfigModel $fieldConfigModel)
    {
        /** @var ConfigProvider $attributeConfigProvider */
        $attributeConfigProvider = $this->getAttributeConfigProvider();
        $attributeConfig = $attributeConfigProvider->getConfig(
            $fieldConfigModel->getEntity()->getClassName(),
            $fieldConfigModel->getFieldName()
        );

        if (!$attributeConfig->is('is_attribute')) {
            throw new BadRequestHttpException(
                $this->getTranslator()->trans('oro.entity_config.attribute.not_attribute')
            );
        }
    }

    /**
     * @param string $alias
     * @return EntityConfigModel
     */
    protected function getEntityByAlias($alias): EntityConfigModel
    {
        $entityClass = $this->getEntityAliasResolver()->getClassByAlias($alias);

        return $this->getConfigModelManager()->findEntityModel($entityClass);
    }

    /**
     * @Route("/index/{alias}", name="oro_attribute_index")
     * @Template("@OroEntityConfig/Attribute/index.html.twig")
     * @param string $alias
     * @return array
     */
    public function indexAction($alias)
    {
        $entityConfigModel = $this->getEntityByAlias($alias);
        $this->ensureEntityConfigSupported($entityConfigModel);
        [$layoutActions] = $this->getConfigProviderHelper()->getLayoutParams($entityConfigModel, 'attribute');

        $response = [
            'entity' => $entityConfigModel,
            'fieldClassName' => FieldConfigModel::class,
            'params' => ['entityId' => $entityConfigModel->getId()],
            'layoutActions' => $layoutActions
        ];

        return $this->addInfoToResponse($response, $alias);
    }

    /**
     * @Route(
     *      "/remove/{id}",
     *      name="oro_attribute_remove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0},
     *      methods={"DELETE"}
     * )
     * @CsrfProtection()
     * @param FieldConfigModel $field
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function removeAction(FieldConfigModel $field)
    {
        $this->ensureFieldConfigSupported($field);

        $successMessage = $this->getTranslator()->trans('oro.entity_config.attribute.successfully_deleted');

        return $this->getRemoveRestoreConfigFieldHandler()->handleRemove($field, $successMessage);
    }

    /**
     * @Route(
     *      "/unremove/{id}",
     *      name="oro_attribute_unremove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0},
     *      methods={"POST"}
     * )
     * @CsrfProtection()
     * @param FieldConfigModel $field
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function unremoveAction(FieldConfigModel $field)
    {
        $this->ensureFieldConfigSupported($field);

        return $this->getRemoveRestoreConfigFieldHandler()->handleRestore(
            $field,
            $this->getTranslator()->trans('oro.entity_config.attribute.cannot_be_restored'),
            $this->getTranslator()->trans('oro.entity_config.attribute.was_restored')
        );
    }

    /**
     * @return ConfigModelManager
     */
    private function getConfigModelManager()
    {
        return $this->get(ConfigModelManager::class);
    }

    private function getExtendConfigProvider(): ConfigProvider
    {
        return $this->get(ConfigManager::class)->getProvider('extend');
    }

    private function getAttributeConfigProvider(): ConfigProvider
    {
        return $this->get(ConfigManager::class)->getProvider('attribute');
    }

    protected function getEntityAliasResolver(): EntityAliasResolver
    {
        return $this->get(EntityAliasResolver::class);
    }

    protected function getCreateUpdateConfigFieldHandler(): CreateUpdateConfigFieldHandler
    {
        return $this->get(CreateUpdateConfigFieldHandler::class);
    }

    protected function getRemoveRestoreConfigFieldHandler(): RemoveRestoreConfigFieldHandler
    {
        return $this->get(RemoveRestoreConfigFieldHandler::class);
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->get(TranslatorInterface::class);
    }

    protected function getConfigProviderHelper(): EntityConfigProviderHelper
    {
        return $this->get(EntityConfigProviderHelper::class);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                RemoveRestoreConfigFieldHandler::class,
                CreateUpdateConfigFieldHandler::class,
                ConfigFieldHandler::class,
                EntityConfigProviderHelper::class,
                EntityAliasResolver::class,
                ConfigManager::class,
                ConfigModelManager::class,
            ]
        );
    }
}
