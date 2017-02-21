<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NavigationBundle\Entity\Title;

class TitleProvider
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var array */
    private $cache = [];

    /** @var ConfigurationProvider */
    private $configurationProvider;

    /**
     * @param DoctrineHelper        $doctrineHelper
     * @param ConfigurationProvider $configurationProvider
     */
    public function __construct(DoctrineHelper $doctrineHelper, ConfigurationProvider $configurationProvider)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * Loads title templates from a database and if they are not found there gets them from a config.
     *
     * @param string $routeName
     *
     * @return array ['title' => title template, 'short_title' => short title template]
     *               also empty array may be returned if a route has no configured title
     *
     * @throws \RuntimeException if the given route has no title templates
     */
    public function getTitleTemplates($routeName)
    {
        //ToDo: update this method and test in BB-6555
        $titles = $this->configurationProvider->getConfiguration(ConfigurationProvider::TITLES_KEY);

        if (array_key_exists($routeName, $titles)) {
            $result = [
                'title'       => $titles[$routeName],
                'short_title' => $titles[$routeName]
            ];
        } else {
            $result = [];
        }

        return [];
    }
}
