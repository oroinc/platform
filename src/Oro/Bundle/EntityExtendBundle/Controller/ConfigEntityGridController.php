<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Form\Type\EntityType;
use Oro\Bundle\EntityExtendBundle\Form\Type\UniqueKeyCollectionType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Oro\Bundle\SecurityBundle\Metadata\EntitySecurityMetadataProvider;
use Oro\Bundle\UIBundle\Route\Router;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * ConfigEntityGrid controller
 *
 * @package Oro\Bundle\EntityExtendBundle\Controller
 * @Route("/entity/extend/entity")
 * @AclAncestor("oro_entityconfig_manage")
 */
class ConfigEntityGridController extends AbstractController
{
    /**
     * @param Request           $request
     * @param EntityConfigModel $entity
     * @return RedirectResponse|array
     * @Route(
     *      "/unique-key/{id}",
     *      name="oro_entityextend_entity_unique_key",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0}
     * )
     * @Template
     */
    public function uniqueAction(Request $request, EntityConfigModel $entity): RedirectResponse|array
    {
        $className = $entity->getClassName();

        $configManager = $this->getConfigManager();
        $extendEntityConfig = $configManager->getProvider('extend')->getConfig($className);

        $form = $this->createForm(
            UniqueKeyCollectionType::class,
            $extendEntityConfig->get('unique_key', false, []),
            [
                'className' => $className
            ]
        );

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $extendEntityConfig->set('unique_key', $form->getData());
                $configManager->persist($extendEntityConfig);
                $configManager->flush();

                return $this->getRouter()->redirect($entity);
            }
        }

        return [
            'form' => $form->createView(),
            'entity_id' => $entity->getId(),
            'entity_config' => $configManager->getProvider('entity')->getConfig($className)
        ];
    }

    /**
     * @Route("/create", name="oro_entityextend_entity_create")
     * @Template
     * @param Request $request
     * @return array|RedirectResponse
     */
    public function createAction(Request $request): RedirectResponse|array
    {
        $configManager = $this->getConfigManager();

        if ($request->isMethod('POST')) {
            $formData = $request->request->get('oro_entity_config_type');
            if (!$formData || !isset($formData['model']['className'])) {
                throw new BadRequestHttpException(
                    'Request should contains "oro_entity_config_type[model][className]" parameter'
                );
            }
            $className = ExtendHelper::ENTITY_NAMESPACE . $formData['model']['className'];

            $entityModel = $configManager->createConfigEntityModel($className);
            $extendConfig = $configManager->getProvider('extend')->getConfig($className);
            $extendConfig->set('owner', ExtendScope::OWNER_CUSTOM);
            $extendConfig->set('state', ExtendScope::STATE_NEW);
            $extendConfig->set('upgradeable', false);
            $extendConfig->set('is_extend', true);

            $config = $configManager->getProvider('security')->getConfig($className);
            $config->set('type', EntitySecurityMetadataProvider::ACL_SECURITY_TYPE);

            $configManager->persist($extendConfig);
        } else {
            $entityModel = $configManager->createConfigEntityModel();
        }

        $form = $this->createForm(ConfigType::class, null, ['config_model' => $entityModel]);

        $cloneEntityModel = clone $entityModel;
        $cloneEntityModel->setClassName('');
        $form->add(
            'model',
            EntityType::class,
            [
                'data' => $cloneEntityModel,
            ]
        );

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                //persist data inside the form
                $request->getSession()->getFlashBag()->add(
                    'success',
                    $this->getTranslator()->trans('oro.entity_extend.controller.config_entity.message.saved')
                );

                return $this->getRouter()->redirect($entityModel);
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route(
     *      "/remove/{id}",
     *      name="oro_entityextend_entity_remove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0},
     *      methods={"DELETE"}
     * )
     * @CsrfProtection()
     *
     * @param EntityConfigModel $entity
     * @return JsonResponse|Response
     */
    public function removeAction(EntityConfigModel $entity): JsonResponse|Response
    {
        $configManager = $this->getConfigManager();
        $entityConfig = $configManager->getProvider('extend')->getConfig($entity->getClassName());

        if ($entityConfig->get('owner') == ExtendScope::OWNER_SYSTEM) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        if ($entityConfig->get('state', ExtendScope::STATE_NEW)) {
            $configEntityManager = $configManager->getEntityManager();
            $configEntityManager->remove($entity);
            $configEntityManager->flush();
        } else {
            $entityConfig->set('state', ExtendScope::STATE_DELETE);
            $configManager->persist($entityConfig);
            $configManager->flush();
        }

        return new JsonResponse(['message' => 'Item deleted', 'successful' => true], Response::HTTP_OK);
    }

    /**
     * @Route(
     *      "/unremove/{id}",
     *      name="oro_entityextend_entity_unremove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0},
     *      methods={"POST"}
     * )
     * @CsrfProtection()
     *
     * @param EntityConfigModel $entity
     * @return JsonResponse|Response
     */
    public function unremoveAction(EntityConfigModel $entity): JsonResponse|Response
    {
        $configManager = $this->getConfigManager();
        $entityConfig = $configManager->getProvider('extend')->getConfig($entity->getClassName());

        if ($entityConfig->get('owner') == ExtendScope::OWNER_SYSTEM) {
            return new Response('', Response::HTTP_FORBIDDEN);
        }

        $entityConfig->set(
            'state',
            class_exists($entity->getClassName()) ? ExtendScope::STATE_UPDATE : ExtendScope::STATE_NEW
        );

        $configManager->persist($entityConfig);
        $configManager->flush();

        return new JsonResponse(['message' => 'Item was restored', 'successful' => true], Response::HTTP_OK);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices()
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                Router::class,
                ConfigManager::class,
                TranslatorInterface::class
            ]
        );
    }

    private function getRouter(): Router
    {
        return $this->get(Router::class);
    }

    private function getConfigManager(): ConfigManager
    {
        return $this->get(ConfigManager::class);
    }

    private function getTranslator(): TranslatorInterface
    {
        return $this->get(TranslatorInterface::class);
    }
}
