<?php

namespace Oro\Bundle\EntityMergeBundle\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Symfony\Component\Security\Core\Util\ClassUtils;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxEntitiesValidator extends ConstraintValidator
{
    /**
     * @var ConfigProvider
     */
    protected $mergeProvider;

    /**
     * @param ConfigProvider $mergeProvider
     */
    public function __construct(ConfigProvider $mergeProvider)
    {
        $this->mergeProvider = $mergeProvider;
    }

    /**
     * {inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        $targetEntity = $value->getMasterEntity();
        $className = ClassUtils::getRealClass($targetEntity);
        $config    = $this->mergeProvider->getConfig($className);

        if ($config && $config->has('merge_max_entities')) {
            $maxEntities = $config->get('merge_max_entities');

            if (sizeof($value->getEntities()) > $maxEntities) {
                $this->context->addViolation($constraint->message, []);
            }
        }
    }
}
