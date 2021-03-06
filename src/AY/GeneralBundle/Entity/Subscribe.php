<?php

namespace AY\GeneralBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * AY\GeneralBundle\Entity\Subscribe
 */
class Subscribe
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $number
     */
    private $number;

    /**
     * @var integer $country
     */
    private $country;

    /**
     * @var integer $number_type
     */
    private $number_type;

    /**
     * @var string $email
     */
    private $email;

    /**
     * @var string $phone
     */
    private $phone;

    /**
     * @var datetime $created_at
     */
    private $created_at;


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
     * Set number
     *
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * Get number
     *
     * @return string 
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set country
     *
     * @param integer $country
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * Get country
     *
     * @return integer 
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set number_type
     *
     * @param integer $numberType
     */
    public function setNumberType($numberType)
    {
        $this->number_type = $numberType;
    }

    /**
     * Get number_type
     *
     * @return integer 
     */
    public function getNumberType()
    {
        return $this->number_type;
    }

    /**
     * Set email
     *
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set phone
     *
     * @param string $phone
     */
    public function setPhone($phone) {
        # Clean phone
        $phone = str_replace(' ', '', $phone);
        $phone = preg_replace('/[^\d]/', '', $phone);

        if (strlen($phone) > 10)
            $phone = preg_replace('/^(7|8)/', '', $phone);

        $this->phone = $phone;
    }

    /**
     * Get phone
     *
     * @return string 
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * Set created_at
     *
     * @param datetime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;
    }

    /**
     * Get created_at
     *
     * @return datetime 
     */
    public function getCreatedAt($format = null) {
        if (!$format) {
            return $this->created_at;
        } elseif ($format == "date") {
            return $this->created_at->format("Y-m-d");
        } elseif ($format == "epoch") {
            return $this->created_at->getTimestamp();
        }
    }

    /**
     * @ORM\prePersist
     */
    public function processPrePersist() {
        $this->created_at = new \DateTime;
        error_log("TIME!");
        error_log( $this->created_at->getTimestamp() );
        error_log( $this->created_at->format("H:i:s") );

        if (!$this->getCountry()) {
            $this->setCountry(0);
        }

        if (!$this->getNumberType()) {
            $this->setNumberType(0);
        }
    }
    /**
     * @var string $code
     */
    private $code;


    /**
     * Set code
     *
     * @param string $code
     */
    public function setCode($code)
    {
        $this->code = $code;
    }

    /**
     * Get code
     *
     * @return string 
     */
    public function getCode()
    {
        return $this->code;
    }
}