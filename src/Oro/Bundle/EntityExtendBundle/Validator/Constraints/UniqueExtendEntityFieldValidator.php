<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Validates field name for uniqueness. When generating setter and getter methods, characters `_` and `-` are removed
 * and as result e.g for names `id` and `i_d` methods names are identical.
 */
class UniqueExtendEntityFieldValidator extends ConstraintValidator
{
    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof FieldConfigModel) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel supported only, %s given',
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        $newFieldName = strtolower(Inflector::classify(($value->getFieldName())));

        // Need hardcoded check for `id` field.
        if ($newFieldName === 'id') {
            $this->addViolation($constraint);

            return;
        }

        $className = $value->getEntity()->getClassName();
        $configs   = $this->configProvider->getConfigs($className, true);
        foreach ($configs as $config) {
            /** @var FieldConfigId $configId */
            $configId  = $config->getId();
            $isDeleted = $config->is('is_deleted');
            $fieldName = $configId->getFieldName();
            // For deleted field we do not generate setter/getter methods.
            if ($isDeleted) {
                if (strtolower($value->getFieldName()) === strtolower($fieldName)) {
                    $this->addViolation($constraint);

                    return;
                }
                continue;
            }
            if ($newFieldName === strtolower(Inflector::classify($fieldName))) {
                $this->addViolation($constraint);

                return;
            }
        }
    }

    /**
     * @param Constraint $constraint
     */
    protected function addViolation(Constraint $constraint)
    {
        /** @var ExecutionContextInterface $context */
        $context = $this->context;
        $context->buildViolation($constraint->message)
            ->atPath($constraint->path)
            ->addViolation();
    }
}
