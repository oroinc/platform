<?php

namespace Oro\Bundle\SecurityBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;

use Oro\Bundle\InstallerBundle\Migrations\Migration;

class OroSecurityBundle implements Migration, ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @inheritdoc
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema)
    {
        //create symfony acl tables
        $this->container->get('security.acl.dbal.schema')->addToSchema($schema);
        return [];
    }
}
