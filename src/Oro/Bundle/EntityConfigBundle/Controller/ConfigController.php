<?php

namespace Oro\Bundle\EntityConfigBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\BatchBundle\ORM\Query\QueryCountCalculator;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Handler\ConfigFieldHandler;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityConfigBundle\Helper\EntityConfigProviderHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\ConfigHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Form\Type\OwnershipType;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Attribute\Acl;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * CRUD controller to manage configurable entities.
 */
#[Acl(
    id: 'oro_entityconfig_manage',
    label: 'oro.entity_config.action.manage',
    type: 'action',
    groupName: '',
    category: 'entity'
)]
#[Route(path: '/entity/config')]
class ConfigController extends AbstractController
{
    #[Route(path: '/', name: 'oro_entityconfig_index')]
    #[Template]
    public function indexAction()
    {
        $actions = [];
        $jsModules = [];
        $providers = $this->getConfigManager()->getProviders();
        /** @var FeatureChecker $featureChecker */
        $featureChecker = $this->container->get('oro_featuretoggle.checker.feature_checker');
        $isCustomEntityCreationFeatureEnabled = $featureChecker->isFeatureEnabled('custom_entity_creation');
        foreach ($providers as $provider) {
            $propertyConfig = $provider->getPropertyConfig();
            $providerActions = $propertyConfig->getLayoutActions();
            foreach ($providerActions as $action) {
                if (array_key_exists('route', $action) && 'oro_entityextend_entity_create' === $action['route']) {
                    if ($isCustomEntityCreationFeatureEnabled) {
                        $actions[] = $action;
                    }
                } else {
                    $actions[] = $action;
                }
            }
            $providerModules = $propertyConfig->getJsModules();
            foreach ($providerModules as $module) {
                $jsModules[] = $module;
            }
        }

        return [
            'entity_class' => EntityConfigModel::class,
            'buttonConfig' => $actions,
            'jsmodules'    => $jsModules
        ];
    }

    /**
     *
     * @param Request $request
     * @param string  $id
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/update/{id}', name: 'oro_entityconfig_update', methods: ['GET', 'POST'])]
    #[Acl(
        id: 'oro_entityconfig_update',
        type: 'entity',
        class: EntityConfigModel::class,
        permission: BasicPermission::EDIT
    )]
    #[Template]
    public function updateAction(Request $request, EntityConfigModel $entity)
    {
        $form = $this->createForm(
            ConfigType::class,
            null,
            ['config_model' => $entity]
        );

        if ($request->getMethod() === 'POST') {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                //persist data inside the form
                $this->addFlash(
                    'success',
                    $this->getTranslator()->trans('oro.entity_config.controller.config_entity.message.saved')
                );

                return $this->container->get(Router::class)->redirect($entity);
            }
        }

        return [
            'entity'        => $entity,
            'entity_config' => $this->getConfigProvider('entity')->getConfig($entity->getClassName()),
            'form'          => $form->createView(),
            'entity_count'  => $this->getRowCount($entity),
            'link'          => $this->getRowCountLink($entity),
        ];
    }

    /**
     *
     * @param EntityConfigModel $entity
     *
     * @return array
     */
    #[Route(path: '/view/{id}', name: 'oro_entityconfig_view')]
    #[Template]
    public function viewAction(EntityConfigModel $entity)
    {
        [, $entityName] = ConfigHelper::getModuleAndEntityNames($entity->getClassName());
        [$layoutActions, $jsModules] = $this->getConfigProviderHelper()->getLayoutParams($entity);

        return [
            'entity'        => $entity,
            'entity_config' => $this->getConfigProvider('entity')->getConfig($entity->getClassName()),
            'extend_config' => $this->getConfigProvider('extend')->getConfig($entity->getClassName()),
            'entity_count'  => $this->getRowCount($entity),
            'link'          => $this->getRowCountLink($entity),
            'entity_name'   => $entityName,
            'button_config' => $layoutActions,
            'jsmodules'     => $jsModules
        ];
    }

