<?php

namespace Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions;

use Oro\Bundle\EntityBundle\ORM\OroEntityManager;
use Oro\Bundle\DataGridBundle\Extension\InlineEditing\Configuration;

/**
 * Class TextGuesser
 * @package Oro\Bundle\DataGridBundle\Extension\InlineEditing\InlineEditColumnOptions
 */
class TextGuesser implements GuesserInterface
{
    /**
     * @var OroEntityManager
     */
    protected $entityManager;

    /**
     * @param OroEntityManager $oroEntityManager
     */
    public function __construct(OroEntityManager $oroEntityManager)
    {
        $this->entityManager = $oroEntityManager;
    }

    /**
     * @param string $columnName
     * @param string $entityName
     * @param array $column
     *
     * @return array
     */
    public function guessColumnOptions($columnName, $entityName, $column)
    {
        $metadata = $this->entityManager->getClassMetadata($entityName);

        $result = [];
        if ($metadata->hasField($columnName) && !$metadata->hasAssociation($columnName)) {
            $result[Configuration::BASE_CONFIG_KEY] = ['enable' => true];
        }

        return $result;
    }
}
