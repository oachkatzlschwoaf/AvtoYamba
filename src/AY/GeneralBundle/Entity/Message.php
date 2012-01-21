<?php

namespace AY\GeneralBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use AY\GeneralBundle\Entity\Util;

/**
 * AY\GeneralBundle\Entity\Message
 */
class Message
{
    private $image_upload;
    
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
     * @var integer $user_id
     */
    private $user_id;

    /**
     * @var string $user_name
     */
    private $user_name;

    /**
     * @var string $text
     */
    private $text;

    /**
     * @var string $image
     */
    private $image;

    /**
     * @var string $image_tmp
     */
    private $image_tmp;

    /**
     * @var string $image_thumb
     */
    private $image_thumb;

    /**
     * @var string $image_thumb_2
     */
    private $image_thumb_2;

    /**
     * @var integer $rate
     */
    private $rate;

    /**
     * @var string $tweet_id
     */
    private $tweet_id;

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
    public function setNumber($number) {
        $number = str_replace(" ", "", $number);
        $util = new Util;
        $this->number = $util->translateForward( $number );
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
     * Set user_id
     *
     * @param integer $userId
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
    }

    /**
     * Get user_id
     *
     * @return integer 
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set user_name
     *
     * @param string $userName
     */
    public function setUserName($userName)
    {
        $this->user_name = $userName;
    }

    /**
     * Get user_name
     *
     * @return string 
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Set text
     *
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * Get text
     *
     * @return string 
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set image
     *
     * @param string $image
     */
    public function setImage($image)
    {
        $this->image = $image;
    }

    /**
     * Get image
     *
     * @return string 
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set image_tmp
     *
     * @param string $imageTmp
     */
    public function setImageTmp($imageTmp)
    {
        $this->image_tmp = $imageTmp;
    }

    /**
     * Get image_tmp
     *
     * @return string 
     */
    public function getImageTmp()
    {
        return $this->image_tmp;
    }

    /**
     * Set image_thumb
     *
     * @param string $imageThumb
     */
    public function setImageThumb($imageThumb)
    {
        $this->image_thumb = $imageThumb;
    }

    /**
     * Get image_thumb
     *
     * @return string 
     */
    public function getImageThumb()
    {
        return $this->image_thumb;
    }

    /**
     * Set image_thumb_2
     *
     * @param string $imageThumb2
     */
    public function setImageThumb2($imageThumb2)
    {
        $this->image_thumb_2 = $imageThumb2;
    }

    /**
     * Get image_thumb_2
     *
     * @return string 
     */
    public function getImageThumb2()
    {
        return $this->image_thumb_2;
    }

    /**
     * Set rate
     *
     * @param integer $rate
     */
    public function setRate($rate)
    {
        $this->rate = $rate;
    }

    /**
     * Get rate
     *
     * @return integer 
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set tweet_id
     *
     * @param string $tweetId
     */
    public function setTweetId($tweetId)
    {
        $this->tweet_id = $tweetId;
    }

    /**
     * Get tweet_id
     *
     * @return string 
     */
    public function getTweetId()
    {
        return $this->tweet_id;
    }

    /**
     * Set created_at
     *
     * @param datetime $createdAt
     */
    public function setCreatedAt() {
        $this->created_at = new \DateTime;
    }

    /**
     * Get created_at
     *
     * @return datetime 
     */
    public function getCreatedAt($format = null) {
        if (!$format) {
            return $this->created_at;
        } elseif ($format === "date") {
            return $this->created_at->format("Y-m-d");
        } elseif ($format === "date_time") {
            return $this->created_at->format("Y-m-d H:i");
        } elseif ($format === "human") {
            $ut = $this->created_at->getTimestamp();
            $util = new Util;
            return $util->getTwitterDate($ut);
        }
    }

    public function getNumberTranslated() {
        $util = new Util; 
        $number = $util->translateBack( $this->number );

        return $number;
    }

    public function getImageUpload() {
        return $this->image_upload;
    }

    public function setImageUpload($image_upload) {
        $this->image_upload = $image_upload;
    }

    public function uploadImage() {
        if ($this->image_upload) {
            $util = new Util;
            $tmp_name = $util->generatePassword(32);

            $this->image_upload->move('/tmp/', $tmp_name);
            $this->setImageTmp('/tmp/'.$tmp_name);

            $this->image_upload = null;
        }
    }

    /**
     * @ORM\prePersist
     */
    public function processPrePersist() {
        $this->setCreatedAt();
        $this->uploadImage();

        if (!$this->getCountry()) {
            $this->setCountry(0);
        }

        if (!$this->getNumberType()) {
            $this->setNumberType(0);
        }

    }

    /**
     * @ORM\postPersist
     */
    public function processPostPersist()
    {
        // Add your code here
    }

    public function isNumberValid($c) {
        // Regexp for gos number
        if (!preg_match('/^[a-z]\d{3}[a-z]{2}\d+$/', $this->number)) {
            $pp = $c->getPropertyPath() . '.number';
            $c->setPropertyPath($pp);
            $c->addViolation('Неправильный формат номера', array(), null);
        } 
    }
}
