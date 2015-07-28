<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Symfony\Component\Translation\TranslatorInterface;
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
     * @param ConfigProvider      $extendConfigProvider
     * @param EntityClassResolver $entityClassResolver
     * @param TranslatorInterface $translator
     * @param EntityManager       $em
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        EntityClassResolver $entityClassResolver,
        TranslatorInterface $translator,
        EntityManager $em
    ) {
        parent::__construct(
            $entityConfigProvider,
            $extendConfigProvider,
            $entityClassResolver,
            $translator
        );
        $this->em = $em;
    }

    /**
     * {@inheritdoc}
     */
    protected function addEntities(array &$result, $applyExclusions, $translate)
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
