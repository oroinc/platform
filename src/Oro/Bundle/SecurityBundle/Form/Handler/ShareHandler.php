<?php

namespace Oro\Bundle\SecurityBundle\Form\Handler;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

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

    /**
     * @param FormInterface $form
     * @param Request $request
     * @param MutableAclProvider $aclProvider
     * @param ObjectManager $manager
     */
    public function __construct(
        FormInterface $form,
        Request $request,
        MutableAclProvider $aclProvider,
        ObjectManager $manager
    ) {
        $this->form = $form;
        $this->request = $request;
        $this->aclProvider = $aclProvider;
        $this->manager = $manager;
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
        if (in_array($this->request->getMethod(), ['POST', 'PUT'])) {
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

            if ($acl) {
                $usernames = [];
                $buIds = [];
                foreach ($acl->getObjectAces() as $ace) {
                    /** @var $ace Entry */
                    $securityIdentity = $ace->getSecurityIdentity();
                    if ($securityIdentity instanceof UserSecurityIdentity) {
                        $usernames[] = $securityIdentity->getUsername();
                    } elseif ($securityIdentity instanceof BusinessUnitSecurityIdentity) {
                        $buIds = $securityIdentity->getId();
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

        // @todo add handling of OrganizationSecurityIdentity, BusinessUnitSecurityIdentity
        $fillOldSidsHandler = function($acl) {
            $oldSids = [];
            foreach ($acl->getObjectAces() as $ace) {
                /** @var Entry $ace */
                $oldSids[] = $ace->getSecurityIdentity();
            }

            return $oldSids;
        };
        $oldSids = $fillOldSidsHandler($acl);
        // saves original value of old sids to extract new added elements
        $oldSidsCopy = $oldSids;

        $newSids = [];
        $users = $model->getUsers();
        foreach ($users as $user) {
            $newSids[] = UserSecurityIdentity::fromAccount($user);
        }
        $businessUnits = $model->getBusinessunits();
        foreach ($businessUnits as $businessUnit) {
            $newSids[] = BusinessUnitSecurityIdentity::fromBusinessUnit($businessUnit);
        }
        // $oldSids - $newSids: to delete
        foreach (array_diff($oldSids, $newSids) as $sid) {
            $acl->deleteObjectAce(array_search($sid, $oldSids, true));
            // fills array again because index was recalculated
            $oldSids = $fillOldSidsHandler($acl);
        }
        // $newSids - $oldSids: to insert
        foreach (array_diff($newSids, $oldSidsCopy) as $sid) {
            $acl->insertObjectAce($sid, EntityMaskBuilder::MASK_VIEW_BASIC);
        }

        $this->aclProvider->updateAcl($acl);
    }
}
