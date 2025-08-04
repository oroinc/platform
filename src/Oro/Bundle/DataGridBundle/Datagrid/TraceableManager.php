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
class TraceableManager implements ManagerInterface, ResetInterface
{
    private array $datagrids = [];
    private array $configurations = [];

    public function __construct(private ManagerInterface $manager, private ?RequestStack $requestStack = null)
    {
    }

    #[\Override]
    public function getDatagrid($name, $parameters = null, array $additionalParameters = [])
    {
        $datagrid = $this->manager->getDatagrid($name, $parameters, $additionalParameters);
        $key = '';
        if (!empty($parameters) || !empty($additionalParameters)) {
            $key .= \json_encode([$parameters, $additionalParameters]);
        }
        return $this->saveAndGetDatagrid($datagrid, $name, $key);
    }

    #[\Override]
    public function getDatagridByRequestParams($name, array $additionalParameters = [])
    {
        $datagrid = $this->manager->getDatagridByRequestParams($name, $additionalParameters);
        $key = '';
        if (!empty($additionalParameters)) {
            $key .= \json_encode($additionalParameters);
        }
        return $this->saveAndGetDatagrid($datagrid, $name, $key);
    }

    #[\Override]
    public function getConfigurationForGrid($name)
    {
        /** @var DatagridConfiguration $configuration */
        $configuration = $this->manager->getConfigurationForGrid($name);
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

    #[\Override]
    public function reset()
    {
        $this->datagrids = [];
        $this->configurations = [];
        if ($this->manager instanceof ResetInterface) {
            $this->manager->reset();
        }
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
