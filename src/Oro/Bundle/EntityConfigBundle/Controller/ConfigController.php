<?php

namespace Oro\Bundle\EntityConfigBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigProviderHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Annotation\Acl;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EntityConfig controller.
 *
 * @Route("/entity/config")
 * @Acl(
 *      id="oro_entityconfig_manage",
 *      label="oro.entity_config.action.manage",
 *      type="action",
 *      group_name="",
 *      category="entity"
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
     * @Template()
     *
     * @param Request $request
     * @param string $id
     *
     * @return array|RedirectResponse
     */
    public function updateAction(Request $request, $id)
    {
        $entity  = $this->getConfigManager()
            ->getEntityManager()
            ->getRepository('Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel')
            ->find($id);

        $form = $this->createForm(
            ConfigType::class,
            null,
            ['config_model' => $entity]
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
        list($layoutActions, $requireJsModules) = $this->getConfigProviderHelper()->getLayoutParams($entity);

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

        list($layoutActions, $requireJsModules) = $this->getConfigProviderHelper()->getLayoutParams($entity);

        return [
            'buttonConfig' => $layoutActions,
            'entity_id'    => $id,
            'entity_name'  => $entity->getClassName(),
            'require_js'   => $requireJsModules,
        ];
    }

    /**
     * @Route("/field/update/{id}", name="oro_entityconfig_field_update")
     * @Template()
     * @param FieldConfigModel $fieldConfigModel
     * @return array|RedirectResponse
     */
    public function fieldUpdateAction(FieldConfigModel $fieldConfigModel)
    {
        $formAction = $this->generateUrl('oro_entityconfig_field_update', ['id' => $fieldConfigModel->getId()]);
        $successMessage = $this->get('translator')->trans('oro.entity_config.controller.config_field.message.saved');
        
        return $this
            ->get('oro_entity_config.form.handler.config_field_handler')
            ->handleUpdate($fieldConfigModel, $formAction, $successMessage);
    }

    /**
     * @Route("/field/search/{id}", name="oro_entityconfig_field_search", defaults={"id"=0})
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

        /** @var ConfigProvider $entityConfigProvider */
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
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        return $this->get('oro_entity_config.config_manager');
    }

    /**
     * @return EntityConfigProviderHelper
     */
    private function getConfigProviderHelper()
    {
        return $this->get('oro_entity_config.helper.entity_config_provider_helper');
    }
}
