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
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value instanceof FieldConfigModel) {
            $className    = $value->getEntity()->getClassName();
            $newFieldName = $this->removeClassifySymbols($value->getFieldName());

            $configs = $this->configProvider->getConfigs($className, true);
            foreach ($configs as $config) {
                /** @var FieldConfigId $configId */
                $configId  = $config->getId();
                $fieldName = $configId->getFieldName();
                if ($newFieldName === $this->removeClassifySymbols($fieldName)) {
                    /** @var ExecutionContextInterface $context */
                    $context = $this->context;
                    $context->buildViolation($constraint->message)
                            ->atPath($constraint->path)
                            ->addViolation();

                    return;
                }
            }
        }
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
