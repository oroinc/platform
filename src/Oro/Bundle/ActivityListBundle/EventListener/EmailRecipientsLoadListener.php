<?php

namespace Oro\Bundle\ActivityListBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EmailBundle\Event\EmailRecipientsLoadEvent;
use Oro\Bundle\EmailBundle\Provider\RelatedEmailsProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EmailRecipientsLoadListener
{
    /** @var Registry */
    protected $registry;

    /** @var ActivityManager */
    protected $activityManager;

    /** @var RelatedEmailsProvider */
    protected $relatedEmailsProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param Registry $registry
     * @param ActivityManager $activityManager
     * @param RelatedEmailsProvider $relatedEmailsProvider
     * @param TranslatorInterface $translator
     */
    public function __construct(
        Registry $registry,
        ActivityManager $activityManager,
        RelatedEmailsProvider $relatedEmailsProvider,
        TranslatorInterface $translator
    ) {
        $this->registry = $registry;
        $this->activityManager = $activityManager;
        $this->relatedEmailsProvider = $relatedEmailsProvider;
        $this->translator = $translator;
    }

    /**
     * @param EmailRecipientsLoadEvent $event
     */
    public function onLoad(EmailRecipientsLoadEvent $event)
    {
        if (!$event->getRemainingLimit() || !$event->getRelatedEntity()) {
            return;
        }

        $relatedEntity = $event->getRelatedEntity();
        $relatedEntityClass = ClassUtils::getClass($relatedEntity);
        $em = $this->registry->getManagerForClass($relatedEntityClass);
        $metadata = $em->getClassMetadata($relatedEntityClass);
        $idNames = $metadata->getIdentifierFieldNames();

        if (count($idNames) !== 1) {
            return;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $relatedEntityId = $propertyAccessor->getValue($relatedEntity, $idNames[0]);

        $activities = $this->activityManager->getActivities($relatedEntityClass);
        foreach ($activities as $class => $field) {
            $activityListQb = $this->createActivityListQb($relatedEntityClass, $idNames[0]);

            $qb = $this->getRepository($class)
                ->createQueryBuilder('e');
            $qb
                ->andWhere($qb->expr()->exists($activityListQb->getQuery()->getDQL()))
                ->setParameter('related_entity_id', $relatedEntityId)
                ->setParameter('related_activity_class', $class);

            $iterator = new BufferedQueryResultIterator($qb);
            $iterator->setBufferSize($event->getRemainingLimit());

            foreach ($iterator as $entity) {
                $emails = $this->relatedEmailsProvider->getEmails($entity, 2);
                if (!$emails) {
                    continue;
                }

                $this->addEmailsToContext($event, $emails);

                if (!$event->getRemainingLimit()) {
                    break 2;
                }
            }
        }
    }

    /**
     * @param EmailRecipientsLoadEvent $event
     * @param array $emails
     */
    protected function addEmailsToContext(EmailRecipientsLoadEvent $event, array $emails)
    {
        $limit = $event->getRemainingLimit();
        $query = $event->getQuery();

        $excludedEmails = $event->getEmails();
        $filteredEmails = array_filter($emails, function ($email) use ($query, $excludedEmails) {
            return !in_array($email, $excludedEmails) && stripos($email, $query) !== false;
        });
        if (!$filteredEmails) {
            return;
        }

        $id = $this->translator->trans('oro.email.autocomplete.contexts');
        $resultsId = null;
        $results = $event->getResults();
        foreach ($results as $recordId => $record) {
            if ($record['text'] === $id) {
                $resultsId = $recordId;

                break;
            }
        }

        $children = $this->createResultFromEmails(array_splice($filteredEmails, 0, $limit));
        if ($resultsId !== null) {
            $results[$resultsId]['children'] = array_merge($results[$resultsId]['children'], $children);
        } else {
            $results = array_merge(
                $results,
                [
                    [
                        'text'     => $id,
                        'children' => $children,
                    ],
                ]
            );
        }

        $event->setResults($results);
    }

    /**
     * @param array $emails
     *
     * @return array
     */
    protected function createResultFromEmails(array $emails)
    {
        $result = [];
        foreach ($emails as $email => $name) {
            $result[] = [
                'id'   => $email,
                'text' => $name,
            ];
        }

        return $result;
    }

    /**
     * @param QueryBuilder $relatedEntityClass
     * @param QueryBuilder $relatedEntityIdFieldName
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
