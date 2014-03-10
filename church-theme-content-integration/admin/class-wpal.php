<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 4/03/14
 * Time: 11:35 AM
 */

require_once 'interface-wpal.php';

class CTCI_WPAL implements CTCI_WPALInterface
{
    public static $ctcPersonGroupTaxonomy = 'ctc_person_group';

    public static $ctcGroupConnectTable = 'ctci_ctcgroup_connect';

    /*public static $ctcGroupConnectProviderTagField = 'data_provider';
    public static $ctcGroupConnectTermIDField = 'term_id';
    public static $ctcGroupConnectGroupIDField = 'provider_group_id';*/

    public function getOption($option)
    {
        // TODO: Implement getOption() method.
    }

    /**
     * Attaches the CTC group in the first argument to the people group in the second argument.
     * This works whether or not the CTC group already has an attach record. It updates if it exists
     * or makes new if it does not.
     *
     * @param CTCI_CTCGroupInterface $ctcGroup
     * @param CTCI_PeopleGroupInterface $group
     * @return bool
     */
    public function attachCTCGroup(CTCI_CTCGroupInterface $ctcGroup, CTCI_PeopleGroupInterface $group)
    {
        /** @var $wpdb wpdb */
        global $wpdb;
        $attachTable = $wpdb->prefix . self::$ctcGroupConnectTable;

        $updateResult = $wpdb->update(
            $attachTable,
            array(
                'data_provider' => $group->getProviderTag(),
                'provider_group_id' => $group->id()
            ),
            array(
                'term_id' => $ctcGroup->id()
            ),
            array('%s', '%s'),
            array('%d')
        );

        // an error occurred during update, so we don't know if attach record exists or not, abort
        if ($updateResult === false) {
            return false;
        } elseif ($updateResult > 0) {
            // successful update
            return true;
        }

        // if no update error and no rows affected, then we need a new entry
        $result = $wpdb->insert($attachTable, array(
                'data_provider' => $group->getProviderTag(),
                'term_id' => $ctcGroup->id(),
                'provider_group_id' => $group->id()
            ), array('%s', '%d', '%s')
        );

        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Updates the name and description of a CTC group with the info from the second argument
     * @param CTCI_CTCGroupInterface $ctcGroup
     * @param CTCI_PeopleGroupInterface $group
     */
    public function updateCTCGroup(CTCI_CTCGroupInterface $ctcGroup, CTCI_PeopleGroupInterface $group)
    {
        wp_update_term($ctcGroup->id(), static::$ctcPersonGroupTaxonomy, array(
            'name' => $group->getName(),
            'description' => $group->getDescription()
        ));
    }

    public function getAttachedCTCGroup(CTCI_PeopleGroupInterface $group)
    {
        /** @var $wpdb wpdb */
        global $wpdb;
        $attachTable = $wpdb->prefix . self::$ctcGroupConnectTable;

        $ctcGroupConnectRow = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT term_id FROM $attachTable WHERE data_provider = %s AND provider_group_id = %s",
                $group->getProviderTag(),
                $group->id()
            ),
            ARRAY_A
        );

        // no attached group
        if ($ctcGroupConnectRow === null) {
            return null;
        }

        $ctcGroupTermRecord = get_term($ctcGroupConnectRow['term_id'], self::$ctcPersonGroupTaxonomy, ARRAY_A);

        if ($ctcGroupTermRecord === null || is_wp_error($ctcGroupTermRecord)) {
            return $ctcGroupTermRecord;
        }

        $ctcGroup = new CTCI_CTCGroup($ctcGroupTermRecord['id'], $ctcGroupTermRecord['name'], $ctcGroupTermRecord['description']);

        return $ctcGroup;
    }
}