<?php

namespace Oro\Bundle\EntityConfigBundle\Validator\Constraints\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Symfony\Component\Validator\Constraints\Length;

/**
 * Provides constraints for field with type "string"
 */
class StringFieldConfigConstraintsProvider implements FieldConfigConstraintsProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ConfigInterface $config): array
    {
        $length = $config->get('length') ?: 255;
        $constraints[] = new Length(['max' => $length]);

        return $constraints;
    }
}
