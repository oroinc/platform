<?php

namespace Oro\Bundle\SearchBundle\Engine;


interface EngineInterface
{
    public function delete($entity, $realtime = true);
    public function reindex();
    public function save($entity, $realtime = true, $needToCompute = false);
    public function search($query);
}
