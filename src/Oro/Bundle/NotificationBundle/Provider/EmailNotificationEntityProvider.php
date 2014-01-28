<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Symfony\Component\Translation\Translator;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EmailNotificationEntityProvider extends EntityProvider
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * Constructor
     *
     * @param ConfigProvider      $entityConfigProvider
     * @param EntityClassResolver $entityClassResolver
     * @param Translator          $translator
     * @param EntityManager       $em
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        EntityClassResolver $entityClassResolver,
        Translator $translator,
        EntityManager $em
    ) {
        parent::__construct($entityConfigProvider, $entityClassResolver, $translator);
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    protected function addEntities(array &$result, $translate)
    {
        $entities = $this->em->getRepository('OroEmailBundle:EmailTemplate')
            ->getDistinctByEntityNameQueryBuilder()
            ->getQuery()
            ->getScalarResult();
        foreach ($entities as $entity) {
            $config = $this->entityConfigProvider->getConfig($entity['entityName']);
            $this->addEntity(
                $result,
                $config->getId()->getClassName(),
                $config->get('label'),
                $config->get('plural_label'),
                $config->get('icon'),
                $translate
            );
        }
    }
}
