<?php

namespace Oro\Bundle\NavigationBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\NavigationBundle\Entity\Title;

class TitleProvider
{
    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var array */
    private $titles;

    /** @var array */
    private $cache = [];

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
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

        return [];
    }

    /**
     * Inject titles from config
     *
     * @param $titles
     */
    public function setTitles($titles)
    {
        $this->titles = $titles;
    }
}
