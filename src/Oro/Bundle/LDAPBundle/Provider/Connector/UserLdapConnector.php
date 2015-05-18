<?php
namespace Oro\Bundle\LDAPBundle\Provider\Connector;

use Oro\Bundle\IntegrationBundle\Provider\AbstractConnector;
use Oro\Bundle\IntegrationBundle\Provider\TwoWaySyncConnectorInterface;

class UserLdapConnector extends AbstractConnector implements TwoWaySyncConnectorInterface
{

    /**
     * Return source iterator to read from
     *
     * @return \Iterator
     */
    protected function getConnectorSource()
    {
        // TODO: Implement getConnectorSource() method.
    }

    /**
     * Returns label for UI
     *
     * @return string
     */
    public function getLabel()
    {
        return "oro.ldap.connector.user.label";
    }

    /**
     * Returns entity name that will be used for matching "import processor"
     *
     * @return string
     */
    public function getImportEntityFQCN()
    {
        return "Oro\Bundle\UserBundle\Entity\User";
    }

    /**
     * Returns job name for import
     *
     * @return string
     */
    public function getImportJobName()
    {
        return "ldap_import_users";
    }

    /**
     * Returns type name, the same as registered in service tag
     *
     * @return string
     */
    public function getType()
    {
        return "ldap";
    }

    /**
     * @return string
     */
    public function getExportJobName()
    {
        return "ldap_export_users";
    }
}