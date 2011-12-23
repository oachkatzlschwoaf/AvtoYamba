<?php

namespace AY\GeneralBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AY\GeneralBundle\Entity\Config
 */
class Config
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $parameter
     */
    private $parameter;

    /**
     * @var string $value
     */
    private $value;


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set parameter
     *
     * @param string $parameter
     */
    public function setParameter($parameter)
    {
        $this->parameter = $parameter;
    }

    /**
     * Get parameter
     *
     * @return string 
     */
    public function getParameter()
    {
        return $this->parameter;
    }

    /**
     * Set value
     *
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * Get value
     *
     * @return string 
     */
    public function getValue()
    {
        return $this->value;
    }
}