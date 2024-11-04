<?php

namespace Oro\Bundle\DashboardBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\Entity\Dashboard;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

/**
 * Dashboard entity form type.
 */
class DashboardType extends AbstractType
{
    private ManagerRegistry $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('label', TextType::class, array('required' => true, 'label' => 'oro.dashboard.label'));

        /** @var EnumOptionRepository $enumRepo */
        $enumRepo = $this->doctrine->getRepository(EnumOption::class);
        if ($enumRepo->count([]) > 1) {
            $fieldOptions = [
                'required'    => true,
                'label'       => 'oro.dashboard.dashboard_type.label',
                'enum_code'   => 'dashboard_type',
                'constraints' => [new NotNull()]
            ];
            if (!$options['create_new']) {
                $fieldOptions['disabled'] = true;
                $fieldOptions['attr'] = ['readonly' => true];
            }

            $builder->add('dashboardType', EnumChoiceType::class, $fieldOptions);
        } else {
            $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($enumRepo) {
                /** @var Dashboard $dashboard */
                $dashboard = $event->getData();
                if (null === $dashboard->getDashboardType()) {
                    $defaultItems = $enumRepo->getDefaultValues('dashboard_type');
                    $dashboard->setDashboardType(reset($defaultItems));
                }
            });
        }

        if ($options['create_new']) {
            $builder->add(
                'startDashboard',
                DashboardSelectType::class,
                ['required' => false, 'label' => 'oro.dashboard.start_dashboard']
            );
        }
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'create_new' => false,
            'data_class' => Dashboard::class
        ]);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_dashboard';
    }
}
