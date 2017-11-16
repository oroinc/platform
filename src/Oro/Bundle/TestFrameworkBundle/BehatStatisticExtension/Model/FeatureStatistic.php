<?php

namespace Oro\Bundle\TestFrameworkBundle\BehatStatisticExtension\Model;

use Doctrine\DBAL\Schema\Schema;

class FeatureStatistic implements StatisticModelInterface
{
    /**
     * @var mixed
     */
    protected $id;

    /**
     * @var string
     */
    protected $path;

    /**
     * @var integer
     */
    protected $time;

    /**
     * @var string
     */
    protected $gitBranch;

    /**
     * @var string
     */
    protected $gitTarget;

    /**
     * @var string
     */
    protected $buildId;

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
    public static function fromArray(array $data)
    {
        $model = new self();
        $model->id        = isset($data['id'])         ? $data['id']         : null;
        $model->path      = isset($data['path'])       ? $data['path']       : null;
        $model->time      = isset($data['time'])       ? $data['time']       : null;
        $model->gitBranch = isset($data['git_branch']) ? $data['git_branch'] : null;
        $model->gitTarget = isset($data['git_target']) ? $data['git_target'] : null;
        $model->buildId   = isset($data['build_id'])   ? $data['build_id']   : null;

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
    public function getDuration()
    {
        return $this->time;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->path;
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
        $table->addIndex(['path']);
        $table->addIndex(['git_branch']);
        $table->addIndex(['git_target']);
        $table->addIndex(['build_id']);
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
