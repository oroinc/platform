<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The form type for import data widget.
 */
class ImportType extends AbstractType
{
    public function __construct(
        private ProcessorRegistry $processorRegistry
    ) {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $fileFieldOptions = [];
        if ($options['fileAcceptAttribute']) {
            $fileFieldOptions['attr'] = ['accept' => $options['fileAcceptAttribute']];
        }
        $fileFieldOptions['constraints'] = [
            new Assert\File(mimeTypes: $options['fileMimeTypes'] ?? ['text/plain', 'text/csv'])
        ];
        $builder->add('file', FileType::class, $fileFieldOptions);

        $processorChoices = [];
        $aliases = $this->processorRegistry->getProcessorAliasesByEntity(
            ProcessorRegistry::TYPE_IMPORT,
            $options['entityName']
        );
        foreach ($aliases as $alias) {
            $processorChoices[sprintf('oro.importexport.import.%s', $alias)] = $alias;
        }
        $processorNames = array_values($processorChoices);
        $builder->add(
            'processorAlias',
            ChoiceType::class,
            array_merge(
                [
                    'choices' => $processorChoices,
                    'required' => true,
                    'empty_data' => reset($processorNames)
                ],
                $options['processorAliasOptions']
            )
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' =>  ImportData::class,
            'processorAliasOptions' => [],
            'fileMimeTypes' => null,
            'fileAcceptAttribute' => null
        ]);
        $resolver->setRequired(['entityName']);
        $resolver->setAllowedTypes('entityName', 'string');
        $resolver->setAllowedTypes('fileMimeTypes', ['array', 'null']);
        $resolver->setAllowedTypes('fileAcceptAttribute', ['string', 'null']);
        $resolver->setAllowedTypes('processorAliasOptions', 'array');
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'oro_importexport_import';
    }
}
