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
        if (isset($this->cache[$routeName])) {
            return $this->cache[$routeName];
        }

        /** @var Title $title */
        $title = $this->doctrineHelper->getEntityRepository('Oro\Bundle\NavigationBundle\Entity\Title')
            ->findOneBy(['route' => $routeName]);

        if ($title) {
            $result = [
                'title'       => $title->getTitle(),
                'short_title' => $title->getShortTitle()
            ];
        } else {
            if (isset($this->titles[$routeName])) {
                $result = [
                    'title'       => $this->titles[$routeName],
                    'short_title' => $this->titles[$routeName]
                ];
            } else {
                $result = [];
            }
        }

        $this->cache[$routeName] = $result;

        return $result;
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
