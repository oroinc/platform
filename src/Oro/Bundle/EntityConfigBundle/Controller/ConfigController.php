<?php

namespace Oro\Bundle\EntityConfigBundle\Controller;

use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Oro\Bundle\SecurityBundle\Annotation\Acl;

use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * EntityConfig controller.
 * @Route("/entity/config")
 * TODO: Discuss ACL impl., currently management of configurable entities can be on or off only
 * @Acl(
 *      id="oro_entityconfig_manage",
 *      label="oro.entity_config.action.manage",
 *      type="action",
 *      group_name=""
 * )
 */
class ConfigController extends Controller
{
    /**
     * @var EntityRoutingHelper
     */
    protected $routingHelper;

    /**
     * Lists all configurable entities.
     * @Route("/", name="oro_entityconfig_index")
     * Acl(
     *      id="oro_entityconfig",
     *      label="oro.entity_config.action.view_entities",
     *      type="action",
     *      group_name=""
     * )
     * @Template()
     */
    public function indexAction()
    {
        $actions       = [];
        $modules       = [];

        $providers = $this->getConfigManager()->getProviders();
        foreach ($providers as $provider) {
            foreach ($provider->getPropertyConfig()->getLayoutActions() as $config) {
                $actions[] = $config;
            }

            $modules = array_merge(
                $modules,
                $provider->getPropertyConfig()->getRequireJsModules()
            );
        }

        return [
            'buttonConfig' => $actions,
            'require_js'   => $modules,
        ];
    }

