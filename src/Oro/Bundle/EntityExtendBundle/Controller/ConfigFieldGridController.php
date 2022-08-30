<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Handler\CreateUpdateConfigFieldHandler;
use Oro\Bundle\EntityConfigBundle\Form\Handler\RemoveRestoreConfigFieldHandler;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SecurityBundle\Annotation\CsrfProtection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The controller for FieldConfigModel entity.
 *
 * @package Oro\Bundle\EntityExtendBundle\Controller
 * @Route("/entity/extend/field")
 * @AclAncestor("oro_entityconfig_manage")
 */
class ConfigFieldGridController extends AbstractController
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
        if (!$this->getExtendConfigProvider()->getConfig($entityConfigModel->getClassName())->is('is_extend')) {
            $request->getSession()->getFlashBag()->add(
                'error',
                $entityConfigModel->getClassName() . 'isn\'t extend'
            );

            return $this->redirect(
                $this->generateUrl('oro_entityconfig_fields', ['id' => $entityConfigModel->getId()])
            );
        }

        $fieldConfigModel = new FieldConfigModel();
        $fieldConfigModel->setEntity($entityConfigModel);

        $formAction = $this->generateUrl('oro_entityextend_field_create', ['id' => $entityConfigModel->getId()]);

        return $this->getCreateUpdateConfigFieldHandler()->handleCreate($request, $fieldConfigModel, $formAction);
    }

    /**
     * @Route("/update/{id}", name="oro_entityextend_field_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * @Template("@OroEntityConfig/Config/fieldUpdate.html.twig")
     * @param Request $request
     * @param EntityConfigModel $entity
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function updateAction(Request $request, EntityConfigModel $entity)
    {
        $redirectUrl = $this->generateUrl('oro_entityextend_field_create', ['id' => $entity->getId()]);
        $successMessage = $this->getTranslator()->trans('oro.entity_extend.controller.config_field.message.saved');
        $formAction = $this->generateUrl('oro_entityextend_field_update', ['id' => $entity->getId()]);

        return $this->getCreateUpdateConfigFieldHandler()
            ->handleFieldSave($request, $entity, $redirectUrl, $formAction, $successMessage);
    }

    /**
     * @throws AccessDeniedException
     */
    private function ensureFieldConfigModelIsCustom(FieldConfigModel $fieldConfigModel)
    {
        $fieldConfig = $this->getExtendConfigProvider()->getConfig(
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
     *      defaults={"id"=0},
     *      methods={"DELETE"}
     * )
     * @CsrfProtection()
     * @param FieldConfigModel $field
     * @return Response
     */
    public function removeAction(FieldConfigModel $field)
    {
        $this->ensureFieldConfigModelIsCustom($field);

        return $this->getRemoveRestoreConfigFieldHandler()->handleRemove(
            $field,
            $this->getTranslator()->trans('oro.entity_extend.controller.config_field.message.deleted')
        );
    }

    /**
     * @Route(
     *      "/unremove/{id}",
     *      name="oro_entityextend_field_unremove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0},
     *      methods={"POST"}
     * )
     * @CsrfProtection()
     * @param FieldConfigModel $field
     * @return Response
     */
    public function unremoveAction(FieldConfigModel $field)
    {
        $this->ensureFieldConfigModelIsCustom($field);

        return $this->getRemoveRestoreConfigFieldHandler()->handleRestore(
            $field,
            $this->getTranslator()->trans('oro.entity_extend.controller.config_field.message.cannot_be_restored'),
            $this->getTranslator()->trans('oro.entity_extend.controller.config_field.message.restored')
        );
    }

    private function getExtendConfigProvider(): ConfigProvider
    {
        return $this->get(ConfigManager::class)->getProvider('extend');
    }

    protected function getTranslator(): TranslatorInterface
    {
        return $this->get(TranslatorInterface::class);
    }

    protected function getCreateUpdateConfigFieldHandler(): CreateUpdateConfigFieldHandler
    {
        return $this->get(CreateUpdateConfigFieldHandler::class);
    }

    protected function getRemoveRestoreConfigFieldHandler(): RemoveRestoreConfigFieldHandler
    {
        return $this->get(RemoveRestoreConfigFieldHandler::class);
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedServices(): array
    {
        return array_merge(
            parent::getSubscribedServices(),
            [
                TranslatorInterface::class,
                RemoveRestoreConfigFieldHandler::class,
                CreateUpdateConfigFieldHandler::class,
                ConfigManager::class,
            ]
        );
    }
}
