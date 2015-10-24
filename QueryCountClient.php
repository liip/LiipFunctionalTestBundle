<?php

namespace Liip\FunctionalTestBundle;

use Symfony\Bundle\FrameworkBundle\Client;

class QueryCountClient extends Client
{
    /** @var QueryCounter */
    private $queryCounter;

    public function setQueryCounter(QueryCounter $queryCounter)
    {
        $this->queryCounter = $queryCounter;
    }

    public function request(
        $method,
        $uri,
        array $parameters = [],
        array $files = [],
        array $server = [],
        $content = null,
        $changeHistory = true
    ) {
        $crawler = parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        if ($this->getProfile()) {
            $this->queryCounter->checkQueryCount(
                $this->getProfile()->getCollector('db')->getQueryCount()
            );
        }

        return $crawler;
    }
}
