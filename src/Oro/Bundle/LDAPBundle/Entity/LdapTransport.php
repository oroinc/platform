<?php

/**
 * LdapTransport.php
 *
 * Project: crm-enterprise-dev
 * Author: Jakub Babiak <jakub@babiak.cz>
 * Created: 13/05/15 15:05
 */

namespace Oro\Bundle\LDAPBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\DataAuditBundle\Metadata\Annotation as Oro;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

use Oro\Bundle\IntegrationBundle\Entity\Transport;
use Symfony\Component\HttpFoundation\ParameterBag;

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
     * @ORM\Column(name="server_hostname", type="string")
     */
    private $serverHostname;

    /**
     * @var string
     *
     * @ORM\Column(name="server_port", type="integer")
     */
    private $serverPort;

    /**
     * @var string
     *
     * @ORM\Column(name="server_encryption", type="string")
     */
    private $serverEncryption;

    /**
     * @var string
     *
     * @ORM\Column(name="server_base_dn", type="string")
     */
    private $serverBaseDn;

    /**
     * @var string
     *
     * @ORM\Column(name="admin_dn", type="string")
     */
    private $adminDn;

    /**
     * @var string
     *
     * @ORM\Column(name="admin_password", type="string")
     */
    private $adminPassword;

    /**
     * @var ParameterBag
     */
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
     */
    public function setServerHostname($serverHostname)
    {
        $this->serverHostname = $serverHostname;
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
     */
    public function setServerPort($serverPort)
    {
        $this->serverPort = $serverPort;
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
     */
    public function setServerEncryption($serverEncryption)
    {
        $this->serverEncryption = $serverEncryption;
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
     */
    public function setServerBaseDn($serverBaseDn)
    {
        $this->serverBaseDn = $serverBaseDn;
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
     */
    public function setAdminDn($adminDn)
    {
        $this->adminDn = $adminDn;
    }

    /**
     * @return string
     */
    public function getAdminPassword()
    {
        return $this->adminPassword;
    }

    /**
     * @param string $adminPassword
     */
    public function setAdminPassword($adminPassword)
    {
        $this->adminPassword = $adminPassword;
    }
}
