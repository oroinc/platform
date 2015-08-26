<?php

namespace Oro\Bundle\SecurityBundle\Extension;

use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Security\Acl\Dbal\MutableAclProvider;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\EntityBundle\Tools\EntityRoutingHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Repository\BusinessUnitRepository;
use Oro\Bundle\SecurityBundle\Acl\Domain\BusinessUnitSecurityIdentity;
use Oro\Bundle\UserBundle\Entity\Repository\UserRepository;
use Oro\Bundle\UserBundle\Entity\User;

class ShareDatasource implements DatasourceInterface
{
    const TYPE = 'share';

    /** @var MutableAclProvider */
    protected $aclProvider;

    /** @var EntityRoutingHelper */
    protected $routingHelper;

    /** @var ObjectManager */
    protected $objectManager;

    /** @var ConfigManager */
    protected $configManager;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var AttachmentManager */
    protected $attachmentManager;

    /** @var object */
    protected $object;

    /**
     * @param MutableAclProvider $aclProvider
     * @param EntityRoutingHelper $routingHelper
     * @param ObjectManager $objectManager
     * @param ConfigManager $configManager
     * @param TranslatorInterface $translator
     * @param AttachmentManager $attachmentManager
     */
    public function __construct(
        MutableAclProvider $aclProvider,
        EntityRoutingHelper $routingHelper,
        ObjectManager $objectManager,
        ConfigManager $configManager,
        TranslatorInterface $translator,
        AttachmentManager $attachmentManager
    ) {
        $this->aclProvider = $aclProvider;
        $this->routingHelper = $routingHelper;
        $this->objectManager = $objectManager;
        $this->configManager = $configManager;
        $this->translator = $translator;
        $this->attachmentManager = $attachmentManager;
    }

    /**
     * {@inheritDoc}
     */
    public function process(DatagridInterface $grid, array $config)
    {
        $parameters = $grid->getParameters();
        $this->object = $this->routingHelper->getEntity($parameters->get('entityClass'), $parameters->get('entityId'));
        $grid->setDatasource(clone $this);
    }

    /**
     * @return ResultRecordInterface[]
     */
    public function getResults()
    {
        $rows = [];

        $objectIdentity = ObjectIdentity::fromDomainObject($this->object);
        try {
            $acl = $this->aclProvider->findAcl($objectIdentity);
        } catch (AclNotFoundException $e) {
            // no ACL found, do nothing
            $acl = null;
        }
        if ($acl) {
            $buIds = [];
            $usernames = [];
            foreach ($acl->getObjectAces() as $ace) {
                /** @var $ace Entry */
                $securityIdentity = $ace->getSecurityIdentity();
                if ($securityIdentity instanceof UserSecurityIdentity) {
                    $usernames[] = $securityIdentity->getUsername();
                } elseif ($securityIdentity instanceof BusinessUnitSecurityIdentity) {
                    $buIds[] = $securityIdentity->getId();
                }
            }
            if ($buIds) {
                /** @var $repo BusinessUnitRepository */
                $repo = $this->objectManager->getRepository('OroOrganizationBundle:BusinessUnit');
                $businessUnits = $repo->getBusinessUnits($buIds);
                $className = 'Oro\Bundle\OrganizationBundle\Entity\BusinessUnit';
                $entityConfigId = new EntityConfigId('entity', $className);
                $classLabel = $this->translator->trans($this->configManager->getConfig($entityConfigId)->get('label'));
                foreach ($businessUnits as $businessUnit) {
                    /* @var $businessUnit BusinessUnit */
                    $details = $classLabel;
                    if ($orgName = $businessUnit->getOrganization()->getName()) {
                        $details .= ' ' . $this->translator->trans('oro.security.datagrid.share_grid_row_details_from')
                            . ' ' . $orgName;
                    }
                    $rows[] = new ResultRecord(
                        [
                            'id' => json_encode([
                                'entityId' => $businessUnit->getId(),
                                'entityClass' => $className,
                            ]),
                            'entity' => [
                                'id' => $businessUnit->getId(),
                                'label' => $businessUnit->getName(),
                                'details' => $details,
                                'image' => 'avatar-business-unit-xsmall.png',
                            ],
                        ]
                    );
                }
            }
            if ($usernames) {
                /** @var $repo UserRepository */
                $repo = $this->objectManager->getRepository('OroUserBundle:User');
                $users = $repo->findUsersByUsernames($usernames);
                $className = 'Oro\Bundle\UserBundle\Entity\User';
                $entityConfigId = new EntityConfigId('entity', $className);
                $classLabel = $this->translator->trans($this->configManager->getConfig($entityConfigId)->get('label'));
                foreach ($users as $user) {
                    /* @var $user User */
                    $details = $classLabel;
                    if ($buName = $user->getOwner()->getName()) {
                        $details .= ' ' . $this->translator->trans('oro.security.datagrid.share_grid_row_details_from')
                            . ' ' . $buName;
                    }
                    $rows[] = new ResultRecord(
                        [
                            'id' => json_encode([
                                'entityId' => $user->getId(),
                                'entityClass' => $className,
                            ]),
                            'entity' => [
                                'id' => $user->getId(),
                                'label' => $user->getUsername(),
                                'details' => $details,
                                'image' => 'avatar-xsmall.png',
                                'avatar' => $user->getAvatar()
                                    ? $this->attachmentManager->getResizedImageUrl(
                                        $user->getAvatar(),
                                        AttachmentManager::SMALL_IMAGE_WIDTH,
                                        AttachmentManager::SMALL_IMAGE_HEIGHT
                                    )
                                    : null,
                            ],
                        ]
                    );
                }
            }
        }

        return $rows;
    }
}
