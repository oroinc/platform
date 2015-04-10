<?php

namespace Oro\Bundle\EmailBundle\Builder\Helper;

use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Templating\EngineInterface;

use Oro\Bundle\EmailBundle\Entity\EmailOwnerInterface;
use Oro\Bundle\EmailBundle\Entity\Manager\EmailAddressManager;
use Oro\Bundle\EmailBundle\Model\EmailHolderInterface;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\EmailBundle\Cache\EmailCacheManager;
use Oro\Bundle\EmailBundle\Entity\Email as EmailEntity;
use Oro\Bundle\EmailBundle\Exception\LoadEmailBodyException;

/**
 * Class EmailModelBuilderHelper
 *
 * @package Oro\Bundle\EmailBundle\Builder\Helper
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EmailModelBuilderHelper
{
    /**
     * @var EntityRoutingHelper
     */
    protected $entityRoutingHelper;

    /**
     * @var EmailAddressHelper
     */
    protected $emailAddressHelper;

    /**
     * @var NameFormatter
     */
    protected $nameFormatter;

    /**
     * @var SecurityContext
     */
    protected $securityContext;

    /**
     * @var EmailAddressManager
     */
    protected $emailAddressManager;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var EmailCacheManager
     */
    protected $emailCacheManager;

    /**
     * @var EngineInterface
     */
    protected $templating;

    /**
     * @param EntityRoutingHelper $entityRoutingHelper
     * @param EmailAddressHelper  $emailAddressHelper
     * @param NameFormatter       $nameFormatter
     * @param SecurityContext     $securityContext
     * @param EmailAddressManager $emailAddressManager
     * @param EntityManager       $entityManager
     * @param EmailCacheManager   $emailCacheManager
     * @param EngineInterface     $engineInterface
     */
    public function __construct(
        EntityRoutingHelper $entityRoutingHelper,
        EmailAddressHelper $emailAddressHelper,
        NameFormatter $nameFormatter,
        SecurityContext $securityContext,
        EmailAddressManager $emailAddressManager,
        EntityManager $entityManager,
        EmailCacheManager $emailCacheManager,
        EngineInterface $engineInterface
    ) {
        $this->entityRoutingHelper = $entityRoutingHelper;
        $this->emailAddressHelper  = $emailAddressHelper;
        $this->nameFormatter       = $nameFormatter;
        $this->securityContext     = $securityContext;
        $this->emailAddressManager = $emailAddressManager;
        $this->entityManager       = $entityManager;
        $this->emailCacheManager   = $emailCacheManager;
        $this->templating          = $engineInterface;
    }

    /**
     * @param string      $emailAddress
     * @param string|null $ownerClass
     * @param mixed|null  $ownerId
     */
    public function preciseFullEmailAddress(&$emailAddress, $ownerClass = null, $ownerId = null)
    {
        if (!$this->emailAddressHelper->isFullEmailAddress($emailAddress)) {
            if (!empty($ownerClass) && !empty($ownerId)) {
                $owner = $this->entityRoutingHelper->getEntity($ownerClass, $ownerId);
                if ($owner) {
                    $ownerName = $this->nameFormatter->format($owner);
                    if (!empty($ownerName)) {
                        $emailAddress = $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $ownerName);

                        return;
                    }
                }
            }
            $repo            = $this->emailAddressManager->getEmailAddressRepository($this->entityManager);
            $emailAddressObj = $repo->findOneBy(array('email' => $emailAddress));
            if ($emailAddressObj) {
                $owner = $emailAddressObj->getOwner();
                if ($owner) {
                    $ownerName = $this->nameFormatter->format($owner);
                    if (!empty($ownerName)) {
                        $emailAddress = $this->emailAddressHelper->buildFullEmailAddress($emailAddress, $ownerName);
                    }
                }
            }
        }
    }

    /**
     * Get the current authenticated user
     *
     * @return User|UserInterface|EmailHolderInterface|EmailOwnerInterface|null
     */
    public function getUser()
    {
        $token = $this->securityContext->getToken();
        if ($token) {
            $user = $token->getUser();
            if ($this->isFullQualifiedUser($user)) {
                return $user;
            }
        }

        return null;
    }

    /**
     * @param string $className
     *
     * @return string
     */
    public function decodeClassName($className)
    {
        return $this->entityRoutingHelper->decodeClassName($className);
    }

    /**
     * @param User $user
     *
     * @return string
     */
    public function buildFullEmailAddress(User $user)
    {
        return $this->emailAddressHelper->buildFullEmailAddress(
            $user->getEmail(),
            $this->nameFormatter->format($user)
        );
    }

    /**
     * @param EmailEntity $emailEntity
     * @param             $templatePath
     *
     * @return null|string
     */
    public function getEmailBody(EmailEntity $emailEntity, $templatePath)
    {
        try {
            $this->emailCacheManager->ensureEmailBodyCached($emailEntity);
        } catch (LoadEmailBodyException $e) {
            return null;
        }

        return $this->templating
            ->render($templatePath, ['email' => $emailEntity]);
    }

    /**
     * @param $prefix
     * @param $subject
     *
     * @return string
     */
    public function prependWith($prefix, $subject)
    {
        if (!preg_match('/^' . $prefix . ':*/', $subject)) {
            return $prefix . $subject;
        }
        return $subject;
    }

    /**
     * @param string $entityClass
     * @param string $entityId
     * @return object
     */
    public function getTargetEntity($entityClass, $entityId)
    {
        return $this->entityRoutingHelper->getEntity($entityClass, $entityId);
    }

    /**
     * @param $entity
     *
     * @return bool
     */
    protected function isFullQualifiedUser($entity)
    {
        return $entity instanceof UserInterface
        && $entity instanceof EmailHolderInterface
        && $entity instanceof EmailOwnerInterface;
    }
}
