<?php

namespace Oro\Bundle\DataGridBundle\Provider\State;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Entity\AbstractGridView;
use Oro\Bundle\DataGridBundle\Entity\Manager\GridViewManager;
use Oro\Bundle\DataGridBundle\Extension\GridViews\GridViewsExtension;
use Oro\Bundle\DataGridBundle\Extension\GridViews\ViewInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Component\DependencyInjection\ServiceLink;

/**
 * Contains base methods for datagrid state providers which are trying to fetch data from grid views.
 */
abstract class AbstractStateProvider implements DatagridStateProviderInterface
{
    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    /** @var ServiceLink */
    private $gridViewManagerLink;

    /** @var array */
    private $defaultGridView = [];

    /**
     * @param ServiceLink $gridViewManagerLink
     * @param TokenAccessorInterface $tokenAccessor
     */
    public function __construct(ServiceLink $gridViewManagerLink, TokenAccessorInterface $tokenAccessor)
    {
        $this->gridViewManagerLink = $gridViewManagerLink;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * @param DatagridConfiguration $datagridConfiguration
     * @param ParameterBag $datagridParameters
     *
     * @return ViewInterface|null
     */
    protected function getActualGridView(
        DatagridConfiguration $datagridConfiguration,
        ParameterBag $datagridParameters
    ): ?ViewInterface {
        $gridName = $datagridConfiguration->getName();

        return $this->getCurrentGridView($datagridParameters, $gridName) ?: $this->getDefaultGridView($gridName);
    }

    /**
     * Gets id for current grid view
     *
     * @param ParameterBag $datagridParameters
     *
     * @return int|string|null
     */
    private function getCurrentGridViewId(ParameterBag $datagridParameters)
    {
        $additionalParameters = $datagridParameters->get(ParameterBag::ADDITIONAL_PARAMETERS, []);

        return $additionalParameters[GridViewsExtension::VIEWS_PARAM_KEY] ?? null;
    }

    /**
     * @param ParameterBag $datagridParameters
     * @param $gridName
     *
     * @return AbstractGridView|null
     */
    private function getCurrentGridView(ParameterBag $datagridParameters, $gridName)
    {
        $currentGridViewId = $this->getCurrentGridViewId($datagridParameters);
        if ($currentGridViewId !== null) {
            $gridView = $this->getGridViewManager()->getView($currentGridViewId, 1, $gridName);
        }

        return $gridView ?? null;
    }

    /**
     * Gets defined as default grid view for current logged user.
     *
     * @param string $gridName
     *
     * @return AbstractGridView|null
     */
    private function getDefaultGridView(string $gridName)
    {
        if (!array_key_exists($gridName, $this->defaultGridView)) {
            $currentUser = $this->tokenAccessor->getUser();
            if (null === $currentUser) {
                return null;
            }
            $this->defaultGridView[$gridName] = $this->getGridViewManager()->getDefaultView($currentUser, $gridName);
        }

        return $this->defaultGridView[$gridName];
    }

    /**
     * @return GridViewManager
     */
    private function getGridViewManager(): GridViewManager
    {
        return $this->gridViewManagerLink->getService();
    }
}
