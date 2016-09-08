<?php

namespace Oro\Bundle\DataGridBundle\Extension\Export;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;

class ExportExtension extends AbstractExtension
{
    const EXPORT_OPTION_PATH = '[options][export]';

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * Constructor
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        // validate configuration and fill default values
        $options = $this->validateConfiguration(
            new Configuration(),
            ['export' => $config->offsetGetByPath(self::EXPORT_OPTION_PATH, false)]
        );
        // translate labels
        foreach ($options as &$option) {
            $option['label'] = $this->translator->trans($option['label']);
        }
        // push options back to config
        $config->offsetSetByPath(self::EXPORT_OPTION_PATH, $options);

        return !empty($options);
    }
}
