<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\HealthChecker;

use Behat\Behat\EventDispatcher\Event\BeforeFeatureTested;
use Behat\Behat\EventDispatcher\Event\BeforeScenarioTested;

/**
 * Feature code style checker
 */
class FeatureCSChecker implements HealthCheckerInterface
{
    const NAME_PATTERN = '/^[^.]*$/';

    /**
     * @var array
     */
    protected $errors = [];

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeFeatureTested::BEFORE => [
                ['checkFeatureName'],
                ['checkFeatureDescription']
            ],
        ];
    }

    /**
     * @param BeforeFeatureTested $event
     */
    public function checkFeatureName(BeforeFeatureTested $event)
    {
        $featureTitle = $event->getFeature()->getTitle();

        if ('' == trim($featureTitle)) {
            $this->errors[] = sprintf('Feature "%s" should have a title', $event->getFeature()->getFile());
            return;
        }

        if (!preg_match(self::NAME_PATTERN, $featureTitle)) {
            $this->errors[] = sprintf('Feature name "%s" contains forbidden characters', $featureTitle);
            return;
        }

        $featureTitle = $event->getFeature()->getTitle();
        $path = $event->getFeature()->getFile();

        $featureFileName = pathinfo($path, PATHINFO_BASENAME);
        $expectedFileName = $this->canonize($featureTitle).'.feature';

        if ($expectedFileName != $featureFileName) {
            $this->rename($path, $expectedFileName);
            $this->errors[] = sprintf(
                'Feature "%s" should be renamed to "%s".',
                $featureFileName,
                $expectedFileName
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'cs';
    }

    /**
     * @param BeforeFeatureTested $event
     */
    public function checkFeatureDescription(BeforeFeatureTested $event)
    {
        if (!$event->getFeature()->getDescription()) {
            $this->errors[] = sprintf('Feature "%s" should have description', $event->getFeature()->getFile());
        }
    }

    /**
     * @return bool
     */
    public function isFailure()
    {
        return !empty($this->errors);
    }

    /**
     * Return array of strings error messages
     * @return string[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param string $featureName
     * @return string
     */
    private function canonize($featureName)
    {
        $fileName = str_replace(' ', '_', $featureName);
        $fileName = strtolower($fileName);

        return preg_replace('/[^A-Za-z0-9\_]/', '', $fileName);
    }

    /**
     * @param string $featurePath
     * @param string $newFileName
     */
    private function rename($featurePath, $newFileName)
    {
        $dir = pathinfo($featurePath, PATHINFO_DIRNAME);
        $newFeaturePath = $dir.DIRECTORY_SEPARATOR.$newFileName;

        if (is_file($newFeaturePath)) {
            $this->errors[] = sprintf('File "%s" cannot be renamed to "%s"', $featurePath, $newFeaturePath);
            return;
        }

        rename($featurePath, $newFeaturePath);
    }
}
