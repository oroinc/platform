<?php

namespace Oro\Bundle\SecurityBundle\Twig;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\AclCacheInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class OroSecurityExtension extends \Twig_Extension
{
    /** @var ObjectManager */
    protected $manager;

    /** @var AclCacheInterface */
    protected $aclCache;

    /** @var SecurityFacade */
    protected $securityFacade;
    
    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param ObjectManager $manager
     * @param AclCacheInterface $aclCache
     * @param SecurityFacade $securityFacade
     * @param NameFormatter $nameFormatter
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ObjectManager $manager,
        AclCacheInterface $aclCache,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter,
        TranslatorInterface $translator
    ) {
        $this->manager = $manager;
        $this->aclCache = $aclCache;
        $this->securityFacade = $securityFacade;
        $this->nameFormatter = $nameFormatter;
        $this->translator = $translator;
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            'resource_granted' => new \Twig_Function_Method($this, 'checkResourceIsGranted'),
            'format_share_scopes' => new \Twig_Function_Method($this, 'formatShareScopes'),
            'oro_share_count' => new \Twig_Function_Method($this, 'getShareCount'),
            'oro_shared_with_name' => new \Twig_Function_Method($this, 'getSharedWithName'),
        );
    }

    /**
     * Check if ACL resource is granted for current user
     *
     * @param string|string[] $attributes Can be a role name(s), permission name(s), an ACL annotation id
     *                                    or something else, it depends on registered security voters
     * @param mixed $object A domain object, object identity or object identity descriptor (id:type)
     *
     * @return bool
     */
    public function checkResourceIsGranted($attributes, $object = null)
    {
        return $this->securityFacade->isGranted($attributes, $object);
    }

    /**
     * Formats json encoded string of share scopes entity config attribute
     *
     * @param string|array|null $value
     * @param string $labelType
     *
     * @return string
     */
    public function formatShareScopes($value, $labelType = 'label')
    {
        if (!$value) {
            return $this->translator->trans('oro.security.share_scopes.not_available');
        }
        $result = [];
        if (is_string($value)) {
            $shareScopes = json_decode($value);
        } elseif (is_array($value)) {
            $shareScopes = $value;
        } else {
            throw new \LogicException('$value must be string or array');
        }

        foreach ($shareScopes as $shareScope) {
            $result[] = $this->translator->trans('oro.security.share_scopes.' . $shareScope . '.' . $labelType);
        }

        return implode(', ', $result);
    }

    /**
     * @param object $object
     *
     * @return int
     */
    public function getShareCount($object)
    {
        $oid = ObjectIdentity::fromDomainObject($object);
        /** @var Acl $acl */
        $acl = $this->aclCache->getFromCacheByIdentity($oid);
        $count = 0;
        if ($acl && $acl->getObjectAces()) {
            $count = count($acl->getObjectAces());
        }

        return $count;
    }

    /**
     * @param object $object
     *
     * @return string
     *
     * @throws \Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException
     */
    public function getSharedWithName($object)
    {
        $oid = ObjectIdentity::fromDomainObject($object);
        /** @var Acl $acl */
        $acl = $this->aclCache->getFromCacheByIdentity($oid);
        $name = '';
        $objectAces = $acl->getObjectAces();
        if ($acl && $objectAces) {
            usort(
                $objectAces,
                [$this, 'compareEntries']
            );
            /** @var Entry $entry */
            $entry = $objectAces[0];
            $sid = $entry->getSecurityIdentity();
            $name = $this->getFormattedName($sid);
        }

        return $name;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string
     */
    public function getName()
    {
        return 'oro_security_extension';
    }

    /**
     * @param Entry $entryA
     * @param Entry $entryB
     *
     * @return int
     */
    protected function compareEntries(Entry $entryA, Entry $entryB)
    {
        $sidA = $entryA->getSecurityIdentity();
        $sidB = $entryB->getSecurityIdentity();
        if ($sidA instanceof UserSecurityIdentity && $sidB instanceof UserSecurityIdentity) {
            return strcmp($sidA->getUsername(), $sidB->getUsername());
        } elseif ($sidA instanceof BusinessUnitSecurityIdentity && $sidB instanceof BusinessUnitSecurityIdentity) {
            $idA = (int) $sidA->getId();
            $idB = (int) $sidB->getId();

            return $idA < $idB ? -1 : 1;
        } elseif ($sidA instanceof UserSecurityIdentity && $sidB instanceof BusinessUnitSecurityIdentity) {
            return 1;
        } elseif ($sidA instanceof BusinessUnitSecurityIdentity && $sidB instanceof UserSecurityIdentity) {
            return -1;
        } else {
            return 0;
        }
    }

    /**
     * @param SecurityIdentityInterface $sid
     *
     * @return string
     */
    protected function getFormattedName(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity && $sid->getUsername()) {
            $user =  $this->manager->getRepository('OroUserBundle:User')
                ->findOneBy(['username' => $sid->getUsername()]);
            if ($user) {
                return $this->nameFormatter->format($user);
            }
        } elseif ($sid instanceof BusinessUnitSecurityIdentity) {
            $businessUnit = $this->manager->getRepository('OroOrganizationBundle:BusinessUnit')->find($sid->getId());
            if ($businessUnit) {
                return $businessUnit->getName();
            }
        }

        return '';
    }
}
