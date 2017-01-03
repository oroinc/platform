<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Handler;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\Session;

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

    /**
     * @param ConfigManager $configManager
     * @param FieldNameValidationHelper $validationHelper
     * @param ConfigHelper $configHelper
     * @param Session $session
     */
    public function __construct(
        ConfigManager $configManager,
        FieldNameValidationHelper $validationHelper,
        ConfigHelper $configHelper,
        Session $session
    ) {
        $this->configManager = $configManager;
        $this->validationHelper = $validationHelper;
        $this->configHelper = $configHelper;
        $this->session = $session;
    }

    /**
     * @param FieldConfigModel $field
     * @param string $successMessage
     * @return JsonResponse
     */
    public function handleRemove(FieldConfigModel $field, $successMessage)
    {
        $fields = $this->configHelper->filterEntityConfigByField(
            $field,
            'extend',
            function (ConfigInterface $config) {
                return in_array(
                    $config->get('state'),
                    [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE]
                );
            }
        );

        $entityConfig = $this->configHelper->getEntityConfigByField($field, 'extend');
        if (!count($fields)) {
            $entityConfig->set('upgradeable', false);
        } else {
            $entityConfig->set('upgradeable', true);
        }

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
            return new JsonResponse(
                [
                    'message'    => $errorMessage,
                    'successful' => false
                ],
                JsonResponse::HTTP_OK
            );
        }

        // TODO: property_exists works only for regular fields, not for relations and option sets. Need better approach
        $isFieldExist = class_exists($field->getEntity()->getClassName())
            && property_exists(
                $field->getEntity()->getClassName(),
                $field->getFieldName()
            );

        $fieldConfig = $this->configHelper->getFieldConfig($field, 'extend');
        $fieldConfig->set(
            'state',
            $isFieldExist ? ExtendScope::STATE_RESTORE : ExtendScope::STATE_NEW
        );

        $entityConfig = $this->configHelper->getEntityConfigByField($field, 'extend');
        $entityConfig->set('upgradeable', true);

        $this->configManager->persist($fieldConfig);
        $this->configManager->persist($entityConfig);
        $this->configManager->flush();

        return new JsonResponse(['message' => $successMessage, 'successful' => true], JsonResponse::HTTP_OK);
    }
}
