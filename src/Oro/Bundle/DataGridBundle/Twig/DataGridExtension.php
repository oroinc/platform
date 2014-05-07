<?php

namespace Oro\Bundle\DataGridBundle\Twig;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;

class DataGridExtension extends \Twig_Extension
{
    const ROUTE = 'oro_datagrid_index';

    /** @var Manager */
    protected $manager;

    /** @var RouterInterface */
    protected $router;

    public function __construct(Manager $manager, RouterInterface $router)
    {
        $this->manager = $manager;
        $this->router  = $router;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'oro_datagrid';
    }

    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_datagrid_build', [$this, 'getGrid']),
            new \Twig_SimpleFunction('oro_datagrid_data', [$this, 'getGridData']),
            new \Twig_SimpleFunction('oro_datagrid_metadata', [$this, 'getGridMetadata']),
        ];
    }

    /**
     * @param string $name
     * @param array $params
     * @return DatagridInterface
     */
    public function getGrid($name, array $params = [])
    {
        return $this->manager->getDatagridByRequestParams($name, $params);
    }

    /**
     * Returns grid metadata array
     *
     * @param DatagridInterface $grid
     * @param array $params
     * @return array
     */
    public function getGridMetadata(DatagridInterface $grid, array $params = [])
    {
        $metaData = $grid->getMetadata();
        $metaData->offsetAddToArray('options', ['url' => $this->generateUrl($grid->getName(), $params)]);

        return $metaData->toArray();
    }

    /**
     * Renders grid data
     *
     * @param DatagridInterface $grid
     * @return array
     */
    public function getGridData(DatagridInterface $grid)
    {
        return $grid->getData()->toArray();
    }

    /**
     * @param string $name
     * @param array  $params
     * @return string
     */
    protected function generateUrl($name, $params)
    {
        return $this->router->generate(self::ROUTE, ['gridName' => $name, $name => $params]);
    }
}
