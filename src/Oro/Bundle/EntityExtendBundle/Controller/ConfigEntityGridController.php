<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use FOS\RestBundle\Util\Codes;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;

/**
 * Class ConfigEntityGridController
 * @package Oro\Bundle\EntityExtendBundle\Controller
 * @Route("/entity/extend/entity")
 * TODO: Discuss ACL impl., currently acl is disabled
 * @AclAncestor("oro_entityconfig_manage")
 */
class ConfigEntityGridController extends Controller
{
    /**
     * @param EntityConfigModel $entity
     * @return array
     *
     * @Route(
     *      "/unique-key/{id}",
     *      name="oro_entityextend_entity_unique_key",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0}
     * )
     * Acl(
     *      id="oro_entityextend_entity_unique_key",
     *      label="oro.entity_extend.action.config_entity_grid.unique",
     *      type="action",
     *      group_name=""
     * )
     * @Template
     */
    public function uniqueAction(EntityConfigModel $entity)
    {
        $className      = $entity->getClassName();
        $entityProvider = $this->get('oro_entity_config.provider.entity');
        $entityConfig   = $entityProvider->getConfig($className);

        $form = $this->createForm(
            'oro_entity_extend_unique_key_collection_type',
            $entityConfig->get('unique_key', false, []),
            [
                'className' => $className
            ]
        );

        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                $entityConfig->set('unique_key', $form->getData());
                $configManager = $entityProvider->getConfigManager();
                $configManager->persist($entityConfig);
                $configManager->flush();

                return $this->get('oro_ui.router')->redirect($entity);
            }
        }

        return array(
            'form'          => $form->createView(),
            'entity_id'     => $entity->getId(),
            'entity_config' => $entityConfig
        );
    }

    /**
     * @Route("/create", name="oro_entityextend_entity_create")
     * Acl(
     *      id="oro_entityextend_entity_create",
     *      label="oro.entity_extend.action.config_entity_grid.create",
     *      type="action",
     *      group_name=""
     * )
     * @Template
     */
    public function createAction()
    {
        $request = $this->getRequest();

        /** @var ConfigManager $configManager */
        $configManager = $this->get('oro_entity_config.config_manager');

        if ($request->getMethod() == 'POST') {
            $className = ExtendHelper::ENTITY_NAMESPACE . $request->request->get(
                'oro_entity_config_type[model][className]',
                null,
                true
            );

            $entityModel  = $configManager->createConfigEntityModel($className);
            $extendConfig = $configManager->getProvider('extend')->getConfig($className);
            $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);
            $extendConfig->set('state', ExtendScope::STATE_NEW);
            $extendConfig->set('upgradeable', false);
            $extendConfig->set('origin', ExtendScope::ORIGIN_CUSTOM);
            $extendConfig->set('is_extend', true);

            $config = $configManager->getProvider('security')->getConfig($className);
            $config->set('type', EntitySecurityMetadataProvider::ACL_SECURITY_TYPE);

            $configManager->persist($extendConfig);
        } else {
            $entityModel = $configManager->createConfigEntityModel();
        }

        $form = $this->createForm(
            'oro_entity_config_type',
            null,
            array(
                'config_model' => $entityModel,
            )
        );

        $cloneEntityModel = clone $entityModel;
        $cloneEntityModel->setClassName('');
        $form->add(
            'model',
            'oro_entity_extend_entity_type',
            array(
                'data' => $cloneEntityModel,
            )
        );

        if ($request->getMethod() == 'POST') {
            $form->submit($request);

            if ($form->isValid()) {
                //persist data inside the form
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.entity_extend.controller.config_entity.message.saved')
                );

                return $this->get('oro_ui.router')->redirect($entityModel);
            }
        }

        return array(
            'form' => $form->createView(),
        );
    }

    /**
     * @Route(
     *      "/remove/{id}",
     *      name="oro_entityextend_entity_remove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0}
     * )
     * Acl(
     *      id="oro_entityextend_entity_remove",
     *      label="oro.entity_extend.action.config_entity_grid.remove",
     *      type="action",
     *      group_name=""
     * )
     */
    public function removeAction(EntityConfigModel $entity)
    {
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find EntityConfigModel entity.');
        }

        /** @var ConfigManager $configManager */
        $configManager = $this->get('oro_entity_config.config_manager');

        $entityConfig = $configManager->getProvider('extend')->getConfig($entity->getClassName());

        if ($entityConfig->get('owner') == ExtendScope::OWNER_SYSTEM) {
            return new Response('', Codes::HTTP_FORBIDDEN);
        }

        $entityConfig->set('state', ExtendScope::STATE_DELETE);

        $configManager->persist($entityConfig);
        $configManager->flush();

        return new JsonResponse(array('message' => 'Item deleted', 'successful' => true), Codes::HTTP_OK);
    }

    /**
     * @Route(
     *      "/unremove/{id}",
     *      name="oro_entityextend_entity_unremove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0}
     * )
     * Acl(
     *      id="oro_entityextend_entity_unremove",
     *      label="oro.entity_extend.action.config_entity_grid.unremove",
     *      type="action",
     *      group_name=""
     * )
     */
    public function unremoveAction(EntityConfigModel $entity)
    {
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find EntityConfigModel entity.');
        }

        /** @var ConfigManager $configManager */
        $configManager = $this->get('oro_entity_config.config_manager');

        $entityConfig = $configManager->getProvider('extend')->getConfig($entity->getClassName());

        if ($entityConfig->get('owner') == ExtendScope::OWNER_SYSTEM) {
            return new Response('', Codes::HTTP_FORBIDDEN);
        }

        $entityConfig->set(
            'state',
            class_exists($entity->getClassName()) ? ExtendScope::STATE_UPDATE : ExtendScope::STATE_NEW
        );

        $configManager->persist($entityConfig);
        $configManager->flush();

        return new JsonResponse(array('message' => 'Item was restored', 'successful' => true), Codes::HTTP_OK);
    }
}
