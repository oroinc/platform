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
     * @return array
     */
    protected function getDashboardsList()
    {
        $choices = array();

        foreach ($this->manager->findAllowedDashboards() as $dashboardModel) {
            $choices[$dashboardModel->getId()] = $dashboardModel->getLabel();
        }

        return $choices;
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
                'empty_value' => 'oro.dashboard.form.start_from',
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_select_dashboard';
    }
}
