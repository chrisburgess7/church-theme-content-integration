<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 17/03/14
 * Time: 11:23 AM
 */

require_once dirname(__FILE__) . '/interface-ctc-person.php';

class CTCI_CTCPerson implements CTCI_CTCPersonInterface {

	protected $urlRegex;
	protected $urlRegex1;
	protected $urlRegex2;
	protected $skypeRegex;

	protected $nameDirty;
	protected $bioDirty;
	protected $positionDirty;
	protected $phoneDirty;
	protected $emailDirty;
	protected $urlsDirty;
	protected $excerptDirty;

	protected $id;
	protected $name;
	protected $bio;
	protected $position;
	protected $phone;
	protected $email;
	protected $urls;
	protected $excerpt;

	public function __construct() {
		$this->setClean();
		// credits: https://gist.github.com/dperini/729294
		$this->urlRegex1 = '_' .
			'(?:(?:https?)://)' .
			'(?>[a-z\x{00a1}-\x{ffff}0-9]+-?)*\.?'; // Note that this is an atomic group - needed to avoid catastrophic backtracking
		$this->urlRegex2 = '(?:\.(?:[a-z\x{00a1}-\x{ffff}0-9]+-?)*[a-z\x{00a1}-\x{ffff}0-9]+)*' . // domain name
			'(?:\.(?:[a-z\x{00a1}-\x{ffff}]{2,}))' . // TLD identifier
			'(?::\d{2,5})?(?:/[^\s]*)?' .  // rest of path
			'_iuS';
		$this->urlRegex = $this->urlRegex1 .
			'(facebook|twitter|plus.google|pinterest|youtube|vimeo|flickr|picasa|' .
			'instagram|foursquare|tumblr|soundcloud|linkedin|github|dribbble|itunes)' .
			$this->urlRegex2;
		$this->skypeRegex = '_skype://(?:[^\s]*)_iuS';
	}

	public function setClean() {
		$this->nameDirty = false;
		$this->bioDirty = false;
		$this->positionDirty = false;
		$this->phoneDirty = false;
		$this->emailDirty = false;
		$this->urlsDirty = false;
		$this->excerptDirty = false;
	}


	/**
	 * @param mixed $bio
	 * @return $this
	 */
	public function setBio( $bio ) {
		$this->bio = $bio;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getBio() {
		return $this->bio;
	}

	/**
	 * @param mixed $email
	 * @return $this
	 */
	public function setEmail( $email ) {
		$this->email = $email;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getEmail() {
		return $this->email;
	}

	/**
	 * @param mixed $excerpt
	 * @return $this
	 */
	public function setExcerpt( $excerpt ) {
		$this->excerpt = $excerpt;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getExcerpt() {
		return $this->excerpt;
	}

	/**
	 * @param mixed $id
	 * @return $this
	 */
	public function setId( $id ) {
		$this->id = $id;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function id() {
		return $this->id;
	}

	/**
	 * @param mixed $name
	 * @return $this
	 */
	public function setName( $name ) {
		$this->name = $name;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param mixed $phone
	 * @return $this
	 */
	public function setPhone( $phone ) {
		$this->phone = $phone;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPhone() {
		return $this->phone;
	}

	/**
	 * @param mixed $position
	 * @return $this
	 */
	public function setPosition( $position ) {
		$this->position = $position;
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getPosition() {
		return $this->position;
	}

	/**
	 * @param mixed $urls
	 * @return $this
	 */
	public function setUrls( $urls ) {
		$this->urls = $urls;
		return $this;
	}

	public function setURLsFromArray( array $urls ) {
		$this->urls = implode("\n", $urls);
		return $this;
	}

	/**
	 * @return mixed
	 */
	public function getUrls() {
		return $this->urls;
	}

	public function isNameDirty() {
		return $this->nameDirty;
	}

	public function isBioDirty() {
		return $this->bioDirty;
	}

	public function isPositionDirty() {
		return $this->positionDirty;
	}

	public function isPhoneDirty() {
		return $this->phoneDirty;
	}

	public function isEmailDirty() {
		return $this->emailDirty;
	}

	public function isURLsDirty() {
		return $this->urlsDirty;
	}

	public function isExcerptDirty() {
		return $this->excerptDirty;
	}

	public function setGroups( array $ctcGroups ) {
		// TODO: Implement setGroups() method.
	}

	public function getGroups() {
		// TODO: Implement getGroups() method.
	}

	public function editName( $name ) {
		$this->setName( $name );
		$this->nameDirty = true;
		return $this;
	}

	public function editBio( $bio ) {
		$this->setBio( $bio );
		$this->bioDirty = true;
		return $this;
	}

	public function editPosition( $position ) {
		$this->setPosition( $position );
		$this->positionDirty = true;
		return $this;
	}

	public function editPhone( $phone ) {
		$this->setPhone( $phone );
		$this->phoneDirty = true;
		return $this;
	}

	public function editEmail( $email ) {
		$this->setEmail( $email );
		$this->emailDirty = true;
		return $this;
	}

	public function editURLs( $urls ) {
		$this->setUrls( $urls );
		$this->urlsDirty = true;
		return $this;
	}

	public function editURLsFromArray( array $urls ) {
		$this->setURLsFromArray( $urls );
		$this->urlsDirty = true;
		return $this;
	}

	public function editURL( $url ) {
		$matches = array();
		// check the passed url to see if it matches a known type
		if ( preg_match( $this->urlRegex, $url, $matches ) ) {
			$urlType = $matches[1];
			// create a regex for just this url type, so we replace the right one
			$urlTypeRegex = $this->urlRegex1 . $urlType . $this->urlRegex2;
			$replaced = 0;
			$urls = preg_replace( $urlTypeRegex, $url, $this->urls, 1, $replaced );
			if ( $replaced ) {
				$this->urls = $urls;
			} else {
				if ( ! empty( $this->urls ) ) {
					$this->urls .= "\n" . $url;
				} else {
					$this->urls = $url;
				}
			}
		} else {
			// check if skype url
			if ( preg_match( $this->skypeRegex, $url ) ) {
				$replaced = 0;
				$urls = preg_replace( $this->skypeRegex, $url, $this->urls, 1, $replaced );
				if ( $replaced ) {
					$this->urls = $urls;
				} else {
					if ( ! empty( $this->urls ) ) {
						$this->urls .= "\n" . $url;
					} else {
						$this->urls = $url;
					}
				}
			} else {
				// if all else fails, just append
				if ( ! empty( $this->urls ) ) {
					$this->urls .= "\n" . $url;
				} else {
					$this->urls = $url;
				}
			}
		}
		$this->urlsDirty = true;
		return $this;
	}

	public function editExcerpt( $excerpt ) {
		$this->setExcerpt( $excerpt );
		$this->excerptDirty = true;
		return $this;
	}
}