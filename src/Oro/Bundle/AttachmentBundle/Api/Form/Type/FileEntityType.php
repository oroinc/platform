<?php

namespace Oro\Bundle\AttachmentBundle\Api\Form\Type;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The form type for file and image associations.
 */
class FileEntityType extends AbstractType
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly EntityLoader $entityLoader,
        private readonly TranslatorInterface $translator
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) use ($options) {
            $entityToIdTransformer = new EntityToIdTransformer(
                $this->doctrineHelper,
                $this->entityLoader,
                $options['metadata'],
                $options['entity_mapper'],
                $options['included_entities']
            );
            $form = $event->getForm();
            /** @var ?File $existingFile */
            $existingFile = $form->getData();
            /** @var ?File $file */
            try {
                $file = $entityToIdTransformer->reverseTransform($event->getData());
            } catch (TransformationFailedException $e) {
                FormUtil::addNamedFormError(
                    $form,
                    Constraint::FORM,
                    $this->translator->trans(
                        $e->getInvalidMessage(),
                        $e->getInvalidMessageParameters(),
                        'validators'
                    )
                );
                $event->setData($existingFile);

                return;
            }
            if (null === $file) {
                $event->setData(null);
                $existingFile?->setEmptyFile(true);

                return;
            }

            $resultFile = $file;
            if (null !== $file->getId() && (null === $existingFile || $existingFile->getId() !== $file->getId())) {
                FormUtil::addNamedFormError(
                    $form,
                    Constraint::FORM,
                    $this->translator->trans('oro.attachment.parent.change_not_allowed', [], 'validators')
                );
                $resultFile = $existingFile;
            }
            $event->setData($resultFile);
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefault('compound', false)
            ->setDefault('multiple', true)
            ->setDefault('entity_mapper', null)
            ->setDefault('included_entities', null)
            ->setRequired(['metadata'])
            ->setAllowedTypes('metadata', [AssociationMetadata::class])
            ->setAllowedTypes('entity_mapper', ['null', EntityMapper::class])
            ->setAllowedTypes('included_entities', ['null', IncludedEntityCollection::class]);
    }
}
