<?php

namespace Oro\Bundle\AttachmentBundle\Api\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Form\DataTransformer\EntityToIdTransformer;
use Oro\Bundle\ApiBundle\Form\FormUtil;
use Oro\Bundle\ApiBundle\Metadata\AssociationMetadata;
use Oro\Bundle\ApiBundle\Request\Constraint;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\ApiBundle\Util\EntityLoader;
use Oro\Bundle\ApiBundle\Util\EntityMapper;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Entity\FileItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The form type for multi file and multi image associations.
 */
class MultiFileEntityType extends AbstractType
{
    public function __construct(
        private readonly DoctrineHelper $doctrineHelper,
        private readonly EntityLoader $entityLoader,
        private readonly TranslatorInterface $translator,
        private readonly MultiFileEntityOptionProcessorInterface $optionProcessor
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
            $resultData = new ArrayCollection();
            $form = $event->getForm();
            /** @var Collection $existingData */
            $existingData = $form->getData() ?? new ArrayCollection();
            $submittedData = $event->getData();
            foreach ($submittedData as $submittedDataItemKey => $submittedDataItem) {
                /** @var ?File $file */
                try {
                    $file = $entityToIdTransformer->reverseTransform($submittedDataItem);
                } catch (TransformationFailedException $e) {
                    FormUtil::addNamedFormError(
                        $form,
                        Constraint::FORM,
                        $this->translator->trans(
                            $e->getInvalidMessage(),
                            $e->getInvalidMessageParameters(),
                            'validators'
                        ),
                        (string)$submittedDataItemKey
                    );
                    continue;
                }
                if (null === $file) {
                    continue;
                }

                $fileItem = $this->findFileItem($existingData, $file);
                if (null === $fileItem) {
                    if (null !== $file->getId()) {
                        FormUtil::addNamedFormError(
                            $form,
                            Constraint::FORM,
                            $this->translator->trans('oro.attachment.parent.change_not_allowed', [], 'validators'),
                            (string)$submittedDataItemKey
                        );
                        continue;
                    }
                    $fileItem = new FileItem();
                    $fileItem->setFile($file);
                }
                if (\is_array($submittedDataItem)) {
                    $this->optionProcessor->process(
                        $fileItem,
                        $submittedDataItem,
                        (string)$submittedDataItemKey,
                        $form
                    );
                }
                $resultData->add($fileItem);
            }
            $event->setData($resultData);
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

    private function findFileItem(Collection $fileItems, File $file): ?FileItem
    {
        if (null === $file->getId()) {
            return null;
        }

        /** @var FileItem $fileItem */
        foreach ($fileItems as $fileItem) {
            if ($fileItem->getFile()->getId() === $file->getId()) {
                return $fileItem;
            }
        }

        return null;
    }
}
