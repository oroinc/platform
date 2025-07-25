<?php

namespace Oro\Bundle\EntityBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityBundle\Form\Type\CustomEntityType;
use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Tools\FieldAccessor;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\Attribute\CsrfProtection;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Entities controller.
 */
#[Route(path: '/entities')]
class EntitiesController extends AbstractController
{
    /**
     * Grid of Custom/Extend entity.
     *
     * @param string $entityName
     *
     * @return array
     */
    #[Route(path: '/{entityName}', name: 'oro_entity_index')]
    #[Template]
    public function indexAction($entityName)
    {
        $entityClass = $this->container->get(EntityRoutingHelper::class)->resolveEntityClass($entityName);

        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException();
        }

        $this->checkAccess('VIEW', $entityClass);

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->container->get('oro_entity_config.provider.entity');

        if (!$entityConfigProvider->hasConfig($entityClass)) {
            throw $this->createNotFoundException();
        }

        $entityConfig = $entityConfigProvider->getConfig($entityClass);

        return [
            'entity_name'  => $entityName,
            'entity_class' => $entityClass,
            'label'        => $entityConfig->get('label'),
            'plural_label' => $entityConfig->get('plural_label')
        ];
    }

    /**
     * @param string $id
     * @param string $entityName
     * @param string $fieldName
     *
     * @return array
     */
    #[Route(
        path: '/detailed/{id}/{entityName}/{fieldName}',
        name: 'oro_entity_detailed',
        defaults: ['id' => 0, 'fieldName' => '']
    )]
    #[Template]
    public function detailedAction($id, $entityName, $fieldName)
    {
        $entityClass = $this->container->get(EntityRoutingHelper::class)->resolveEntityClass($entityName);

        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException();
        }

        $this->checkAccess('VIEW', $entityClass);

        $entityProvider = $this->container->get('oro_entity_config.provider.entity');
        $extendProvider = $this->container->get('oro_entity_config.provider.extend');
        $relationConfig = $extendProvider->getConfig($entityClass, $fieldName);
        $relationTargetEntity = $relationConfig->get('target_entity');

        if (!class_exists($relationTargetEntity)) {
            throw $this->createNotFoundException();
        }

        /** @var ConfigInterface[] $fields */
        $fields = $extendProvider->filter(
            function (ConfigInterface $config) use ($relationConfig) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $config->getId();

                return
                    ExtendHelper::isFieldAccessible($config)
                    && in_array($fieldConfigId->getFieldName(), (array)$relationConfig->get('target_detailed'), true);
            },
            $relationConfig->get('target_entity')
        );

        $entity = $this->container->get('doctrine')->getRepository($relationTargetEntity)->find($id);

        if (!$entity) {
            return $this->createNotFoundException();
        }

        $dynamicRow = array();
        foreach ($fields as $field) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId      = $field->getId();
            $fieldName          = $fieldConfigId->getFieldName();
            $label              = $entityProvider->getConfigById($fieldConfigId)->get('label') ?: $fieldName;
            $dynamicRow[$label] = FieldAccessor::getValue($entity, $fieldName);
        }

        return array(
            'dynamic' => $dynamicRow,
            'entity'  => $entity
        );
    }

    /**
     * Grid of Custom/Extend entity.
     *
     * @param string $id
     * @param string $entityName
     * @param string $fieldName
     *
     * @return array
     */
    #[Route(
        path: '/relation/{id}/{entityName}/{fieldName}',
        name: 'oro_entity_relation',
        defaults: ['id' => 0, 'className' => '', 'fieldName' => '']
    )]
    #[Template]
    public function relationAction($id, $entityName, $fieldName)
    {
        $entityClass = $this->container->get(EntityRoutingHelper::class)->resolveEntityClass($entityName);

        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException();
        }

        $this->checkAccess('VIEW', $entityClass);

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->container->get('oro_entity_config.provider.entity');
        $extendConfigProvider = $this->container->get('oro_entity_config.provider.extend');

        if (!$entityConfigProvider->hasConfig($entityClass)) {
            throw $this->createNotFoundException();
        }

        $entityConfig = $entityConfigProvider->getConfig($entityClass);
        $fieldConfig  = $extendConfigProvider->getConfig($entityClass, $fieldName);

        return [
            'id'              => $id,
            'field_name'      => $fieldName,
            'entity_name'     => $entityName,
            'entity_class'    => $entityClass,
            'label'           => $entityConfig->get('label'),
            'entity_provider' => $entityConfigProvider,
            'extend_provider' => $extendConfigProvider,
            'relation'        => $fieldConfig
        ];
    }

    /**
     * View custom entity instance.
     *
     * @param string $entityName
     * @param string $id
     *
     * @return array
     */
    #[Route(path: '/view/{entityName}/item/{id}', name: 'oro_entity_view')]
    #[Template]
    public function viewAction($entityName, $id)
    {
        $entityClass = $this->container->get(EntityRoutingHelper::class)->resolveEntityClass($entityName);

        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException();
        }

        $this->checkAccess('VIEW', $entityClass);

        /** @var OroEntityManager $em */
        $em = $this->container->get('doctrine')->getManager();
        $entityConfigProvider = $this->container->get('oro_entity_config.provider.entity');
        $record = $em->getRepository($entityClass)->find($id);

        if (!$record) {
            throw $this->createNotFoundException();
        }

        return [
            'entity_name'   => $entityName,
            'entity'        => $record,
            'entity_config' => $entityConfigProvider->getConfig($entityClass),
            'entity_class'  => $entityClass,
        ];
    }

    /**
     * Update custom entity instance.
     *
     * @param Request $request
     * @param string $entityName
     * @param string $id
     *
     * @return array
     */
    #[Route(path: '/update/{entityName}/item/{id}', name: 'oro_entity_update', defaults: ['id' => 0])]
    #[Template]
    public function updateAction(Request $request, $entityName, $id)
    {
        $entityClass = $this->container->get(EntityRoutingHelper::class)->resolveEntityClass($entityName);

        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException();
        }

        $this->checkAccess(!$id ? 'CREATE' : 'EDIT', $entityClass);

        /** @var OroEntityManager $em */
        $em = $this->container->get('doctrine')->getManager();

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->container->get('oro_entity_config.provider.entity');
        $entityConfig         = $entityConfigProvider->getConfig($entityClass);

        $entityRepository = $em->getRepository($entityClass);

        $record = !$id ? new $entityClass() : $entityRepository->find($id);

        $form = $this->createForm(
            CustomEntityType::class,
            $record,
            array(
                'data_class'   => $entityClass,
                'block_config' => array(
                    'general' => array(
                        'title' => 'General'
                    )
                ),
            )
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($record);
                $em->flush();

                $request->getSession()->getFlashBag()->add(
                    'success',
                    $this->container->get(TranslatorInterface::class)->trans('oro.entity.controller.message.saved')
                );

                return $this->container->get(Router::class)->redirect($record);
            }
        }

        return [
            'entity'        => $record,
            'entity_name'   => $entityName,
            'entity_config' => $entityConfig,
            'entity_class'  => $entityClass,
            'form'          => $form->createView(),
        ];
    }

    /**
     * Delete custom entity instance.
     *
     * @param string $entityName
     * @param string $id
     *
     * @return JsonResponse
     */
    #[Route(path: '/delete/{entityName}/item/{id}', name: 'oro_entity_delete', methods: ['DELETE'])]
    #[CsrfProtection()]
    public function deleteAction($entityName, $id)
    {
        $entityClass = $this->container->get(EntityRoutingHelper::class)->resolveEntityClass($entityName);

        if (!class_exists($entityClass)) {
            throw $this->createNotFoundException();
        }

        $this->checkAccess('DELETE', $entityClass);

        /** @var OroEntityManager $em */
        $em = $this->container->get('doctrine')->getManager();

        $entityRepository = $em->getRepository($entityClass);

        $record = $entityRepository->find($id);
        if (!$record) {
            return new JsonResponse('', Response::HTTP_FORBIDDEN);
        }

        $em->remove($record);
        $em->flush();

        return new JsonResponse('', Response::HTTP_OK);
    }

    /**
     * Checks if an access to the given entity is granted or not
     *
     * @param string $permission
     * @param string $entityName
     * @return bool
     * @throws AccessDeniedException
     */
    private function checkAccess($permission, $entityName)
    {
        if (!$this->isGranted($permission, 'entity:' . $entityName)) {
            throw new AccessDeniedException('Access denied.');
        }
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                EntityRoutingHelper::class,
                'oro_entity_config.provider.entity' => ConfigProvider::class,
                'oro_entity_config.provider.extend' => ConfigProvider::class,
                TranslatorInterface::class,
                Router::class,
                'doctrine' => ManagerRegistry::class,
            ]
        );
    }
}
