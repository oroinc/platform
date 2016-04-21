<?php

namespace Oro\Bundle\DashboardBundle\Model;

use Doctrine\Common\Collections\Collection;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;

use Oro\Component\PhpUtils\ArrayUtil;

class DashboardModel implements EntityModelInterface
{
    const DEFAULT_TEMPLATE = 'OroDashboardBundle:Index:default.html.twig';

    /**
     * @var Dashboard
     */
    protected $entity;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var Collection
     */
    protected $widgets;

    /**
     * @param Dashboard $dashboard
     * @param Collection $widgets
     * @param array $config
     */
    public function __construct(Dashboard $dashboard, Collection $widgets, array $config)
    {
        $this->entity  = $dashboard;
        $this->widgets = $widgets;
        $this->config  = $config;
    }

    /**
     * Get dashboard config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get dashboard entity
     *
     * @return Dashboard
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Get widgets models
     *
     * @return Collection
     */
    public function getWidgets()
    {
        return $this->widgets;
    }

    /**
     * Get identifier of dashboard
     *
     * @return int
     */
    public function getId()
    {
        return $this->getEntity()->getId();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getEntity()->getName();
    }

    /**
     * @param string $name
     * @return DashboardModel
     */
    public function setName($name)
    {
        $this->getEntity()->setName($name);
        return $this;
    }

    /**
     * @return Dashboard
     */
    public function getStartDashboard()
    {
        return $this->getEntity()->getStartDashboard();
    }

    /**
     * @param Dashboard $startDashboard
     * @return DashboardModel
     */
    public function setStartDashboard(Dashboard $startDashboard)
    {
        $this->getEntity()->setStartDashboard($startDashboard);
        return $this;
    }

    /**
     * Add widget to dashboard
     *
     * @param WidgetModel $widget
     * @param int|null $layoutColumn
     * @return DashboardModel
     */
    public function addWidget(WidgetModel $widget, $layoutColumn = null)
    {
        if (null !== $layoutColumn) {
            $widget->setLayoutPosition($this->getMinLayoutPosition($layoutColumn));
        }
        $this->widgets->add($widget);
        $this->getEntity()->addWidget($widget->getEntity());

        return $this;
    }

    /**
     * Get widget model by id
     *
     * @param integer $id
     * @return WidgetModel|null
     */
    public function getWidgetById($id)
    {
        /** @var WidgetModel $widget */
        foreach ($this->getWidgets() as $widget) {
            if ($widget->getId() == $id) {
                return $widget;
            }
        }
        return null;
    }

    /**
     * Get ordered widgets for column
     *
     * @param int $column
     * @param bool $appendGreater
     * @param bool $appendLesser
     * @return array
     */
    public function getOrderedColumnWidgets($column, $appendGreater = false, $appendLesser = false)
    {
        $elements = $this->widgets->filter(
            function ($element) use ($column, $appendGreater, $appendLesser) {
                /** @var WidgetModel $element */
                $actualColumn = current($element->getLayoutPosition());
                return
                    ($actualColumn == $column) ||
                    ($appendGreater && $actualColumn > $column) ||
                    ($appendLesser && $actualColumn < $column);
            }
        );

        $result = $elements->getValues();
        /**
         * We had to use stable sort to make UI consistent
         * independent of php version
         */
        ArrayUtil::sortBy($result, false, 'layout_position');

        return $result;
    }

    /**
     * Checks if dashboard has widget
     *
     * @param WidgetModel $widgetModel
     * @return bool
     */
    public function hasWidget(WidgetModel $widgetModel)
    {
        return $this->getEntity()->hasWidget($widgetModel->getEntity());
    }

    /**
     * @return boolean
     */
    public function isDefault()
    {
        return $this->entity->getIsDefault();
    }

    /**
     * @param boolean $isDefault
     * @return DashboardModel
     */
    public function setIsDefault($isDefault)
    {
        $this->getEntity()->setIsDefault($isDefault);
        return $this;
    }

    /**
     * Get dashboard label
     *
     * @return string
     */
    public function getLabel()
    {
        $label = $this->entity->getLabel();
        return $label ? $label : (isset($this->config['label']) ? $this->config['label'] : '');
    }

    /**
     * @param string $label
     * @return DashboardModel
     */
    public function setLabel($label)
    {
        $this->getEntity()->setLabel($label);
        return $this;
    }

    /**
     * Get dashboard owner
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->entity->getOwner();
    }

    /**
     * @param User $owner
     * @return DashboardModel
     */
    public function setOwner(User $owner)
    {
        $this->getEntity()->setOwner($owner);
        return $this;
    }

    /**
     * Get dashboard organization
     *
     * @return User
     */
    public function getOrganization()
    {
        return $this->entity->getOrganization();
    }

    /**
     * @param Organization $organization
     * @return DashboardModel
     */
    public function setOrganization(Organization $organization)
    {
        $this->getEntity()->setOrganization($organization);
        return $this;
    }

    /**
     * Get dashboard template
     *
     * @return string
     */
    public function getTemplate()
    {
        $config = $this->getConfig();
        return isset($config['twig']) ? $config['twig'] : self::DEFAULT_TEMPLATE;
    }

    /**
     * Get min layout position in passed column
     *
     * @param int $column
     * @return array
     */
    protected function getMinLayoutPosition($column)
    {
        $result = array($column, 1);

        /** @var WidgetModel $currentWidget */
        foreach ($this->getWidgets() as $currentWidget) {
            $position = $currentWidget->getLayoutPosition();

            if ($position[0] == $result[0] && $position[1] < $result[1]) {
                $result = $position;
            }
        }

        $result[1] = $result[1] - 1;

        return $result;
    }
}
