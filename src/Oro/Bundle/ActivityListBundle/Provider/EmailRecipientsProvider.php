<?php

namespace Oro\Bundle\ActivityListBundle\Provider;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsHelper;
use Oro\Bundle\EmailBundle\Model\EmailRecipientsProviderArgs;
use Oro\Bundle\EmailBundle\Provider\EmailRecipientsProviderInterface;

class EmailRecipientsProvider implements EmailRecipientsProviderInterface
{
    /** @var Registry */
    protected $registry;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var EmailRecipientsHelper */
    protected $emailRecipientsHelper;

    /**
     * @param Registry $registry
     * @param ActivityManager $activityManager
     * @param RelatedEmailsProvider $relatedEmailsProvider
     * @param EmailRecipientsHelper $emailRecipientsHelper
     */
    public function __construct(
        Registry $registry,
        ActivityManager $activityManager,
        RelatedEmailsProvider $relatedEmailsProvider,
        EmailRecipientsHelper $emailRecipientsHelper
    ) {
        $this->registry = $registry;
        $this->activityManager = $activityManager;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->emailRecipientsHelper = $emailRecipientsHelper;
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

        $result = [];
        $activities = $this->activityManager->getActivities($relatedEntityClass);
        $activityListQb = $this->createActivityListQb($relatedEntityClass, $idNames[0]);
        $activityListDql = $activityListQb->getQuery()->getDQL();
        $limit = $args->getLimit();
        foreach (array_keys($activities) as $class) {

            $qb = $this->getRepository($class)
                ->createQueryBuilder('e');
            $qb
                ->andWhere($qb->expr()->exists($activityListDql))
                ->setParameter('related_entity_id', $relatedEntityId)
                ->setParameter('related_activity_class', $class);

            $iterator = new BufferedQueryResultIterator($qb);
            $iterator->setBufferSize($limit);

            foreach ($iterator as $entity) {
                $result = array_merge(
                    $result,
                    EmailRecipientsHelper::filterRecipients(
                        $args,
                        $this->relatedEmailsProvider->getEmails($entity, 2)
                    )
                );

                $limit -= count($result);
                if ($limit <= 0) {
                    break 2;
                }
            }
        }

        return $result;
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
     * @param string $relatedEntityIdFieldName
     *
     * @return QueryBuilder
     */
    protected function createActivityListQb($relatedEntityClass, $relatedEntityIdFieldName)
    {
        $joinField = sprintf(
            'al.%s',
            ExtendHelper::buildAssociationName(
                $relatedEntityClass,
                ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
            )
        );

        $activityListQb = $this->getRepository('OroActivityListBundle:ActivityList')
                ->createQueryBuilder('al');

        $activityListQb
                ->select('1')
                ->join($joinField, 'a')
                ->andWhere(sprintf('a.%s = :related_entity_id', $relatedEntityIdFieldName))
                ->andWhere('al.relatedActivityClass = :related_activity_class')
                ->andWhere('al.relatedActivityId = e.id');

        return $activityListQb;
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
