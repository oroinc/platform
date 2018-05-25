<?php

namespace Oro\Bundle\IntegrationBundle\Datagrid;

use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmDatasource;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\IntegrationBundle\Manager\TypesRegistry;
use Symfony\Component\Translation\TranslatorInterface;

class StatusGridHelper
{
    /** @var TypesRegistry */
    protected $typesRegistry;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param TypesRegistry       $typesRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(TypesRegistry $typesRegistry, TranslatorInterface $translator)
    {
        $this->typesRegistry = $typesRegistry;
        $this->translator    = $translator;
    }

    /**
     * @param BuildBefore $event
     */
    public function statusGridBuildBefore(BuildBefore $event)
    {
        $params = $event->getDatagrid()->getParameters();

        if ($params->has('integrationType')) {
            $type = $params->get('integrationType');

            $connectorChoices = $this->typesRegistry->getAvailableConnectorsTypesChoiceList($type);
            $config = $event->getDatagrid()->getConfig();

            $config->offsetSetByPath(
                '[filters][columns][connector][options][field_options][choices]',
                array_flip($connectorChoices)
            );
        }
    }

    /**
     * Binds integration ID to query
     *
     * @param BuildAfter $event
     */
    public function statusGridBuildAfter(BuildAfter $event)
    {
        /** @var OrmDatasource $source */
        $source = $event->getDatagrid()->getDatasource();
        $params = $event->getDatagrid()->getParameters();

        if ($params->has('integrationId')) {
            $id = $params->get('integrationId');

            $source->getQueryBuilder()->setParameter('integrationId', $id);
        }
    }

    /**
     * Connector code callable property handler
     *
     * @return callable
     */
    public function connectorCodeProperty()
    {
        $registry   = $this->typesRegistry;
        $translator = $this->translator;

        return function (ResultRecordInterface $record) use ($registry, $translator) {
            $value           = $record->getValue('connector');
            $integrationType = $record->getValue('integrationType');

            try {
                $realConnector = $registry->getConnectorType($integrationType, $value);

                return $translator->trans($realConnector->getLabel());
            } catch (\LogicException $e) {
                return $value;
            }
        };
    }
}
