<?php

declare(strict_types=1);

namespace Oro\Bundle\ConfigBundle\ORM\Hydration;

use Doctrine\DBAL\Types\ConversionException;
use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Oro\Bundle\ConfigBundle\Entity\ConfigValue;

/**
 * Suppresses deserialization errors to ensure that {@see ConfigObject} is hydrated even if there are {@see ConfigValue}
 * elements contains invalid data that cannot be deserialized.
 */
class ConfigObjectHydrator extends ObjectHydrator
{
    #[\Override]
    protected function hydrateRowData(array $row, array &$result)
    {
        try {
            parent::hydrateRowData($row, $result);
        } catch (ConversionException $exception) {
            // Suppresses {@see ConversionException} coming from {@see ArrayType}.
            trigger_error(
                'Recoverable error occurred during loading system configuration. '
                . $exception->getMessage(),
                E_USER_DEPRECATED
            );
        } catch (\TypeError $error) {
            // Suppresses errors coming from unserialize().
            foreach ($error->getTrace() as $frame) {
                if ('unserialize' === ($frame['function'] ?? null)) {
                    trigger_error(
                        'Recoverable error occurred during loading system configuration. ' . $error->getMessage(),
                        E_USER_DEPRECATED
                    );
                    return;
                }
            }

            throw $error;
        }
    }
}
