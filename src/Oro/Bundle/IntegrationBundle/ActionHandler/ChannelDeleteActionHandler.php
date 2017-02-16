<?php

namespace Oro\Bundle\IntegrationBundle\Action;

use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Psr\Log\LoggerAwareTrait;

class ChannelDeleteActionHandler implements ChannelActionHandlerInterface
{
    use LoggerAwareTrait;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Channel $channel
     *
     * @return bool
     */
    public function handleAction(Channel $channel)
    {
        try {
            $this->entityManager->remove($channel);
            $this->entityManager->flush();

            return true;
        } catch (DBALException $e) {
            if ($this->logger) {
                $this->logger->error($e->getMessage(), $e->getTrace());
            }

            return false;
        }
    }
}
