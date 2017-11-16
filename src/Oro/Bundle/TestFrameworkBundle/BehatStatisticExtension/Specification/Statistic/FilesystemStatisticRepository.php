<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Specification\Statistic;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model\FeatureStatistic;

/**
 * Get statistic json file from the root of the application, and return feature duration by path
 */
class FilesystemStatisticRepository implements StatisticRepositoryInterface, ObjectRepository
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

    /**
     * {@inheritdoc}
     */
    public function find($id)
    {
        $model = new FeatureStatistic();

        if (isset($this->featureDuration[$id])) {
            return $model->setTime($this->featureDuration[$id]);
        }

        return $model->setTime($this->averageFeatureTime);
    }

    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy(array $criteria)
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        throw new \RuntimeException('Not supported');
    }
}
