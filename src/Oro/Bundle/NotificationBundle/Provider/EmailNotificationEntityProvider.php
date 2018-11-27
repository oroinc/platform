<?php

namespace Oro\Bundle\NotificationBundle\Provider;

use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EntityBundle\ORM\EntityClassResolver;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Provides the list of entities for which it is allowed to configure email notifications.
 */
class EmailNotificationEntityProvider extends EntityProvider
{
    /** @var ManagerRegistry */
    private $doctrine;

    /**
     * @param ConfigProvider      $entityConfigProvider
     * @param ConfigProvider      $extendConfigProvider
     * @param EntityClassResolver $entityClassResolver
     * @param TranslatorInterface $translator
     * @param FeatureChecker      $featureChecker
     * @param ManagerRegistry     $doctrine
     */
    public function __construct(
        ConfigProvider $entityConfigProvider,
        ConfigProvider $extendConfigProvider,
        EntityClassResolver $entityClassResolver,
        TranslatorInterface $translator,
        FeatureChecker $featureChecker,
        ManagerRegistry $doctrine
    ) {
        parent::__construct(
            $entityConfigProvider,
            $extendConfigProvider,
            $entityClassResolver,
            $translator,
            $featureChecker
        );
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    protected function addEntities(array &$result, $applyExclusions, $translate)
    {
        $entities = $this->getEmailTemplateRepository()
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

    /**
     * @return EmailTemplateRepository
     */
    private function getEmailTemplateRepository()
    {
        return $this->doctrine
            ->getManagerForClass(EmailTemplate::class)
            ->getRepository(EmailTemplate::class);
    }
}
