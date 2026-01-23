<?php

declare(strict_types=1);

namespace Oro\Bundle\SyncBundle\WebsocketServerState;

use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Manages the state of the Websocket server by storing and retrieving the last updated timestamp
 * for a given state ID in the database.
 */
class WebsocketServerSharedStateManager implements WebsocketServerStateManagerInterface
{
    public function __construct(private readonly ManagerRegistry $doctrine)
    {
    }

    #[\Override]
    public function updateState(string $stateId): \DateTimeInterface
    {
        $stateDate = new \DateTime('now', new \DateTimeZone('UTC'));
        $this->doctrine->getConnection()
            ->executeStatement(
                'INSERT INTO oro_sync_websocket_server_state (id, updated_at) VALUES (:id, :date)
                 ON CONFLICT (id) DO UPDATE SET updated_at = EXCLUDED.updated_at',
                [
                    'id' => $stateId,
                    'date' => $stateDate,
                ],
                [
                    'id' => Types::STRING,
                    'date' => Types::DATETIME_MUTABLE,
                ]
            );

        return $stateDate;
    }

    #[\Override]
    public function getState(string $stateId): ?\DateTimeInterface
    {
        $result = $this->doctrine->getConnection()->createQueryBuilder()
            ->from('oro_sync_websocket_server_state')
            ->select('updated_at')
            ->where('id = :id')
            ->setParameter('id', $stateId, Types::STRING)
            ->executeQuery()
            ->fetchOne() ?: null;

        if ($result !== null) {
            $result = new \DateTime($result, new \DateTimeZone('UTC'));
        }

        return $result;
    }
}
