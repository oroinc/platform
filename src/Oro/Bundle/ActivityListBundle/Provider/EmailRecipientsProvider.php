<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Entity\Repository\ActivityListRepository;
use Oro\Bundle\ActivityListBundle\Helper\ActivityListAclCriteriaHelper;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;

class EmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    /** @var Registry */
    protected $registry;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var ActivityListAclCriteriaHelper */
    protected $activityListAclHelper;

    /** @var ActivityListChainProvider */
    protected $activityListProvider;

    /**
     * @param Registry $registry
     * @param ActivityManager $activityManager
     * @param RelatedEmailsProvider $relatedEmailsProvider
     * @param ActivityListAclCriteriaHelper $activityListAclHelper
     * @param ActivityListChainProvider $activityListChainProvider
     */
    public function __construct(
        Registry $registry,
        ActivityManager $activityManager,
        RelatedEmailsProvider $relatedEmailsProvider,
        ActivityListAclCriteriaHelper $activityListAclHelper,
        ActivityListChainProvider $activityListChainProvider
    ) {
        $this->registry = $registry;
        $this->activityManager = $activityManager;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->activityListAclHelper = $activityListAclHelper;
        $this->activityListProvider = $activityListChainProvider;
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
        $em = $this->registry->getManagerForClass($relatedEntityClass);
        $metadata = $em->getClassMetadata($relatedEntityClass);
        $idNames = $metadata->getIdentifierFieldNames();

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
                ->andWhere($qb->expr()->exists($activityListDql))
                ->setParameter('related_activity_class', $class);

            foreach ($activityListQb->getParameters() as $param) {
                $qb->setParameter($param->getName(), $param->getValue(), $param->getType());
            }

            $iterator = new BufferedQueryResultIterator($qb);
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
    public function getSection()
    {
        return 'oro.email.autocomplete.contexts';
    }

    /**
     * @param string $relatedEntityClass
     * @param mixed $relatedEntityId
     *
     * @return QueryBuilder
     */
    protected function createActivityListQb($relatedEntityClass, $relatedEntityId)
    {
        $activityListQb = $this->getActivityListRepository()->getBaseActivityListQueryBuilder(
            $relatedEntityClass,
            $relatedEntityId
        );

        $activityListQb
            ->andWhere('activity.relatedActivityId = e.id')
            ->andWhere('activity.relatedActivityClass = :related_activity_class');

        $this->activityListAclHelper->applyAclCriteria($activityListQb, $this->activityListProvider->getProviders());

        return $activityListQb;
    }

    /**
     * @return ActivityListRepository
     */
    protected function getActivityListRepository()
    {
        return $this->getRepository('OroActivityListBundle:ActivityList');
    }

    /**
     * @param string $persistentObjectName
     *
     * @return EntityRepository
     */
    protected function getRepository($persistentObjectName)
    {
        return $this->registry->getRepository($persistentObjectName);
    }
}
