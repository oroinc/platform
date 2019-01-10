<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use FOS\RestBundle\Util\Codes;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\EntityType;
use Oro\Bundle\EntityExtendBundle\Form\Type\UniqueKeyCollectionType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * ConfigEntityGrid controller
 * @package Oro\Bundle\EntityExtendBundle\Controller
 * @Route("/entity/extend/entity")
 * @AclAncestor("oro_entityconfig_manage")
 */
class ConfigEntityGridController extends Controller
{
    /**
     * @param Request $request
     * @param EntityConfigModel $entity
     * @return array
     *
     * @Route(
     *      "/unique-key/{id}",
     *      name="oro_entityextend_entity_unique_key",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0}
     * )
     * @Template
     */
    public function uniqueAction(Request $request, EntityConfigModel $entity)
    {
        $className      = $entity->getClassName();
        $entityProvider = $this->get('oro_entity_config.provider.entity');
        $entityConfig   = $entityProvider->getConfig($className);

        $form = $this->createForm(
            UniqueKeyCollectionType::class,
            $entityConfig->get('unique_key', false, []),
            [
                'className' => $className
            ]
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
     * @Template
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request)
    {
        /** @var ConfigManager $configManager */
        $configManager = $this->get('oro_entity_config.config_manager');

        if ($request->getMethod() == 'POST') {
            $formData = $request->request->get('oro_entity_config_type');
            if (!$formData || !isset($formData['model']['className'])) {
                throw new BadRequestHttpException(
                    'Request should contains "oro_entity_config_type[model][className]" parameter'
                );
            }
            $className = ExtendHelper::ENTITY_NAMESPACE . $formData['model']['className'];

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
            ConfigType::class,
            null,
            array(
                'config_model' => $entityModel,
            )
        );

        $cloneEntityModel = clone $entityModel;
        $cloneEntityModel->setClassName('');
        $form->add(
            'model',
            EntityType::class,
            array(
                'data' => $cloneEntityModel,
            )
        );

        if ($request->getMethod() == 'POST') {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
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
