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
use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Database\Tools\DatabaseToolInterface;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
interface DatabaseBackupInterface
{
    public function getBackupFilePath(): ?string;

    public function isBackupExists(): bool;

    public function backup(AbstractExecutor $executor): void;

    public function restore(AbstractExecutor $executor): void;
}
