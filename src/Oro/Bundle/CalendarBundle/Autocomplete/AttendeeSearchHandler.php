<?php

namespace Oro\Bundle\CalendarBundle\Autocomplete;

use Oro\Bundle\ActivityBundle\Autocomplete\ContextSearchHandler;
use Oro\Bundle\SearchBundle\Query\Result\Item;

class AttendeeSearchHandler extends ContextSearchHandler
{
    /**
     * {@inheritdoc}
     */
    protected function convertItems(array $items)
    {
        $recordIds = array_map(
            function (Item $item) {
                return $item->getRecordId();
            },
            $items
        );

        /** @var User[] $users */
        $users = !$recordIds ? [] : $this->objectManager
            ->getRepository('Oro\Bundle\UserBundle\Entity\User')
            ->findById($recordIds);

        $result = [];
        foreach ($users as $user) {
            $result[] = [
                'id'   => json_encode(
                    [
                        'entityClass' => 'Oro\Bundle\UserBundle\Entity\User',
                        'entityId'    => $user->getId(),
                    ]
                ),
                'text' => $user->getFullName(),
                'displayName' => $user->getFullName(),
                'email' => $user->getEmail(),
            ];
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchAliases()
    {
        return ['oro_user'];
    }
}
