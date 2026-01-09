<?php

namespace Oro\Bundle\TagBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Component\DependencyInjection\ContainerAwareInterface;
use Oro\Component\DependencyInjection\ContainerAwareTrait;

class DeleteUnnecessaryACLCapabilities implements Migration, ContainerAwareInterface
{
    use ContainerAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $aclManager = $this->container->get('oro_security.acl.manager');
        $aclProvider = $this->container->get('security.acl.dbal.provider');
        try {
            $aclProvider->deleteAclClass($aclManager->getOid('Action:oro_tag_view_tag_cloud'));
        } catch (\Exception $e) {
        }
    }
}
