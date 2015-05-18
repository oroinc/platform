<?php
namespace Oro\Bundle\LDAPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\IntegrationBundle\Entity\Transport;

/**
 * Class LdapTransport
 * @package Oro\Bundle\LDAPBundle\Entity
 *
 * @ORM\Entity
 * @Config()
 * @Oro\Loggable()
 */
class LdapTransport extends Transport
{
    /**
     * @var string
     *
     * @ORM\Column(name="oro_ldap_server_hostname", type="string")
     */
    private $serverHostname;

    /**
     * @var string
     *
     * @ORM\Column(name="oro_ldap_server_port", type="integer")
     */
    private $serverPort;

    /**
     * @var string
     *
     * @ORM\Column(name="oro_ldap_server_encryption", type="string")
     */
    private $serverEncryption;

    /**
     * @var string
     *
     * @ORM\Column(name="oro_ldap_server_base_dn", type="string")
     */
    private $serverBaseDn;

    /**
     * @var string
     *
     * @ORM\Column(name="oro_ldap_admin_dn", type="string")
     */
    private $adminDn;

    /**
     * @var string
     *
     * @ORM\Column(name="oro_ldap_admin_password", type="string")
     */
    private $adminPassword;

    /** @var ParameterBag */
    protected $settings;

    public function __construct()
    {
        $this->serverHostname = '127.0.0.1';
        $this->serverPort = 389;
        $this->serverBaseDn = 'dc=domain,dc=com';
        $this->adminDn = 'dn=admin,dc=domain,dc=com';
    }

    /**
     * @return ParameterBag
     */
    public function getSettingsBag()
    {
        if (null === $this->settings) {
            $this->settings = new ParameterBag([
                'server_hostname' => $this->getServerHostname(),
                'server_port' => $this->getServerPort(),
                'server_encryption' => $this->getServerEncryption(),
                'server_base_dn' => $this->getServerBaseDn(),
                'admin_dn' => $this->getAdminDn(),
                'admin_password' => $this->getAdminPassword(),
            ]);
        }

        return $this->settings;
    }

    /**
     * @return string
     */
    public function getServerHostname()
    {
        return $this->serverHostname;
    }

    /**
     * @param string $serverHostname
     *
     * @return $this
     */
    public function setServerHostname($serverHostname)
    {
        $this->serverHostname = $serverHostname;

        return $this;
    }

    /**
     * @return string
     */
    public function getServerPort()
    {
        return $this->serverPort;
    }

    /**
     * @param string $serverPort
     *
     * @return $this
     */
    public function setServerPort($serverPort)
    {
        $this->serverPort = $serverPort;

        return $this;
    }

    /**
     * @return string
     */
    public function getServerEncryption()
    {
        return $this->serverEncryption;
    }

    /**
     * @param string $serverEncryption
     *
     * @return $this
     */
    public function setServerEncryption($serverEncryption)
    {
        $this->serverEncryption = $serverEncryption;

        return $this;
    }

    /**
     * @return string
     */
    public function getServerBaseDn()
    {
        return $this->serverBaseDn;
    }

    /**
     * @param string $serverBaseDn
     *
     * @return $this
     */
    public function setServerBaseDn($serverBaseDn)
    {
        $this->serverBaseDn = $serverBaseDn;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdminDn()
    {
        return $this->adminDn;
    }

    /**
     * @param string $adminDn
     *
     * @return $this
     */
    public function setAdminDn($adminDn)
    {
        $this->adminDn = $adminDn;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdminPassword()
    {
        return $this->adminPassword;
    }

    /**
     * @param $adminPassword
     *
     * @return $this
     */
    public function setAdminPassword($adminPassword)
    {
        $this->adminPassword = $adminPassword;

        return $this;
    }
}
