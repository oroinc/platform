<?php

namespace Oro\Bundle\EntityConfigBundle\Controller;

use Oro\Bundle\EntityBundle\ORM\EntityAliasResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigProviderHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * @Route("/attribute")
 */
class AttributeController extends Controller
{
    /**
     * @Route("/create/{alias}", name="oro_attribute_create")
     * @Template
     * @param Request $request
     * @param string $alias
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

        $response = $this
            ->get('oro_entity_config.form.handler.create_update_config_field_handler')
            ->handleCreate($request, $fieldConfigModel, $formAction);

        return $this->addInfoToResponse($response, $alias);
    }

    /**
     * @Route("/save/{alias}", name="oro_attribute_save")
     * @Template("OroEntityConfigBundle:Attribute:update.html.twig")
     * @param Request $request
     * @param string $alias
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function saveAction(Request $request, $alias)
    {
        $entityConfigModel = $this->getEntityByAlias($alias);
        $this->ensureEntityConfigSupported($entityConfigModel);

        $redirectUrl = $this->generateUrl('oro_attribute_create', ['alias' => $alias]);
        $successMessage = $this->get('translator')->trans('oro.entity_config.attribute.successfully_saved');
        $formAction = $this->generateUrl('oro_attribute_save', ['alias' => $alias]);

        $options['attribute'] = ['is_attribute' => true];

        $response = $this
            ->get('oro_entity_config.form.handler.create_update_config_field_handler')
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
     * @Template
     * @param FieldConfigModel $fieldConfigModel
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function updateAction(FieldConfigModel $fieldConfigModel)
    {
        $entityConfigModel = $fieldConfigModel->getEntity();

        $this->ensureEntityConfigSupported($entityConfigModel);
        $this->ensureFieldConfigSupported($fieldConfigModel);

        $formAction = $this->generateUrl('oro_attribute_update', ['id' => $fieldConfigModel->getId()]);
        $successMessage = $this->get('translator')->trans('oro.entity_config.attribute.successfully_saved');

        $response = $this
            ->get('oro_entity_config.form.handler.config_field_handler')
            ->handleUpdate($fieldConfigModel, $formAction, $successMessage);

        $aliasResolver = $this->get('oro_entity.entity_alias_resolver');

        return $this->addInfoToResponse($response, $aliasResolver->getAlias($entityConfigModel->getClassName()));
    }

    /**
     * @param EntityConfigModel $entityConfigModel
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
                $this->get('translator')->trans('oro.entity_config.attribute.entity_not_supported')
            );
        }
    }

    /**
     * @return ConfigModelManager
     */
    private function getConfigModelManager()
    {
        return $this->get('oro_entity_config.attribute.config_model_manager');
    }

    /**
     * @return ConfigProvider
     */
    private function getExtendConfigProvider()
    {
        return $this->get('oro_entity_config.provider.extend');
    }

    /**
     * @return ConfigProvider
     */
    private function getAttributeConfigProvider()
    {
        return $this->get('oro_entity_config.provider.attribute');
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @throws BadRequestHttpException
     */
    private function ensureFieldConfigSupported(FieldConfigModel $fieldConfigModel)
    {
        /** @var ConfigProvider $attributeConfigProvider */
        $attributeConfigProvider = $this->get('oro_entity_config.provider.attribute');
        $attributeConfig = $attributeConfigProvider->getConfig(
            $fieldConfigModel->getEntity()->getClassName(),
            $fieldConfigModel->getFieldName()
        );

        if (!$attributeConfig->is('is_attribute')) {
            throw new BadRequestHttpException(
                $this->get('translator')->trans('oro.entity_config.attribute.not_attribute')
            );
        }
    }

    /**
     * @param string $alias
     * @return EntityConfigModel
     */
    private function getEntityByAlias($alias)
    {
        /** @var EntityAliasResolver $aliasResolver */
        $aliasResolver = $this->get('oro_entity.entity_alias_resolver');
        $entityClass = $aliasResolver->getClassByAlias($alias);

        return $this->getConfigModelManager()->findEntityModel($entityClass);
    }

    /**
     * @Route("/index/{alias}", name="oro_attribute_index")
     * @Template
     * @param string $alias
     * @return array
     */
    public function indexAction($alias)
    {
        $entityConfigModel = $this->getEntityByAlias($alias);
        $this->ensureEntityConfigSupported($entityConfigModel);
        list($layoutActions) = $this->getConfigProviderHelper()->getLayoutParams($entityConfigModel, 'attribute');

        $response = [
            'entity' => $entityConfigModel,
            'fieldClassName' => $this->container->getParameter('oro_entity_config.entity.entity_field.class'),
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
     *      defaults={"id"=0}
     * )
     * @param FieldConfigModel $field
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function removeAction(FieldConfigModel $field)
    {
        $this->ensureFieldConfigSupported($field);

        $successMessage = $this->get('translator')->trans('oro.entity_config.attribute.successfully_deleted');

        $response = $this
            ->get('oro_entity_config.form.handler.remove_restore_field_handler')
            ->handleRemove($field, $successMessage);

        return $response;
    }

    /**
     * @Route(
     *      "/unremove/{id}",
     *      name="oro_attribute_unremove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0}
     * )
     * @param FieldConfigModel $field
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function unremoveAction(FieldConfigModel $field)
    {
        $this->ensureFieldConfigSupported($field);

        return $this->get('oro_entity_config.form.handler.remove_restore_field_handler')
            ->handleRestore(
                $field,
                $this->get('translator')->trans('oro.entity_config.attribute.cannot_be_restored'),
                $this->get('translator')->trans('oro.entity_config.attribute.was_restored')
            );
    }

    /**
     * @return EntityConfigProviderHelper
     */
    private function getConfigProviderHelper()
    {
        return $this->get('oro_entity_config.helper.entity_config_provider_helper');
    }
}
