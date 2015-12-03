<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
        $builder->add(
            'file',
            FileType::class,
            array(
                'required' => true
            )
        );

        $processorChoices = $this->getImportProcessorsChoices($options['entityName']);

        $builder->add(
            'processorAlias',
            ChoiceType::class,
            array(
                'choices' => $processorChoices,
                'required' => true,
                'preferred_choices' => $processorChoices ? array(reset($processorChoices)) : array(),
            )
        );
    }

    protected function getImportProcessorsChoices($entityName)
    {
        $aliases = $this->processorRegistry->getProcessorAliasesByEntity(
            ProcessorRegistry::TYPE_IMPORT,
            $entityName
        );
        $result = array();
        foreach ($aliases as $alias) {
            $result[$alias] = $this->generateProcessorLabel($alias);
        }
        return $result;
    }

    protected function generateProcessorLabel($alias)
    {
        return sprintf('oro.importexport.import.%s', $alias);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(
                'data_class' => 'Oro\Bundle\ImportExportBundle\Form\Model\ImportData',
            )
        );
        $resolver->setRequired(array('entityName'));
        $resolver->setAllowedTypes(
            array(
                'entityName' => 'string'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
