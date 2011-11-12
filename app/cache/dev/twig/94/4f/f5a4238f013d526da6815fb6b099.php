<?php

/* AYGeneralBundle:Default:number.html.twig */
class __TwigTemplate_944ff5a4238f013d526da6815fb6b099 extends Twig_Template
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
        echo "
Номерочек: ";
        // line 5
        echo twig_escape_filter($this->env, $this->getContext($context, 'number'), "html");
        echo "

";
        // line 7
        if ($this->getAttribute($this->getAttribute($this->getContext($context, 'app'), "session", array(), "any", false), "hasFlash", array("notice", ), "method", false)) {
            // line 8
            echo "    <div style='color: green; border: 1px solid green; width: 40%'>
        ";
            // line 9
            echo twig_escape_filter($this->env, $this->getAttribute($this->getAttribute($this->getContext($context, 'app'), "session", array(), "any", false), "flash", array("notice", ), "method", false), "html");
            echo "
    </div>
";
        }
        // line 12
        echo "
<div style='border: 1px solid black; background: #abcdef; margin: 10px; width: 40%'>
    <form method=\"post\" novalidate=\"novalidate\">
        ";
        // line 15
        echo $this->env->getExtension('form')->renderErrors($this->getContext($context, 'ss_form'));
        echo "
        подписаться по емейл или телефону (смс):
        ";
        // line 17
        echo $this->env->getExtension('form')->renderWidget($this->getContext($context, 'ss_form'));
        echo "
        <input type=\"submit\">
    </form>
</div>

";
        // line 22
        if ($this->getContext($context, 'messages')) {
            // line 23
            echo "    <div style='border: 1px solid black; margin: 10px; width: 40%'>
        у нас есть сообщеньки!

        <ul>
            ";
            // line 27
            $context['_parent'] = (array) $context;
            $context['_seq'] = twig_ensure_traversable($this->getContext($context, 'messages'));
            foreach ($context['_seq'] as $context['_key'] => $context['m']) {
                // line 28
                echo "                <li>
                    ";
                // line 29
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "id", array(), "any", false), "html");
                echo ". 
                    by ";
                // line 30
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "getUserName", array(), "method", false), "html");
                echo "
                    at
                    ";
                // line 32
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "getCreatedAt", array("date", ), "method", false), "html");
                echo "
                    about
                    <b><a href=\"";
                // line 34
                echo twig_escape_filter($this->env, $this->env->getExtension('routing')->getPath("number", array("number" => $this->getAttribute($this->getContext($context, 'm'), "getNumber", array(), "method", false))), "html");
                echo "\">";
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "getNumberTranslated", array(), "method", false), "html");
                echo "</a></b>
                    <br />
                    ";
                // line 36
                if ($this->getAttribute($this->getContext($context, 'm'), "getImage", array(), "method", false)) {
                    // line 37
                    echo "                        <img src=\"";
                    echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "getImageThumb", array(), "method", false), "html");
                    echo "\" />
                    ";
                } elseif ($this->getAttribute($this->getContext($context, 'm'), "getImageTmp", array(), "method", false)) {
                    // line 39
                    echo "                        <i>изображение в процессе обработки</i>
                    ";
                }
                // line 40
                echo "                        
                    <br />
                    ";
                // line 42
                echo twig_escape_filter($this->env, $this->getAttribute($this->getContext($context, 'm'), "getText", array(), "method", false), "html");
                echo "
                </li>
            ";
            }
            $_parent = $context['_parent'];
            unset($context['_seq'], $context['_iterated'], $context['_key'], $context['m'], $context['_parent'], $context['loop']);
            $context = array_merge($_parent, array_intersect_key($context, $_parent));
            // line 45
            echo "        </ul>

    </div>
";
        } else {
            // line 49
            echo "
а про него еще никто ничего не сказал :-)

";
        }
        // line 53
        echo "

";
    }

    public function getTemplateName()
    {
        return "AYGeneralBundle:Default:number.html.twig";
    }

    public function isTraitable()
    {
        return false;
    }
}
