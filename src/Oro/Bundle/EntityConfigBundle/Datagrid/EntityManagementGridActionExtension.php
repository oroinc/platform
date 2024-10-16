<?php

namespace Oro\Bundle\EntityConfigBundle\Datagrid;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SecurityBundle\Acl\BasicPermission;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * Removes edit action from entity and field config datagrid in not manageable entity rows.
 */
class EntityManagementGridActionExtension extends AbstractExtension
{
    public function __construct(
        private AuthorizationCheckerInterface $authorizationChecker,
        private Registry $doctrine
    ) {
    }

    /**
     * Should be applied after FormatterExtension.
     */
    #[\Override]
    public function getPriority()
    {
        return -5;
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config)
    {
        return $config->isOrmDatasource() &&
            in_array($config->getOrmQuery()->getRootEntity(), [EntityConfigModel::class, FieldConfigModel::class]);
    }

    #[\Override]
    public function visitResult(DatagridConfiguration $config, ResultsObject $result)
    {
        $data = $result->getData();
        $rootEntity = $config->getOrmQuery()->getRootEntity();
        $repo = $this->doctrine->getRepository($rootEntity);

        foreach ($data as &$row) {
            $entityConfigModel = $repo->find($row['id']);

            if (!$this->authorizationChecker->isGranted(BasicPermission::EDIT, $entityConfigModel)) {
                $row['update_link'] = false;
                $row['action_configuration']['update'] = false;
            }
        }

        $result->setData($data);
    }
}
