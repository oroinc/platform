<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FilterBundle\Form\Type\Filter\AbstractDateFilterType;
use Oro\Bundle\FilterBundle\Provider\DateModifierInterface;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\QueryDefinitionUtil;
use Oro\Bundle\ReportBundle\Entity\Report;
use Oro\Bundle\ReportBundle\Entity\ReportType;
use Oro\Bundle\ReportBundle\Tests\Functional\ReportDateTimeFilterExtension;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Migrations\Data\ORM\LoadAdminUserData;

/**
 * Do not use fixtures, as the time specified in the filters may be different from the actual time at the
 * time of testing!
 * Creating entities in real time is a bit longer, but reduces the likelihood of an error in which the time
 * delay for the test exceeds 1 minute.
 */
class ReportControllerWithFiltersTest extends WebTestCase
{
    use ReportDateTimeFilterExtension;

    private const ENTITY_NOTE = Note::class;
    private const ENTITY_REPORT = Report::class;
    private const REPORT_NAME = 'Test report';
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    /** @var User */
    private $owner;

    /** @var BusinessUnit */
    private $businessUnit;

    /** @var ReportType */
    private $reportType;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var ConfigManager */
    private $configManager;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->entityManager = $this->getContainer()->get('doctrine')->getManager();
        $this->configManager = $this->getContainer()->get('oro_config.manager');
    }

    protected function tearDown(): void
    {
        $this->removeEntities(self::ENTITY_NOTE);
        $this->removeEntities(self::ENTITY_REPORT);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testCreateReportWithDateFilter(
        string $timeZone,
        array $definition,
        array $actual,
        array $expected
    ): void {
        $this->updateSystemTimeZone($timeZone);
        $this->prepareEntities($actual);
        $report = $this->createReport($definition);

        $this->client->request('GET', $this->getUrl('oro_report_view', ['id' => $report->getId()]));
        $result = $this->client->getResponse();

        $this->assertReportValid($actual, $expected, $result);
    }

    private function assertReportValid(array $actualRecords, array $expectedRecords, string $content): void
    {
        foreach ($expectedRecords as $expectedRecord) {
            $this->assertStringContainsString($expectedRecord, $content);
        }

        foreach (array_diff(array_keys($actualRecords), $expectedRecords) as $invalidRecord) {
            $this->assertStringNotContainsString($invalidRecord, $content);
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dataProvider(): \Generator
    {
        yield 'Date between' => [
            'timezone' => 'UTC',
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_BETWEEN, '-3 minutes', '+3 minutes'),
            'actual' => $this->buildEntities(['Note1' => [], 'Note2' => ['-5 minutes'], 'Note3' => ['+5 minutes']]),
            'expected' => ['Note1']
        ];

        yield 'Date between with Atlantic/Cape Verde timezone' => [
            'timezone' => 'Atlantic/Cape_Verde', // (UTC -01:00)
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_BETWEEN, '-3 minutes'),
            'actual' => $this->buildEntities(['Note1' => ['+58 minutes'], 'Note2' => ['+56 minutes']]),
            'expected' => ['Note1']
        ];

        yield 'Date between with Africa/Douala timezone' => [
            'timezone' => 'Africa/Douala', // (UTC +01:00)
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_BETWEEN, '+3 minutes'),
            'actual' => $this->buildEntities(['Note1' => ['-58 minutes'], 'Note2' => ['-56 minutes']]),
            'expected' => ['Note1']
        ];

        yield 'Date between with "now" variable' => [
            'timezone' => 'UTC',
            'definition' => $this->buildFilter(
                AbstractDateFilterType::TYPE_BETWEEN,
                $this->buildFilterVariable(DateModifierInterface::VAR_NOW, '-1'), // -1 day
                $this->buildFilterVariable(DateModifierInterface::VAR_NOW),
                true
            ),
            'actual' => $this->buildEntities([
                'Note1' => ['-1 day +5 minutes'],
                'Note2' => ['+5 minutes'],
                'Note3' => ['-1 day -5 minutes']
            ]),
            'expected' => ['Note1']
        ];

        yield 'Date between with "now" variable and Pacific/Midway timezone' => [
            'timezone' => 'Pacific/Midway', // (UTC -11:00)
            'definition' => $this->buildFilter(
                AbstractDateFilterType::TYPE_BETWEEN,
                $this->buildFilterVariable(DateModifierInterface::VAR_NOW, '-1'), // -1 day
                $this->buildFilterVariable(DateModifierInterface::VAR_NOW),
                true
            ),
            'actual' => $this->buildEntities([
                'Note1' => ['-1 day +5 minutes'],
                'Note2' => ['+5 minutes'],
                'Note3' => ['-1 day -5 minutes']
            ]),
            'expected' => ['Note1']
        ];

        yield 'Date equals with "today" variable and Pacific/Midway timezone' => [
            'timezone' => 'Pacific/Midway', // (UTC -11:00)
            'definition' => $this->buildFilter(
                AbstractDateFilterType::TYPE_EQUAL,
                $this->buildFilterVariable(DateModifierInterface::VAR_TODAY),
                isVariable: true
            ),
            'actual' => $this->buildEntities(['Note1' => []]),
            'expected' => ['Note1']
        ];

        yield 'Date equals with "today" variable and Pacific/Kiritimati timezone' => [
            'timezone' => 'Pacific/Kiritimati', // (UTC +14:00)
            'definition' => $this->buildFilter(
                AbstractDateFilterType::TYPE_EQUAL,
                $this->buildFilterVariable(DateModifierInterface::VAR_TODAY),
                isVariable: true
            ),
            'actual' => $this->buildEntities(['Note1' => []]),
            'expected' => ['Note1']
        ];

        yield 'Date not equals with "today" variable and Pacific/Midway timezone' => [
            'timezone' => 'Pacific/Midway', // (UTC -11:00)
            'definition' => $this->buildFilter(
                AbstractDateFilterType::TYPE_NOT_EQUAL,
                end: $this->buildFilterVariable(DateModifierInterface::VAR_TODAY),
                isVariable: true
            ),
            'actual' => $this->buildEntities([
                'Note1' => [],
                'Note2' => ['-1 day -1 minutes'],
                'Note3' => ['+1 day +1 minutes']
            ]),
            'expected' => ['Note2', 'Note3']
        ];

        yield 'Date not equals with "today" variable and Pacific/Kiritimati timezone' => [
            'timezone' => 'Pacific/Kiritimati', // (UTC +14:00)
            'definition' => $this->buildFilter(
                AbstractDateFilterType::TYPE_NOT_EQUAL,
                end: $this->buildFilterVariable(DateModifierInterface::VAR_TODAY),
                isVariable: true
            ),
            'actual' => $this->buildEntities([
                'Note1' => [],
                'Note2' => ['-1 day -1 minutes'],
                'Note3' => ['+1 day +1 minutes']
            ]),
            'expected' => ['Note2', 'Note3']
        ];

        yield 'Date between with "today" variable and Pacific/Midway timezone' => [
            'timezone' => 'Pacific/Midway', // (UTC -11:00)
            'definition' => $this->buildFilter(
                AbstractDateFilterType::TYPE_BETWEEN, // No time modifier required.
                $this->buildFilterVariable(DateModifierInterface::VAR_TODAY), // 2000-12-12 00:00:00
                $this->buildFilterVariable(DateModifierInterface::VAR_TODAY), // 2000-12-13 00:00:00
                true
            ),
            'actual' => $this->buildEntities(['Note1' => []]),
            'expected' => ['Note1']
        ];

        yield 'Date not between' => [
            'timezone' => 'UTC',
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_NOT_BETWEEN, '-3 minutes', '+3 minutes'),
            'actual' => $this->buildEntities(['Note1' => [], 'Note2' => ['-5 minutes'], 'Note3' => ['+5 minutes']]),
            'expected' => ['Note2', 'Note3'],
        ];

        yield 'Date not between with Atlantic/Cape Verde timezone' => [
            'timezone' => 'Atlantic/Cape_Verde', // (UTC -01:00)
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_NOT_BETWEEN, '-3 minutes'),
            'actual' => $this->buildEntities(['Note1' => ['+58 minutes'], 'Note2' => ['+56 minutes']]),
            'expected' => ['Note2']
        ];

        yield 'Date later than' => [
            'timezone' => 'UTC',
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_MORE_THAN),
            'actual' => $this->buildEntities(['Note1' => ['+1 minutes'], 'Note2' => ['-1 minutes']]),
            'expected' => ['Note1']
        ];

        yield 'Date later than with Atlantic/Cape Verde timezone' => [
            'timezone' => 'Atlantic/Cape_Verde', // (UTC -01:00)
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_MORE_THAN),
            'actual' => $this->buildEntities(['Note1' => ['+1 hour +5 minutes'], 'Note2' => ['+55 minutes']]),
            'expected' => ['Note1']
        ];

        yield 'Date earlier than' => [
            'timezone' => 'UTC',
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_LESS_THAN),
            'actual' => $this->buildEntities(['Note1' => ['-1 minutes'], 'Note2' => ['+1 minutes']]),
            'expected' => ['Note1']
        ];

        yield 'Date earlier than with Atlantic/Cape Verde timezone' => [
            'timezone' => 'Atlantic/Cape_Verde', // (UTC -01:00)
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_LESS_THAN),
            'actual' => $this->buildEntities(['Note1' => ['+59 minutes'], 'Note2' => ['+1 hour +1 minutes']]),
            'expected' => ['Note1']
        ];

        yield 'Date equals' => [
            'timezone' => 'UTC',
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_EQUAL),
            'actual' => $this->buildEntities(['Note1' => [], 'Note2' => ['+1 minutes'], 'Note3' => ['-1 minutes']]),
            'expected' => ['Note1']
        ];

        yield 'Date equals with Atlantic/Cape Verde timezone' => [
            'timezone' => 'Atlantic/Cape_Verde', // (UTC -01:00)
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_EQUAL),
            'actual' => $this->buildEntities(['Note1' => ['-1 hour'], 'Note2' => [], 'Note3' => ['+1 hour']]),
            'expected' => ['Note3']
        ];

        yield 'Date not equals' => [
            'timezone' => 'UTC',
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_NOT_EQUAL),
            'actual' => $this->buildEntities(['Note1' => [], 'Note2' => ['+1 minutes'], 'Note3' => ['-1 minutes']]),
            'expected' => ['Note2', 'Note3']
        ];

        yield 'Date not equals with Atlantic/Cape Verde timezone' => [
            'timezone' => 'Atlantic/Cape_Verde', // (UTC -01:00)
            'definition' => $this->buildFilter(AbstractDateFilterType::TYPE_NOT_EQUAL),
            'actual' => $this->buildEntities(['Note1' => [], 'Note2' => ['+1 hour'], 'Note3' => ['-1 hour']]),
            'expected' => ['Note1', 'Note3']
        ];

        yield 'Start of week variable' => [
            'timezone' => 'UTC',
            'definition' => $this->buildFilter(
                AbstractDateFilterType::TYPE_EQUAL,
                $this->buildFilterVariable(DateModifierInterface::VAR_SOW),
                isVariable: true
            ),
            'actual' => $this->buildEntities(['Note1' => ['this week'], 'Note2' => ['this week -1 day']]),
            'expected' => ['Note1']
        ];

        yield 'Start of week variable with Pacific/Kiritimati timezone' => [
            'timezone' => 'Pacific/Kiritimati', // (UTC +14:00)
            'definition' => $this->buildFilter(
                AbstractDateFilterType::TYPE_EQUAL,
                $this->buildFilterVariable(DateModifierInterface::VAR_SOW),
                isVariable: true
            ),
            'actual' => $this->buildEntities([
                'Note1' => ['this week', 'now', 'Pacific/Kiritimati'],
                'Note2' => ['this week -1 day', 'now', 'Pacific/Kiritimati'],
            ]),
            'expected' => ['Note1']
        ];
    }

    private function buildFilter(int $type, string $start = null, string $end = null, bool $isVariable = false): array
    {
        if ($isVariable) {
            $start = $start ?: '';
            $end = $end ?: '';
        } else {
            $start = $start ? $this->dateTimeWithModifyAsString($start) : $this->dateTimeWithModifyAsString();
            $end = $end ? $this->dateTimeWithModifyAsString($end) : $this->dateTimeWithModifyAsString();
        }

        $filterData = ['type' => strval($type), 'part' => 'value', 'value' => ['start' => $start, 'end' => $end]];

        return [
            'columns' => [['name' => 'message', 'label' => 'Note message', 'func' => '', 'sorting' => '']],
            'filters' => [['columnName' => 'createdAt', 'criterion' => ['filter' => 'datetime', 'data' => $filterData]]]
        ];
    }

    private function buildFilterVariable(int $variable, string $modifier = ''): string
    {
        return sprintf('{{%s}}%s', $variable, $modifier);
    }

    private function buildEntities(array $entitiesData): array
    {
        $data = [];
        foreach ($entitiesData as $name => $entityData) {
            $data[$name] = call_user_func_array([$this, 'dateTimeWithModify'], array_values($entityData));
        }

        return $data;
    }

    private function prepareEntities(array $entitiesData): void
    {
        foreach ($entitiesData as $message => $createdAt) {
            $note = $this->createEntity($message, $createdAt);
            $this->entityManager->persist($note);
        }
        $this->entityManager->flush();
    }

    private function updateSystemTimeZone(string $timeZone): void
    {
        $this->configManager->set('oro_locale.timezone', $timeZone);
        $this->configManager->flush();
    }

    private function createEntity(string $message, \DateTime $createAt): Note
    {
        $owner = $this->getOwner();
        $note = new Note();
        $note->setOwner($owner);
        $note->setMessage($message);
        $note->setCreatedAt($createAt);
        $note->setOrganization($owner->getOrganization());

        return $note;
    }

    private function createReport(array $definition): Report
    {
        $businessUnit = $this->getBusinessUnit();
        $type = $this->getReportType();

        $report = new Report();
        $report->setName(self::REPORT_NAME);
        $report->setEntity(self::ENTITY_NOTE);
        $report->setType($type);
        $report->setDefinition(QueryDefinitionUtil::encodeDefinition($definition));
        $report->setOwner($businessUnit);
        $report->setOrganization($businessUnit->getOrganization());
        $report->setChartOptions([]);

        $this->entityManager->persist($report);
        $this->entityManager->flush();

        return $report;
    }

    private function removeEntities(string $className): void
    {
        $noteRepository = $this->entityManager->getRepository($className);
        foreach ($noteRepository->findAll() as $note) {
            $this->entityManager->remove($note);
        }
        $this->entityManager->flush();
    }

    private function getOwner(string $email = LoadAdminUserData::DEFAULT_ADMIN_EMAIL): User
    {
        if (!$this->owner) {
            $userRepository = $this->entityManager->getRepository(User::class);
            return $userRepository->findOneBy(['email' => $email]);
        }

        return $this->owner;
    }

    private function getBusinessUnit(
        string $businessUnit = LoadOrganizationAndBusinessUnitData::MAIN_BUSINESS_UNIT
    ): BusinessUnit {
        if (!$this->businessUnit) {
            /** @var BusinessUnit $owner */
            $buRepository = $this->entityManager->getRepository(BusinessUnit::class);
            return $buRepository->findOneBy(['name' => $businessUnit]);
        }

        return $this->businessUnit;
    }

    private function getReportType(string $reportType = ReportType::TYPE_TABLE): ReportType
    {
        if (!$this->reportType) {
            /** @var ReportType $type */
            $reportTypeRepository = $this->entityManager->getRepository(ReportType::class);
            return $reportTypeRepository->findOneBy(['name' => $reportType]);
        }

        return $this->reportType;
    }
}
