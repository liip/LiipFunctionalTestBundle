<?php

use Symfony\Component\DependencyInjection\Argument\RewindableGenerator;

// This file has been auto-generated by the Symfony Dependency Injection Component for internal use.
// Returns the private 'security.firewall.map.context.secured_area' shared service.

$a = ${($_ = isset($this->services['router']) ? $this->services['router'] : $this->getRouterService()) && false ?: '_'};

return $this->services['security.firewall.map.context.secured_area'] = new \Symfony\Bundle\SecurityBundle\Security\FirewallContext(new RewindableGenerator(function () {
    yield 0 => ${($_ = isset($this->services['security.channel_listener']) ? $this->services['security.channel_listener'] : $this->load(__DIR__.'/getSecurity_ChannelListenerService.php')) && false ?: '_'};
    yield 1 => ${($_ = isset($this->services['security.context_listener.0']) ? $this->services['security.context_listener.0'] : $this->load(__DIR__.'/getSecurity_ContextListener_0Service.php')) && false ?: '_'};
    yield 2 => ${($_ = isset($this->services['security.authentication.listener.basic.secured_area']) ? $this->services['security.authentication.listener.basic.secured_area'] : $this->load(__DIR__.'/getSecurity_Authentication_Listener_Basic_SecuredAreaService.php')) && false ?: '_'};
    yield 3 => ${($_ = isset($this->services['security.authentication.listener.anonymous.secured_area']) ? $this->services['security.authentication.listener.anonymous.secured_area'] : $this->load(__DIR__.'/getSecurity_Authentication_Listener_Anonymous_SecuredAreaService.php')) && false ?: '_'};
    yield 4 => ${($_ = isset($this->services['security.access_listener']) ? $this->services['security.access_listener'] : $this->load(__DIR__.'/getSecurity_AccessListenerService.php')) && false ?: '_'};
}, 5), new \Symfony\Component\Security\Http\Firewall\ExceptionListener(${($_ = isset($this->services['security.token_storage']) ? $this->services['security.token_storage'] : $this->services['security.token_storage'] = new \Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage()) && false ?: '_'}, ${($_ = isset($this->services['security.authentication.trust_resolver']) ? $this->services['security.authentication.trust_resolver'] : $this->services['security.authentication.trust_resolver'] = new \Symfony\Component\Security\Core\Authentication\AuthenticationTrustResolver('Symfony\\Component\\Security\\Core\\Authentication\\Token\\AnonymousToken', 'Symfony\\Component\\Security\\Core\\Authentication\\Token\\RememberMeToken')) && false ?: '_'}, new \Symfony\Component\Security\Http\HttpUtils($a, $a), 'secured_area', ${($_ = isset($this->services['security.authentication.basic_entry_point.secured_area']) ? $this->services['security.authentication.basic_entry_point.secured_area'] : $this->services['security.authentication.basic_entry_point.secured_area'] = new \Symfony\Component\Security\Http\EntryPoint\BasicAuthenticationEntryPoint('Admin Area')) && false ?: '_'}, NULL, NULL, ${($_ = isset($this->services['monolog.logger.security']) ? $this->services['monolog.logger.security'] : $this->load(__DIR__.'/getMonolog_Logger_SecurityService.php')) && false ?: '_'}, false), new \Symfony\Bundle\SecurityBundle\Security\FirewallConfig('secured_area', 'security.user_checker', 'security.request_matcher.00qf1z7', true, false, 'security.user.provider.concrete.chain_provider', 'secured_area', 'security.authentication.basic_entry_point.secured_area', NULL, NULL, array(0 => 'http_basic', 1 => 'anonymous'), NULL));
