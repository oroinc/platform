<?php

namespace Oro\Bundle\ImportExportBundle\Form\Type;

use Oro\Bundle\ImportExportBundle\Form\Model\ExportData;
use Oro\Bundle\ImportExportBundle\Processor\ProcessorRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Form type for selecting an export processor.
 *
 * This form type provides a choice field for selecting which export processor to use
 * for a given entity. It dynamically populates the choices based on available processors
 * for the entity, allowing users to select from multiple export options. The form can
 * be configured to filter processors by alias and supports both single and multiple
 * processor selections.
 */
class ExportType extends AbstractType
{
    public const NAME = 'oro_importexport_export';

    /**
     * @var ProcessorRegistry
     */
    protected $processorRegistry;

    public function __construct(ProcessorRegistry $processorRegistry)
    {
        $this->processorRegistry = $processorRegistry;
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'processorAlias',
            ChoiceType::class,
            [
                'label' => 'oro.importexport.export.popup.options.label',
                'choices' => $this->getExportProcessorsChoices($options),
                'required' => true,
                'placeholder' => false,
            ]
        );
    }

    /**
     * @param array $options
     * @return array
     */
    protected function getExportProcessorsChoices($options)
    {
        $entityName = $options['entityName'];
        $processorAlias = $options['processorAlias'] ?? null;

        $aliases = $this->processorRegistry->getProcessorAliasesByEntity(
            $this->getProcessorType(),
            $entityName
        );

        if (is_array($processorAlias) && count($processorAlias) > 0) {
            $aliases = array_intersect($aliases, $processorAlias);
        } elseif (is_string($processorAlias)) {
            $aliases = array_intersect($aliases, [$processorAlias]);
        }

        $result = [];
        foreach ($aliases as $alias) {
            $result[$this->generateProcessorLabel($alias)] = $alias;
        }

        return $result;
    }

    /**
     * @return string
     */
    protected function getProcessorType()
    {
        return ProcessorRegistry::TYPE_EXPORT;
    }

    /**
     * @param string $alias
     * @return string
     */
    protected function generateProcessorLabel($alias)
    {
        return sprintf('oro.importexport.export.%s', $alias);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ExportData::class,
            'processorAlias' => null
        ]);
        $resolver->setRequired(['entityName']);

        $resolver->setAllowedTypes('entityName', 'string');
        $resolver->setAllowedTypes('processorAlias', ['string', 'array', 'null']);
    }

    public function getName()
    {
        return $this->getBlockPrefix();
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return self::NAME;
    }
}
