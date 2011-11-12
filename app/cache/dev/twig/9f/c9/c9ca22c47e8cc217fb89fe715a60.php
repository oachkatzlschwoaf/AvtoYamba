<?php

/* AYGeneralBundle:Default:index.html.twig */
class __TwigTemplate_9fc9c9ca22c47e8cc217fb89fe715a60 extends Twig_Template
{
    protected $parent;

    public function __construct(Twig_Environment $env)
    {
        parent::__construct($env);

        $this->parent = array();
        $this->blocks = array(
            'body' => array($this, 'block_body'),
        );
    }

    public function getParent(array $context)
    {
        $parent = "::base.html.twig";
        if ($parent instanceof Twig_Template) {
            $name = $parent->getTemplateName();
            $this->parent[$name] = $parent;
            $parent = $name;
        } elseif (!isset($this->parent[$parent])) {
            $this->parent[$parent] = $this->env->loadTemplate($parent);
        }

        return $this->parent[$parent];
    }

    protected function doDisplay(array $context, array $blocks = array())
    {
        $context = array_merge($this->env->getGlobals(), $context);

        $this->getParent($context)->display($context, array_merge($this->blocks, $blocks));
    }

    // line 3
    public function block_body($context, array $blocks = array())
    {
        // line 4
        echo "Превед! 

<div style='border: 1px solid gray; background: #cccccc; margin: 10px; width: 40%'>

    <form method=\"post\" ";
        // line 8
        echo $this->env->getExtension('form')->renderEnctype($this->getContext($context, 'form'));
        echo " novalidate=\"novalidate\">
        ";
        // line 9
        echo $this->env->getExtension('form')->renderErrors($this->getContext($context, 'form'));
        echo "

        ";
        // line 11
        echo $this->env->getExtension('form')->renderWidget($this->getContext($context, 'form'));
        echo "

        <input type=\"submit\">
    </form>

</div>

<div style='border: 1px solid gray; background: #fffbcd; margin: 10px; width: 40%'>

    <form method=\"post\" action=\"";
        // line 20
        echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("search_number"), "html");
        echo "\" >
        Найти номер:
        <input type=\"text\" name=\"number\"> 

        <input type=\"submit\">
    </form>

</div>

";
        // line 29
        if ($this->getContext($context, 'messages')) {
            // line 30
            echo "    <div style='border: 1px solid black; margin: 10px; width: 40%'>
        у нас есть сообщеньки!

        <ul>
            ";
            // line 34
            $context['_parent'] = (array) $context;
            $context['_seq'] = twig_ensure_traversable($this->getContext($context, 'messages'));
            foreach ($context['_seq'] as $context['_key'] => $context['m']) {
                // line 35
                echo "                <li>
                    ";
                // line 36
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "id", array(), "any", false), "html");
                echo ". 
                    by ";
                // line 37
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "getUserName", array(), "method", false), "html");
                echo "
                    at
                    ";
                // line 39
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "getCreatedAt", array("date", ), "method", false), "html");
                echo "
                    about
                    <b><a href=\"";
                // line 41
                echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("number", array("number" => $this->getAttribute($this->getContext($context, 'm'), "getNumber", array(), "method", false))), "html");
                echo "\">";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "getNumberTranslated", array(), "method", false), "html");
                echo "</a></b>
                    <br />
                    ";
                // line 43
                if ($this->getAttribute($this->getContext($context, 'm'), "getImage", array(), "method", false)) {
                    // line 44
                    echo "                        <img src=\"";
                    echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "getImageThumb", array(), "method", false), "html");
                    echo "\" />
                    ";
                } elseif ($this->getAttribute($this->getContext($context, 'm'), "getImageTmp", array(), "method", false)) {
                    // line 46
                    echo "                        <i>изображение в процессе обработки</i>
                    ";
                }
                // line 47
                echo "                        
                    <br />
                    ";
                // line 49
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "getText", array(), "method", false), "html");
                echo "
                </li>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['m'], $context['_parent'], $context['loop']);
            $context = array_merge($_parent, array_intersect_key($context, $_parent));
            // line 52
            echo "        </ul>

    </div>
";
        }
        // line 56
        echo "
";
    }

    public function getTemplateName()
    {
        return "AYGeneralBundle:Default:index.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }
}
