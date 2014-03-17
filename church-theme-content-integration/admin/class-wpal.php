<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 4/03/14
 * Time: 11:35 AM
 */

require_once 'interface-wpal.php';

class CTCI_WPAL implements CTCI_WPALInterface {
	public static $ctcPersonGroupTaxonomy = 'ctc_person_group';

	public static $ctcGroupConnectTable = 'ctci_ctcgroup_connect';

	/*public static $ctcGroupConnectProviderTagField = 'data_provider';
	public static $ctcGroupConnectTermIDField = 'term_id';
	public static $ctcGroupConnectGroupIDField = 'provider_group_id';*/

	public function getOption( $option ) {
		// TODO: Implement getOption() method.
	}

	/**
	 * @param CTCI_PeopleGroupInterface $group
	 * @return CTCI_CTCGroup An array containing term_id and taxonomy_id
	 * @throws CTCI_CreateCTCGroupException
	 */
	public function createCTCGroup( CTCI_PeopleGroupInterface $group ) {
		$result = wp_insert_term( $group->getName(), CTCI_WPAL::$ctcPersonGroupTaxonomy, array(
				'description' => $group->getDescription()
			)
		);
		if ( is_wp_error($result) ) {
			throw new CTCI_CreateCTCGroupException($result);
		}
		return $this->getCTCGroup( $result['term_id'] );
	}

	/**
	 * Attaches the CTC group in the first argument to the people group in the second argument.
	 * This works whether or not the CTC group already has an attach record. It updates if it exists
	 * or makes new if it does not.
	 *
	 * @param CTCI_CTCGroupInterface $ctcGroup
	 * @param CTCI_PeopleGroupInterface $group
	 * @throws CTCI_UpdateCTCGroupAttachRecordException
	 * @throws CTCI_InsertCTCGroupAttachRecordException
	 * @return string       If record updated, returns 'updated', if inserted, returns 'inserted'
	 */
	public function attachCTCGroup( CTCI_CTCGroupInterface $ctcGroup, CTCI_PeopleGroupInterface $group ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$attachTable = $wpdb->prefix . self::$ctcGroupConnectTable;

		// simply updates the record if it already exists
		// so this will overwrite any existing attach record for the group / term id
		$updateResult = $wpdb->update(
			$attachTable,
			array(
				'data_provider' => $group->getProviderTag(),
				'provider_group_id' => $group->id()
			),
			array(
				'term_id' => $ctcGroup->id()
			),
			array( '%s', '%s' ),
			array( '%d' )
		);

		// an error occurred during update, so we don't know if attach record exists or not, abort
		if ( $updateResult === false ) {
			throw new CTCI_UpdateCTCGroupAttachRecordException($ctcGroup, $group);
		} elseif ( $updateResult > 0 ) {
			// successful update
			return 'updated';
		}

		// if no update error and no rows affected, then we need a new entry
		$result = $wpdb->insert( $attachTable, array(
				'data_provider' => $group->getProviderTag(),
				'term_id' => $ctcGroup->id(),
				'provider_group_id' => $group->id()
			), array( '%s', '%d', '%s' )
		);

		if ( $result === false ) {
			throw new CTCI_InsertCTCGroupAttachRecordException($ctcGroup, $group);
		} else {
			return 'inserted';
		}
	}

	/**
	 * @param CTCI_PeopleGroupInterface $group
	 * @return CTCI_CTCGroup
	 */
	public function createAttachedCTCGroup( CTCI_PeopleGroupInterface $group ) {
		$ctcGroup = $this->createCTCGroup( $group );
		$this->attachCTCGroup( $ctcGroup, $group );
		return $ctcGroup;
	}

	/**
	 * Updates the name and description of a CTC group with the info from the second argument
	 * @param CTCI_CTCGroupInterface $ctcGroup
	 * @param CTCI_PeopleGroupInterface $group
	 * @throws CTCI_UpdateCTCGroupException
	 * @return array
	 */
	public function updateCTCGroup( CTCI_CTCGroupInterface $ctcGroup, CTCI_PeopleGroupInterface $group ) {
		$result = wp_update_term( $ctcGroup->id(), static::$ctcPersonGroupTaxonomy, array(
			'name' => $group->getName(),
			'description' => $group->getDescription()
		) );
		if ( is_wp_error($result) ) {
			throw new CTCI_UpdateCTCGroupException($result);
		}
		return $result;
	}

	/**
	 * @param $term_id
	 * @return CTCI_CTCGroup
	 * @throws CTCI_CouldNotRetrieveCTCGroupException
	 */
	public function getCTCGroup( $term_id ) {
		$ctcGroupTermRecord = get_term( $term_id, self::$ctcPersonGroupTaxonomy, ARRAY_A );

		if ( $ctcGroupTermRecord === null || is_wp_error( $ctcGroupTermRecord ) ) {
			throw new CTCI_CouldNotRetrieveCTCGroupException;
		}

		$ctcGroup = new CTCI_CTCGroup( $ctcGroupTermRecord[ 'term_id' ], $ctcGroupTermRecord[ 'name' ], $ctcGroupTermRecord[ 'description' ] );

		return $ctcGroup;
	}

	/**
	 * @param CTCI_PeopleGroupInterface $group
	 * @throws CTCI_CouldNotRetrieveCTCGroupException
	 * @return CTCI_CTCGroupInterface|null
	 */
	public function getAttachedCTCGroup( CTCI_PeopleGroupInterface $group ) {
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
		if ( $ctcGroupConnectRow === null ) {
			return null;
		}

		return $this->getCTCGroup( $ctcGroupConnectRow[ 'term_id' ] );
	}

	public function createCTCPerson( CTCI_CTCPersonInterface $ctcPerson ) {

	}

	public function getCTCPeopleAttachedViaProvider( $providerTag ) {

	}
}

class CTCI_CreateCTCGroupException extends Exception {
	protected $wp_error;
	public function __construct( WP_Error $wp_error, $message = "", $code = 0, Exception $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->wp_error = $wp_error;
	}

	public function getWPError() {
		return $this->wp_error;
	}
}

class CTCI_UpdateCTCGroupException extends Exception {
	protected $wp_error;
	public function __construct( WP_Error $wp_error, $message = "", $code = 0, Exception $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->wp_error = $wp_error;
	}

	public function getWPError() {
		return $this->wp_error;
	}
}

class CTCI_CTCGroupAttachException extends Exception {
	protected $ctcGroup;
	protected $group;
	public function __construct( CTCI_CTCGroup $ctcGroup, CTCI_PeopleGroup $group, $message = "", $code = 0, Exception $previous = null ) {
		parent::__construct( $message, $code, $previous );
		$this->ctcGroup = $ctcGroup;
		$this->group = $group;
	}
	public function getCTCGroup() { return $this->ctcGroup; }
	public function getGroup() { return $this->group; }
}

class CTCI_UpdateCTCGroupAttachRecordException extends CTCI_CTCGroupAttachException {}
class CTCI_InsertCTCGroupAttachRecordException extends CTCI_CTCGroupAttachException {}

class CTCI_CouldNotRetrieveCTCGroupException extends Exception {}