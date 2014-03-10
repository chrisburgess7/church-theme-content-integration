<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 4/03/14
 * Time: 2:07 PM
 */

interface CTCI_WPALInterface
{
    public function getOption($option);

    public function attachCTCGroup(CTCI_CTCGroupInterface $ctcGroup, CTCI_PeopleGroupInterface $group);

    public function updateCTCGroup(CTCI_CTCGroupInterface $ctcGroup, CTCI_PeopleGroupInterface $group);

    public function getAttachedCTCGroup(CTCI_PeopleGroupInterface $group);
} 