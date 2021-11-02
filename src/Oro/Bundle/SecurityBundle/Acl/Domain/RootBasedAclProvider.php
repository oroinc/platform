<?php

namespace Oro\Bundle\SecurityBundle\Acl\Domain;

use Oro\Bundle\SecurityBundle\Acl\Cache\UnderlyingAclCache;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Extends the default Symfony ACL provider with support of a root ACL.
 * It means that the special ACL named "root" will be used in case when more sufficient ACL was not found.
 */
class RootBasedAclProvider implements AclProviderInterface, ResetInterface
{
    private AclProviderInterface $baseAclProvider;
    private UnderlyingAclCache $underlyingCache;
    private ObjectIdentityFactory $objectIdentityFactory;
    private SecurityIdentityToStringConverterInterface $sidConverter;
    private FullAccessFieldRootAclBuilder $fullAccessFieldRootAclBuilder;

    /** @var RootBasedAclWrapper[] */
    private $rootBasedAclWrappers = [];

    /** @var RootAclWrapper[] */
    private $rootAclWrappers = [];

    public function __construct(
        ObjectIdentityFactory $objectIdentityFactory,
        SecurityIdentityToStringConverterInterface $sidConverter,
        FullAccessFieldRootAclBuilder $fullAccessFieldRootAclBuilder
    ) {
        $this->objectIdentityFactory = $objectIdentityFactory;
        $this->sidConverter = $sidConverter;
        $this->fullAccessFieldRootAclBuilder = $fullAccessFieldRootAclBuilder;
    }

    /**
     * Sets Underlying cache.
     */
    public function setUnderlyingCache(UnderlyingAclCache $underlyingCache)
    {
        $this->underlyingCache = $underlyingCache;
    }

    /**
     * Sets the base ACL provider.
     */
    public function setBaseAclProvider(AclProviderInterface $provider)
    {
        $this->baseAclProvider = $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->rootBasedAclWrappers = [];
        $this->rootAclWrappers = [];
    }

    /**
     * {@inheritdoc}
     */
    public function findChildren(ObjectIdentityInterface $parentOid, $directChildrenOnly = false)
    {
        return $this->baseAclProvider->findChildren($parentOid, $directChildrenOnly);
    }

    /**
     * {@inheritdoc}
     */
    public function findAcl(ObjectIdentityInterface $oid, array $sids = [])
    {
        $rootOid = $this->objectIdentityFactory->root($oid);
        try {
            $acl = $this->getAcl($oid, $sids, $rootOid);
        } catch (AclNotFoundException $noAcl) {
            try {
                // Try to get ACL for underlying object
                $underlyingOid = $this->objectIdentityFactory->underlying($oid);
                $acl = $this->getAcl($underlyingOid, $sids, $rootOid);
                $this->underlyingCache->cacheUnderlying($oid);
            } catch (\Exception $noUnderlyingAcl) {
                // Try to get ACL for root object
                try {
                    $this->baseAclProvider->cacheEmptyAcl($oid, $sids);

                    return $this->baseAclProvider->findAcl($rootOid, $sids);
                } catch (AclNotFoundException $noRootAcl) {
                    throw new AclNotFoundException(
                        sprintf('There is no ACL for %s. The root ACL %s was not found as well.', $oid, $rootOid),
                        0,
                        $noAcl
                    );
                }
            }
        }

        return $acl;
    }

    /**
     * {@inheritdoc}
     */
    public function findAcls(array $oids, array $sids = [])
    {
        return $this->baseAclProvider->findAcls($oids, $sids);
    }

    /**
     * Get Acl based on given OID and Parent OID.
     *
     * @param ObjectIdentityInterface $oid
     * @param array                   $sids
     * @param ObjectIdentityInterface $rootOid
     *
     * @return AclInterface
     */
    private function getAcl(ObjectIdentityInterface $oid, array $sids, ObjectIdentityInterface $rootOid)
    {
        if ($this->underlyingCache->isUnderlying($oid)) {
            return $this->getAcl($this->objectIdentityFactory->underlying($oid), $sids, $rootOid);
        }

        $acl = $this->baseAclProvider->findAcl($oid, $sids);

        try {
            $rootAcl = $this->baseAclProvider->findAcl($rootOid, $sids);
        } catch (AclNotFoundException $noRootAcl) {
            return $acl;
        }

        $this->fullAccessFieldRootAclBuilder->fillFieldRootAces($rootAcl, $sids);
        if ($this->baseAclProvider->isEmptyAcl($acl)) {
            return $rootAcl;
        }

        $rootAclKey = spl_object_hash($rootAcl);
        $key = spl_object_hash($acl) . '_' . $rootAclKey;
        if (!isset($this->rootBasedAclWrappers[$key])) {
            if (!isset($this->rootAclWrappers[$rootAclKey])) {
                $this->rootAclWrappers[$rootAclKey] = new RootAclWrapper($rootAcl, $this->sidConverter);
            }
            $this->rootBasedAclWrappers[$key] = new RootBasedAclWrapper($acl, $this->rootAclWrappers[$rootAclKey]);
        }

        return $this->rootBasedAclWrappers[$key];
    }
}
