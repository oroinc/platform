<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
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
    /** @var ConfigManager */
    private $configManager;

    /** @var FieldNameValidationHelper */
    private $validationHelper;

    /** @var ConfigHelper */
    private $configHelper;

    /** @var Session */
    private $session;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param ConfigManager $configManager
     * @param FieldNameValidationHelper $validationHelper
     * @param ConfigHelper $configHelper
     * @param Session $session
     * @param ManagerRegistry $registry
     */
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

    /**
     * @param FieldConfigModel $field
     * @param string $successMessage
     * @return JsonResponse
     */
    public function handleRemove(FieldConfigModel $field, $successMessage)
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
        $entityConfig->set('upgradeable', true);

        $fieldConfig = $this->configHelper->getFieldConfig($field, 'extend');
        $fieldConfig->set('state', ExtendScope::STATE_DELETE);

        $this->configManager->persist($fieldConfig);
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();

        $this->session->getFlashBag()->add('success', $successMessage);

        return new JsonResponse(['message' => $successMessage, 'successful' => true], JsonResponse::HTTP_OK);
    }

    /**
     * @param FieldConfigModel $field
     * @param string $errorMessage
     * @param string $successMessage
     * @return JsonResponse
     */
    public function handleRestore(FieldConfigModel $field, $errorMessage, $successMessage)
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

        return new JsonResponse(['message' => $successMessage, 'successful' => true], JsonResponse::HTTP_OK);
    }
}
