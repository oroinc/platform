<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model;

use Doctrine\DBAL\Schema\Schema;

final class FeatureStatistic implements StatisticModelInterface
{
    /**
     * @var string
     */
    private $basePath;

    /**
     * @var mixed
     */
    private $id;

    /**
     * @var string
     */
    private $path;

    /**
     * @var integer
     */
    private $time;

    /**
     * @var string
     */
    private $gitBranch;

    /**
     * @var string
     */
    private $gitTarget;

    /**
     * @var string
     */
    private $buildId;

    /**
     * @param string $basePath
     * @return $this
     */
    public function setBasePath($basePath)
    {
        $this->basePath = $basePath;

        return $this;
    }

    /**
     * @param string $featurePath relative path to the feature file
     * @return $this
     */
    public function setPath($featurePath)
    {
        $basePathDirectories = explode(DIRECTORY_SEPARATOR, realpath($this->basePath));
        $featureDirectories = explode(DIRECTORY_SEPARATOR, realpath($featurePath));

        $this->path = implode(DIRECTORY_SEPARATOR, array_diff($featureDirectories, $basePathDirectories));

        return $this;
    }

    /**
     * @param mixed $time
     * @return $this
     */
    public function setTime($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * @param mixed $gitBranch
     * @return $this
     */
    public function setGitBranch($gitBranch)
    {
        $this->gitBranch = $gitBranch;

        return $this;
    }

    /**
     * @param mixed $gitTarget
     * @return $this
     */
    public function setGitTarget($gitTarget)
    {
        $this->gitTarget = $gitTarget;

        return $this;
    }

    /**
     * @param mixed $buildId
     * @return $this
     */
    public function setBuildId($buildId)
    {
        $this->buildId = $buildId;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return [
            'path' => $this->path,
            'time' => $this->time,
            'git_branch' => $this->gitBranch,
            'git_target' => $this->gitTarget,
            'build_id' => $this->buildId,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function isNew()
    {
        return null === $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public static function declareSchema(Schema $schema)
    {
        $table = $schema->createTable(self::getName());
        $id = $table->addColumn("id", "integer", ["unsigned" => true]);
        $id->setAutoincrement(true);

        $table->addColumn("path", "string");
        $table->addColumn("time", "integer");
        $table->addColumn('git_branch', 'string', ['notnull' => false]);
        $table->addColumn('git_target', 'string', ['notnull' => false]);
        $table->addColumn('build_id', 'integer', ['notnull' => false]);

        $table->setPrimaryKey(["id"]);
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'feature_stat';
    }
}
