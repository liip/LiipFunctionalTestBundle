<?php

/*
 * This file is part of the Liip/FunctionalTestBundle
 *
 * (c) Lukas Kahwe Smith <smith@pooteeweet.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Liip\FunctionalTestBundle\Database\Tools;

/**
 * @author Aleksey Tupichenkov <alekseytupichenkov@gmail.com>
 */
class ORMMysqlDatabaseTool extends ORMDatabaseTool
{
    public function getDriverName(): string
    {
        return 'mysql';
    }

    public function purgeData(): void
    {
        $this->connection->query('SET FOREIGN_KEY_CHECKS=0');


        $this->connection->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
