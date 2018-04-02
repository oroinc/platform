<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\DashboardBundle\Model\DashboardModel;
use Oro\Bundle\DashboardBundle\Model\Manager;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
        return EntityType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'class' => 'OroDashboardBundle:Dashboard',
                'choice_label' => 'label',
                'choices' => $this->getChoices(),
                'placeholder' => 'oro.dashboard.start_dashboard.empty_value',
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
