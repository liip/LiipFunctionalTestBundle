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
        array $parameters = array(),
        array $files = array(),
        array $server = array(),
        $content = null,
        $changeHistory = true
    ) {
        $crawler = parent::request($method, $uri, $parameters, $files, $server, $content, $changeHistory);

        if ($this->getProfile()) {
            $this->queryCounter->checkQueryCount(
                $this->getProfile()->getCollector('db')->getQueryCount()
            );
        } else {
            // @codeCoverageIgnoreStart
            echo "\n".
                'Profiler is disabled, it must be enabled for the '.
                'Query Counter. '.
                'See https://github.com/liip/LiipFunctionalTestBundle#query-counter'.
                "\n";
            // @codeCoverageIgnoreEnd
        }

        return $crawler;
    }
}
