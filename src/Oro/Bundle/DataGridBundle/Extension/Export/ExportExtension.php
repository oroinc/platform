<?php

namespace Oro\Bundle\DataGridBundle\Extension\Export;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\DatasourceInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ExportExtension extends AbstractExtension
{
    const EXPORT_OPTION_PATH = '[options][export]';

    /** @var TranslatorInterface */
    protected $translator;

    /** @var AuthorizationCheckerInterface */
    protected $authorizationChecker;

    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param TranslatorInterface           $translator
     * @param AuthorizationCheckerInterface $authorizationChecker
     * @param TokenStorageInterface         $tokenStorage
     */
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
    public function isApplicable(DatagridConfiguration $config)
    {
        if (!parent::isApplicable($config) || !$this->isGranted()) {
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
     * {@inheritdoc}
     *
     * @throws UnexpectedTypeException
     */
    public function visitDatasource(DatagridConfiguration $config, DatasourceInterface $datasource)
    {
        $exportParameters = $this->getParameters()->get('_export');

        if (is_array($exportParameters) && array_key_exists('ids', $exportParameters)) {
            if (! $datasource instanceof OrmDatasource) {
                throw new UnexpectedTypeException($datasource, OrmDatasource::class);
            }

            /* @var OrmDatasource $datasource */
            $qb = $datasource->getQueryBuilder();
            $alias = $qb->getRootAliases()[0];
            $name = $qb->getEntityManager()
                ->getClassMetadata($qb->getRootEntities()[0])
                ->getSingleIdentifierFieldName();

            $qb->andWhere($alias.'.'.$name.' IN (:exportIds)')
                ->setParameter('exportIds', $exportParameters['ids']);
        }
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
            null !== $this->tokenStorage->getToken()
            && $this->authorizationChecker->isGranted('oro_datagrid_gridview_export');
    }
}
