<?php
namespace Oro\Bundle\LDAPBundle\Provider\Connector;

use FR3D\LdapBundle\Ldap\LdapManager;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

class UserLdapConnector extends AbstractConnector implements TwoWaySyncConnectorInterface
{

    /** @var LdapManager */
    protected $manager;

    /** @var array */
    private $users;

    public function setManager(LdapManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    protected function getConnectorSource()
    {
        $this->manager->findUsers();
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return "oro.ldap.connector.user.label";
    }

    /**
     * {@inheritdoc}
     */
    public function getImportEntityFQCN()
    {
        return "Oro\Bundle\UserBundle\Entity\User";
    }

    /**
     * {@inheritdoc}
     */
    public function getImportJobName()
    {
        return "ldap_import_users";
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return "ldap";
    }

    /**
     * {@inheritdoc}
     */
    public function getExportJobName()
    {
        return "ldap_export_users";
    }

    public function initializeFromContext(ContextInterface $context)
    {
        parent::initializeFromContext($context);
        $configuration = $context->getConfiguration();
    }
}