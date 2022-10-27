<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model;

use Doctrine\DBAL\Schema\Schema;

/**
 * Implementation of Statistic model.
 */
class FeatureStatistic implements StatisticModelInterface
{
    /** @var int */
    private $id;

    /** @var string */
    private $path;

    /** @var integer */
    private $time;

    /** @var string */
    private $gitBranch;

    /** @var string */
    private $gitTarget;

    /** @var integer */
    private $buildId;

    /** @var string */
    private $stageName;

    /** @var string */
    private $jobName;

    /** @var \DateTime */
    private $createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $path relative path to the feature file
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param int $time
     * @return $this
     */
    public function setDuration($time)
    {
        $this->time = $time;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDuration()
    {
        return $this->time;
    }

    /**
     * @param string $gitBranch
     * @return $this
     */
    public function setGitBranch($gitBranch)
    {
        $this->gitBranch = $gitBranch;

        return $this;
    }

    /**
     * @param string $gitTarget
     * @return $this
     */
    public function setGitTarget($gitTarget)
    {
        $this->gitTarget = $gitTarget;

        return $this;
    }

    /**
     * @param int $buildId
     * @return $this
     */
    public function setBuildId($buildId)
    {
        $this->buildId = $buildId;

        return $this;
    }

    /**
     * @param int $stageName
     * @return $this
     */
    public function setStageName($stageName)
    {
        $this->stageName = $stageName;

        return $this;
    }

    /**
     * @param int $jobName
     * @return $this
     */
    public function setJobName($jobName)
    {
        $this->jobName = $jobName;

        return $this;
    }

    /**
     * @param \DateTime $createdAt
     * @return FeatureStatistic
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $createdAt = $this->createdAt ?: new \DateTime('now', new \DateTimeZone('UTC'));

        return [
            'path' => $this->path,
            'time' => $this->time,
            'git_branch' => $this->gitBranch,
            'git_target' => $this->gitTarget,
            'build_id' => $this->buildId,
            'stage_name' => $this->stageName,
            'job_name' => $this->jobName,
            'created_at' => $createdAt->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function fromArray(array $data)
    {
        $model = new self();
        $model->id = $data['id'] ?? null;
        $model->path = $data['path'] ?? null;
        $model->time = $data['time'] ?? null;
        $model->gitBranch = $data['git_branch'] ?? null;
        $model->gitTarget = $data['git_target'] ?? null;
        $model->buildId = $data['build_id'] ?? null;
        $model->stageName = $data['stage_name'] ?? null;
        $model->jobName = $data['job_name'] ?? null;
        $model->createdAt = $data['created_at'] ? new \DateTime($data['created_at'], new \DateTimeZone('UTC')) : null;

        return $model;
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

        $id = $table->addColumn('id', 'integer', ['unsigned' => true]);
        $id->setAutoincrement(true);

        $table->addColumn('path', 'string');
        $table->addColumn('time', 'integer');
        $table->addColumn('git_branch', 'string', ['notnull' => false]);
        $table->addColumn('git_target', 'string', ['notnull' => false]);
        $table->addColumn('build_id', 'integer', ['notnull' => false]);
        $table->addColumn('stage_name', 'string', ['notnull' => false]);
        $table->addColumn('job_name', 'string', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['notnull' => false, 'default' => 'CURRENT_TIMESTAMP']);

        $table->setPrimaryKey(['id']);
        $table->addIndex(['path']);
        $table->addIndex(['git_branch']);
        $table->addIndex(['git_target']);
        $table->addIndex(['build_id']);
        $table->addIndex(['build_id', 'git_target', 'git_branch']);
        $table->addIndex(['created_at']);
    }

    /**
     * {@inheritdoc}
     */
    public static function getName()
    {
        return 'feature_stat';
    }

    /**
     * {@inheritdoc}
     */
    public static function getIdField()
    {
        return 'path';
    }
}
