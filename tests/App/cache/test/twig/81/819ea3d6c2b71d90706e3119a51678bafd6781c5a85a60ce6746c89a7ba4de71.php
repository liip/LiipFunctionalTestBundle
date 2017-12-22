<?php

/* LiipFunctionalTestBundle::layout.html.twig */
class __TwigTemplate_b9bab08d8ac8333448a80ea8648e60a6d20646d008e23b6cc75a474e63981267 extends Twig_Template
{
    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = false;

        $this->blocks = array(
            'body' => array($this, 'block_body'),
        );
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $__internal_a32680f574cfc1b46706ce8cc4b8d60d00c0d14b897992ba4b8aa44b1054f09d = $this->env->getExtension("Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension");
        $__internal_a32680f574cfc1b46706ce8cc4b8d60d00c0d14b897992ba4b8aa44b1054f09d->enter($__internal_a32680f574cfc1b46706ce8cc4b8d60d00c0d14b897992ba4b8aa44b1054f09d_prof = new Twig_Profiler_Profile($this->getTemplateName(), "template", "LiipFunctionalTestBundle::layout.html.twig"));

        // line 1
        echo "<!DOCTYPE html>
<html lang=\"en\">
    <head>
        <meta charset=\"utf-8\">
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
        
        <title>LiipFunctionalTestBundle</title>
    </head>
    
    <body>
        <p id=\"user\">";
        // line 12
        if ((null === twig_get_attribute($this->env, $this->getSourceContext(), ($context["app"] ?? null), "user", array()))) {
            // line 13
            echo "Not logged in.";
        } else {
            // line 15
            echo "Logged in as ";
            echo twig_escape_filter($this->env, twig_get_attribute($this->env, $this->getSourceContext(), twig_get_attribute($this->env, $this->getSourceContext(), ($context["app"] ?? null), "user", array()), "username", array()), "html", null, true);
            echo ".";
        }
        // line 17
        echo "</p>

        <h1>LiipFunctionalTestBundle</h1>
        
        ";
        // line 21
        $context['_parent'] = $context;
        $context['_seq'] = twig_ensure_traversable(twig_get_attribute($this->env, $this->getSourceContext(), twig_get_attribute($this->env, $this->getSourceContext(), twig_get_attribute($this->env, $this->getSourceContext(), ($context["app"] ?? null), "session", array()), "flashbag", array()), "get", array(0 => "notice"), "method"));
        foreach ($context['_seq'] as $context["_key"] => $context["flash_message"]) {
            // line 22
            echo "            <div class=\"flash-notice\">
                ";
            // line 23
            echo twig_escape_filter($this->env, $context["flash_message"], "html", null, true);
            echo "
            </div>
        ";
        }
        $_parent = $context['_parent'];
        unset($context['_seq'], $context['_iterated'], $context['_key'], $context['flash_message'], $context['_parent'], $context['loop']);
        $context = array_intersect_key($context, $_parent) + $_parent;
        // line 26
        echo "        
        <div id=\"content\">";
        // line 27
        $this->displayBlock('body', $context, $blocks);
        echo "</div>
    </body>
</html>
";
        
        $__internal_a32680f574cfc1b46706ce8cc4b8d60d00c0d14b897992ba4b8aa44b1054f09d->leave($__internal_a32680f574cfc1b46706ce8cc4b8d60d00c0d14b897992ba4b8aa44b1054f09d_prof);

    }

    public function block_body($context, array $blocks = array())
    {
        $__internal_66d8b24f106fdc9348ba68781279103ca1dcd13f7b6505a60ae3211aea4e4dd1 = $this->env->getExtension("Symfony\\Bridge\\Twig\\Extension\\ProfilerExtension");
        $__internal_66d8b24f106fdc9348ba68781279103ca1dcd13f7b6505a60ae3211aea4e4dd1->enter($__internal_66d8b24f106fdc9348ba68781279103ca1dcd13f7b6505a60ae3211aea4e4dd1_prof = new Twig_Profiler_Profile($this->getTemplateName(), "block", "body"));

        
        $__internal_66d8b24f106fdc9348ba68781279103ca1dcd13f7b6505a60ae3211aea4e4dd1->leave($__internal_66d8b24f106fdc9348ba68781279103ca1dcd13f7b6505a60ae3211aea4e4dd1_prof);

    }

    public function getTemplateName()
    {
        return "LiipFunctionalTestBundle::layout.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }

    public function getDebugInfo()
    {
        return array (  70 => 27,  67 => 26,  58 => 23,  55 => 22,  51 => 21,  45 => 17,  40 => 15,  37 => 13,  35 => 12,  23 => 1,);
    }

    public function getSourceContext()
    {
        return new Twig_Source("<!DOCTYPE html>
<html lang=\"en\">
    <head>
        <meta charset=\"utf-8\">
        <meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" />
        
        <title>LiipFunctionalTestBundle</title>
    </head>
    
    <body>
        <p id=\"user\">
            {%- if (app.user is null) -%}
                Not logged in.
            {%- else -%}
                Logged in as {{ app.user.username }}.
            {%- endif -%}
        </p>

        <h1>LiipFunctionalTestBundle</h1>
        
        {% for flash_message in app.session.flashbag.get('notice') %}
            <div class=\"flash-notice\">
                {{ flash_message }}
            </div>
        {% endfor %}
        
        <div id=\"content\">{% block body %}{% endblock %}</div>
    </body>
</html>
", "LiipFunctionalTestBundle::layout.html.twig", "/home/jean/workspace/LiipFunctionalTestBundle/src/Resources/views/layout.html.twig");
    }
}
