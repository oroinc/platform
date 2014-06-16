<?php

namespace Oro\Bundle\EmbeddedFormBundle\Manager;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\IntegrationBundle\Manager\DeleteProviderInterface;

class DeleteProvider implements DeleteProviderInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    public function supports($type)
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteRelatedData(Channel $channel)
    {
        // delete embedded forms
        $this->em->createQuery(
            'DELETE FROM OroEmbeddedFormBundle:EmbeddedForm e WHERE e.channel = ' . $channel->getId()
        )->execute();
    }
}
