<?php

namespace Oro\Bundle\EmailBundle\Acl\Voter;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EmailBundle\Entity\Email;
use Oro\Bundle\EmailBundle\Entity\EmailAttachment;
use Oro\Bundle\EmailBundle\Entity\EmailBody;
use Oro\Bundle\EmailBundle\Entity\EmailUser;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

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
     * Checks if the voter supports the given attribute.
     *
     * @param mixed $attribute An attribute (usually the attribute name string)
     *
     * @return bool true if this Voter supports the attribute, false otherwise
     */
    protected function supportsAttribute($attribute)
    {
        return in_array($attribute, ['VIEW', 'EDIT'], true);
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
                if ($this->container->get('security.authorization_checker')->isGranted($attribute, $emailUser)) {
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