    /**
     * @Route("/update/{id}", name="oro_entityconfig_update")
     * Acl(
     *      id="oro_entityconfig_update",
     *      label="oro.entity_config.action.update_entity",
     *      type="action",
     *      group_name=""
     * )
     * @Template()
     *
     * @param string $id
     *
     * @return array|RedirectResponse
     */
    public function updateAction($id)
    {
        $entity  = $this->getConfigManager()
            ->getEntityManager()
            ->getRepository('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->find($id);
        $request = $this->getRequest();

        $form = $this->createForm(
            'oro_entity_config_type',
            null,
            ['config_model' => $entity]
        );

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                //persist data inside the form
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.entity_config.controller.config_entity.message.saved')
                );

                return $this->get('oro_ui.router')->redirect($entity);
            }
        }

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

        return [
            'entity'        => $entity,
            'entity_config' => $entityConfigProvider->getConfig($entity->getClassName()),
            'form'          => $form->createView(),
            'entity_count'  => $this->getRowCount($entity),
            'link'          => $this->getRowCountLink($entity),
        ];
    }

    /**
     * View Entity
     * @Route("/view/{id}", name="oro_entityconfig_view")
     * Acl(
     *      id="oro_entityconfig_view",
     *      label="oro.entity_config.action.view_entity",
     *      type="action",
     *      group_name=""
     * )
     * @Template()
     *
     * @param EntityConfigModel $entity
     *
     * @return array
     */
    public function viewAction(EntityConfigModel $entity)
    {
        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

        /** @var ConfigProvider $entityConfigProvider */
        $extendConfigProvider = $this->get('oro_entity_config.provider.extend');

        list(, $entityName) = ConfigHelper::getModuleAndEntityNames($entity->getClassName());
        list ($layoutActions, $requireJsModules) = $this->getLayoutParams($entity);

        return [
            'entity'        => $entity,
            'entity_config' => $entityConfigProvider->getConfig($entity->getClassName()),
            'extend_config' => $extendConfigProvider->getConfig($entity->getClassName()),
            'entity_count'  => $this->getRowCount($entity),
            'link'          => $this->getRowCountLink($entity),
            'entity_name'   => $entityName,
            'button_config' => $layoutActions,
            'require_js'    => $requireJsModules,
        ];
    }

    /**
     * TODO: Check if this method ever used
     * Lists Entity fields
     * @Route("/fields/{id}", name="oro_entityconfig_fields", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template()
     *
     * @param string $id
     *
     * @return array
     */
    public function fieldsAction($id)
    {
        $entity = $this->getConfigManager()
            ->getEntityManager()
            ->getRepository('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->find($id);

        list ($layoutActions, $requireJsModules) = $this->getLayoutParams($entity);

        return [
            'buttonConfig' => $layoutActions,
            'entity_id'    => $id,
            'entity_name'  => $entity->getClassName(),
            'require_js'   => $requireJsModules,
        ];
    }

    /**
     * @Route("/field/update/{id}", name="oro_entityconfig_field_update")
     * Acl(
     *      id="oro_entityconfig_field_update",
     *      label="oro.entity_config.action.update_entity_field",
     *      type="action",
     *      group_name=""
     * )
     * @Template()
     *
     * @param string $id
     *
     * @return array|RedirectResponse
     */
    public function fieldUpdateAction($id)
    {
        /** @var FieldConfigModel $field */
        $field   = $this->getConfigManager()
            ->getEntityManager()
            ->getRepository('Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel')
            ->find($id);
        $request = $this->getRequest();

        $form = $this->createForm(
            'oro_entity_config_type',
            null,
            ['config_model' => $field]
        );

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                //persist data inside the form
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.entity_config.controller.config_field.message.saved')
                );

                return $this->get('oro_ui.router')->redirect($field);
            }
        }

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

        /** @var ConfigProvider $entityExtendProvider */
        $entityExtendProvider = $this->get('oro_entity_config.provider.extend');

        $entityConfig = $entityConfigProvider->getConfig($field->getEntity()->getClassName());
        $fieldConfig  = $entityConfigProvider->getConfig(
            $field->getEntity()->getClassName(),
            $field->getFieldName()
        );

        return [
            'entity_config' => $entityConfig,
            'field_config'  => $fieldConfig,
            'field'         => $field,
            'form'          => $form->createView(),
            'formAction'    => $this->generateUrl('oro_entityconfig_field_update', ['id' => $field->getId()]),
            'require_js'    => $entityExtendProvider->getPropertyConfig()->getRequireJsModules()
        ];
    }

    /**
     * @Route("/field/search/{id}", name="oro_entityconfig_field_search", defaults={"id"=0})
     * Acl(
     *      id="oro_entityconfig_field_search",
     *      label="oro.entity_config.action.field_search",
     *      type="action",
     *      group_name=""
     * )
     * @param string $id
     * @return Response
     */
    public function fieldSearchAction($id)
    {
        $fields = [];
        if ($id) {
            $entityRoutingHelper = $this->get('oro_entity.routing_helper');
            $className           = $entityRoutingHelper->resolveEntityClass($id);

            /** @var EntityFieldProvider $fieldProvider */
            $fieldProvider = $this->get('oro_entity.entity_field_provider');

            $entityFields = $fieldProvider->getFields($className);
            foreach ($entityFields as $field) {
                if (!in_array(
                    $field['type'],
                    ['integer', 'string', 'smallint', 'decimal', 'bigint', 'text', 'money']
                )) {
                    continue;
                }
                $fields[$field['name']] = $field['label'] ? : $field['name'];
            }
        }

        /**
         * in case no fields were found - add empty_value into result
         */
        if (empty($fields)) {
            $fields[''] = $this->get('translator')->trans('oro.entity.form.choose_entity_field');
        }

        return new Response(json_encode($fields));
    }

    /**
     * @Route("/widget/info/{id}", name="oro_entityconfig_widget_info")
     * @Template
     *
     * @param EntityConfigModel $entity
     *
     * @return array
     */
    public function infoAction(EntityConfigModel $entity)
    {
        list($moduleName, $entityName) = ConfigHelper::getModuleAndEntityNames($entity->getClassName());

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

        /** @var ConfigProvider $extendConfigProvider */
        $extendConfigProvider = $this->get('oro_entity_config.provider.extend');
        $extendConfig         = $extendConfigProvider->getConfig($entity->getClassName());

        /** @var ConfigProvider $ownershipConfigProvider */
        $ownershipConfigProvider = $this->get('oro_entity_config.provider.ownership');
        $ownerTypes              = $this->get('oro_organization.form.type.ownership_type')->getOwnershipsArray();
        $ownerType               = $ownershipConfigProvider->getConfig($entity->getClassName())->get('owner_type');
        $ownerType               = $ownerTypes[empty($ownerType) ? 'NONE' : $ownerType];

        return [
            'entity'            => $entity,
            'entity_config'     => $entityConfigProvider->getConfig($entity->getClassName()),
            'entity_extend'     => $extendConfig,
            'entity_owner_type' => $ownerType,
            'entity_name'       => $entityName,
            'module_name'       => $moduleName,
        ];
    }

    /**
     * @param EntityConfigModel $entity
     *
     * @return array
     *
     * @Route("/widget/unique_keys/{id}", name="oro_entityconfig_widget_unique_keys")
     * @Template
     */
    public function uniqueKeysAction(EntityConfigModel $entity)
    {
        $className = $entity->getClassName();

        /** @var ConfigProvider $extendConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');
        $entityConfig         = $entityConfigProvider->getConfig($className);
        $translator           = $this->get('translator');

        $uniqueKeys = $entityConfig->get('unique_key', false, ['keys' => []]);

        foreach ($uniqueKeys['keys'] as $index => $uniqueKey) {
            $uniqueKeys['keys'][$index]['key'] = array_map(
                function ($fieldName) use ($entityConfigProvider, $className, $translator) {
                    $label = $entityConfigProvider
                        ->getConfig($className, $fieldName)
                        ->get('label');

                    return $translator->trans($label);
                },
                $uniqueKey['key']
            );
        }

        return [
            'entity'     => $entity,
            'unique_key' => $uniqueKeys
        ];
    }

    /**
     * @Route("/widget/entity_fields/{id}", name="oro_entityconfig_widget_entity_fields")
     * @Template
     * @param EntityConfigModel $entity
     * @return array
     */
    public function entityFieldsAction(EntityConfigModel $entity)
    {
        return ['entity' => $entity];
    }

    /**
     * @param EntityConfigModel $entity
     * @return int
     */
    protected function getRowCount(EntityConfigModel $entity)
    {
        if (class_exists($entity->getClassName())) {
            /** @var QueryBuilder $qb */
            $qb = $this->getDoctrine()
                ->getManagerForClass($entity->getClassName())
                ->createQueryBuilder();
            $qb->select('entity');
            $qb->from($entity->getClassName(), 'entity');

            return QueryCountCalculator::calculateCount($qb->getQuery());
        }

        return 0;
    }

    /**
     * @param EntityConfigModel $entity
     * @return string
     */
    protected function getRowCountLink(EntityConfigModel $entity)
    {
        $link = '';
        if (class_exists($entity->getClassName())) {
            /** @var ConfigProvider $extendConfigProvider */
            $extendConfigProvider = $this->get('oro_entity_config.provider.extend');
            $extendConfig         = $extendConfigProvider->getConfig($entity->getClassName());

            $metadata = $this->getConfigManager()->getEntityMetadata($entity->getClassName());
            if ($metadata && $metadata->routeName) {
                $link = $this->generateUrl($metadata->routeName);
            }

            if ($extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
                $link = $this->generateUrl(
                    'oro_entity_index',
                    ['entityName' => $this->getRoutingHelper()->getUrlSafeClassName($entity->getClassName())]
                );
            }
        }

        return $link;
    }

    /**
     * @return EntityRoutingHelper
     */
    protected function getRoutingHelper()
    {
        if (!$this->routingHelper) {
            $this->routingHelper = $this->get('oro_entity.routing_helper');
        }

        return $this->routingHelper;
    }

    /**
     * Return configured layout actions and requirejs modules
     *
     * @param  EntityConfigModel $entity
     * @return array
     */
    protected function getLayoutParams(EntityConfigModel $entity)
    {
        $actions          = [];
        $requireJsModules = [];

        $providers = $this->getConfigManager()->getProviders();
        foreach ($providers as $provider) {
            $layoutActions = $provider->getPropertyConfig()->getLayoutActions(PropertyConfigContainer::TYPE_FIELD);
            foreach ($layoutActions as $action) {
                if ($this->isLayoutActionApplicable($action, $entity, $provider)) {
                    if (isset($action['entity_id']) && $action['entity_id'] == true) {
                        $action['args'] = ['id' => $entity->getId()];
                    }
                    $actions[] = $action;
                }
            }

            $requireJsModules = array_merge(
                $requireJsModules,
                $provider->getPropertyConfig(PropertyConfigContainer::TYPE_FIELD)->getRequireJsModules()
            );
        }

        return [$actions, $requireJsModules];
    }

    /**
     * @param array             $action
     * @param EntityConfigModel $entity
     * @param ConfigProvider    $provider
     *
     * @return bool
     */
    protected function isLayoutActionApplicable(
        array $action,
        EntityConfigModel $entity,
        ConfigProvider $provider
    ) {
        if (!isset($action['filter'])) {
            return true;
        }

        $result = true;
        foreach ($action['filter'] as $key => $value) {
            if ($key === 'mode') {
                if ($entity->getMode() !== $value) {
                    $result = false;
                    break;
                }
            } else {
                $config = $provider->getConfig($entity->getClassName());
                if (is_array($value)) {
                    if (!$config->in($key, $value)) {
                        $result = false;
                        break;
                    }
                } elseif ($config->get($key) != $value) {
                    $result = false;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->get('oro_entity_config.config_manager');
    }
}
