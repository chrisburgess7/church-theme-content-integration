<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 27/02/14
 * Time: 2:46 PM
 */

class CTCI_PeopleSync
{
    /**
     * @var CTCI_PeopleDataProviderInterface
     */
    protected $dataProvider;

    public function __construct(CTCI_PeopleDataProviderInterface $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }

    public function sync()
    {
        $this->dataProvider->setupForPeopleSync();

        if ($this->dataProvider->getPeopleSyncSetting('syncGroups')) {
            $this->updateGroups();
        }

        $people = $this->dataProvider->getPeople();

        foreach ($people as $person) {

            // get existing person record, if any
            $ctcPerson = $this->getCTCPerson($this->dataProvider->getProviderPersonTag(), $person->id());

            if ($ctcPerson !== null) {
                if ($this->dataProvider->getPeopleSyncSetting('syncGroups')) {
                    $this->syncCTCPersonsGroups($ctcPerson, $person);
                }
                $this->syncCTCPerson($ctcPerson, $person);
            } else {
                // attempt to attach the person from the data provider to
                // a person record in wp db
                $attached = $this->attachPerson($person);

                if (!$attached) {
                    $this->createNewCTCPerson($person);
                }
            }
        }

        $this->syncCleanUp();
    }

    /**
     * Update the CTC groups to match any changes from the data provider.
     * This is needed so that any renaming of groups retains existing information like description.
     */
    protected function updateGroups()
    {
        $groups = $this->dataProvider->getGroups();

        foreach ($groups as $group) {
            // get attached ctc group id from custom table, if it exists

            // if ctc group id exists

                // check if name of that group matches, if not update name

            // else

                // check all unattached ctc groups for a name match, and if found attach
        }
    }

    /**
     * @param $providerTag
     * @param $personId
     * @return WP_Post
     */
    protected function getCTCPerson($providerTag, $personId)
    {
        return false;
    }

    protected function syncCTCPerson(WP_Post $ctcPerson, CTCI_PersonInterface $person)
    {

    }

    protected function syncCTCPersonsGroups(WP_Post $ctcPerson, CTCI_PersonInterface $person)
    {
        // replaces all existing terms i.e. groups, with the new ones
        //wp_set_object_terms($ctcPerson->ID, $person->getGroups(), CTCI_Config::$ctcPersonGroupTaxonomy);
    }

    protected function attachPerson($person)
    {
        // should only attach to a person with no attachment to any data provider (not just the current one)
        return false;
    }

    protected function createNewCTCPerson($person)
    {
    }

    protected function syncCleanUp($data = array())
    {
    }
} 