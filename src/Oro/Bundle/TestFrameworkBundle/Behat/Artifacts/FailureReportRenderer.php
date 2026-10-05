<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Artifacts;

/**
 * Renders the Markdown failure report of a FailureContext and its rows in the failure indexes.
 * It only formats text and does no file operations.
 *
 * The first sections follow the Playwright "Fix with AI" prompt, so the report can go to an AI assistant as is.
 * The Oro artifacts and the browser console errors come after them.
 */
class FailureReportRenderer
{
    private const MAX_SNAPSHOT_LINES = 400;

    private const MAX_SOURCE_LINES = 200;

    private const SOURCE_CONTEXT_LINES = 60;

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function renderReport(FailureContext $context): string
    {
        $lines = [
            '# Instructions',
            '',
            '- Following Oro Behat scenario failed.',
            '- Explain why, be concise, respect Oro Behat testing conventions.',
            '- Provide a snippet of code with the fix, if possible.',
            '',
            '# Test info',
            '',
            sprintf(
                '- Name: %s >> %s',
                $context->featureTitle ?: basename($context->featureFile),
                $context->scenarioTitle ?: 'line ' . $context->scenarioLine
            ),
            sprintf('- Location: %s:%d', $context->featureFile, $context->scenarioLine),
            '',
            '# Error details',
            '',
            '```',
            '' !== trim($context->exception) ? trim($context->exception) : '(no exception message)',
            '```',
        ];

        if (null !== $context->ariaSnapshot && '' !== trim($context->ariaSnapshot)) {
            $lines[] = '';
            $lines[] = '# Page snapshot';
            $lines[] = '';
            $lines[] = '```yaml';
            array_push($lines, ...$this->limitLines(rtrim($context->ariaSnapshot), self::MAX_SNAPSHOT_LINES));
            $lines[] = '```';
        }

        if (null !== $context->featureSource && '' !== trim($context->featureSource)) {
            $lines[] = '';
            $lines[] = '# Test source';
            $lines[] = '';
            $lines[] = '```gherkin';
            array_push($lines, ...$this->renderSource($context->featureSource, $context->stepLine));
            $lines[] = '```';
        }

        $lines[] = '';
        $lines[] = '# Artifacts';
        $lines[] = '';
        $lines[] = '| | |';
        $lines[] = '|---|---|';
        foreach ($this->buildArtifactRows($context) as [$name, $value]) {
            $lines[] = sprintf('| %s | %s |', $name, $this->escapeCell($value));
        }

        $lines[] = '';
        $lines[] = '# Browser console errors';
        $lines[] = '';
        if ($context->jsErrors) {
            $lines[] = '```';
            foreach ($context->jsErrors as $error) {
                $lines[] = sprintf(
                    '[%s] [%s] [%s] %s',
                    $error['time'] ?? '',
                    $error['session'] ?? '',
                    $error['type'] ?? '',
                    $error['message'] ?? ''
                );
            }
            $lines[] = '```';
        } else {
            $lines[] = '_None captured._';
        }

        return implode("\n", $lines) . "\n";
    }

    public function renderIndexHeader(): string
    {
        return "# Behat failure reports\n\n| Scenario | Feature file | Failed step | Report |\n|---|---|---|---|\n";
    }

