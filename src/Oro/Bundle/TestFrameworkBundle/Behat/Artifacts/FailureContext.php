<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

/**
 * Data that the suite collects about one failed Behat scenario for the FailureReportRenderer report.
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class FailureContext
{
    public string $featureFile = '';

    public ?string $featureTitle = null;

    public ?string $scenarioTitle = null;

    public int $scenarioLine = 0;

    /** Failed step with its keyword, for example `And I click "Save"` */
    public string $stepText = '';

    public int $stepLine = 0;

    public string $exception = '';

    public ?string $pageUrl = null;

    /** @var string[] */
    public array $screenshotUrls = [];

    /** @var array<array{session: string, type: string, message: string, time: string}> */
    public array $jsErrors = [];

    /** @var array<string, string> Session name => trace archive path */
    public array $tracePaths = [];

    public ?string $prodLogPath = null;

    /** Public base URL of the collected job artifacts, with a slash at the end. CI only. */
    public ?string $artifactsBaseUrl = null;

    public string $failedAt = '';

    /** ARIA snapshot in YAML of the page where the scenario failed */
    public ?string $ariaSnapshot = null;

    /** Raw content of the feature file */
    public ?string $featureSource = null;
}
