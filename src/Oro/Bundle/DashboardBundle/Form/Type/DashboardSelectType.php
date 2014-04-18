<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\DashboardBundle\Model\Manager;

class DashboardSelectType extends AbstractType
{
    /**
     * @var Manager
     */
    protected $manager;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'choices' => $this->getDashboardsList(),
                'empty_value' => 'oro.dashboard.start_dashboard.empty_value',
            )
        );
    }

    /**
     * @return array
     */
    protected function getDashboardsList()
    {
        $result = array();

        foreach ($this->manager->findAllowedDashboards() as $dashboardModel) {
            $result[$dashboardModel->getId()] = $dashboardModel->getLabel();
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_dashboard_select';
    }
}
