<?php

namespace Oro\Bundle\EmailBundle\Acl\Voter;

use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;

class EmailVoter implements VoterInterface
{
    /**
     * @var array
     */
    protected static $supportedClasses = [
        'Oro\Bundle\EmailBundle\Entity\Email',
        'Oro\Bundle\EmailBundle\Entity\EmailBody',
        'Oro\Bundle\EmailBundle\Entity\EmailAttachment'
    ];

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, ['VIEW', 'EDIT'], true);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        return in_array($class, self::$supportedClasses, true);
    }

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        if (!$object || !is_object($object)) {
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
                if ($this->container->get('oro_security.security_facade')->isGranted($attribute, $emailUser)) {
                    return self::ACCESS_GRANTED;
                }
                if ($mailbox = $emailUser->getMailboxOwner() !== null
                    && $token instanceof UsernamePasswordOrganizationToken
                ) {
                    $manager = $this->container->get('oro_email.mailbox.manager');
                    $mailboxes = $manager->findAvailableMailboxes(
                        $token->getUser(),
                        $token->getOrganizationContext()
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
