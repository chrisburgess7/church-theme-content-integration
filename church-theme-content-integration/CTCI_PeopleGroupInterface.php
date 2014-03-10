<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 5/03/14
 * Time: 10:06 AM
 */

interface CTCI_PeopleGroupInterface
{
    public function setId($id);
    public function id();
    public function setName($name);
    public function getName();
    public function setDescription($description);
    public function getDescription();
    public function setProviderTag($tag);
    public function getProviderTag();
} 