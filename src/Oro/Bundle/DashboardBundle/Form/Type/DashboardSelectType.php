<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;

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
        return 'entity';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array(
                'class' => 'OroDashboardBundle:Dashboard',
                'property' => 'label',
                'choices' => $this->getChoices(),
                'empty_value' => 'oro.dashboard.start_dashboard.empty_value',
            )
        );
    }

    /**
     * @return Dashboard
     */
    protected function getChoices()
    {
        return array_map(
            function (DashboardModel $dashboardModel) {
                return $dashboardModel->getEntity();
            },
            $this->manager->findAllowedDashboards()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'oro_dashboard_select';
    }
}
