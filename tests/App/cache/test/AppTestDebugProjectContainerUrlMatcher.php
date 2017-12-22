<?php

use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\RequestContext;

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class AppTestDebugProjectContainerUrlMatcher extends Symfony\Bundle\FrameworkBundle\Routing\RedirectableUrlMatcher
{
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($rawPathinfo)
    {
        $allow = array();
        $pathinfo = rawurldecode($rawPathinfo);
        $trimmedPathinfo = rtrim($pathinfo, '/');
        $context = $this->context;
        $request = $this->request;
        $requestMethod = $canonicalMethod = $context->getMethod();
        $scheme = $context->getScheme();

        if ('HEAD' === $requestMethod) {
            $canonicalMethod = 'GET';
        }


        // liipfunctionaltestbundle_homepage
        if ('' === $trimmedPathinfo) {
            $ret = array (  '_controller' => 'Liip\\FunctionalTestBundle\\Tests\\App\\Controller\\DefaultController::indexAction',  '_route' => 'liipfunctionaltestbundle_homepage',);
            if (substr($pathinfo, -1) !== '/') {
                return array_replace($ret, $this->redirect($rawPathinfo.'/', 'liipfunctionaltestbundle_homepage'));
            }

            return $ret;
        }

        // liipfunctionaltestbundle_user
        if (0 === strpos($pathinfo, '/user') && preg_match('#^/user/(?P<userId>\\d+)$#s', $pathinfo, $matches)) {
            return $this->mergeDefaults(array_replace($matches, array('_route' => 'liipfunctionaltestbundle_user')), array (  '_controller' => 'Liip\\FunctionalTestBundle\\Tests\\App\\Controller\\DefaultController::userAction',));
        }

        // liipfunctionaltestbundle_form
        if ('/form' === $pathinfo) {
            return array (  '_controller' => 'Liip\\FunctionalTestBundle\\Tests\\App\\Controller\\DefaultController::formAction',  '_route' => 'liipfunctionaltestbundle_form',);
        }

        // liipfunctionaltestbundle_json
        if ('/json' === $pathinfo) {
            return array (  '_controller' => 'Liip\\FunctionalTestBundle\\Tests\\App\\Controller\\DefaultController::jsonAction',  '_route' => 'liipfunctionaltestbundle_json',);
        }

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}
