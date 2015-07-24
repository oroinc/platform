<?php

namespace Oro\Bundle\SecurityBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\ClassUtils;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\AclInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Form\Model\Share;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;

class ShareHandler
{
    /** @var FormInterface */
    protected $form;

    /** @var Request */
    protected $request;

    /** @var MutableAclProvider */
    protected $aclProvider;

    /** @var ObjectManager */
    protected $manager;

    /** @var ConfigProvider */
    protected $configProvider;

    /** @var array */
    protected $shareScopes;

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param MutableAclProvider $aclProvider
     * @param ObjectManager $manager
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        MutableAclProvider $aclProvider,
        ObjectManager $manager,
        ConfigProvider $configProvider
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->aclProvider = $aclProvider;
        $this->manager = $manager;
        $this->configProvider = $configProvider;
    }

    /**
     * Process form
     *
     * @param Share $model
     * @param object $entity
     *
     * @return bool
     */
    public function process(Share $model, $entity)
    {
        $this->prepareForm($entity);
        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->setData($model);
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                $this->onSuccess($model, $entity);

                return true;
            }
        } else {
            $objectIdentity = ObjectIdentity::fromDomainObject($entity);
            try {
                $acl = $this->aclProvider->findAcl($objectIdentity);
            } catch (AclNotFoundException $e) {
                // no ACL found, do nothing
                $acl = null;
            }
            $this->applyEntities($model, $acl);
        }

        return false;
    }

    /**
     * @param Share $model
     * @param object $entity
     */
    protected function onSuccess($model, $entity)
    {
        $objectIdentity = ObjectIdentity::fromDomainObject($entity);
        try {
            $acl = $this->aclProvider->findAcl($objectIdentity);
        } catch (AclNotFoundException $e) {
            $acl = $this->aclProvider->createAcl($objectIdentity);
        }

        $oldSids = $this->extractSids($acl);
        // saves original value of old sids to extract new added elements
        $oldSidsCopy = $oldSids;
        $newSids = $this->generateSids($model);
        // $oldSids - $newSids: to delete
        foreach (array_diff($oldSids, $newSids) as $sid) {
            $acl->deleteObjectAce(array_search($sid, $oldSids, true));
            // fills array again because index was recalculated
            $oldSids = $this->extractSids($acl);
        }
        // $newSids - $oldSids: to insert
        foreach (array_diff($newSids, $oldSidsCopy) as $sid) {
            $acl->insertObjectAce($sid, EntityMaskBuilder::MASK_VIEW_SYSTEM);
        }

        $this->aclProvider->updateAcl($acl);
    }

    /**
     * @param object $entity
     */
    protected function prepareForm($entity)
    {
        $entityName = ClassUtils::getClass($entity);
        $this->shareScopes = $this->configProvider->hasConfig($entityName)
            ? $this->configProvider->getConfig($entityName)->get('share_scopes')
            : null;
        if (!$this->shareScopes) {
            throw new \LogicException('Sharing scopes are disabled');
        }

        if (!in_array('user', $this->shareScopes, true)) {
            $this->form->remove('users');
        }

        if (!in_array('business_unit', $this->shareScopes, true)) {
            $this->form->remove('businessunits');
        }
    }

    /**
     * Extracts entities from SecurityIdentities and apply them to form model
     *
     * @param Share $model
     * @param AclInterface|null $acl
     */
    protected function applyEntities(Share $model, AclInterface $acl = null)
    {
        if (!$acl) {
            return;
        }

        $usernames = [];
        $buIds = [];
        foreach ($acl->getObjectAces() as $ace) {
            /** @var $ace Entry */
            $securityIdentity = $ace->getSecurityIdentity();
            if ($securityIdentity instanceof UserSecurityIdentity) {
                $usernames[] = $securityIdentity->getUsername();
            } elseif ($securityIdentity instanceof BusinessUnitSecurityIdentity) {
                $buIds[] = $securityIdentity->getId();
            }
        }
        if ($usernames) {
            /** @var $repo UserRepository */
            $repo = $this->manager->getRepository('OroUserBundle:User');
            $users = $repo->findUsersByUsernames($usernames);
            $model->setUsers($users);
        }
        if ($buIds) {
            /** @var $repo BusinessUnitRepository */
            $repo = $this->manager->getRepository('OroOrganizationBundle:BusinessUnit');
            $businessUnits = $repo->getBusinessUnits($buIds);
            $model->setBusinessunits($businessUnits);
        }
        $this->form->setData($model);
    }

    /**
     * Extracts SIDs from ACL depending on entity config to prevent deletion of sharing when sharing scope is changed.
     *
     * @param AclInterface $acl
     *
     * @return array
     */
    protected function extractSids(AclInterface $acl)
    {
        $sids = [];
        foreach ($acl->getObjectAces() as $ace) {
            /** @var Entry $ace */
            $sid = $ace->getSecurityIdentity();
            if ($this->isSidApplicable($sid)) {
                $sids[] = $sid;
            }
        }

        return $sids;
    }

    /**
     * Determines if SID can be manipulated depending on entity config to prevent deletion of sharing
     * when sharing scope is changed.
     *
     * @param SecurityIdentityInterface $sid
     *
     * @return bool
     */
    protected function isSidApplicable(SecurityIdentityInterface $sid)
    {
        return (
            $this->form->has('users') &&
            $sid instanceof UserSecurityIdentity &&
            in_array('user', $this->shareScopes, true)
        )
        ||
        (
            $this->form->has('businessunits') &&
            $sid instanceof BusinessUnitSecurityIdentity &&
            in_array('business_unit', $this->shareScopes, true)
        );
    }

    /**
     * Generate SIDs from entities
     *
     * @param Share $model
     *
     * @return array
     */
    protected function generateSids(Share $model)
    {
        $newSids = [];
        $users = $model->getUsers();
        foreach ($users as $user) {
            $newSids[] = UserSecurityIdentity::fromAccount($user);
        }
        $businessUnits = $model->getBusinessunits();
        foreach ($businessUnits as $businessUnit) {
            $newSids[] = BusinessUnitSecurityIdentity::fromBusinessUnit($businessUnit);
        }

        return $newSids;
    }
}
