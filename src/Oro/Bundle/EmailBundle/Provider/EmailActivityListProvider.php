<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ActivityBundle\Tools\ActivityAssociationHelper;
use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Entity\ActivityOwner;
use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListGroupProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Tools\ActivityListEntityConfigDumperExtension;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\CommentBundle\Tools\CommentAssociationHelper;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Oro\Component\DependencyInjection\ServiceLink;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * For the Email activity in the case when EmailAddress does not have owner(User|Organization),
 * we are trying to extract Organization from the current logged user.
 *
 * @todo Should be refactored in the BAP-8520
 * @see EmailActivityListProvider::isApplicable
 * @see EmailActivityListProvider::getOrganization
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class EmailActivityListProvider implements
    ActivityListProviderInterface,
    ActivityListDateProviderInterface,
    ActivityListGroupProviderInterface,
    CommentProviderInterface,
    FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const ACTIVITY_CLASS = 'Oro\Bundle\EmailBundle\Entity\Email';
    const ACL_CLASS = 'Oro\Bundle\EmailBundle\Entity\EmailUser';

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ServiceLink */
    protected $doctrineRegistryLink;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var Router */
    protected $router;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /** @var HtmlTagHelper */
    protected $htmlTagHelper;

    /** @var TokenAccessorInterface */
    protected $tokenAccessor;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var ServiceLink */
    protected $mailboxProcessStorageLink;

    /** @var ActivityAssociationHelper */
    protected $activityAssociationHelper;

    /** @var CommentAssociationHelper */
    protected $commentAssociationHelper;

    /**
     * @param DoctrineHelper                $doctrineHelper
     * @param ServiceLink                   $doctrineRegistryLink
     * @param EntityNameResolver            $entityNameResolver
     * @param Router                        $router
     * @param ConfigManager                 $configManager
     * @param EmailThreadProvider           $emailThreadProvider
     * @param HtmlTagHelper                 $htmlTagHelper
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenAccessorInterface        $tokenAccessor
     * @param ServiceLink                   $mailboxProcessStorageLink
     * @param ActivityAssociationHelper     $activityAssociationHelper
     * @param CommentAssociationHelper      $commentAssociationHelper
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ServiceLink $doctrineRegistryLink,
        EntityNameResolver $entityNameResolver,
        Router $router,
        ConfigManager $configManager,
        EmailThreadProvider $emailThreadProvider,
        HtmlTagHelper $htmlTagHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        ServiceLink $mailboxProcessStorageLink,
        ActivityAssociationHelper $activityAssociationHelper,
        CommentAssociationHelper $commentAssociationHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->doctrineRegistryLink = $doctrineRegistryLink;
        $this->entityNameResolver = $entityNameResolver;
        $this->router = $router;
        $this->configManager = $configManager;
        $this->emailThreadProvider = $emailThreadProvider;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->mailboxProcessStorageLink = $mailboxProcessStorageLink;
        $this->activityAssociationHelper = $activityAssociationHelper;
        $this->commentAssociationHelper = $commentAssociationHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget($entityClass, $accessible = true)
    {
        return $this->activityAssociationHelper->isActivityAssociationEnabled(
            $entityClass,
            self::ACTIVITY_CLASS,
            $accessible
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes($activityEntity)
    {
        return [
            'itemView'  => 'oro_email_view',
            'groupView' => 'oro_email_view_group',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityClass()
    {
        return self::ACTIVITY_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getAclClass()
    {
        return self::ACL_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubject($entity)
    {
        /** @var $entity Email */
        return $entity->getSubject();
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription($entity)
    {
        /** @var $entity Email */
        if ($entity->getEmailBody()) {
            return $entity->getEmailBody()->getTextBody();
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOwner($entity)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getCreatedAt($entity)
    {
        /** @var $entity Email */
        return $entity->getSentAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getUpdatedAt($entity)
    {
        /** @var $entity Email */
        return $entity->getSentAt();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization($activityEntity)
    {
        /**
         * @var $activityEntity Email
         * @var $emailAddressOwner EmailOwnerInterface
         */
        $emailAddressOwner = $activityEntity->getFromEmailAddress()->getOwner();
        if ($emailAddressOwner && $emailAddressOwner->getOrganization()) {
            return $emailAddressOwner->getOrganization();
        }

        $currentOrganization = $this->tokenAccessor->getOrganization();
        if (null !== $currentOrganization) {
            return $currentOrganization;
        }

        $processes = $this->mailboxProcessStorageLink->getService()->getProcesses();
        foreach ($processes as $process) {
            $settingsClass = $process->getSettingsEntityFQCN();

            $mailboxes = $this->doctrineRegistryLink->getService()->getRepository('OroEmailBundle:Mailbox')
                ->findBySettingsClassAndEmail($settingsClass, $activityEntity);

            foreach ($mailboxes as $mailbox) {
                return $mailbox->getOrganization();
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityListEntity)
    {
        /** @var Email $email */
        $email = $headEmail = $this->doctrineRegistryLink->getService()
            ->getRepository($activityListEntity->getRelatedActivityClass())
            ->find($activityListEntity->getRelatedActivityId());
        if (null !== $email->getThread()) {
            $headEmail = $this->emailThreadProvider->getHeadEmail(
                $this->doctrineHelper->getEntityManager($activityListEntity->getRelatedActivityClass()),
                $email
            );
        }

        $data = [
            'ownerName'     => $email->getFromName(),
            'ownerLink'     => null,
            'entityId'      => $email->getId(),
            'headOwnerName' => $headEmail->getFromName(),
            'headSubject'   => $headEmail->getSubject(),
            'headSentAt'    => $headEmail->getSentAt()->format('c'),
            'isHead'        => null !== $email->getThread(),
            'treadId'       => $email->getThread() ? $email->getThread()->getId() : null
        ];
        $data = $this->setReplaedEmailId($email, $data);

        if ($email->getFromEmailAddress()->getHasOwner()) {
            $owner = $email->getFromEmailAddress()->getOwner();
            $data['headOwnerName'] = $data['ownerName'] = $this->entityNameResolver->getName($owner);
            $data = $this->setOwnerLink($owner, $data);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'OroEmailBundle:Email:js/activityItemTemplate.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupedTemplate(): string
    {
        return 'OroEmailBundle:Email:js/groupedActivityItemTemplate.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId($entity)
    {
        if ($this->doctrineHelper->getEntityClass($entity) === self::ACL_CLASS) {
            $entity = $entity->getEmail();
        }
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity)
    {
        return $this->doctrineHelper->getEntityClass($entity) == self::ACTIVITY_CLASS;
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntities($entity)
    {
        return $entity->getActivityTargets() ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function isCommentsEnabled($entityClass)
    {
        return $this->commentAssociationHelper->isCommentAssociationEnabled($entityClass);
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupedEntities($entity, $associatedEntityClass = null, $associatedEntityId = null): array
    {
        /** @var Email $entity */

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->doctrineRegistryLink->getService()
            ->getRepository(ActivityList::class)
            ->createQueryBuilder('a')
            ->innerJoin(
                'OroEmailBundle:Email',
                'e',
                'WITH',
                'a.relatedActivityId = e.id and a.relatedActivityClass = :class'
            )
            ->setParameter('class', self::ACTIVITY_CLASS);
        if (null !== $entity->getThread()) {
            $queryBuilder
                ->andWhere('e.thread = :thread')
                ->setParameter('thread', $entity->getThread());
        }
        if ($associatedEntityClass && $associatedEntityId) {
            $associationName = ExtendHelper::buildAssociationName(
                $associatedEntityClass,
                ActivityListEntityConfigDumperExtension::ASSOCIATION_KIND
            );
            $queryBuilder
                ->andWhere(':targetId MEMBER OF a.' . $associationName)
                ->setParameter('targetId', $associatedEntityId);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * {@inheritdoc}
     */
    public function collapseGroupedItems(array $items): array
    {
        $emailIds = [];
        foreach ($items as $item) {
            if ($item['relatedActivityClass'] === Email::class) {
                $emailIds[] = $item['relatedActivityId'];
            }
        }
        $emailIds = array_unique($emailIds);
        if (count($emailIds) > 1) {
            $qb = $this->doctrineHelper->getEntityRepository(Email::class)
                ->createQueryBuilder('e')
                ->select('e.id, IDENTITY(e.thread) AS threadId')
                ->where('e.id IN (:ids) AND IDENTITY(e.thread) IS NOT NULL')
                ->setParameter('ids', $emailIds);
            $rows = $qb->getQuery()->getArrayResult();
            $emailThreadMap = [];
            foreach ($rows as $row) {
                $emailThreadMap[$row['id']] = $row['threadId'];
            }
            $filteredIds = [];
            $processedThreads = [];
            foreach ($items as $item) {
                if ($item['relatedActivityClass'] === Email::class) {
                    $emailId = $item['relatedActivityId'];
                    if (isset($emailThreadMap[$emailId])) {
                        $threadId = $emailThreadMap[$emailId];
                        if (in_array($threadId, $processedThreads)) {
                            continue;
                        }
                        $processedThreads[] = $threadId;
                    }
                }
                $filteredIds[] = $item;
            }
            $items = $filteredIds;
        }

        return $items;
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityOwners($entity, ActivityList $activityList)
    {
        $entity = $this->getEmailEntity($entity);
        $filter = ['email' => $entity];
        $targetEntities = $this->getTargetEntities($entity);
        $organizations = [$this->getOrganization($entity)];
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach ($entity->getEmailUsers() as $emailUser) {
            if (!in_array($emailUser->getOrganization(), $organizations, true)) {
                $organizations[] = $emailUser->getOrganization();
            }
        }

        foreach ($targetEntities as $target) {
            try {
                $organization = $propertyAccessor->getValue($target, 'organization');
                if (!in_array($organization, $organizations, true)) {
                    $organizations[] = $organization;
                }
            } catch (\Exception $e) {
                // skipp target
            }
        }
        if (count($organizations) > 0) {
            $filter['organization'] = $organizations;
        }

        return $this->collectActivityOwners($activityList, $filter);
    }

    /**
     * @param ActivityList $activityList
     * @param array $filter
     * @return array
     */
    private function collectActivityOwners(ActivityList $activityList, array $filter)
    {
        $activityOwners = [];
        /** @var EmailUser[] $owners */
        $owners = $this->doctrineRegistryLink->getService()
            ->getRepository('OroEmailBundle:EmailUser')
            ->findBy($filter);

        if ($owners) {
            foreach ($owners as $owner) {
                if (($owner->getMailboxOwner() && $owner->getOrganization()) ||
                    (!$owner->getMailboxOwner() && $owner->getOrganization() && $owner->getOwner())) {
                    $activityOwner = new ActivityOwner();
                    $activityOwner->setActivity($activityList);
                    $activityOwner->setOrganization($owner->getOrganization());
                    $user = $owner->getOwner();
                    if (!$owner->getOwner() && $owner->getMailboxOwner()) {
                        $settings =  $owner->getMailboxOwner()->getProcessSettings();
                        if ($settings) {
                            $user = $settings->getOwner();
                        }
                    }
                    $activityOwner->setUser($user);
                    $activityOwners[] = $activityOwner;
                }
            }
        }

        return $activityOwners;
    }

    /**
     * @param $entity
     * @return Email
     */
    protected function getEmailEntity($entity)
    {
        if (ClassUtils::getClass($entity) === self::ACL_CLASS) {
            $entity = $entity->getEmail();
        }

        return $entity;
    }

    /**
     * @param Email $email
     * @param $data
     *
     * @return mixed
     */
    protected function setReplaedEmailId($email, $data)
    {
        if ($email->getThread()) {
            $emails = $email->getThread()->getEmails();
            // if there are just two email - add replayedEmailId to use on client side
            if (count($emails) === 2) {
                $data['replayedEmailId'] = $emails[0]->getId();
            }
        }

        return $data;
    }

    /**
     * @param EmailOwnerInterface $owner
     * @param array $data
     *
     * @return mixed
     */
    protected function setOwnerLink($owner, array $data)
    {
        $route = null;
        $entityMetadata = $this->configManager->getEntityMetadata(ClassUtils::getClass($owner));
        
        if (null !== $entityMetadata) {
            $route = $entityMetadata->getRoute('view');
        }
        
        if (null !== $route && $this->authorizationChecker->isGranted('VIEW', $owner)) {
            $id = $this->doctrineHelper->getSingleEntityIdentifier($owner);
            try {
                $data['ownerLink'] = $this->router->generate($route, ['id' => $id]);
            } catch (RouteNotFoundException $e) {
                // Do not set owner link if route is not found.
            }
        }

        return $data;
    }
}
