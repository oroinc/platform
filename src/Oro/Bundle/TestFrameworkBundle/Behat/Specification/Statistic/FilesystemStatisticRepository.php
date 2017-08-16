<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Specification\Statistic;

/**
 * Get statistic json file from the root of the application, and return feature duration by path
 */
class FilesystemStatisticRepository implements StatisticRepositoryInterface
{
    const FEATURE_DURATION_FILE = 'feature_duration.json';

    /**
     * @var array
     */
    protected $featureDuration;

    /**
     * @var int
     */
    protected $averageFeatureTime;

    /**
     * @param string $kernelRootDir
     */
    public function __construct($kernelRootDir)
    {
        $file = $kernelRootDir.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.self::FEATURE_DURATION_FILE;

        if (!is_file($file)) {
            $this->featureDuration = [];
            return;
        }

        $this->featureDuration = json_decode(file_get_contents($file), true);

        if ($this->featureDuration) {
            $this->averageFeatureTime = (int) round(array_sum($this->featureDuration)/count($this->featureDuration));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getFeatureDuration($path)
    {
        foreach ($this->featureDuration as $feature => $duration) {
            if (false !== strpos($path, $feature)) {
                return $duration;
            }
        }

        return $this->averageFeatureTime;
    }
}
