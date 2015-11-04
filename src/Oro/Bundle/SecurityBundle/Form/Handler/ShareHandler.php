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
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\SecurityBundle\Acl\Extension\EntityMaskBuilder;
use Oro\Bundle\SecurityBundle\Form\Model\Share;
use Oro\Bundle\UserBundle\Entity\User;

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
        $entityName = ClassUtils::getClass($entity);
        $this->shareScopes = $this->configProvider->hasConfig($entityName)
            ? $this->configProvider->getConfig($entityName)->get('share_scopes')
            : null;
        if (!$this->shareScopes) {
            throw new \LogicException('Sharing scopes are disabled');
        }
        if (in_array($this->request->getMethod(), ['POST', 'PUT'], true)) {
            $this->form->setData($model);
            $this->form->submit($this->request);
            if ($this->form->isValid()) {
                $this->onSuccess($model, $entity);

                return true;
            }
        } else {
            $this->form->get('entityClass')->setData($entityName);
            $this->form->get('entityId')->setData($entity->getId());
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
            $acl->insertObjectAce($sid, $this->getMaskBySid($sid));
        }

        $this->aclProvider->updateAcl($acl);
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
        foreach ($acl->getObjectAces() as $key => $ace) {
            /** @var Entry $ace */
            $sid = $ace->getSecurityIdentity();
            if ($this->isSidApplicable($sid)) {
                $sids[$key] = $sid;
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
            $sid instanceof UserSecurityIdentity &&
            in_array(Share::SHARE_SCOPE_USER, $this->shareScopes, true)
        )
        ||
        (
            $sid instanceof BusinessUnitSecurityIdentity &&
            in_array(Share::SHARE_SCOPE_BUSINESS_UNIT, $this->shareScopes, true)
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
        foreach ($model->getEntities() as $entity) {
            if ($entity instanceof User) {
                $newSids[] = UserSecurityIdentity::fromAccount($entity);
            } elseif ($entity instanceof BusinessUnit) {
                $newSids[] = BusinessUnitSecurityIdentity::fromBusinessUnit($entity);
            }
        }

        return $newSids;
    }

    /**
     * Get VIEW mask by Security Identity
     *
     * @param SecurityIdentityInterface $sid
     *
     * @return int
     */
    protected function getMaskBySid(SecurityIdentityInterface $sid)
    {
        if ($sid instanceof UserSecurityIdentity) {
            return EntityMaskBuilder::MASK_VIEW_BASIC;
        } elseif ($sid instanceof BusinessUnitSecurityIdentity) {
            return EntityMaskBuilder::MASK_VIEW_LOCAL;
        } else {
            return EntityMaskBuilder::IDENTITY;
        }
    }
}
