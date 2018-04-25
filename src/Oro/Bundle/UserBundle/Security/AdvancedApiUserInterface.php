<?php

namespace Oro\Bundle\UserBundle\Security;

use Doctrine\Common\Collections\Collection;

/**
 * The interface that must be implemented by all classes represent users that can be authenticated via API key.
 */
interface AdvancedApiUserInterface
{
    /**
     * Gets a collection of API keys.
     *
     * @return UserApiKeyInterface[]|Collection
     */
    public function getApiKeys();
}
