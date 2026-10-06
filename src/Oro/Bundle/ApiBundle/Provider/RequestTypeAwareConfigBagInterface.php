<?php

declare(strict_types=1);

namespace Oro\Bundle\ApiBundle\Provider;

/**
 * Represents an API configuration bag associated with a request type expression.
 */
interface RequestTypeAwareConfigBagInterface
{
    public function getRequestTypeExpression(): string;
}
