<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
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
use Oro\Bundle\EmailBundle\Entity\Mailbox;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EmailBundle\Entity\Repository\MailboxRepository;
use Oro\Bundle\EmailBundle\Exception\InvalidArgumentException;
use Oro\Bundle\EmailBundle\Mailbox\MailboxProcessStorage;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Provides a way to use Email entity in an activity list.
 * For the Email activity in the case when EmailAddress does not have owner(User|Organization),
 * we are trying to extract Organization from the current logged user.
 *
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

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var UrlGeneratorInterface */
    protected $urlGenerator;

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

    /** @var MailboxProcessStorage */
    protected $mailboxProcessStorage;

    /** @var ActivityAssociationHelper */
    protected $activityAssociationHelper;

    /** @var CommentAssociationHelper */
    protected $commentAssociationHelper;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        EntityNameResolver $entityNameResolver,
        UrlGeneratorInterface $urlGenerator,
        ConfigManager $configManager,
        EmailThreadProvider $emailThreadProvider,
        HtmlTagHelper $htmlTagHelper,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        MailboxProcessStorage $mailboxProcessStorage,
        ActivityAssociationHelper $activityAssociationHelper,
        CommentAssociationHelper $commentAssociationHelper
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->entityNameResolver = $entityNameResolver;
        $this->urlGenerator = $urlGenerator;
        $this->configManager = $configManager;
        $this->emailThreadProvider = $emailThreadProvider;
        $this->htmlTagHelper = $htmlTagHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenAccessor = $tokenAccessor;
        $this->mailboxProcessStorage = $mailboxProcessStorage;
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
            Email::class,
            $accessible
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes($entity)
    {
        return [
            'itemView'  => 'oro_email_view',
            'groupView' => 'oro_email_view_group',
        ];
    }

    /**
     * {@inheritdoc}
     * @param Email $entity
     */
    public function getSubject($entity)
    {
        return $entity->getSubject();
    }

    /**
     * {@inheritdoc}
     * @param Email $entity
     */
    public function getDescription($entity)
    {
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
     * @param Email $entity
     */
    public function getCreatedAt($entity)
    {
        return $entity->getSentAt();
    }

    /**
     * {@inheritdoc}
     * @param Email $entity
     */
    public function getUpdatedAt($entity)
    {
        return $entity->getSentAt();
    }

    /**
     * {@inheritdoc}
     * @param Email $entity
     */
    public function getOrganization($entity)
    {
        $emailAddressOwner = $entity->getFromEmailAddress()->getOwner();
        if ($emailAddressOwner && $emailAddressOwner->getOrganization()) {
            return $emailAddressOwner->getOrganization();
        }

        $currentOrganization = $this->tokenAccessor->getOrganization();
        if (null !== $currentOrganization) {
            return $currentOrganization;
        }

        /** @var MailboxRepository $mailboxRepository */
        $mailboxRepository = $this->doctrineHelper->getEntityRepositoryForClass(Mailbox::class);
        $processes = $this->mailboxProcessStorage->getProcesses();
        foreach ($processes as $process) {
            $settingsClass = $process->getSettingsEntityFQCN();
            $mailboxes = $mailboxRepository->findBySettingsClassAndEmail($settingsClass, $entity);

            foreach ($mailboxes as $mailbox) {
                return $mailbox->getOrganization();
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ActivityList $activityList)
    {
        $relatedActivityClass = $activityList->getRelatedActivityClass();
        $em = $this->doctrineHelper->getEntityManagerForClass($relatedActivityClass);
        /** @var Email $email */
        $email = $em->getRepository($relatedActivityClass)
            ->find($activityList->getRelatedActivityId());
        $headEmail = $email;
        if (null !== $email->getThread()) {
            $headEmail = $this->emailThreadProvider->getHeadEmail($em, $email);
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
        return '@OroEmail/Email/js/activityItemTemplate.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupedTemplate(): string
    {
        return '@OroEmail/Email/js/groupedActivityItemTemplate.html.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId($entity)
    {
        if ($this->doctrineHelper->getEntityClass($entity) === EmailUser::class) {
            $entity = $entity->getEmail();
        }

        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity)
    {
        if (\is_object($entity)) {
            if (!$entity instanceof Email) {
                return false;
            }

            // the activity lists for private emails should not be created.
            return $this->doctrineHelper->getEntityRepositoryForClass(Email::class)->isEmailPublic($entity->getId());
        }

        return $entity === Email::class;
    }

    /**
     * {@inheritdoc}
     * @param Email $entity
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
        if (!$entity instanceof Email) {
            throw new InvalidArgumentException(
                sprintf(
                    'Argument must be instance of "%s", "%s" given',
                    Email::class,
                    is_object($entity) ? ClassUtils::getClass($entity) : gettype($entity)
                )
            );
        }

        if (null === $entity->getThread()) {
            return [];
        }

        $queryBuilder = $this->doctrineHelper->getEntityRepositoryForClass(ActivityList::class)
            ->createQueryBuilder('a');
        $queryBuilder
            ->innerJoin(
                'OroEmailBundle:Email',
                'e',
                'WITH',
                'a.relatedActivityId = e.id and a.relatedActivityClass = :class'
            )
            ->andWhere('e.thread = :thread')
            ->setParameter('class', Email::class)
            ->setParameter('thread', $entity->getThread());

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
            $qb = $this->doctrineHelper->getEntityRepositoryForClass(Email::class)
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
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function collectActivityOwners(ActivityList $activityList, array $filter): array
    {
        $activityOwners = [];
        /** @var EmailUser[] $owners */
        $owners = $this->doctrineHelper->getEntityRepositoryForClass(EmailUser::class)
            ->findBy($filter);

        if ($owners) {
            foreach ($owners as $owner) {
                // the activity lists for private emails should not have owner
                // to be sure it is not shown at the activity list grid.
                if ($owner->isEmailPrivate()) {
                    continue;
                }
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
     * {@inheritDoc}
     */
    public function isActivityListApplicable(ActivityList $activityList): bool
    {
        return $activityList->getActivityOwners()->count() > 0;
    }

    /**
     * @param $entity
     * @return Email
     */
    protected function getEmailEntity($entity)
    {
        if ($entity instanceof EmailUser) {
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
                $data['ownerLink'] = $this->urlGenerator->generate($route, ['id' => $id]);
            } catch (RouteNotFoundException $e) {
                // Do not set owner link if route is not found.
            }
        }

        return $data;
    }
}
