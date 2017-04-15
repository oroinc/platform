<?php

namespace Oro\Bundle\DistributionBundle\Translation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    /** @var array */
    protected $domains = [
        'messages',
    ];

    /**
     * {@inheritdoc}
     */
    public function addResource($format, $resource, $locale, $domain = null)
    {
        //Collect all possible domains
        if (!in_array($domain, $this->domains, true)) {
            $this->domains[] = $domain;
        }

        parent::addResource($format, $resource, $locale, $domain);
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize()
    {
        parent::initialize();
        // add dynamic resources to the end to make sure that they override static translations
        $languages = $this->getDbalLanguages();
        foreach ($languages as $locale) {
            foreach ($this->domains as $domain) {
                parent::addResource('oro_dbal_translation', null, $locale, $domain);
            }
        }
    }

    /**
     * @return array
     */
    protected function getDbalLanguages()
    {
        /** @var Connection $connection */
        $connection = $this->container->get('doctrine')->getConnection();

        /** @var QueryBuilder $qb */
        $qb = $connection->createQueryBuilder();

        return array_column($qb->select('l.code')->from('oro_language', 'l')->execute()->fetchAll(), 'code');
    }
}
