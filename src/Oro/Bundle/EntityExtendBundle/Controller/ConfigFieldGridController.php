<?php

namespace Oro\Bundle\EntityExtendBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use FOS\RestBundle\Util\Codes;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

use Oro\Bundle\EntityExtendBundle\Form\Type\FieldType;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;

/**
 * Class ConfigGridController
 *
 * @package Oro\Bundle\EntityExtendBundle\Controller
 * @Route("/entity/extend/field")
 * TODO: Discuss ACL impl., currently acl is disabled
 * @AclAncestor("oro_entityconfig_manage")
 */
class ConfigFieldGridController extends Controller
{
    const SESSION_ID_FIELD_TYPE = '_extendbundle_create_entity_%s_field_type';
    const SESSION_ID_FIELD_NAME = '_extendbundle_create_entity_%s_field_name';

    /**
     * @Route("/create/{id}", name="oro_entityextend_field_create", requirements={"id"="\d+"}, defaults={"id"=0})
     * Acl(
     *      id="oro_entityextend_field_create",
     *      label="oro.entity_extend.action.config_field_grid.create",
     *      type="action",
     *      group_name=""
     * )
     *
     * @Template
     */
    public function createAction(EntityConfigModel $entity)
    {
        /** @var ConfigProvider $entityConfigProvider */
        $extendConfigProvider = $this->get('oro_entity_config.provider.extend');

        if (!$extendConfigProvider->getConfig($entity->getClassName())->is('is_extend')) {
            $this->get('session')->getFlashBag()->add('error', $entity->getClassName() . 'isn\'t extend');
            return $this->redirect(
                $this->generateUrl('oro_entityconfig_fields', ['id' => $entity->getId()])
            );
        }

        $newFieldModel = new FieldConfigModel();
        $newFieldModel->setEntity($entity);

        $form = $this->createForm(
            'oro_entity_extend_field_type',
            $newFieldModel,
            ['class_name' => $entity->getClassName()]
        );
        $request = $this->getRequest();

        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            if ($form->isValid()) {
                $fieldName          = $newFieldModel->getFieldName();
                $originalFieldNames = $form->getConfig()->getAttribute(FieldType::ORIGINAL_FIELD_NAMES_ATTRIBUTE);
                if (isset($originalFieldNames[$fieldName])) {
                    $fieldName = $originalFieldNames[$fieldName];
                }

                $request->getSession()->set(
                    sprintf(self::SESSION_ID_FIELD_NAME, $entity->getId()),
                    $fieldName
                );
                $request->getSession()->set(
                    sprintf(self::SESSION_ID_FIELD_TYPE, $entity->getId()),
                    $newFieldModel->getType()
                );

                return $this->get('oro_ui.router')->redirect($entity);
            }
        }

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

