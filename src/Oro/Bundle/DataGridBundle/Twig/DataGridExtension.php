<?php

namespace Oro\Bundle\DataGridBundle\Twig;

use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\DataGridBundle\Datagrid\Manager;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Builder;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class DataGridExtension extends \Twig_Extension
{
    const ROUTE = 'oro_datagrid_index';

    /** @var Manager */
    protected $manager;

    /** @var RouterInterface */
    protected $router;

    /** @var SecurityFacade $securityFacade */
    protected $securityFacade;

    /**
     * @param Manager         $manager
     * @param RouterInterface $router
     * @param SecurityFacade  $securityFacade
     */
    public function __construct(Manager $manager, RouterInterface $router, $securityFacade)
    {
        $this->manager        = $manager;
        $this->router         = $router;
        $this->securityFacade = $securityFacade;
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
        if ($this->isAclGrantedForGridName($name)) {
            return $this->manager->getDatagridByRequestParams($name, $params);
        }

        return null;
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

        $metaData->offsetAddToArray(
            'options',
            [
                'url'       => $this->generateUrl($grid->getName(), $params),
                'urlParams' => $params,
            ]
        );

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

    /**
     * @param $gridName
     *
     * @return bool
     */
    protected function isAclGrantedForGridName($gridName)
    {
        $gridConfig = $this->manager->getConfigurationForGrid($gridName);

        if ($gridConfig) {
            $acl = $gridConfig->offsetGetByPath(Builder::DATASOURCE_ACL_PATH);
            if ($acl && !$this->securityFacade->isGranted($acl)) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }
}
