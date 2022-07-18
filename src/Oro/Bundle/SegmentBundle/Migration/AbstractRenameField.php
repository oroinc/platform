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

            $definition['filters'] = $this->replaceFiltersFieldName($definition['filters'] ?? []);

            $report->setDefinition(QueryDefinitionUtil::encodeDefinition($definition));
            $manager->persist($report);
        }

        $manager->flush();
    }

    /**
     * Recursion inside this method handles the case when filter aggregate array of filters,
     * For example this is a fragment of existing definition:
     * [
     *      'columns' => [
     *              0 => array (
     *                  'name' => 'username',
     *                  'label' => 'Email',
     *                  'func' => '',
     *                  'sorting' => '',
     *              ),
     *              1 => array (
     *                  'name' => 'enabled',
     *                  'label' => 'Enabled',
     *                  'func' => '',
     *                  'sorting' => '',
     *              ),
     *              2 => array (
     *                  'name' => 'customerOro\\Bundle\\CustomerBundle\\Entity\\Customer::dt_status',
     *                  'label' => 'Status',
     *                  'func' => '',
     *                  'sorting' => '',
     *              ),
     *              3 => array (
     *                  'name' => 'userRolesOro\\Bundle\\CustomerBundle\\Entity\\CustomerUserRole::label',
     *                  'label' => 'Label',
     *                  'func' => '',
     *                  'sorting' => '',
     *              ),
     *      ],
     *      'filters' => [
     *          [
     *              'columnName' => 'customerOro\\Bundle\\CustomerBundle\\Entity\\Customer::dt_status',
     *              'criterion' => [
     *                  'filter' => 'enum',
     *                  'data' => [
     *                      'type' => '1',
     *                      'value' => ['inactive'],
     *                      'params' => ['class' => 'Extend\\Entity\\EV_Dt_Status']
     *                  ]
     *              ]
     *          ],
     *          'AND',
     *          [
     *              'columnName' => 'enabled',
     *              'criterion' => [
     *                  'filter' => 'boolean',
     *                  'data' => ['value' => '1']
     *              ]
     *          ],
     *          'AND',
     *          [
     *              [
     *                  'columnName' => 'userRolesOro\\Bundle\\CustomerBundle\\Entity\\CustomerUserRole::label',
     *                  'criterion' => [
     *                      'filter' => 'string',
     *                      'data' => ['value' => 'Admin', 'type' => '1']
     *                  ]
     *              ],
     *              'OR',
     *              [
     *                  'columnName' => 'userRolesOro\\Bundle\\CustomerBundle\\Entity\\CustomerUserRole::label',
     *                  'criterion' => [
     *                      'filter' => 'string',
     *                      'data' => [
     *                          'value' => 'price',
     *                          'type' => '1',
     *                      ]
     *                  ]
     *              ]
     *          ]
     *  ......
     * ]
     */
    private function replaceFiltersFieldName(array $filters): array
    {
        foreach ($filters as &$filterData) {
            if (!is_array($filterData)) {
                // Skips iteration if it is an "AND" or "OR" operator.
                continue;
            }

            if (isset($filterData['columnName'])) {
                $filterData['columnName'] = $this->replaceFieldName($filterData['columnName']);
            } else {
                $filterData = $this->replaceFiltersFieldName($filterData);
            }
        }

        return $filters;
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
