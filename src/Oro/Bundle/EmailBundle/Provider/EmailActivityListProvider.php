<?php

namespace Oro\Bundle\EmailBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\QueryBuilder;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

use Oro\Bundle\ActivityListBundle\Entity\ActivityList;
use Oro\Bundle\ActivityListBundle\Model\ActivityListProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListDateProviderInterface;
use Oro\Bundle\ActivityListBundle\Model\ActivityListGroupProviderInterface;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\Provider\EmailThreadProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\CommentBundle\Model\CommentProviderInterface;
use Oro\Bundle\EmailBundle\Tools\EmailHelper;

class EmailActivityListProvider implements
    ActivityListProviderInterface,
    ActivityListDateProviderInterface,
    ActivityListGroupProviderInterface,
    CommentProviderInterface
{
    const ACTIVITY_CLASS = 'Oro\Bundle\EmailBundle\Entity\Email';
    const MAX_DESCRIPTION_LENGTH = 80;

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var ServiceLink */
    protected $doctrineRegistryLink;

    /** @var ServiceLink */
    protected $nameFormatterLink;

    /** @var Router */
    protected $router;

    /** @var ConfigManager */
    protected $configManager;

    /** @var EmailThreadProvider */
    protected $emailThreadProvider;

    /**
     * @var EmailHelper
     */
    protected $emailHelper;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param ServiceLink         $doctrineRegistryLink
     * @param ServiceLink         $nameFormatterLink
     * @param Router              $router
     * @param ConfigManager       $configManager
     * @param EmailThreadProvider $emailThreadProvider
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        ServiceLink $doctrineRegistryLink,
        ServiceLink $nameFormatterLink,
        Router $router,
        ConfigManager $configManager,
        EmailThreadProvider $emailThreadProvider,
        EmailHelper $emailHelper
    ) {
        $this->doctrineHelper       = $doctrineHelper;
        $this->doctrineRegistryLink = $doctrineRegistryLink;
        $this->nameFormatterLink    = $nameFormatterLink;
        $this->router               = $router;
        $this->configManager        = $configManager;
        $this->emailThreadProvider  = $emailThreadProvider;
        $this->emailHelper          = $emailHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicableTarget(ConfigIdInterface $configId, ConfigManager $configManager)
    {
        $provider = $configManager->getProvider('activity');

        return $provider->hasConfigById($configId)
            && $provider->getConfigById($configId)->has('activities')
            && in_array(self::ACTIVITY_CLASS, $provider->getConfigById($configId)->get('activities'));
    }

    /**
     * {@inheritdoc}
     */
    public function getRoutes()
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

            $body = $entity->getEmailBody();
            $content = $this->emailHelper->getOnlyLastAnswer($body);
            $content = $this->emailHelper->getStrippedBody($content);
            $content = $this->emailHelper->getShortBody($content, self::MAX_DESCRIPTION_LENGTH);

            return $content;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getDate($entity)
    {
        /** @var $entity Email */
        return $entity->getSentAt();
    }

    /**
     * {@inheritdoc}
     */
    public function isHead($entity)
    {
        /** @var $entity Email */
        return $entity->isHead();
    }

    /**
     *  {@inheritdoc}
     */
    public function isDateUpdatable()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getOrganization($activityEntity)
    {
        /** @var $activityEntity Email */
        return $activityEntity->getFromEmailAddress()->getOwner()->getOrganization();
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
        if ($email->isHead() && $email->getThread()) {
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
            'isHead'        => $email->isHead() && $email->getThread(),
            'treadId'       => $email->getThread() ? $email->getThread()->getId() : null
        ];

        if ($email->getThread()) {
            $emails = $email->getThread()->getEmails();
            // if there are just two email - add replayedEmailId to use on client side
            if (count($emails) == 2) {
                $data['replayedEmailId'] = $emails[0]->getId();
            }
        }

        if ($email->getFromEmailAddress()->hasOwner()) {
            $owner             = $email->getFromEmailAddress()->getOwner();
            $data['ownerName'] = $this->nameFormatterLink->getService()->format($owner);

            $route = $this->configManager->getEntityMetadata(ClassUtils::getClass($owner))
                ->getRoute('view');
            if (null !== $route) {
                $id                = $this->doctrineHelper->getSingleEntityIdentifier($owner);
                $data['ownerLink'] = $this->router->generate($route, ['id' => $id]);
            }
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplate()
    {
        return 'OroEmailBundle:Email:js/activityItemTemplate.js.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupedTemplate()
    {
        return 'OroEmailBundle:Email:js/groupedActivityItemTemplate.js.twig';
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityId($entity)
    {
        return $this->doctrineHelper->getSingleEntityIdentifier($entity);
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable($entity)
    {
        return $this->doctrineHelper->getEntityClass($entity) == self::ACTIVITY_CLASS
            && $entity->getFromEmailAddress()->hasOwner();
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetEntities($entity)
    {
        return $entity->getActivityTargetEntities();
    }

    /**
     * {@inheritdoc}
     */
    public function hasComments(ConfigManager $configManager, $entity)
    {
        $config = $configManager->getProvider('comment')->getConfig($entity);

        return $config->is('enabled');
    }

    /**
     * {@inheritdoc}
     */
    public function getGroupedEntities($email)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->doctrineRegistryLink->getService()
            ->getRepository('OroActivityListBundle:ActivityList')->createQueryBuilder('a');

        $queryBuilder->innerJoin(
            'OroEmailBundle:Email',
            'e',
            'INNER',
            'a.relatedActivityId = e.id and a.relatedActivityClass = :class'
        )
            ->setParameter('class', self::ACTIVITY_CLASS)
            ->andWhere('e.thread = :thread')
            ->setParameter('thread', $email->getThread());

        return $queryBuilder->getQuery()->getResult();
    }
}
