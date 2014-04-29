<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 4/03/14
 * Time: 11:35 AM
 */

require_once 'interface-wpal.php';

class CTCI_WPAL implements CTCI_WPALInterface {
	// CTC values
	public static $ctcPersonPostType = 'ctc_person';
	public static $ctcPersonPositionMetaTag = '_ctc_person_position';
	public static $ctcPersonPhoneMetaTag = '_ctc_person_phone';
	public static $ctcPersonEmailMetaTag = '_ctc_person_email';
	public static $ctcPersonURLSMetaTag = '_ctc_person_urls';
	public static $ctcPersonGroupTaxonomy = 'ctc_person_group';

	// CTCI values
	public static $ctcGroupConnectTable = 'ctci_ctcgroup_connect';
	public static $ctcPersonProviderTagMetaTag = '_ctci_person_provider_tag';
	public static $ctcPersonProviderIdMetaTag = '_ctci_person_provider_id';

	public function getOption( $option ) {
		// TODO: Implement getOption() method.
	}

	public function getCurrentAdminPageURL() {
		// TODO: Implement getCurrentAdminPageURL() method.
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
	 * @throws CTCI_CreateCTCGroupException
	 * @throws CTCI_UpdateCTCGroupAttachRecordException
	 * @throws CTCI_InsertCTCGroupAttachRecordException
	 */
	public function createAttachedCTCGroup( CTCI_PeopleGroupInterface $group ) {
		$ctcGroup = $this->createCTCGroup( $group );
		$this->attachCTCGroup( $ctcGroup, $group );
		return $ctcGroup;
	}

	/**
	 * @param CTCI_CTCGroupInterface $ctcGroup
	 * @return int                  Number of groups deleted
	 * @throws CTCI_CouldNotUnattachCTCGroupException
	 */
	public function unattachCTCGroup( CTCI_CTCGroupInterface $ctcGroup ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$attachTable = $wpdb->prefix . self::$ctcGroupConnectTable;
		$return = $wpdb->delete( $attachTable, array( 'term_id' => $ctcGroup->id() ), array( '%d') );
		if ( $return === false ) {
			throw new CTCI_CouldNotUnattachCTCGroupException( $ctcGroup );
		} else {
			return $return;
		}
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
	 * @param CTCI_CTCGroupInterface $ctcGroup
	 * @throws CTCI_CouldNotDeleteCTCGroupException
	 */
	public function deleteCTCGroup( CTCI_CTCGroupInterface $ctcGroup ) {
		$this->unattachCTCGroup( $ctcGroup );
		$result = wp_delete_term( $ctcGroup->id(), CTCI_WPAL::$ctcPersonGroupTaxonomy );
		if ( $result === false || is_wp_error( $result ) ) {
			throw new CTCI_CouldNotDeleteCTCGroupException( $ctcGroup );
		}
	}

	/**
	 * @param $term_id
	 * @return CTCI_CTCGroup
	 * @throws CTCI_CouldNotRetrieveCTCGroupException
	 */
	public function getCTCGroup( $term_id ) {
		// convert to int, as sometimes WP returns term id's as strings
		$term_id = (int) $term_id;
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

	/**
	 * @param $providerTag
	 * @return CTCI_CTCGroupInterface[]
	 */
	public function getCTCGroupsAttachedViaProvider( $providerTag ) {
		/** @var $wpdb wpdb */
		global $wpdb;
		$attachTable = $wpdb->prefix . self::$ctcGroupConnectTable;

		$ctcGroupsConnect = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT term_id, provider_group_id FROM $attachTable WHERE data_provider = %s",
				$providerTag
			),
			ARRAY_A
		);

		// no attached groups for provider
		if ( $ctcGroupsConnect === null ) {
			return array();
		}

		$ctcGroups = array();
		foreach ( $ctcGroupsConnect as $groupConnect ) {
			$ctcGroup = $this->getCTCGroup( $groupConnect['term_id'] );
			$ctcGroup->setAttachedGroup( $providerTag, $groupConnect['provider_group_id'] );
			$ctcGroups[ $ctcGroup->getAttachedGroupProviderId() ] = $ctcGroup;
		}

		return $ctcGroups;
	}

	/**
	 * @return CTCI_CTCGroup[]
	 * @throws CTCI_CouldNotRetrieveUnattachedCTCGroupsException
	 */
	public function getUnattachedCTCGroups() {
		/** @var $wpdb wpdb */
		global $wpdb;
		$attachTable = $wpdb->prefix . self::$ctcGroupConnectTable;

		$attachedIds = $wpdb->get_results( "SELECT term_id FROM $attachTable" );
		$attachedTermIds = array();
		foreach ( $attachedIds as $id ) {
			$attachedTermIds[] = (int)$id->term_id;
		}

		$unattachedCTCGroupTerms = get_terms( CTCI_WPAL::$ctcPersonGroupTaxonomy, array(
			'hide_empty' => false,
			'exclude' => $attachedTermIds
		) );

		if ( is_wp_error( $unattachedCTCGroupTerms ) ) {
			throw new CTCI_CouldNotRetrieveUnattachedCTCGroupsException;
		}

		$ctcGroups = array();
		foreach ( $unattachedCTCGroupTerms as $ctcGroupTerm ) {
			$id = (int)$ctcGroupTerm->term_id;
			$ctcGroups[$id] = new CTCI_CTCGroup( $id, $ctcGroupTerm->name, $ctcGroupTerm->description );
		}
		return $ctcGroups;
	}

	/**
	 * Create a CTC Person from the CTCI_PersonInterface object. Note that this does not set the person's groups.
	 * @param CTCI_PersonInterface $person
	 * @return CTCI_CTCPersonInterface
	 * @throws CTCI_CouldNotCreateCTCPersonFromPersonException
	 */
	public function createAttachedCTCPerson( CTCI_PersonInterface $person ) {
		$fieldsToInsert = array( 'post_status' => 'publish', 'post_type' => self::$ctcPersonPostType );
		if ( $person->syncName() ) {
			$fieldsToInsert['post_title'] = $person->getName();
		}

		$id = wp_insert_post( $fieldsToInsert, true );
		if ( is_wp_error( $id ) ) {
			throw new CTCI_CouldNotCreateCTCPersonFromPersonException( $person, $id );
		}

		add_post_meta( $id, self::$ctcPersonPositionMetaTag, $person->getPosition(), true );
		add_post_meta( $id, self::$ctcPersonPhoneMetaTag, $person->getPhone(), true );
		add_post_meta( $id, self::$ctcPersonEmailMetaTag, $person->getEmail(), true );
		add_post_meta( $id, self::$ctcPersonURLSMetaTag, implode( "\n", $person->getURLs() ), true );

		$ctcPerson = $this->populateCTCPersonFromPost( get_post( $id ) );

		$this->attachCTCPerson( $ctcPerson, $person );

		return $ctcPerson;
	}

	/**
	 * @param CTCI_CTCPersonInterface $ctcPerson
	 * @return bool
	 */
	public function updateCTCPerson( CTCI_CTCPersonInterface $ctcPerson ) {
		$success = true;
		$fieldsToUpdate = array( 'ID' => $ctcPerson->id() );
		if ( $ctcPerson->isNameDirty() ) {
			// shouldn't need the wp_strip_all_tags, but we'll play it safe
			$fieldsToUpdate['post_title'] = wp_strip_all_tags( $ctcPerson->getName() );
		}
		if ( $ctcPerson->isBioDirty() ) {
			$fieldsToUpdate['post_content'] = $ctcPerson->getBio();
		}
		if ( $ctcPerson->isExcerptDirty() ) {
			$fieldsToUpdate['post_excerpt'] = $ctcPerson->getExcerpt();
		}
		$updateResult = wp_update_post( $fieldsToUpdate );
		if ( 0 === $updateResult || is_wp_error( $updateResult ) ) {
			$success = false;
		}
		if ( get_post_meta( $ctcPerson->id(), self::$ctcPersonEmailMetaTag, true ) !== $ctcPerson->getEmail() ) {
			if ( false === update_post_meta( $ctcPerson->id(), self::$ctcPersonEmailMetaTag, $ctcPerson->getEmail() ) ) {
				$success = false;
			}
		}
		if ( get_post_meta( $ctcPerson->id(), self::$ctcPersonPhoneMetaTag, true ) !== $ctcPerson->getPhone() ) {
			if ( false === update_post_meta( $ctcPerson->id(), self::$ctcPersonPhoneMetaTag, $ctcPerson->getPhone() ) ) {
				$success = false;
			}
		}
		if ( get_post_meta( $ctcPerson->id(), self::$ctcPersonPositionMetaTag, true ) !== $ctcPerson->getPosition() ) {
			if ( false === update_post_meta( $ctcPerson->id(), self::$ctcPersonPositionMetaTag, $ctcPerson->getPosition() ) ) {
				$success = false;
			}
		}
		if ( get_post_meta( $ctcPerson->id(), self::$ctcPersonURLSMetaTag, true ) !== $ctcPerson->getURLs() ) {
			if ( false === update_post_meta( $ctcPerson->id(), self::$ctcPersonURLSMetaTag, $ctcPerson->getURLs() ) ) {
				$success = false;
			}
		}
		return $success;
	}

	/**
	 * @param CTCI_CTCPersonInterface $ctcPerson
	 * @param CTCI_PersonInterface $person
	 * @param string $mode      Set to 'new' to only attach if ctcPerson has no current attach record.
	 *                          Set to 'replace' to either add a new attachment, or overwrite an existing one.
	 * @return bool     If $mode is 'new' returns true if attach successful, false if ctcPerson is already attached
	 *                  If $mode is 'replace' true if update succeeded, including if ctcPerson was already attached
	 *                  to the given $person. Otherwise false if an error occurred.
	 */
	public function attachCTCPerson( CTCI_CTCPersonInterface $ctcPerson, CTCI_PersonInterface $person, $mode = 'new' ) {

		if ( $mode === 'new' ) {
			if ( ! add_post_meta( $ctcPerson->id(), self::$ctcPersonProviderTagMetaTag, $person->getProviderTag(), true ) ) {
				return false;
			}
			if ( ! add_post_meta( $ctcPerson->id(), self::$ctcPersonProviderIdMetaTag, $person->id(), true ) ) {
				return false;
			}
			return true;
		} elseif ( $mode === 'replace' ) {
			// these will add the meta tags if they don't exist
			$tagSuccess = update_post_meta( $ctcPerson->id(), self::$ctcPersonProviderTagMetaTag, $person->getProviderTag() );
			$idSuccess = update_post_meta( $ctcPerson->id(), self::$ctcPersonProviderIdMetaTag, $person->id() );

			// because update_post_meta returns false for errors as well as if the meta tag already exists with the same value
			// we need to run our own queries to be sure that the update has occurred...
			$tagValueCorrect =
				get_post_meta( $ctcPerson->id(), self::$ctcPersonProviderTagMetaTag ) ===
				array( $person->getProviderTag() );
			$idValueCorrect =
				get_post_meta( $ctcPerson->id(), self::$ctcPersonProviderIdMetaTag ) ===
				array( $person->id() );

			if ( ( $tagSuccess === false && ! $tagValueCorrect ) || $idSuccess === false && ! $idValueCorrect ) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * @param CTCI_CTCPersonInterface $ctcPerson
	 * @return bool     Returns false if any call to delete_post_meta returned false, otherwise true
	 */
	public function unattachCTCPerson( CTCI_CTCPersonInterface $ctcPerson ) {
		$success = true;
		if ( ! delete_post_meta( $ctcPerson->id(), self::$ctcPersonProviderTagMetaTag ) ) {
			$success = false;
		}
		if ( ! delete_post_meta( $ctcPerson->id(), self::$ctcPersonProviderIdMetaTag ) ) {
			$success = false;
		}
		return $success;
	}

	/**
	 * Set the groups for the given CTC person. This will replace all existing groups for the person, but will ignore
	 * any passed PeopleGroups that are not attached to a CTC group. If there is no attach record mapping the people
	 * group to a CTC group, there is no way to know what CTC group term to set on the CTC person.
	 * @param CTCI_CTCPersonInterface $ctcPerson
	 * @param CTCI_PeopleGroupInterface[] $groups
	 */
	public function setCTCPersonsGroups( CTCI_CTCPersonInterface $ctcPerson, array $groups ) {
		$groupIds = array();
		foreach ( $groups as $group ) {
			$ctcGroup = $this->getAttachedCTCGroup( $group );
			if ( null !== $ctcGroup ) {
				// need to make sure that these are ints so wp_set_object_terms treats them as ids and not slugs
				$groupIds[] = (int)$ctcGroup->id();
			}
		}
		wp_set_object_terms( $ctcPerson->id(), $groupIds, self::$ctcPersonGroupTaxonomy );
	}

	public function deleteCTCPerson( CTCI_CTCPersonInterface $ctcPerson ) {
		$success = wp_delete_post( $ctcPerson->id() );
		if ( $success === false ) {
			return false;
		} else {
			return true;
		}
	}

	public function unpublishCTCPerson( CTCI_CTCPersonInterface $ctcPerson ) {
		$return = wp_update_post( array(
			'ID' => $ctcPerson->id(),
			'post_status' => 'draft'
		) );
		if ( $return === 0) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * @param $providerTag
	 * @return array|CTCI_CTCPersonInterface[]
	 */
	public function getCTCPeopleAttachedViaProvider( $providerTag ) {
		$posts = get_posts( array(
			'numberposts' => -1,    // needed to ensure all posts are retrieved
			'post_type' => self::$ctcPersonPostType,
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => self::$ctcPersonProviderTagMetaTag,
					'value' => $providerTag
				)
			)
		) );
		$ctcPeople = array();
		foreach ( $posts as $post ) {
			$ctcPeople[ $post->ID ] = $this->populateCTCPersonFromPost( $post );
		}
		return $ctcPeople;
	}

	protected function populateCTCPersonFromPost( WP_Post $post ) {
		$ctcPerson = new CTCI_CTCPerson();
		$ctcPerson
			->setId( $post->ID )
			->setName( $post->post_title )
			->setBio( $post->post_content )
			->setExcerpt( $post->post_excerpt )
			->setPosition( get_post_meta( $post->ID, self::$ctcPersonPositionMetaTag, true ) )
			->setPhone( get_post_meta( $post->ID, self::$ctcPersonPhoneMetaTag, true ) )
			->setEmail( get_post_meta( $post->ID, self::$ctcPersonEmailMetaTag, true ) )
			->setUrls( get_post_meta( $post->ID, self::$ctcPersonURLSMetaTag, true ) );
		return $ctcPerson;
	}

	/**
	 * @param CTCI_CTCPersonInterface $ctcPerson
	 * @return string   The provider ID attached to the given person, or an empty string if no attach record
	 */
	public function getAttachedPersonId( CTCI_CTCPersonInterface $ctcPerson ) {
		return get_post_meta( $ctcPerson->id(), self::$ctcPersonProviderIdMetaTag, true );
	}

	/**
	 * @return CTCI_CTCPersonInterface[]
	 */
	public function getUnattachedCTCPeople() {
		$posts = get_posts( array(
			'post_type' => self::$ctcPersonPostType,
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => self::$ctcPersonProviderTagMetaTag,
					'value' => 'novalue', // see https://codex.wordpress.org/Class_Reference/WP_Query#Custom_Field_Parameters
					// for why this is here - WP issue
					'compare' => 'NOT EXISTS',
				)
			)
		) );
		$ctcPeople = array();
		foreach ( $posts as $post ) {
			$ctcPeople[ $post->ID ] = $this->populateCTCPersonFromPost( $post );
		}
		return $ctcPeople;
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

class CTCI_CTCGroupExceptionType extends Exception {
	protected $ctcGroup;

	public function __construct( CTCI_CTCGroupInterface $ctcGroup, $message = '', $code = 0, $innerException = null ) {
		parent::__construct( $message, $code, $innerException );
		$this->ctcGroup = $ctcGroup;
	}

	public function getCTCGroup() {
		return $this->ctcGroup;
	}
}

class CTCI_CouldNotUnattachCTCGroupException extends CTCI_CTCGroupExceptionType {}

class CTCI_CouldNotDeleteCTCGroupException extends CTCI_CTCGroupExceptionType {}

class CTCI_CouldNotRetrieveUnattachedCTCGroupsException extends Exception {}

class CTCI_CouldNotCreateCTCPersonFromPersonException extends Exception {
	protected $person;
	protected $wp_error;
	public function __construct( CTCI_PersonInterface $person, WP_Error $error, $message = '', $code = 0, $innerException = null) {
		parent::__construct( $message, $code, $innerException );
		$this->person = $person;
		$this->wp_error = $error;
	}

	/**
	 * @return CTCI_PersonInterface
	 */
	public function getPerson() {
		return $this->person;
	}

	/**
	 * @return WP_Error
	 */
	public function getWPError() {
		return $this->wp_error;
	}
}