<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\PermissionGrantingStrategyInterface;

class RootBasedAclWrapper implements AclInterface
{
    /**
     * @var Acl
     */
    private $acl;

    /**
     * @var Acl
     */
    private $rootAcl;

    /**
     * @var PermissionGrantingStrategyInterface
     */
    private $permissionGrantingStrategy;

    /**
     * Constructor
     *
     * @param Acl $acl
     * @param Acl $rootAcl
     */
    public function __construct(Acl $acl, Acl $rootAcl)
    {
        $this->acl = $acl;
        $this->rootAcl = $rootAcl;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassAces()
    {
        $aces = $this->acl->getClassAces();
        $this->addRootAces($aces);

        return $aces;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassFieldAces($field)
    {
        $aces = $this->acl->getClassFieldAces($field);
        $this->addRootAces($aces, $field);

        return $aces;
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectAces()
    {
        return $this->acl->getObjectAces();
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectFieldAces($field)
    {
        return $this->acl->getObjectFieldAces($field);
    }

    /**
     * {@inheritdoc}
     */
    public function getObjectIdentity()
    {
        /**
         *  @todo: Check ObjectIdentity for ACL records from the database.
         *         It is quite possible we will have to return rootAcl ObjectIdentity to
         *         turn additional ACL masks check by AclExtension::adaptRootMask() method.
         */
        if (!count($this->acl->getClassAces()) && !count($this->acl->getObjectAces())) {
            return $this->rootAcl->getObjectIdentity();
        }

        return $this->acl->getObjectIdentity();
    }

    /**
     * {@inheritdoc}
     */
    public function getParentAcl()
    {
        return $this->acl->getParentAcl();
    }

    /**
     * {@inheritdoc}
     */
    public function isEntriesInheriting()
    {
        return $this->acl->isEntriesInheriting();
    }

    /**
     * {@inheritdoc}
     */
    public function isFieldGranted($field, array $masks, array $securityIdentities, $administrativeMode = false)
    {
        return $this->getPermissionGrantingStrategy()
            ->isFieldGranted($this, $field, $masks, $securityIdentities, $administrativeMode);
    }

    /**
     * {@inheritdoc}
     */
    public function isGranted(array $masks, array $securityIdentities, $administrativeMode = false)
    {
        return $this->getPermissionGrantingStrategy()
            ->isGranted($this, $masks, $securityIdentities, $administrativeMode);
    }

    /**
     * {@inheritdoc}
     */
    public function isSidLoaded($securityIdentities)
    {
        return $this->acl->isSidLoaded($securityIdentities);
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        throw new \LogicException('Not supported.');
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        throw new \LogicException('Not supported.');
    }

    /**
     * Gets the permission granting strategy implementation
     *
     * @return PermissionGrantingStrategyInterface
     */
    protected function getPermissionGrantingStrategy()
    {
        if ($this->permissionGrantingStrategy === null) {
            // Unfortunately permissionGrantingStrategy property is private, so the only way
            // to get it is to use the reflection
            $r = new \ReflectionClass(get_class($this->acl));
            $p = $r->getProperty('permissionGrantingStrategy');
            $p->setAccessible(true);
            $this->permissionGrantingStrategy = $p->getValue($this->acl);
        }

        return $this->permissionGrantingStrategy;
    }

    /**
     * @param EntryInterface[] $aces
     * @param string|null      $field
     */
    protected function addRootAces(array &$aces, $field = null)
    {
        /** @var PermissionGrantingStrategy $permissionGrantingStrategy */
        $permissionGrantingStrategy = $this->getPermissionGrantingStrategy();
        $ext = $permissionGrantingStrategy->getContext()->getAclExtension();

        $groupedAces = [];
        foreach ($aces as $ace) {
            $groupedAces[(string)$ace->getSecurityIdentity()][] = $ace;
        }

        /** @var EntryInterface[] $rootAces */
        $rootAces = $field
            ? $this->rootAcl->getObjectFieldAces($field)
            : $this->rootAcl->getObjectAces();

        $rootAces = $field && empty($rootAces)
            ? $this->rootAcl->getClassFieldAces($field)
            : $rootAces;

        foreach ($rootAces as $rootAce) {
            $exists = false;
            $rootSid = (string)$rootAce->getSecurityIdentity();
            if (isset($groupedAces[$rootSid])) {
                /** @var EntryInterface $ace */
                foreach ($groupedAces[$rootSid] as $ace) {
                    if ($ext->getServiceBits($rootAce->getMask()) === $ext->getServiceBits($ace->getMask())) {
                        $exists = true;
                        break;
                    }
                }
            }
            if (!$exists) {
                $aces[] = $rootAce;
            }
        }
    }
}
