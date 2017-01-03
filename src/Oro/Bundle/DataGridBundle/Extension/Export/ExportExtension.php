<?php

namespace Oro\Bundle\DataGridBundle\Extension\Export;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ExportExtension extends AbstractExtension
{
    const EXPORT_OPTION_PATH = '[options][export]';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * Constructor
     *
     * @param TranslatorInterface $translator
     * @param SecurityFacade $securityFacade
     */
    public function __construct(TranslatorInterface $translator, SecurityFacade $securityFacade)
    {
        $this->translator = $translator;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config)
    {
        if (!$this->isGranted()) {
            return false;
        }

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

    /**
     * Checks ACL Permissions
     *
     * @return bool
     */
    protected function isGranted()
    {
        // we have to be sure that token is not null because Marketing Lists uses Grid building to get
        // query builder, some functional also uses Grid building without security context set
        return
            null !== $this->securityFacade->getToken()
            && $this->securityFacade->isGranted('oro_datagrid_gridview_export');
    }
}
