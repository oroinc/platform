<?php

namespace Oro\Bundle\SecurityBundle\Form\Handler;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;

use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
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
                foreach ($acl->getObjectAces() as $ace) {
                    /** @var $ace Entry */
                    $securityIdentity = $ace->getSecurityIdentity();
                    $usernames[] = $securityIdentity->getUsername();
                }
                if ($usernames) {
                    /** @var $repo UserRepository */
                    $repo = $this->manager->getRepository('OroUserBundle:User');
                    $users = $repo->findUsersByUsernames($usernames);
                    $model->setUsers(new ArrayCollection($users));
                    $this->form->setData($model);
                }
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
        $users = $this->getUsers($model);
        foreach ($users as $user) {
            $newSids[] = UserSecurityIdentity::fromAccount($user);
        }
        // $oldSids - $newSids: to delete
        foreach (array_diff($oldSids, $newSids) as $sid) {
            $acl->deleteObjectAce(array_search($sid, $oldSids));
            // fills array again because index was recalculated
            $oldSids = $fillOldSidsHandler($acl);
        }
        // $newSids - $oldSids: to insert
        foreach (array_diff($newSids, $oldSidsCopy) as $sid) {
            $acl->insertObjectAce($sid, EntityMaskBuilder::MASK_VIEW_BASIC);
        }

        $this->aclProvider->updateAcl($acl);

        if ((int)$objectIdentity->getIdentifier()) {
            /** @var \Doctrine\ORM\EntityRepository $repo */
            $repo = $this->manager->getRepository('OroSecurityBundle:AclEntry');
            $queryBuilder = $repo->createQueryBuilder('ae');
            $aceIds = [];

            foreach ($acl->getObjectAces() as $ace) {
                /** @var Entry $ace */
                $aceIds[] = $ace->getId();
            }

            if ($aceIds) {
                $queryBuilder
                    ->update()
                    ->set('ae.recordId', ':recordId')
                    ->where($queryBuilder->expr()->in('ae.id', $aceIds))
                    ->setParameter('recordId', $objectIdentity->getIdentifier());
                $queryBuilder->getQuery()->execute();
            }
        }
    }

    /**
     * @param Share $model
     * @return array
     */
    protected function getUsers($model)
    {
        $users = [];
        $users = array_merge($users, $model->getUsers()->toArray());
        foreach ($model->getBusinessunits() as $businessUnit) {
            /** @var $businessUnit BusinessUnit */
            $users = array_merge($users, $businessUnit->getUsers()->toArray());
        }

        return $users;
    }
}
