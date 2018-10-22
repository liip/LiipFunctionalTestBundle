<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Database\Backup;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;
use Doctrine\ORM\EntityManager;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
final class MongodbDatabaseBackup extends AbstractDatabaseBackup implements DatabaseBackupInterface
{
    protected static $referenceData;

    protected static $metadata;

    protected static $databases;

    public function init(array $metadatas, array $classNames): void
    {
        $this->metadatas = $metadatas;
        $this->classNames = $classNames;
    }

    public function getBackupFilePath(): string
    {
        return '';
    }

    public function backup(AbstractExecutor $executor): void
    {

    }

    public function restore(AbstractExecutor $executor): void
    {

    }
}
