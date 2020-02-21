<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Extension\AclExtensionInterface;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;

/**
 * The root ACL wrapper that uses a memory cache to improve performance
 * of merging a main ACL ACEs with a root ACL ACEs.
 */
class RootAclWrapper
{
    /** @var Acl */
    private $acl;

    /** @var SecurityIdentityToStringConverterInterface */
    private $sidConverter;

    /** @var array [sid => [[ace, ace index], ...], ...] */
    private $groupedAces;

    /** @var array [field name => [sid => [[ace, ace index], ...], ...], ...] */
    private $groupedFieldAces = [];

    /**
     * @param Acl                                        $acl
     * @param SecurityIdentityToStringConverterInterface $sidConverter
     */
    public function __construct(Acl $acl, SecurityIdentityToStringConverterInterface $sidConverter)
    {
        $this->acl = $acl;
        $this->sidConverter = $sidConverter;
    }

    /**
     * Returns the object identity associated with this ACL.
     *
     * @return ObjectIdentityInterface
     */
    public function getObjectIdentity()
    {
        return $this->acl->getObjectIdentity();
    }

    /**
     * @param AclExtensionInterface $ext
     * @param EntryInterface[]      $aces
     * @param string|null           $field
     *
     * @return EntryInterface[]
     */
    public function addRootAces(AclExtensionInterface $ext, array $aces, $field = null)
    {
        $rootAces = $this->getAces($field);
        $groupedRootAces = $this->getGroupedAces($rootAces, $field);

        $resultAces = $rootAces;
        foreach ($aces as $ace) {
            $processed = false;
            $sid = $this->sidConverter->convert($ace->getSecurityIdentity());
            if (isset($groupedRootAces[$sid])) {
                /** @var EntryInterface $rootAce */
                foreach ($groupedRootAces[$sid] as [$rootAce, $rootAceIndex]) {
                    if ($ext->getServiceBits($ace->getMask()) === $ext->getServiceBits($rootAce->getMask())) {
                        $resultAces[$rootAceIndex] = $ace;
                        $processed = true;
                        break;
                    }
                }
            }
            if (!$processed) {
                $resultAces[] = $ace;
            }
        }

        return $resultAces;
    }

    /**
     * @param string|null $field
     *
     * @return EntryInterface[]
     */
    private function getAces($field = null)
    {
        if ($field) {
            $rootAces = $this->acl->getObjectFieldAces($field);
            if (empty($rootAces)) {
                $rootAces = $this->acl->getClassFieldAces($field);
            }

            return $rootAces;
        }

        return $this->acl->getObjectAces();
    }

    /**
     * @param EntryInterface[] $aces
     * @param string|null      $field
     *
     * @return array [sid => [[ace, ace index], ...], ...]
     */
    private function getGroupedAces($aces, $field = null)
    {
        if ($field) {
            if (!isset($this->groupedFieldAces[$field])) {
                $this->groupedFieldAces[$field] = $this->buildGroupedAces($aces);
            }

            return $this->groupedFieldAces[$field];
        }

        if (null === $this->groupedAces) {
            $this->groupedAces = $this->buildGroupedAces($aces);
        }

        return $this->groupedAces;
    }

    /**
     * @param array $aces
     *
     * @return array [sid => [[ace, ace index], ...], ...]
     */
    private function buildGroupedAces($aces)
    {
        $groupedAces = [];
        foreach ($aces as $aceIndex => $ace) {
            $groupedAces[$this->sidConverter->convert($ace->getSecurityIdentity())][] = [$ace, $aceIndex];
        }

        return $groupedAces;
    }
}
