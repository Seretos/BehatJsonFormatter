<?php
/**
 * Created by PhpStorm.
 * User: aappen
 * Date: 04.02.18
 * Time: 19:57
 */

namespace seretos\BehatJsonFormatter\Entity;


use JsonSerializable;

class BehatSuite implements JsonSerializable{
    /**
     * @var string
     */
    private $name;

    /**
     * @var BehatFeature[]
     */
    private $features;

    public function __construct () {
        $this->features = [];
    }

    /**
     * @return string
     */
    public function getName () {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return BehatSuite
     */
    public function setName ($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * @return BehatFeature[]
     */
    public function getFeatures () {
        return $this->features;
    }

    /**
     * @param BehatFeature[] $features
     *
     * @return BehatSuite
     */
    public function setFeatures ($features) {
        $this->features = $features;

        return $this;
    }

    /**
     * @param              $key
     * @param BehatFeature $feature
     *
     * @return BehatSuite
     */
    public function setFeature($key, BehatFeature $feature){
        $this->features[$key] = $feature;

        return $this;
    }

    /**
     * @param $key
     *
     * @return null|BehatFeature
     */
    public function getFeature($key){
        if(isset($this->features[$key])) {
            return $this->features[$key];
        }
        return null;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize () {
        return ['name' => $this->name,'features' => $this->features];
    }
}