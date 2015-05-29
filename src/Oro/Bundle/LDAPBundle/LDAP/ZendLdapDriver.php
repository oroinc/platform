<?php

namespace Oro\Bundle\LDAPBundle\LDAP;

use FR3D\LdapBundle\Driver\ZendLdapDriver as BaseDriver;

use Psr\Log\LoggerInterface;

class ZendLdapDriver extends BaseDriver
{
    /** @var Ldap */
    protected $driver;

    /**
     * @param Ldap $driver
     * @param LoggerInterface $logger
     */
    public function __construct(Ldap $driver, LoggerInterface $logger = null)
    {
        parent::__construct($driver, $logger);
        $this->driver = $driver;
    }

    /**
     * @param string $dn
     *
     * @return bool
     */
    public function exists($dn)
    {
        return $this->driver->exists($dn);
    }

    /**
     * @param string $dn
     * @param array $entry
     */
    public function save($dn, array $entry)
    {
        $this->driver->save($dn, $entry);
    }

    /**
     * @param string $from
     * @param string $to
     */
    public function move($from, $to)
    {
        $this->driver->move($from, $to);
    }
}
