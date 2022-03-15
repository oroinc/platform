<?php

namespace Oro\Bundle\EmailBundle\Entity\Provider;

use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides a way to check whether an email address owner is configured as public.
 */
class PublicEmailOwnerProvider implements ResetInterface
{
    /** @var string[] */
    private array $publicOwners;
    private array $cache = [];

    public function __construct(array $publicOwners)
    {
        $this->publicOwners = $publicOwners;
    }

    /**
     * Checks whether the given email address owner is configured as public.
     */
    public function isPublicEmailOwner(string $ownerClass): bool
    {
        if (isset($this->cache[$ownerClass])) {
            return $this->cache[$ownerClass];
        }

        $result = false;
        foreach ($this->publicOwners as $publicOwnerClass) {
            if (is_a($ownerClass, $publicOwnerClass, true)) {
                $result = true;
                break;
            }
        }
        $this->cache[$ownerClass] = $result;

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function reset(): void
    {
        $this->cache = [];
    }
}
