<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Handler;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Handle remove and unremove of extend fields
 */
class RemoveRestoreConfigFieldHandler
{
    private ConfigManager $configManager;
    private FieldNameValidationHelper $validationHelper;
    private ConfigHelper $configHelper;
    private Session $session;
    private ManagerRegistry $registry;

    public function __construct(
        ConfigManager $configManager,
        FieldNameValidationHelper $validationHelper,
        ConfigHelper $configHelper,
        Session $session,
        ManagerRegistry $registry
    ) {
        $this->configManager = $configManager;
        $this->validationHelper = $validationHelper;
        $this->configHelper = $configHelper;
        $this->session = $session;
        $this->registry = $registry;
    }

    public function handleRemove(FieldConfigModel $field, string $successMessage): JsonResponse
    {
        $validationMessages = $this->validationHelper->getRemoveFieldValidationErrors($field);
        if ($validationMessages) {
            foreach ($validationMessages as $message) {
                $this->session->getFlashBag()->add('error', $message);
            }

            return new JsonResponse(
                [
                    'message' => implode('. ', $validationMessages),
                    'successful' => false
                ],
                JsonResponse::HTTP_OK
            );
        }

        $entityConfig = $this->configHelper->getEntityConfigByField($field, 'extend');

        $fieldConfig = $this->configHelper->getFieldConfig($field, 'extend');
        if ($fieldConfig->is('state', ExtendScope::STATE_NEW)) {
            $configEntityManager = $this->configManager->getEntityManager();
            $configEntityManager->remove($field);
            $configEntityManager->flush();
        } else {
            $entityConfig->set('upgradeable', true);
            $fieldConfig->set('state', ExtendScope::STATE_DELETE);
            $this->configManager->persist($fieldConfig);
        }

        $otherFieldsRequireUpdate = $this->configHelper->filterEntityConfigByField(
            $field,
            'extend',
            function (ConfigInterface $field) use ($fieldConfig) {
                return
                    $field->in('state', [
                        ExtendScope::STATE_NEW,
                        ExtendScope::STATE_UPDATE,
                        ExtendScope::STATE_RESTORE
                    ])
                    && $field->getId()->getFieldName() !== $fieldConfig->getId()->getFieldName()
                ;
            },
        );

        if ($entityConfig->in('state', [ExtendScope::STATE_UPDATE, ExtendScope::STATE_NEW])
            && !$entityConfig->get('pending_changes')
            && !$otherFieldsRequireUpdate
        ) {
            $entityConfig->set('upgradeable', false);
            if ($entityConfig->is('state', ExtendScope::STATE_UPDATE)) {
                $entityConfig->set('state', ExtendScope::STATE_ACTIVE);
            }
        }

        $this->configManager->persist($entityConfig);
        $this->configManager->flush();

        $this->session->getFlashBag()->add('success', $successMessage);

        return new JsonResponse(['message' => $successMessage, 'successful' => true], JsonResponse::HTTP_OK);
    }

    public function handleRestore(FieldConfigModel $field, string $errorMessage, string $successMessage): JsonResponse
    {
        if (!$this->validationHelper->canFieldBeRestored($field)) {
            $this->session->getFlashBag()->add('error', $errorMessage);

            return new JsonResponse(
                [
                    'message'    => $errorMessage,
                    'successful' => false
                ],
                JsonResponse::HTTP_OK
            );
        }

        $entityClass = $field->getEntity()->getClassName();

        $isFieldExist = false;
        if (class_exists($entityClass) && ($em = $this->registry->getManagerForClass($entityClass))) {
            $metadata = $em->getClassMetadata($entityClass);

            $fieldName = $field->getFieldName();
            $isFieldExist = $metadata->hasField($fieldName) || $metadata->hasAssociation($fieldName);
        }

        $fieldConfig = $this->configHelper->getFieldConfig($field, 'extend');
        $fieldConfig->set(
            'state',
            $isFieldExist ? ExtendScope::STATE_RESTORE : ExtendScope::STATE_NEW
        );
        $fieldConfig->set('is_deleted', false);

        $entityConfig = $this->configHelper->getEntityConfigByField($field, 'extend');
        $entityConfig->set('upgradeable', true);

        $this->configManager->persist($fieldConfig);
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();

        $this->session->getFlashBag()->add('success', $successMessage);

        return new JsonResponse(['message' => $successMessage, 'successful' => true], JsonResponse::HTTP_OK);
    }
}
