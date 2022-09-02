<?php

namespace Oro\Bundle\DataGridBundle\Extension\Export;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\ImportExportBundle\Entity\ImportExportResult;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a way to enable export of data displayed by a datagrid.
 */
class ExportExtension extends AbstractExtension
{
    public const EXPORT_OPTION_PATH = '[options][export]';

    private TranslatorInterface $translator;
    private AuthorizationCheckerInterface $authorizationChecker;
    private TokenStorageInterface $tokenStorage;

    public function __construct(
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage
    ) {
        $this->translator = $translator;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(DatagridConfiguration $config): bool
    {
        if (!parent::isApplicable($config) || !$this->isGranted()) {
            return false;
        }

        $options = $config->offsetGetByPath(self::EXPORT_OPTION_PATH, false);

        return
            true === $options
            || (\is_array($options) && !empty($options));
    }

    /**
     * {@inheritdoc}
     */
    public function processConfigs(DatagridConfiguration $config): void
    {
        // validate configuration and fill default values
        $options = $this->validateConfiguration(
            new Configuration(),
            ['export' => $config->offsetGetByPath(self::EXPORT_OPTION_PATH, false)]
        );

        // translate labels
        foreach ($options as $key => $option) {
            $options[$key]['label'] = isset($option['label'])
                ? $this->translator->trans((string)$option['label'])
                : '';
        }

        // push options back to config
        $config->offsetSetByPath(self::EXPORT_OPTION_PATH, $options);
    }

    private function isGranted(): bool
    {
        // we have to be sure that token is not null because Marketing Lists uses Grid building to get
        // query builder, some functional also uses Grid building without security context set
        return
            null !== $this->tokenStorage->getToken()
            && $this->authorizationChecker->isGranted('oro_datagrid_gridview_export')
            && $this->authorizationChecker->isGranted('VIEW', 'entity:' . ImportExportResult::class);
    }
}
