<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\AccessRule\ActivityListAccessRule;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\SecurityBundle\AccessRule\AclAccessRule;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * Provider that allows to select email recipients by related entity.
 */
class EmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    private ManagerRegistry $doctrine;
    private ActivityManager $activityManager;
    private RelatedEmailsProvider $relatedEmailsProvider;
    private AclHelper $aclHelper;

    public function __construct(
        ManagerRegistry $doctrine,
        ActivityManager $activityManager,
        RelatedEmailsProvider $relatedEmailsProvider,
        AclHelper $aclHelper
    ) {
        $this->doctrine = $doctrine;
        $this->activityManager = $activityManager;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecipients(EmailRecipientsProviderArgs $args)
    {
        if (!$args->getRelatedEntity()) {
            return [];
        }

        $relatedEntity = $args->getRelatedEntity();
        $relatedEntityClass = ClassUtils::getClass($relatedEntity);
        $idNames = $this->doctrine->getManagerForClass($relatedEntityClass)
            ->getClassMetadata($relatedEntityClass)
            ->getIdentifierFieldNames();
        if (count($idNames) !== 1) {
            return [];
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $relatedEntityId = $propertyAccessor->getValue($relatedEntity, $idNames[0]);

        $recipients = [];
        $activities = $this->activityManager->getActivities($relatedEntityClass);
        $activityListQb = $this->createActivityListQb($relatedEntityClass, $relatedEntityId);
        $activityListDql = $activityListQb->getQuery()->getDQL();
        $limit = $args->getLimit();
        $activityKeys = array_keys($activities);
        foreach ($activityKeys as $class) {
            $qb = $this->getRepository($class)
                ->createQueryBuilder('e');
            $qb
                ->andWhere($qb->expr()->in('e.id', $activityListDql))
                ->setParameter('related_activity_class', $class);

            foreach ($activityListQb->getParameters() as $param) {
                $qb->setParameter($param->getName(), $param->getValue(), $param->getType());
            }

            $query = $this->aclHelper->apply(
                $qb,
                BasicPermission::VIEW,
                [
                    AclAccessRule::DISABLE_RULE => true,
                    ActivityListAccessRule::ACTIVITY_OWNER_TABLE_ALIAS => 'ao'
                ]
            );

            $iterator = new BufferedIdentityQueryResultIterator($query);
            $iterator->setBufferSize($limit);

            foreach ($iterator as $entity) {
                $recipients = array_merge(
                    $recipients,
                    EmailRecipientsHelper::filterRecipients(
                        $args,
                        $this->relatedEmailsProvider->getRecipients($entity, 2, false, $args->getOrganization())
                    )
                );

                $limit -= count($recipients);
                if ($limit <= 0) {
                    break 2;
                }
            }
        }

        return $recipients;
    }

    /**
     * {@inheritdoc}
     */
    public function getSection(): string
    {
        return 'oro.email.autocomplete.contexts';
    }

    private function createActivityListQb(string $relatedEntityClass, mixed $relatedEntityId): QueryBuilder
    {
        return $this->getActivityListRepository()
            ->getBaseActivityListQueryBuilder($relatedEntityClass, $relatedEntityId)
            ->select('activity.relatedActivityId')
            ->andWhere('activity.relatedActivityClass = :related_activity_class');
    }

    private function getActivityListRepository(): ActivityListRepository
    {
        return $this->doctrine->getRepository(ActivityList::class);
    }

    private function getRepository(string $persistentObjectName): EntityRepository
    {
        return $this->doctrine->getRepository($persistentObjectName);
    }
}
