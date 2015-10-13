<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints;

use Doctrine\Common\Inflector\Inflector;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class UniqueExtendEntityFieldValidator extends ConstraintValidator
{
    /** @var ConfigProvider  */
    protected $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param FieldConfigModel $value
     * @param Constraint       $constraint
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

        $newFieldName = $this->removeClassifySymbols($value->getFieldName());

        // Need hardcoded check for the `id`, `serialized_data` fields
        // cause we do not fetch this information from ConfigProvider.
        if (in_array($newFieldName, ['id', $this->removeClassifySymbols('serialized_data')], true)) {
            $this->addViolation($constraint);

            return;
        }

        $className = $value->getEntity()->getClassName();
        $configs   = $this->configProvider->getConfigs($className, true);
        foreach ($configs as $config) {
            /** @var FieldConfigId $configId */
            $configId  = $config->getId();
            $fieldName = $configId->getFieldName();
            if ($newFieldName === $this->removeClassifySymbols($fieldName)) {
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

    /**
     * @param string $text
     *
     * @return string
     */
    protected function removeClassifySymbols($text)
    {
        $text = Inflector::classify($text);
        $text = str_replace(' ', '', $text);

        return strtolower($text);
    }
}
