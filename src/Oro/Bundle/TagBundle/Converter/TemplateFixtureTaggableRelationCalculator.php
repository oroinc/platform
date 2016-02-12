<?php

namespace Oro\Bundle\TagBundle\Converter;

use InvalidArgumentException;

use Oro\Bundle\ImportExportBundle\Converter\RelationCalculatorInterface;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateManager;
use Oro\Bundle\TagBundle\Entity\Taggable;

class TemplateFixtureTaggableRelationCalculator implements RelationCalculatorInterface
{
    /** @var TemplateManager */
    protected $templateManager;

    /**
     * @param TemplateManager $templateManager
     */
    public function __construct(TemplateManager $templateManager)
    {
        $this->templateManager = $templateManager;
    }

    /**
     * @param string $entityName
     * @param string $fieldName
     * @return int|mixed
     */
    public function getMaxRelatedEntities($entityName, $fieldName)
    {
        if ($fieldName !== 'tags') {
            throw new InvalidArgumentException('Field must be "tags" for taggable relation calculator');
        }

        $counts = array_map(
            function (Taggable $taggable) {
                $tags = $taggable->getTags();
                if (!isset($tags['all'])) {
                    return 0;
                }

                return count($tags['all']);
            },
            iterator_to_array($this->templateManager->getEntityFixture($entityName)->getData())
        );

        return $counts ? max($counts) : 0;
    }
}
