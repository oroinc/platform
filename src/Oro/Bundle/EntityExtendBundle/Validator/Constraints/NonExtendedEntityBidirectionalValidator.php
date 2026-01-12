<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Symfony\Component\Form\Form;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validator for the {@see NonExtendedEntityBidirectional} constraint.
 *
 * This validator checks that bidirectional relations are only created with extended entities
 * and that one-to-many relations are always bidirectional. It examines the target entity's
 * extend configuration and the relation type to determine if the configuration is valid.
 */
class NonExtendedEntityBidirectionalValidator extends ConstraintValidator
{
    /**
     * ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    #[\Override]
    public function validate($value, Constraint $constraint)
    {
        $config = $this->configManager->getEntityConfig('extend', $value['target_entity']);
        $isBidirectional = isset($value['bidirectional']) ? (bool)$value['bidirectional'] : false;

        if (!$config->is('is_extend') && $isBidirectional) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }

        /** @var Form $parentForm */
        $parentForm = $this->context->getRoot();

        /** @var FieldConfigModel $fieldConfig */
        $fieldConfig = $parentForm->getConfig()->getOption('config_model');

        if ($fieldConfig->getType() == RelationType::ONE_TO_MANY && !$isBidirectional) {
            $this->context->buildViolation($constraint->unidirectionalNotAllowedMessage)
                ->addViolation();
        }
    }
}
