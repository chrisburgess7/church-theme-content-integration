<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 8/03/14
 * Time: 10:49 AM
 */

require_once 'CTCI_CTCGroupInterface.php';

class CTCI_CTCGroup implements CTCI_CTCGroupInterface
{
    protected $id;
    protected $name;
    protected $desc;

    public function __construct($id, $name, $description)
    {
        $this->setId($id)->setName($name)->setDescription($description);
    }

    /**
     * @param $id
     * @return CTCI_CTCGroup
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    public function id()
    {
        return $this->id;
    }

    /**
     * @param $name
     * @return CTCI_CTCGroup
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @param $description
     * @return CTCI_CTCGroup
     */
    public function setDescription($description)
    {
        $this->desc = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->desc;
    }
}