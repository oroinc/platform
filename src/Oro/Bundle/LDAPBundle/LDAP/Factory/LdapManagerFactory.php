<?php

namespace Oro\Bundle\LDAPBundle\LDAP\Factory;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Psr\Log\LoggerInterface;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LDAPBundle\LDAP\Ldap;
use Oro\Bundle\LDAPBundle\LDAP\LdapManager;
use Oro\Bundle\LDAPBundle\LDAP\ZendLdapDriver;
use Oro\Bundle\LDAPBundle\Provider\ChannelType;

class LdapManagerFactory
{
    /**
     * Cashed LdapManager instances.
     *
     * @var LdapManager[]
     */
    protected $instances = [];

    /** @var Registry */
    private $registry;

    private $userManager;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Registry $registry, $userManager, LoggerInterface $logger)
    {
        $this->registry = $registry;
        $this->userManager = $userManager;
        $this->logger = $logger;
    }

    /**
     * Creates new
     *
     * @param array|\Traversable $settings
     *
     * @return LdapManager
     */
    public function getInstance($settings)
    {
        $ldap = new Ldap(
            $this->transformSettings($settings, $this->getLdapTransforms())
        );
        $driver = new ZendLdapDriver($ldap, $this->logger);
        return new LdapManager(
            $this->registry,
            $driver,
            $this->userManager,
            $this->transformSettings($settings, $this->getLdapManagerTransforms())
        );
    }

    /**
     * Returns instance of LdapManager configured using settings from Channel.
     *
     * @param Channel $channel
     *
     * @return LdapManager
     *
     * @throws \Exception
     */
    public function getInstanceForChannel(Channel $channel)
    {
        if (isset($this->instances[$channel->getId()])) {
            return $this->instances[$channel->getId()];
        }

        if ($channel->getType() != ChannelType::TYPE) {
            throw new \Exception("Channel {" . $channel->getType() . "} must be of type: " . ChannelType::TYPE . ".");
        }

        $settings = iterator_to_array($channel->getTransport()->getSettingsBag());
        $mappingSettings = $channel->getMappingSettings();

        $mappingSettings->merge($settings);

        return $this->instances[$channel->getId()] = $this->getInstance($mappingSettings);
    }

    /**
     * Returns instance for integration.
     *
     * @param integer $channelId
     *
     * @return LdapManager
     *
     * @throws \Exception
     */
    public function getInstanceForChannelId($channelId)
    {
        $repository = $this->registry->getRepository('OroIntegrationBundle:Channel');

        $channel = $repository->find($channelId);

        return $this->getInstanceForChannel($channel);
    }

    /**
     * Transforms role mapping to be usable in LdapManager configuration.
     *
     * @param $roleMapping
     * @return array
     */
    private function transformRoleMapping($roleMapping)
    {
        $roles = [];
        foreach ($roleMapping as $mapping) {
            if (isset($roles[$mapping['ldapName']])) {
                $roles[$mapping['ldapName']] = array_merge($roles[$mapping['ldapName']], $mapping['crmRoles']);
            } else {
                $roles[$mapping['ldapName']] = $mapping['crmRoles'];
            }
        }

        return ['role_mapping' => $roles];
    }

    /**
     * Transforms user mapping to be usable in LdapManager configuration.
     *
     * @param $mapping
     * @return array
     */
    private function transformUserMapping($mapping)
    {
        $definedMapping = array_filter($mapping, 'strlen');
        if (!isset($definedMapping['username'])) {
            return [];
        }

        $username = $definedMapping['username'];
        unset($definedMapping['username']);

        $sortedMapping = array_merge(['username' => $username], $definedMapping);
        $attributes = [];
        foreach ($sortedMapping as $userField => $ldapAttr) {
            $attributes[] = [
                'ldap_attr' => $ldapAttr,
                'user_method' => sprintf('set%s', ucfirst($userField)),
                'user_field' => $userField,
            ];
        }

        return compact('attributes');
    }

    /**
     * Transforms settings into acceptable form for LdapManager and LdapDriver.
     *
     * @param array|\Traversable $settings
     * @param array $transforms
     *
     * @return array Array of transformed settings.
     */
    private function transformSettings($settings, array $transforms)
    {
        if ($settings instanceof \Traversable) {
            $settings = iterator_to_array($settings);
        }

        $transformed = [];

        foreach ($settings as $settingKey => $settingValue) {
            if (!isset($transforms[$settingKey])) {
                continue;
            }

            if (is_callable($transforms[$settingKey])) {
                $transformed = array_merge(
                    $transformed,
                    call_user_func($transforms[$settingKey], $settingValue)
                );
            } else {
                $transformed[$transforms[$settingKey]] = $settingValue;
            }
        }

        return $transformed;
    }

    /**
     * Returns array of transforms for LdapManager settings.
     *
     * @return array
     */
    private function getLdapManagerTransforms()
    {
        return [
            'server_base_dn' => 'baseDn',
            'user_filter' => 'filter',
            'role_filter' => 'role_filter',
            'role_id_attribute' => 'role_id_attribute',
            'role_user_id_attribute' => 'role_user_id_attribute',
            'export_user_base_dn' => 'export_dn',
            'export_user_class' => 'export_class',
            'role_mapping' => [$this, 'transformRoleMapping'],
            'user_mapping' => [$this, 'transformUserMapping'],
        ];
    }

    /**
     * Returns array of transforms for Ldap settings.
     *
     * @return array
     */
    private function getLdapTransforms()
    {
        return [
            'server_base_dn' => 'baseDn',
            'server_hostname' => 'host',
            'server_port' => 'port',
            'admin_dn' => 'username',
            'admin_password' => 'password',
            'server_encryption' => function ($encryption) {
                return [
                    'useSsl' => $encryption === 'ssl' ? true : false,
                    'useStartTls' => $encryption === 'tls' ? true : false,
                ];
            },
        ];
    }
}