<?php

namespace Oro\Bundle\WorkflowBundle\Acl\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class WorkflowEntityVoter implements VoterInterface
{
    /**
     * @var array
     */
    protected $supportedAttributes = array('EDIT', 'DELETE');

    /**
     * @var array
     */
    protected $supportedClasses;

    /**
     * {@inheritDoc}
     */
    public function supportsAttribute($attribute)
    {
        return in_array($attribute, $this->supportedAttributes);
    }

    /**
     * {@inheritDoc}
     */
    public function supportsClass($class)
    {
        if (null === $this->supportedClasses) {
            $this->supportedClasses = array(); // TODO Create repository method and use it here
        }

        return in_array($class, $this->supportedClasses);
    }

    /**
     * Returns the vote for the given parameters.
     *
     * This method must return one of the following constants:
     * ACCESS_GRANTED, ACCESS_DENIED, or ACCESS_ABSTAIN.
     *
     * @param TokenInterface $token A TokenInterface instance
     * @param object $object The object to secure
     * @param array $attributes An array of attributes associated with the method being invoked
     *
     * @return integer either ACCESS_GRANTED, ACCESS_ABSTAIN, or ACCESS_DENIED
     */

    /**
     * {@inheritDoc}
     */
    public function vote(TokenInterface $token, $object, array $attributes)
    {
        // TODO: Implement vote() method.

        return self::ACCESS_ABSTAIN;
    }
}
