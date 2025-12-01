<?php

namespace Oro\Bundle\DashboardBundle\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\DashboardBundle\DashboardType\CloneableDashboardTypeInterface;
use Oro\Bundle\DashboardBundle\DashboardType\DashboardTypeConfigProviderInterface;
use Oro\Bundle\DashboardBundle\Form\Type\DashboardType;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumOptionRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Adds cloneable_dashboard_types variable to dashboard form view.
 */
class DashboardTypeExtension extends AbstractTypeExtension
{
    /** @var iterable<DashboardTypeConfigProviderInterface> */
    private iterable $dashboardTypeProviders;

    private ManagerRegistry $doctrine;

    /**
     * @param iterable<DashboardTypeConfigProviderInterface> $dashboardTypeProviders
     */
    public function __construct(
        iterable $dashboardTypeProviders,
        ManagerRegistry $doctrine
    ) {
        $this->dashboardTypeProviders = $dashboardTypeProviders;
        $this->doctrine = $doctrine;
    }

    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [DashboardType::class];
    }

    #[\Override]
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (!isset($view['dashboardType'])) {
            return;
        }

        /** @var EnumOptionRepository $enumRepository */
        $enumRepository = $this->doctrine->getRepository(EnumOption::class);

        $enumOptions = $enumRepository->findBy(['enumCode' => 'dashboard_type']);

        // Build list of cloneable dashboard type IDs
        $cloneableTypes = [];
        foreach ($enumOptions as $enumOption) {
            $enumId = $enumOption->getId();

            foreach ($this->dashboardTypeProviders as $provider) {
                if ($provider->isSupported($enumId)
                    && $provider instanceof CloneableDashboardTypeInterface
                    && $provider->isCloneable()
                ) {
                    $cloneableTypes[] = $enumId;
                    break;
                }
            }
        }

        $view['dashboardType']->vars['cloneable_dashboard_types'] = $cloneableTypes;
    }
}
