<?php

namespace Oro\Bundle\EntityExtendBundle\Validator\Constraints\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Validator\Constraints\Provider\FieldConfigConstraintsProviderInterface;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\Decimal;

/**
 * Provides constraints for field with type "decimal"
 */
class DecimalFieldConfigConstraintsProvider implements FieldConfigConstraintsProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function create(ConfigInterface $config): array
    {
        $constraints = [];
        if ($config->has('precision') && $config->has('scale')) {
            $constraints[] = new Decimal([
                'precision' => $config->get('precision'),
                'scale'     => $config->get('scale')
            ]);
        }

        return $constraints;
    }
}
