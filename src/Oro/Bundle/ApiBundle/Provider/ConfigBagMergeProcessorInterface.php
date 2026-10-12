<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * Allows to modify API configuration loaded from a child config bag
 * before it is merged into a combined configuration.
 */
interface ConfigBagMergeProcessorInterface
{
    public function process(
        array $config,
        string $sourceRequestTypeExpression,
        string $resultRequestTypeExpression
    ): array;
}
