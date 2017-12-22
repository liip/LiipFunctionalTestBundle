<?php

use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Psr\Log\LoggerInterface;

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class AppTestDebugProjectContainerUrlGenerator extends Symfony\Component\Routing\Generator\UrlGenerator
{
    private static $declaredRoutes;

    public function __construct(RequestContext $context, LoggerInterface $logger = null)
    {
        $this->context = $context;
        $this->logger = $logger;
        if (null === self::$declaredRoutes) {
            self::$declaredRoutes = array(
        'liipfunctionaltestbundle_homepage' => array (  0 =>   array (  ),  1 =>   array (    '_controller' => 'Liip\\FunctionalTestBundle\\Tests\\App\\Controller\\DefaultController::indexAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'liipfunctionaltestbundle_user' => array (  0 =>   array (    0 => 'userId',  ),  1 =>   array (    '_controller' => 'Liip\\FunctionalTestBundle\\Tests\\App\\Controller\\DefaultController::userAction',  ),  2 =>   array (    'userId' => '\\d+',  ),  3 =>   array (    0 =>     array (      0 => 'variable',      1 => '/',      2 => '\\d+',      3 => 'userId',    ),    1 =>     array (      0 => 'text',      1 => '/user',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'liipfunctionaltestbundle_form' => array (  0 =>   array (  ),  1 =>   array (    '_controller' => 'Liip\\FunctionalTestBundle\\Tests\\App\\Controller\\DefaultController::formAction',  ),  2 =>   array (  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/form',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
        'liipfunctionaltestbundle_json' => array (  0 =>   array (  ),  1 =>   array (    '_controller' => 'Liip\\FunctionalTestBundle\\Tests\\App\\Controller\\DefaultController::jsonAction',  ),  2 =>   array (    'userId' => '\\d+',  ),  3 =>   array (    0 =>     array (      0 => 'text',      1 => '/json',    ),  ),  4 =>   array (  ),  5 =>   array (  ),),
    );
        }
    }

    public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH)
    {
        if (!isset(self::$declaredRoutes[$name])) {
            throw new RouteNotFoundException(sprintf('Unable to generate a URL for the named route "%s" as such route does not exist.', $name));
        }

        list($variables, $defaults, $requirements, $tokens, $hostTokens, $requiredSchemes) = self::$declaredRoutes[$name];

        return $this->doGenerate($variables, $defaults, $requirements, $tokens, $parameters, $name, $referenceType, $hostTokens, $requiredSchemes);
    }
}
