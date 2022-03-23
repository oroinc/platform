<?php

namespace Oro\Bundle\EmailBundle\Tests\Functional\Stub;

use Oro\Bundle\EmailBundle\Entity\Provider\PublicEmailOwnerProvider;

/**
 * The decorator for PublicEmailOwnerProvider that allows to substitute
 * the list of public email address owners in functional tests.
 */
class PublicEmailOwnerProviderStub extends PublicEmailOwnerProvider
{
    private PublicEmailOwnerProvider $publicEmailOwnerProvider;

    private $stubPublicOwners = [];

    public function __construct(PublicEmailOwnerProvider $publicEmailOwnerProvider)
    {
        $this->publicEmailOwnerProvider = $publicEmailOwnerProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function isPublicEmailOwner(string $ownerClass): bool
    {
        foreach ($this->stubPublicOwners as [$class, $isPublic]) {
            if (is_a($ownerClass, $class, true)) {
                return $isPublic;
            }
        }

        return $this->publicEmailOwnerProvider->isPublicEmailOwner($ownerClass);
    }

    public function addPublicEmailOwner(string $ownerClass): void
    {
        $this->stubPublicOwners[] = [$ownerClass, true];
    }

    public function removePublicEmailOwner(string $ownerClass): void
    {
        $this->stubPublicOwners[] = [$ownerClass, false];
    }

    public function resetEmailOwners(): void
    {
        $this->stubPublicOwners = [];
    }
}