    /**
     *
     * @param string $id
     *
     * @return array
     */
    #[Route(
        path: '/fields/{id}',
        name: 'oro_entityconfig_fields',
        requirements: ['id' => '\d+'],
        defaults: ['id' => 0]
    )]
    #[Template]
    public function fieldsAction($id)
    {
        $entity = $this->getConfigManager()
            ->getEntityManager()
            ->getRepository(EntityConfigModel::class)
            ->find($id);

        [$layoutActions, $jsModules] = $this->getConfigProviderHelper()->getLayoutParams($entity);

        return [
            'buttonConfig' => $layoutActions,
            'entity_id'    => $id,
            'entity_name'  => $entity->getClassName(),
            'jsmodules'    => $jsModules
        ];
    }

    /**
     *
     * @param FieldConfigModel $fieldConfigModel
     *
     * @return array|RedirectResponse
     */
    #[Route(path: '/field/update/{id}', name: 'oro_entityconfig_field_update')]
    #[Acl(
        id: 'oro_entityconfig_update_field',
        type: 'entity',
        class: FieldConfigModel::class,
        permission: BasicPermission::EDIT
    )]
    #[Template]
    public function fieldUpdateAction(FieldConfigModel $fieldConfigModel)
    {
        $formAction = $this->generateUrl('oro_entityconfig_field_update', ['id' => $fieldConfigModel->getId()]);
        $successMessage = $this->getTranslator()->trans('oro.entity_config.controller.config_field.message.saved');

        return $this->getConfigFieldHandler()
            ->handleUpdate($fieldConfigModel, $formAction, $successMessage);
    }

    /**
     *
     * @param string $id
     * @return Response
     */
    #[Route(path: '/field/search/{id}', name: 'oro_entityconfig_field_search', defaults: ['id' => 0])]
    public function fieldSearchAction($id)
    {
        $fields = [];
        if ($id) {
            $className = $this->getRoutingHelper()->resolveEntityClass($id);
            $entityFields = $this->getEntityFieldProvider()->getEntityFields($className);
            foreach ($entityFields as $field) {
                if (!\in_array(
                    $field['type'],
                    ['integer', 'string', 'smallint', 'decimal', 'bigint', 'text', 'money'],
                    true
                )) {
                    continue;
                }
                $fields[$field['name']] = $field['label'] ?: $field['name'];
            }
        }

        /**
         * in case no fields were found - add empty_value into result
         */
        if (empty($fields)) {
            $fields[''] = $this->getTranslator()->trans('oro.entity.form.choose_entity_field');
        }

        return new Response(json_encode($fields));
    }

    /**
     *
     * @param EntityConfigModel $entity
     *
     * @return array
     */
    #[Route(path: '/widget/info/{id}', name: 'oro_entityconfig_widget_info')]
    #[Template('@OroEntityConfig/Config/widget/info.html.twig')]
    public function infoAction(EntityConfigModel $entity)
    {
        [$moduleName, $entityName] = ConfigHelper::getModuleAndEntityNames($entity->getClassName());

        $extendConfig = $this->getConfigProvider('extend')->getConfig($entity->getClassName());
        $ownerTypes = $this->container->get(OwnershipType::class)->getOwnershipsArray();
        $ownerType = $this->getConfigProvider('ownership')->getConfig($entity->getClassName())->get('owner_type');
        $ownerType = $ownerTypes[empty($ownerType) ? 'NONE' : $ownerType];

        return [
            'entity'            => $entity,
            'entity_config'     => $this->getConfigProvider('entity')->getConfig($entity->getClassName()),
            'entity_extend'     => $extendConfig,
            'entity_owner_type' => $ownerType,
            'entity_name'       => $entityName,
            'module_name'       => $moduleName,
        ];
    }

    /**
     *
     * @param EntityConfigModel $entity
     *
     * @return array
     */
    #[Route(path: '/widget/unique_keys/{id}', name: 'oro_entityconfig_widget_unique_keys')]
    #[Template]
    public function uniqueKeysAction(EntityConfigModel $entity)
    {
        $className = $entity->getClassName();

        $entityConfigProvider = $this->getConfigProvider('entity');
        $translator = $this->getTranslator();

        $entityConfig = $entityConfigProvider->getConfig($className);
        $uniqueKeys = $entityConfig->get('unique_key', false, ['keys' => []]);

        foreach ($uniqueKeys['keys'] as $index => $uniqueKey) {
            $uniqueKeys['keys'][$index]['key'] = array_map(
                function ($fieldName) use ($entityConfigProvider, $className, $translator) {
                    return $translator->trans(
                        (string) $entityConfigProvider->getConfig($className, $fieldName)->get('label')
                    );
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
     *
     * @param EntityConfigModel $entity
     *
     * @return array
     */
    #[Route(path: '/widget/entity_fields/{id}', name: 'oro_entityconfig_widget_entity_fields')]
    #[Template]
    public function entityFieldsAction(EntityConfigModel $entity)
    {
        return ['entity' => $entity];
    }

    /**
     * @param EntityConfigModel $entity
     *
     * @return int
     */
    private function getRowCount(EntityConfigModel $entity)
    {
        if (class_exists($entity->getClassName())) {
            /** @var QueryBuilder $qb */
            $qb = $this->container->get('doctrine')
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
     *
     * @return string
     */
    private function getRowCountLink(EntityConfigModel $entity)
    {
        $link = '';
        if (class_exists($entity->getClassName())) {
            $metadata = $this->getConfigManager()->getEntityMetadata($entity->getClassName());
            if ($metadata && $metadata->routeName) {
                $link = $this->generateUrl($metadata->routeName);
            }

            $extendConfig = $this->getConfigProvider('extend')->getConfig($entity->getClassName());
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
    private function getRoutingHelper()
    {
        return $this->container->get(EntityRoutingHelper::class);
    }

    /**
     * @return ConfigManager
     */
    private function getConfigManager()
    {
        return $this->container->get(ConfigManager::class);
    }

    /**
     * @param string $scope
     *
     * @return ConfigProvider
     */
    private function getConfigProvider($scope)
    {
        return $this->getConfigManager()->getProvider($scope);
    }

    /**
     * @return ConfigFieldHandler
     */
    private function getConfigFieldHandler()
    {
        return $this->container->get(ConfigFieldHandler::class);
    }

    /**
     * @return EntityConfigProviderHelper
     */
    private function getConfigProviderHelper()
    {
        return $this->container->get(EntityConfigProviderHelper::class);
    }

    /**
     * @return EntityFieldProvider
     */
    private function getEntityFieldProvider()
    {
        return $this->container->get('oro_entity.entity_field_provider');
    }

    /**
     * @return TranslatorInterface
     */
    private function getTranslator()
    {
        return $this->container->get(TranslatorInterface::class);
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                'oro_featuretoggle.checker.feature_checker' => FeatureChecker::class,
                'oro_entity.entity_field_provider' => EntityFieldProvider::class,
                ConfigManager::class,
                EntityRoutingHelper::class,
                EntityConfigProviderHelper::class,
                TranslatorInterface::class,
                Router::class,
                ConfigFieldHandler::class,
                OwnershipType::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
