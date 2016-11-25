<?php

namespace Oro\Bundle\EmailBundle\Model;

use Oro\Bundle\EmailBundle\Entity\EmailAddress;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Provider\EmailOwnersProvider;

class EmailActivityUpdates
{
    /** @var EmailOwnersProvider */
    protected $emailOwnersProvider;

    /** @var EmailAddress[] */
    protected $updatedEmailAddresses = [];

    /**
     * @param EmailOwnersProvider $emailOwnersProvider
     */
    public function __construct(EmailOwnersProvider $emailOwnersProvider)
    {
        $this->emailOwnersProvider = $emailOwnersProvider;
    }

    /**
     * @param EmailAddress[] $emailAddresses
     */
    public function processUpdatedEmailAddresses(array $emailAddresses)
    {
        $this->updatedEmailAddresses = array_merge(
            $this->updatedEmailAddresses,
            $emailAddresses
        );
    }

    /**
     * @return EmailOwnerInterface[]
     */
    public function getFilteredOwnerEntitiesToUpdate()
    {
        $owners = array_map(
            function (EmailAddress $emailAddress) {
                return $emailAddress->getOwner();
            },
            $this->updatedEmailAddresses
        );

        return array_filter(
            $owners,
            function (EmailOwnerInterface $owner = null) {
                return $owner && $this->emailOwnersProvider->hasEmailsByOwnerEntity($owner);
            }
        );
    }

    public function clearPendingEntities()
    {
        $this->updatedEmailAddresses = [];
    }
}
