<?php

namespace Oro\Bundle\EmailBundle\Acl\Voter;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\EmailBundle\Entity\Manager\MailboxManager;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

/**
 * Prevents finding not available mailboxes.
 */
class EmailVoter implements VoterInterface
{
    /**
     * If you want to change content of this array, please pay attention to classes
     * Oro\Bundle\EmailBundle\Migrations\Data\ORM\UpdateEmailEditAclRule
     * Oro\Bundle\EmailBundle\EventListener\RoleSubscriber
     */
    const SUPPORTED_CLASSES = [
        Email::class,
        EmailBody::class,
        EmailAttachment::class,
    ];

    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var MailboxManager */
    private $mailboxManager;

    public function __construct(AuthorizationCheckerInterface $authorizationChecker, MailboxManager $mailboxManager)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->mailboxManager = $mailboxManager;
    }

    /**
     * Checks if the voter supports the given attribute.
     *
     * @param mixed $attribute An attribute (usually the attribute name string)
     *
     * @return bool true if this Voter supports the attribute, false otherwise
     */
    protected function supportsAttribute($attribute)
    {
        return in_array($attribute, [BasicPermission::VIEW, BasicPermission::EDIT], true);
    }

    /**
     * Checks if the voter supports the given class.
     *
     * @param string $class A class name
     *
     * @return bool true if this Voter can process the class
     */
    protected function supportsClass($class)
    {
        return in_array($class, self::SUPPORTED_CLASSES, true);
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!is_object($object)) {
            return self::ACCESS_ABSTAIN;
        }

        $objectClass = ClassUtils::getClass($object);
        if (!$this->supportsClass($objectClass)) {
            return self::ACCESS_ABSTAIN;
        }

        foreach ($attributes as $attribute) {
            if (!$this->supportsAttribute($attribute)) {
                return self::ACCESS_ABSTAIN;
            }
        }

        $object = $this->convertToSupportedObject($object, $objectClass);

        /** @var EmailUser[] $emailUsers */
        $emailUsers = $object->getEmailUsers();
        foreach ($attributes as $attribute) {
            foreach ($emailUsers as $emailUser) {
                if ($this->authorizationChecker->isGranted($attribute, $emailUser)) {
                    return self::ACCESS_GRANTED;
                }

                $mailbox = $emailUser->getMailboxOwner();
                if ($mailbox !== null && $token instanceof UsernamePasswordOrganizationToken) {
                    $mailboxes = $this->mailboxManager->findAvailableMailboxes(
                        $token->getUser(),
                        $token->getOrganization()
                    );

                    if (in_array($mailbox, $mailboxes)) {
                        return self::ACCESS_GRANTED;
                    }
                }
            }
        }

        return self::ACCESS_DENIED;
    }

    /**
     * @param $object
     * @param $objectClass
     *
     * @return mixed
     */
    protected function convertToSupportedObject($object, $objectClass)
    {
        if ($objectClass === EmailBody::CLASS_NAME) {
            $object = $object->getEmail();
        }
        if ($objectClass === EmailAttachment::CLASS_NAME) {
            $object = $object->getEmailBody()->getEmail();
        }

        return $object;
    }
}
