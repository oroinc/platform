<?php

namespace Oro\Bundle\IntegrationBundle\Provider;

/**
 * Defines the contract for integration types that support configurable default ownership.
 *
 * Implementations of this interface can specify whether entities created by the integration
 * should be owned by a user or a business unit. This allows different integration types to
 * have different ownership models based on their requirements.
 */
interface DefaultOwnerTypeAwareInterface
{
    public const USER          = 'user';
    public const BUSINESS_UNIT = 'business_unit';

    /**
     * Returns default owner type for entities created by this integration.
     *
     * @return string
     */
    public function getDefaultOwnerType();
}
