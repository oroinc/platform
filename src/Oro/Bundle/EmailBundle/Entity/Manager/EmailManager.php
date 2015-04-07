<?php

namespace Oro\Bundle\EmailBundle\Entity\Manager;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\EmailBundle\Entity\Email;

class EmailManager
{
    /**
     * @var EmailThreadManager
     */
    protected $emailThreadManager;

    /** @var EntityManager */
    protected $em;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param EntityManager $em
     * @param EmailThreadManager $emailThreadManager
     * @param ContainerInterface $container
     */
    public function __construct(
        EntityManager $em,
        EmailThreadManager $emailThreadManager,
        ContainerInterface $container
    ) {
        $this->em = $em;
        $this->emailThreadManager = $emailThreadManager;
        $this->container = $container;
    }

    /**
     * Set email as seen
     *
     * @param Email $entity
     */
    public function setEmailSeen(Email $entity)
    {
        if (!$entity->isSeen()) {
            $entity->setSeen(true);
            $this->em->persist($entity);
            $this->em->flush();
        }
    }

    /**
     * @return array
     */
    public function getSupportedTargets()
    {
        $entities = $this->container->get('oro_entity.entity_provider')->getEntities();
        $entityTargets = [];

        $i=1;
        $email = new Email();
        foreach ($entities as $entity) {
            $className = $entity['name'];
            if (!empty($className) && $email->supportActivityTarget($className)) {
                $entityTargets[] = [
                    'label' => $entity['label'],
                    'id' => 'context-item-'.$i,
                    'first' => ($i == 1 ? true : false)
                ];

                $i++;
            }
        }

        return $entityTargets;
    }
}
