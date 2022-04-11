<?php

namespace Oro\Bundle\SegmentBundle\Migration;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\DistributionBundle\Handler\ApplicationState;
use Oro\Bundle\QueryDesignerBundle\Model\AbstractQueryDesigner;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\ReportBundle\Entity\Report;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Renames field in query definition.
 */
abstract class AbstractRenameField extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        if (!$this->container->get(ApplicationState::class)->isInstalled()) {
            return;
        }

        $reports = $this->getQueryAwareEntities($manager);
        /** @var Report $report */
        foreach ($reports as $report) {
            $definition = QueryDefinitionUtil::safeDecodeDefinition($report->getDefinition());

            foreach ($definition['columns'] ?? [] as $key => $columnData) {
                $definition['columns'][$key]['name'] = $this->replaceFieldName($columnData['name']);
            }

            foreach ($definition['grouping_columns'] ?? [] as $key => $columnData) {
                $definition['grouping_columns'][$key]['name'] = $this->replaceFieldName($columnData['name']);
            }

            foreach ($definition['filters'] ?? [] as $key => $filterData) {
                if ($filterData === 'OR' || $filterData === 'AND') {
                    // Skips iteration if it is an "AND" or "OR" operator.
                    continue;
                }

                $definition['filters'][$key]['columnName'] = $this->replaceFieldName($filterData['columnName']);
            }

            $report->setDefinition(QueryDefinitionUtil::encodeDefinition($definition));
            $manager->persist($report);
        }

        $manager->flush();
    }

    private function replaceFieldName(string $columnName): string
    {
        $columnNameParts = explode('+', $columnName);
        if ($columnNameParts[0] === $this->getOldFieldName()) {
            $columnNameParts[0] = $this->getNewFieldName();
        }

        return implode('+', $columnNameParts);
    }

    /**
     * Name of the field which should be changed.
     */
    abstract protected function getOldFieldName(): string;

    /**
     * New name for the field.
     */
    abstract protected function getNewFieldName(): string;

    /**
     * @param ObjectManager $manager
     * @return AbstractQueryDesigner[]
     */
    abstract protected function getQueryAwareEntities(ObjectManager $manager): array;
}
