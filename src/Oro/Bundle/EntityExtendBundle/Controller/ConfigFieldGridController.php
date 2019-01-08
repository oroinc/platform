<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class ConfigGridController
 *
 * @package Oro\Bundle\EntityExtendBundle\Controller
 * @Route("/entity/extend/field")
 * @AclAncestor("oro_entityconfig_manage")
 */
class ConfigFieldGridController extends Controller
{
    /**
     * @Route("/create/{id}", name="oro_entityextend_field_create", requirements={"id"="\d+"}, defaults={"id"=0})
     *
     * @Template
     * @param Request $request
     * @param EntityConfigModel $entityConfigModel
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function createAction(Request $request, EntityConfigModel $entityConfigModel)
    {
        /** @var ConfigProvider $entityConfigProvider */
        $extendConfigProvider = $this->get('oro_entity_config.provider.extend');

        if (!$extendConfigProvider->getConfig($entityConfigModel->getClassName())->is('is_extend')) {
            $this->get('session')->getFlashBag()->add('error', $entityConfigModel->getClassName() . 'isn\'t extend');

            return $this->redirect(
                $this->generateUrl('oro_entityconfig_fields', ['id' => $entityConfigModel->getId()])
            );
        }

        $fieldConfigModel = new FieldConfigModel();
        $fieldConfigModel->setEntity($entityConfigModel);

        $formAction = $this->generateUrl('oro_entityextend_field_create', ['id' => $entityConfigModel->getId()]);

        return $this
            ->get('oro_entity_config.form.handler.create_update_config_field_handler')
            ->handleCreate($request, $fieldConfigModel, $formAction);
    }

    /**
     * @Route("/update/{id}", name="oro_entityextend_field_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template("OroEntityConfigBundle:Config:fieldUpdate.html.twig")
     * @param Request $request
     * @param EntityConfigModel $entity
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function updateAction(Request $request, EntityConfigModel $entity)
    {
        $redirectUrl = $this->generateUrl('oro_entityextend_field_create', ['id' => $entity->getId()]);
        $successMessage = $this->get('translator')->trans('oro.entity_extend.controller.config_field.message.saved');
        $formAction = $this->generateUrl('oro_entityextend_field_update', ['id' => $entity->getId()]);

        return $this->get('oro_entity_config.form.handler.create_update_config_field_handler')
            ->handleFieldSave($request, $entity, $redirectUrl, $formAction, $successMessage);
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @throws AccessDeniedException
     */
    private function ensureFieldConfigModelIsCustom(FieldConfigModel $fieldConfigModel)
    {
        /** @var ConfigProvider $extendConfigProvider */
        $extendConfigProvider = $this->get('oro_entity_config.provider.extend');
        $fieldConfig = $extendConfigProvider->getConfig(
            $fieldConfigModel->getEntity()->getClassName(),
            $fieldConfigModel->getFieldName()
        );

        if (!$fieldConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
            throw new AccessDeniedException();
        }
    }

    /**
     * @Route(
     *      "/remove/{id}",
     *      name="oro_entityextend_field_remove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0}
     * )
     * @param FieldConfigModel $field
     * @return Response
     */
    public function removeAction(FieldConfigModel $field)
    {
        $this->ensureFieldConfigModelIsCustom($field);
        $successMessage = $this->get('translator')->trans('oro.entity_extend.controller.config_field.message.deleted');

        $response = $this
            ->get('oro_entity_config.form.handler.remove_restore_field_handler')
            ->handleRemove($field, $successMessage);

        return $response;
    }

    /**
     * @Route(
     *      "/unremove/{id}",
     *      name="oro_entityextend_field_unremove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0}
     * )
     * @param FieldConfigModel $field
     * @return Response
     */
    public function unremoveAction(FieldConfigModel $field)
    {
        $this->ensureFieldConfigModelIsCustom($field);

        return $this->get('oro_entity_config.form.handler.remove_restore_field_handler')->handleRestore(
            $field,
            $this->get('translator')->trans('oro.entity_extend.controller.config_field.message.cannot_be_restored'),
            $this->get('translator')->trans('oro.entity_extend.controller.config_field.message.restored')
        );
    }
}