        return [
            'form'          => $form->createView(),
            'entity_id'     => $entity->getId(),
            'entity_config' => $entityConfigProvider->getConfig($entity->getClassName()),
        ];
    }

    /**
     * @Route("/update/{id}", name="oro_entityextend_field_update", requirements={"id"="\d+"}, defaults={"id"=0})
     * Acl(
     *      id="oro_entityextend_field_update",
     *      label="oro.entity_extend.action.config_field_grid.update",
     *      type="action",
     *      group_name=""
     * )
     */
    public function updateAction(EntityConfigModel $entity)
    {
        $request   = $this->getRequest();
        $fieldName = $request->getSession()->get(sprintf(self::SESSION_ID_FIELD_NAME, $entity->getId()));
        $fieldType = $request->getSession()->get(sprintf(self::SESSION_ID_FIELD_TYPE, $entity->getId()));
        if (!$fieldName || !$fieldType) {
            return $this->redirect($this->generateUrl('oro_entityextend_field_create', ['id' => $entity->getId()]));
        }

        /** @var ConfigManager $configManager */
        $configManager      = $this->get('oro_entity_config.config_manager');
        $extendProvider     = $configManager->getProvider('extend');
        $extendEntityConfig = $extendProvider->getConfig($entity->getClassName());

        $fieldOptions = [
            'extend' => [
                'is_extend' => true,
                'origin'    => ExtendScope::ORIGIN_CUSTOM,
                'owner'     => ExtendScope::OWNER_CUSTOM,
                'state'     => ExtendScope::STATE_NEW
            ]
        ];

        // check if a field type is complex, for example reverse relation or public enum
        $fieldTypeParts = explode('||', $fieldType);
        if (count($fieldTypeParts) > 1) {
            if (in_array($fieldTypeParts[0], ['enum', 'multiEnum'])) {
            // enum
                $fieldType = $fieldTypeParts[0];
                $fieldOptions['enum']['enum_code'] = $fieldTypeParts[1];
            } else {
                $firstPartItems = explode('|', $fieldTypeParts[0]);
                if (count($firstPartItems) === 4) {
                // reverse relation
                    $fieldType = ExtendHelper::getReverseRelationType($firstPartItems[0]);
                    $relationKey = $fieldTypeParts[0];
                    $fieldOptions['extend']['relation_key'] = $relationKey;
                    $relations = $extendEntityConfig->get('relation');
                    $fieldOptions['extend']['target_entity'] = $relations[$relationKey]['target_entity'];
                }
            }
        }

        $newFieldModel = $configManager->createConfigFieldModel($entity->getClassName(), $fieldName, $fieldType);
        $this->updateFieldConfigs($configManager, $newFieldModel, $fieldOptions);

        $form = $this->createForm('oro_entity_config_type', null, ['config_model' => $newFieldModel]);

        if ($request->getMethod() == 'POST') {
            $form->submit($request);
            if ($form->isValid()) {
                //persist data inside the form
                $this->get('session')->getFlashBag()->add(
                    'success',
                    $this->get('translator')->trans('oro.entity_extend.controller.config_field.message.saved')
                );

                $extendEntityConfig->set('upgradeable', true);

                $configManager->persist($extendEntityConfig);
                $configManager->flush();

                return $this->get('oro_ui.router')->redirect($newFieldModel);
            }
        }

        /** @var ConfigProvider $entityConfigProvider */
        $entityConfigProvider = $this->get('oro_entity_config.provider.entity');

        $entityConfig = $entityConfigProvider->getConfig($entity->getClassName());
        $fieldConfig  = $entityConfigProvider->getConfig($entity->getClassName(), $newFieldModel->getFieldName());

        return $this->render(
            'OroEntityConfigBundle:Config:fieldUpdate.html.twig',
            [
                'entity_config' => $entityConfig,
                'field_config'  => $fieldConfig,
                'field'         => $newFieldModel,
                'form'          => $form->createView(),
                'formAction'    => $this->generateUrl('oro_entityextend_field_update', ['id' => $entity->getId()]),
                'require_js'    => $configManager->getProvider('extend')->getPropertyConfig()->getRequireJsModules()
            ]
        );
    }

    /**
     * @Route(
     *      "/remove/{id}",
     *      name="oro_entityextend_field_remove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0}
     * )
     * Acl(
     *      id="oro_entityextend_field_remove",
     *      label="oro.entity_extend.action.config_field_grid.remove",
     *      type="action",
     *      group_name=""
     * )
     */
    public function removeAction(FieldConfigModel $field)
    {
        if (!$field) {
            throw $this->createNotFoundException('Unable to find FieldConfigModel entity.');
        }

        $className = $field->getEntity()->getClassName();

        /** @var ConfigManager $configManager */
        $configManager        = $this->get('oro_entity_config.config_manager');
        $extendConfigProvider = $configManager->getProvider('extend');

        $fieldConfig = $extendConfigProvider->getConfig($className, $field->getFieldName());
        if (!$fieldConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
            return new Response('', Codes::HTTP_FORBIDDEN);
        }

        $fieldConfig->set('state', ExtendScope::STATE_DELETE);
        $configManager->persist($fieldConfig);

        $fields = $extendConfigProvider->filter(
            function (ConfigInterface $config) {
                return in_array(
                    $config->get('state'),
                    [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]
                );
            },
            $className
        );

        $entityConfig = $extendConfigProvider->getConfig($className);
        if (!count($fields)) {
            $entityConfig->set('upgradeable', false);
        } else {
            $entityConfig->set('upgradeable', true);
        }
        $configManager->persist($entityConfig);

        $configManager->flush();

        $this->get('session')->getFlashBag()->add(
            'success',
            $this->get('translator')->trans('Field successfully deleted')
        );

        return new JsonResponse(['message' => 'Field successfully deleted', 'successful' => true], Codes::HTTP_OK);
    }

    /**
     * @Route(
     *      "/unremove/{id}",
     *      name="oro_entityextend_field_unremove",
     *      requirements={"id"="\d+"},
     *      defaults={"id"=0}
     * )
     * Acl(
     *      id="oro_entityextend_field_unremove",
     *      label="oro.entity_extend.action.config_field_grid.unremove",
     *      type="action",
     *      group_name=""
     * )
     */
    public function unremoveAction(FieldConfigModel $field)
    {
        if (!$field) {
            throw $this->createNotFoundException('Unable to find FieldConfigModel entity.');
        }

        $className = $field->getEntity()->getClassName();

        /** @var ConfigManager $configManager */
        $configManager = $this->get('oro_entity_config.config_manager');
        $extendConfigProvider = $configManager->getProvider('extend');

        $fieldConfig = $extendConfigProvider->getConfig($className, $field->getFieldName());

        if (!$fieldConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
            return new Response('', Codes::HTTP_FORBIDDEN);
        }

        $fieldNameValidationHelper = $this->get('oro_entity_extend.extend.field_name_validation_helper');
        if (!$fieldNameValidationHelper->canFieldBeRestored($field)) {
            return new JsonResponse(
                [
                    'message'    => 'This field cannot be restored because a field with similar name exists.',
                    'successful' => false
                ],
                Codes::HTTP_OK
            );
        }

        // TODO: property_exists works only for regular fields, not for relations and option sets. Need better approach
        $isFieldExist = class_exists($field->getEntity()->getClassName())
            && property_exists(
                $field->getEntity()->getClassName(),
                $field->getFieldName()
            );
        $fieldConfig->set(
            'state',
            $isFieldExist ? ExtendScope::STATE_RESTORE : ExtendScope::STATE_NEW
        );

        $configManager->persist($fieldConfig);

        $entityConfig = $extendConfigProvider->getConfig($className);
        $entityConfig->set('upgradeable', true);

        $configManager->persist($entityConfig);

        $configManager->flush();

        return new JsonResponse(['message' => 'Field was restored', 'successful' => true], Codes::HTTP_OK);
    }

    /**
     * @param ConfigManager    $configManager
     * @param FieldConfigModel $fieldModel
     * @param array            $options
     */
    protected function updateFieldConfigs(ConfigManager $configManager, FieldConfigModel $fieldModel, $options)
    {
        $className = $fieldModel->getEntity()->getClassName();
        $fieldName = $fieldModel->getFieldName();
        foreach ($options as $scope => $scopeValues) {
            $configProvider = $configManager->getProvider($scope);
            $config         = $configProvider->getConfig($className, $fieldName);
            $hasChanges     = false;
            foreach ($scopeValues as $code => $val) {
                if (!$config->is($code, $val)) {
                    $config->set($code, $val);
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                $configManager->persist($config);
                $indexedValues = $configProvider->getPropertyConfig()->getIndexedValues($config->getId());
                $fieldModel->fromArray($config->getId()->getScope(), $config->all(), $indexedValues);
            }
        }
    }
}
