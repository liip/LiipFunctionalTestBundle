<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Services\DatabaseBackup;

use Doctrine\Common\DataFixtures\Executor\AbstractExecutor;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
interface DatabaseBackupInterface
{
    public function init(array $metadatas, array $classNames);

    /**
     * @return string
     */
    public function getBackupFilePath();

    /**
     * @return bool
     */
    public function isBackupActual();

    public function backup(AbstractExecutor $executor);

    public function restore(AbstractExecutor $executor);
}
