<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class DeleteUnnecessaryACLCapabilities implements Migration, ContainerAwareInterface
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $aclManager = $this->container->get('oro_security.acl.manager');
        $aclProvider = $this->container->get('security.acl.dbal.provider');
        $aclObjects = [
            'Action:oro_tag_view_tag_cloud'
        ];

        foreach ($aclObjects as $aclObject) {
            try {
                $oid = $aclManager->getOid($aclObject);
                $aclProvider->deleteAclClass($oid);
            } catch (\Exception $ex) {
                continue;
            }
        }
    }
}