    /**
     * REPORT.html replaces the Playwright HTML report, which only the @playwright/test runner can make.
     */
    public function renderHtmlIndexHeader(): string
    {
        return <<<'HTML'
<!DOCTYPE html>
<meta charset="utf-8">
<title>Behat failure reports</title>
<style>
    body { font: 14px/1.5 -apple-system, "Segoe UI", Roboto, sans-serif; margin: 24px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ddd; padding: 6px 10px; text-align: left; vertical-align: top; }
    th { background: #f5f5f5; }
    code { background: #f5f5f5; padding: 1px 4px; }
</style>
<h1>Behat failure reports</h1>
<table>
<tr><th>Scenario</th><th>Feature file</th><th>Failed step</th><th>Artifacts</th></tr>

HTML;
    }

    /**
     * The artifact links are relative, so the page works both locally and in the collected job artifacts.
     */
    public function renderHtmlIndexRow(FailureContext $context, string $reportFileName): string
    {
        $links = [sprintf('<a href="./%s">report</a>', rawurlencode($reportFileName))];
        foreach ($context->tracePaths as $session => $path) {
            $label = count($context->tracePaths) > 1 ? sprintf('trace (%s)', $session) : 'trace';
            $links[] = sprintf('<a href="./%s">%s</a>', rawurlencode(basename($path)), htmlspecialchars($label));
        }
        foreach ($context->screenshotUrls as $i => $url) {
            $label = count($context->screenshotUrls) > 1 ? sprintf('screenshot %d', $i + 1) : 'screenshot';
            $links[] = sprintf('<a href="%s">%s</a>', htmlspecialchars($url), $label);
        }
        if (null !== $context->artifactsBaseUrl) {
            $links[] = sprintf('<a href="%sprod.log">prod.log</a>', htmlspecialchars($context->artifactsBaseUrl));
            $links[] = sprintf('<a href="%s">all</a>', htmlspecialchars($context->artifactsBaseUrl));
        }

        return sprintf(
            "<tr><td>%s</td><td>%s</td><td><code>%s</code></td><td>%s</td></tr>\n",
            htmlspecialchars($context->scenarioTitle ?: 'line ' . $context->scenarioLine),
            htmlspecialchars(sprintf('%s:%d', basename($context->featureFile), $context->scenarioLine)),
            htmlspecialchars($context->stepText),
            implode(' · ', $links)
        );
    }

    /**
     * @param string[] $reportUrls Download URLs of the published report artifact
     */
    public function renderIndexRow(FailureContext $context, string $reportFileName, array $reportUrls = []): string
    {
        $reportCell = sprintf('[%s](./%s)', $this->escapeCell($reportFileName), rawurlencode($reportFileName));
        foreach ($reportUrls as $url) {
            $reportCell .= sprintf(' · [download](%s)', $url);
        }

        return sprintf(
            "| %s | %s | %s | %s |\n",
            $this->escapeCell($context->scenarioTitle ?: 'line ' . $context->scenarioLine),
            $this->escapeCell(sprintf('%s:%d', basename($context->featureFile), $context->scenarioLine)),
            $this->escapeCell(sprintf('`%s`', $context->stepText)),
            $reportCell
        );
    }

    /**
     * @return array<array{0: string, 1: string}>
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function buildArtifactRows(FailureContext $context): array
    {
        $rows = [
            ['Feature file', $this->fileLink($context->featureFile, $context->scenarioLine)],
            ['Failed step', sprintf('`%s` — line %d', $context->stepText, $context->stepLine)],
        ];

        if (null !== $context->pageUrl) {
            $rows[] = ['Page URL', $context->pageUrl];
        }

        foreach ($context->screenshotUrls as $i => $url) {
            $label = count($context->screenshotUrls) > 1 ? sprintf('Screenshot %d', $i + 1) : 'Screenshot';
            $rows[] = [$label, sprintf('[%s](%s)', basename(parse_url($url, PHP_URL_PATH) ?: $url), $url)];
        }

        foreach ($context->tracePaths as $session => $path) {
            $label = count($context->tracePaths) > 1 ? sprintf('Trace (%s)', $session) : 'Trace';
            $link = null !== $context->artifactsBaseUrl
                ? sprintf('[%s](%splaywright-traces/%s)', basename($path), $context->artifactsBaseUrl, basename($path))
                : $this->fileLink($path);
            $rows[] = [$label, sprintf('%s — `npx playwright show-trace %s`', $link, $path)];
        }

        if (null !== $context->prodLogPath) {
            $link = null !== $context->artifactsBaseUrl
                ? sprintf('[prod.log](%sprod.log)', $context->artifactsBaseUrl)
                : $this->fileLink($context->prodLogPath);
            $rows[] = ['Prod log', $link];
        }

        if (null !== $context->artifactsBaseUrl) {
            $rows[] = ['All artifacts', $context->artifactsBaseUrl];
        }

        if ('' !== $context->failedAt) {
            $rows[] = ['Failed at', $context->failedAt];
        }

        return $rows;
    }

    /**
     * Numbers the source lines and marks the failed line with ">", as the Playwright "Test source" section does.
     * For a long file, it shows only the lines around the failed line.
     *
     * @return string[]
     */
    private function renderSource(string $source, int $failedLine): array
    {
        $sourceLines = preg_split('/\R/u', rtrim($source, "\r\n")) ?: [];
        $total = count($sourceLines);

        $first = 1;
        $last = $total;
        if ($total > self::MAX_SOURCE_LINES) {
            $first = max(1, $failedLine - self::SOURCE_CONTEXT_LINES);
            $last = min($total, $failedLine + self::SOURCE_CONTEXT_LINES);
        }

        $width = strlen((string)$last);
        $rendered = [];
        if ($first > 1) {
            $rendered[] = sprintf('  %s | …', str_pad('', $width));
        }
        for ($line = $first; $line <= $last; $line++) {
            $rendered[] = sprintf(
                '%s %s | %s',
                $line === $failedLine ? '>' : ' ',
                str_pad((string)$line, $width, ' ', STR_PAD_LEFT),
                $sourceLines[$line - 1]
            );
        }
        if ($last < $total) {
            $rendered[] = sprintf('  %s | …', str_pad('', $width));
        }

        return $rendered;
    }

    /**
     * @return string[]
     */
    private function limitLines(string $text, int $maxLines): array
    {
        $lines = preg_split('/\R/u', $text) ?: [];
        if (count($lines) <= $maxLines) {
            return $lines;
        }

        $lines = array_slice($lines, 0, $maxLines);
        $lines[] = sprintf('… (truncated to %d lines)', $maxLines);

        return $lines;
    }

    private function fileLink(string $path, ?int $line = null): string
    {
        $label = basename($path) . (null !== $line ? ':' . $line : '');

        return sprintf('[%s](file://%s)', $label, implode('/', array_map('rawurlencode', explode('/', $path))));
    }

    /**
     * A Markdown table cell must stay on one line and must not contain unescaped pipes.
     */
    private function escapeCell(string $value): string
    {
        return str_replace(['|', "\r\n", "\n", "\r"], ['\\|', ' ', ' ', ' '], $value);
    }
}
