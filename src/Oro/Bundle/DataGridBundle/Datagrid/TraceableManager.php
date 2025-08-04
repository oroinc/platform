<?php

namespace Oro\Bundle\DataGridBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Provider\SystemAwareResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\VarDumper\Caster\ClassStub;
use Symfony\Contracts\Service\ResetInterface;

/**
 * TraceableManager is a decorator for ManagerInterface that saves datagrids and configurations for later inspection.
 */
class TraceableManager extends Manager implements ResetInterface
{
    private array $datagrids = [];
    private array $configurations = [];
    private ?RequestStack $requestStack = null;

    public function setRequestStack(?RequestStack $requestStack = null)
    {
        $this->requestStack = $requestStack;
    }

    public function getDatagrid($name, $parameters = null, array $additionalParameters = [])
    {
        $datagrid = parent::getDatagrid($name, $parameters, $additionalParameters);
        $key = '';
        if ($parameters instanceof ParameterBag) {
            $parameters = $parameters->all();
        }
        if (!empty($parameters) || !empty($additionalParameters)) {
            $key .= \json_encode([$parameters, $additionalParameters]);
        }
        return $this->saveAndGetDatagrid($datagrid, $name, $key);
    }

    public function getConfigurationForGrid($name)
    {
        /** @var DatagridConfiguration $configuration */
        $configuration = parent::getConfigurationForGrid($name);
        return $configuration;
    }

    public function getDatagrids(?Request $request = null): array
    {
        $hash = $request ? spl_object_hash($request) : null;
        return $this->datagrids[$hash] ?? [];
    }

    public function getConfigurations(?Request $request = null): array
    {
        $hash = $request ? spl_object_hash($request) : null;
        return $this->configurations[$hash] ?? [];
    }

    public function reset()
    {
        $this->datagrids = [];
        $this->configurations = [];
    }

    private function saveAndGetDatagrid(DatagridInterface $datagrid, string $name, string $key): DatagridInterface
    {
        $request = $this->requestStack?->getCurrentRequest();
        $hash = $request ? spl_object_hash($request) : null;

        if (!isset($this->datagrids[$hash][$name][$key])) {
            $config = $datagrid->getConfig();

            $this->datagrids[$hash][$name][$key] = [
                'configuration' => $config->toArray(),
                'resolved_metadata' => $datagrid->getResolvedMetadata()->toArray(),
                'parameters' => $datagrid->getParameters()->all(),
                'extensions' => array_map(
                    fn ($extension) => [
                        'stub' => new ClassStub($extension::class),
                        'priority' => $extension->getPriority(),
                    ],
                    $datagrid->getAcceptor()->getExtensions()
                ),
                'names' => [
                    ...$config->offsetGetOr(SystemAwareResolver::KEY_EXTENDED_FROM, []),
                    $config->getName()
                ]
            ];
        }

        return $datagrid;
    }
}
