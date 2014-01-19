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
     * @param EntityManager       $em
     * @param ConfigProvider      $entityConfigProvider
     * @param EntityClassResolver $entityClassResolver
     * @param Translator          $translator
     */
    public function __construct(
        EntityManager $em,
        ConfigProvider $entityConfigProvider,
        EntityClassResolver $entityClassResolver,
        Translator $translator
    ) {
        parent::__construct($entityConfigProvider, $entityClassResolver, $translator);
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    protected function addEntities(array &$result, $translate)
    {
        $entities = $this->em->createQueryBuilder()
            ->from('OroEmailBundle:EmailTemplate', 't')
            ->select('t.entityName')
            ->distinct()
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
