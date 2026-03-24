<?php

declare(strict_types=1);

namespace Oro\Bundle\EmailBundle\Form\Type;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EmailBundle\Entity\Repository\EmailTemplateRepository;
use Oro\Bundle\EmailBundle\Provider\EmailTemplateOrganizationProvider;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting an email template name filtered by entity class and current organization.
 *
 * Provides a Select2-enhanced dropdown of distinct email template names for the given entity.
 */
final class EmailTemplateNameSelectType extends AbstractType
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly EmailTemplateOrganizationProvider $organizationProvider
    ) {
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('entity_name', null);
        $resolver->setAllowedTypes('entity_name', ['null', 'string']);

        $resolver->setNormalizer('choices', function (Options $options): array {
            /** @var EmailTemplateRepository $repository */
            $repository = $this->doctrine->getRepository(EmailTemplate::class);
            $names = $repository->getDistinctNamesForEntity(
                $options['entity_name'],
                $this->organizationProvider->getOrganization()
            );

            return \array_combine($names, $names);
        });
    }

    #[\Override]
    public function getParent(): string
    {
        return Select2ChoiceType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_email_template_name_select';
    }
}
