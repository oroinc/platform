<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Oro\Bundle\ImportExportBundle\Form\Model\ImportData;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportType extends AbstractType
{
    const NAME = 'oro_importexport_import';

    /**
     * @var ProcessorRegistry
     */
    protected $processorRegistry;

    /**
     * @param ProcessorRegistry $processorRegistry
     */
    public function __construct(ProcessorRegistry $processorRegistry)
    {
        $this->processorRegistry = $processorRegistry;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('file', 'file');

        $processorChoices = $this->getImportProcessorsChoices($options['entityName']);

        $builder->add(
            'processorAlias',
            'choice',
            array_merge(
                [
                    'choices' => $processorChoices,
                    'required' => true,
                    'preferred_choices' => $processorChoices ? [reset($processorChoices)] : [],
                    'empty_data' => key($processorChoices),
                ],
                $options['processorAliasOptions']
            )
        );
    }

    /**
     * @param string $entityName
     *
     * @return string[]
     */
    protected function getImportProcessorsChoices(string $entityName): array
    {
        $aliases = $this->processorRegistry->getProcessorAliasesByEntity(
            ProcessorRegistry::TYPE_IMPORT,
            $entityName
        );

        $result = [];
        foreach ($aliases as $alias) {
            $result[$alias] = $this->generateProcessorLabel($alias);
        }

        return $result;
    }

    /**
     * @param string $alias
     *
     * @return string
     */
    protected function generateProcessorLabel(string $alias): string
    {
        return sprintf('oro.importexport.import.%s', $alias);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' =>  ImportData::class,
                'processorAliasOptions' => [],
            ]
        );
        $resolver->setRequired(['entityName']);
        $resolver->setAllowedTypes(
            [
                'entityName' => 'string',
                'processorAliasOptions' => 'array',
            ]
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
        return self::NAME;
    }
}
