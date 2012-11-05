<?php

namespace Liip\FunctionalTestBundle\Test;

use Symfony\Bundle\FrameworkBundle\Client;
use Liip\FunctionalTestBundle\Exception\AllowedQueriesExceededException;

class QueryCountClient extends Client
{
    private $defaultQueryCount;

    public function setDefaultQueryCount($count)
    {
        $this->defaultQueryCount = $count;
    }

    public function request(
        $method,
        $uri,
        array $parameters = array(),
        array $files = array(),
        array $server = array(),
        $content = null,
        $changeHistory = true
    ) {
        $crawler = parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        $queryCount = $this->getProfile()->getCollector('db')->getQueryCount();

        $this->getMaxQueryCount();

        if ($queryCount > $this->defaultQueryCount) {
            throw new AllowedQueriesExceededException(
                "Allowed amount of queries ({$this->defaultQueryCount}) exceeded (actual: $queryCount)."
            );
        }

        return $crawler;
    }
}
