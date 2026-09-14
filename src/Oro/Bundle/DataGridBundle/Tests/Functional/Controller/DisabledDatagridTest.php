<?php

declare(strict_types=1);

namespace Oro\Bundle\DataGridBundle\Tests\Functional\Controller;

use Oro\Bundle\FeatureToggleBundle\Tests\Functional\Stub\FeatureCheckerStub;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Checks that a datagrid disabled via the "datagrids" section of "features.yml" is not available.
 */
class DisabledDatagridTest extends WebTestCase
{
    private const string GRID_NAME = 'audit-grid';
    private const string ENTITY_GRID_NAME = 'business-unit-grid';
    private const string ENTITY_CLASS = 'Oro\\Bundle\\OrganizationBundle\\Entity\\BusinessUnit';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->disableReboot();

        /** @var FeatureCheckerStub $featureChecker */
        $featureChecker = self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $featureChecker->setResourceEnabled(self::GRID_NAME, 'datagrids', false);
    }

    #[\Override]
    protected function tearDown(): void
    {
        /** @var FeatureCheckerStub $featureChecker */
        $featureChecker = self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $featureChecker->setResourceEnabled(self::GRID_NAME, 'datagrids', null);
        $featureChecker->setResourceEnabled(self::ENTITY_CLASS, 'entities', null);

        parent::tearDown();
    }

    public function testTryToGetDataForDisabledDatagrid(): void
    {
        $this->client->request('GET', $this->getUrl('oro_datagrid_index', ['gridName' => self::GRID_NAME]));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetFilterMetadataForDisabledDatagrid(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_datagrid_filter_metadata', ['gridName' => self::GRID_NAME])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testTryToExportDisabledDatagrid(): void
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_datagrid_export_action', ['gridName' => self::GRID_NAME, 'format' => 'csv'])
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testTryToExecuteMassActionForDisabledDatagrid(): void
    {
        $this->ajaxRequest(
            'GET',
            $this->getUrl(
                'oro_datagrid_mass_action',
                ['gridName' => self::GRID_NAME, 'actionName' => 'delete', 'values' => '1']
            )
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetDataForDatagridWhenItsEntityIsDisabled(): void
    {
        /** @var FeatureCheckerStub $featureChecker */
        $featureChecker = self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $featureChecker->setResourceEnabled(self::ENTITY_CLASS, 'entities', false);

        $this->client->request('GET', $this->getUrl('oro_datagrid_index', ['gridName' => self::ENTITY_GRID_NAME]));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testGetDataForDatagridWhenItsEntityIsEnabled(): void
    {
        /** @var FeatureCheckerStub $featureChecker */
        $featureChecker = self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $featureChecker->setResourceEnabled(self::ENTITY_CLASS, 'entities', true);

        $this->client->request('GET', $this->getUrl('oro_datagrid_index', ['gridName' => self::ENTITY_GRID_NAME]));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }

    public function testGetDataForEnabledDatagrid(): void
    {
        /** @var FeatureCheckerStub $featureChecker */
        $featureChecker = self::getContainer()->get('oro_featuretoggle.checker.feature_checker');
        $featureChecker->setResourceEnabled(self::GRID_NAME, 'datagrids', true);

        $this->client->request('GET', $this->getUrl('oro_datagrid_index', ['gridName' => self::GRID_NAME]));

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_OK);
    }
}
