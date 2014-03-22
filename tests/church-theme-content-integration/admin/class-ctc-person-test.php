<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 20/03/14
 * Time: 3:59 PM
 */

require_once dirname( __FILE__ ) . '/../../../church-theme-content-integration/admin/class-ctc-person.php';

class CTCI_CTCPersonTest extends PHPUnit_Framework_TestCase {

	public static function editURLData() {
		$fullURLSample = str_replace( "\r", '',
'https://www.facebook.com/dialog/feed?app_id={app_id}&link={url}&picture={img}&name={title}&description={desc}&redirect_uri={redirect_url}
https://twitter.com/share?url={url}&text={title}&via={via}&hashtags={hashtags}
https://plus.google.com/share?url={url}
https://pinterest.com/pin/create/bookmarklet/?media={img}&url={url}&is_video={is_video}&description={title}
http://www.youtube.com/watch?v=uMdl80k-3yo
https://vimeo.com/391720
https://www.flickr.com/photos/eshu/galleries/72157621948084368/
http://picasa.google.com.au/
http://instagram.com/username111
https://foursquare.com/explore?cat=food&mode=url&near=Wollongong%2C%20NSW
http://every1-knows-its-butters.tumblr.com/
http://www.tumblr.com/share/link?url={url}&name={title}&description={desc}
skype://notsurewhatgoeshere
https://soundcloud.com/iambrodydalle
http://www.linkedin.com/shareArticle?url={url}&title={title}
https://github.com/chrisburgess7
http://dribbble.com/MailChimp
https://itunes.apple.com/au/podcast/the-history-of-britain/id802163163?mt=2');

		return array(
			array(
				array( 'http://facebook.com/url1' ),
				array( 'http://facebook.com/url2' ),
				'http://facebook.com/url2'
			), array(
				array( 'http://facebook.com/url1', 'http://twitter.com/username' ),
				array( 'http://facebook.com/url2' ),
				"http://facebook.com/url2\nhttp://twitter.com/username"
			), array(
				$fullURLSample,
				array( 'http://facebook.com/username2' ),
				str_replace( "\r", '',
'http://facebook.com/username2
https://twitter.com/share?url={url}&text={title}&via={via}&hashtags={hashtags}
https://plus.google.com/share?url={url}
https://pinterest.com/pin/create/bookmarklet/?media={img}&url={url}&is_video={is_video}&description={title}
http://www.youtube.com/watch?v=uMdl80k-3yo
https://vimeo.com/391720
https://www.flickr.com/photos/eshu/galleries/72157621948084368/
http://picasa.google.com.au/
http://instagram.com/username111
https://foursquare.com/explore?cat=food&mode=url&near=Wollongong%2C%20NSW
http://every1-knows-its-butters.tumblr.com/
http://www.tumblr.com/share/link?url={url}&name={title}&description={desc}
skype://notsurewhatgoeshere
https://soundcloud.com/iambrodydalle
http://www.linkedin.com/shareArticle?url={url}&title={title}
https://github.com/chrisburgess7
http://dribbble.com/MailChimp
https://itunes.apple.com/au/podcast/the-history-of-britain/id802163163?mt=2')
			), array(
				$fullURLSample,
				array( 'http://twitter.com/username2' ),
				str_replace( "\r", '',
'https://www.facebook.com/dialog/feed?app_id={app_id}&link={url}&picture={img}&name={title}&description={desc}&redirect_uri={redirect_url}
http://twitter.com/username2
https://plus.google.com/share?url={url}
https://pinterest.com/pin/create/bookmarklet/?media={img}&url={url}&is_video={is_video}&description={title}
http://www.youtube.com/watch?v=uMdl80k-3yo
https://vimeo.com/391720
https://www.flickr.com/photos/eshu/galleries/72157621948084368/
http://picasa.google.com.au/
http://instagram.com/username111
https://foursquare.com/explore?cat=food&mode=url&near=Wollongong%2C%20NSW
http://every1-knows-its-butters.tumblr.com/
http://www.tumblr.com/share/link?url={url}&name={title}&description={desc}
skype://notsurewhatgoeshere
https://soundcloud.com/iambrodydalle
http://www.linkedin.com/shareArticle?url={url}&title={title}
https://github.com/chrisburgess7
http://dribbble.com/MailChimp
https://itunes.apple.com/au/podcast/the-history-of-britain/id802163163?mt=2')
			), array(
				$fullURLSample,
				array(
					'http://twitter.com/username77',
					'http://www.facebook.com/username77',
					'https://plus.google.com/u/0/+UserName/posts',
					'http://www.linkedin.com/profile/view?id=123456789',
					'https://itunes.apple.com/au/podcast/mypodcast/id123456789?mt=2'
				),
				str_replace( "\r", '',
'http://www.facebook.com/username77
http://twitter.com/username77
https://plus.google.com/u/0/+UserName/posts
https://pinterest.com/pin/create/bookmarklet/?media={img}&url={url}&is_video={is_video}&description={title}
http://www.youtube.com/watch?v=uMdl80k-3yo
https://vimeo.com/391720
https://www.flickr.com/photos/eshu/galleries/72157621948084368/
http://picasa.google.com.au/
http://instagram.com/username111
https://foursquare.com/explore?cat=food&mode=url&near=Wollongong%2C%20NSW
http://every1-knows-its-butters.tumblr.com/
http://www.tumblr.com/share/link?url={url}&name={title}&description={desc}
skype://notsurewhatgoeshere
https://soundcloud.com/iambrodydalle
http://www.linkedin.com/profile/view?id=123456789
https://github.com/chrisburgess7
http://dribbble.com/MailChimp
https://itunes.apple.com/au/podcast/mypodcast/id123456789?mt=2')
			), array(
				$fullURLSample,
				array(
					'skype://username.skype.com'
				),
				str_replace( "\r", '',
'https://www.facebook.com/dialog/feed?app_id={app_id}&link={url}&picture={img}&name={title}&description={desc}&redirect_uri={redirect_url}
https://twitter.com/share?url={url}&text={title}&via={via}&hashtags={hashtags}
https://plus.google.com/share?url={url}
https://pinterest.com/pin/create/bookmarklet/?media={img}&url={url}&is_video={is_video}&description={title}
http://www.youtube.com/watch?v=uMdl80k-3yo
https://vimeo.com/391720
https://www.flickr.com/photos/eshu/galleries/72157621948084368/
http://picasa.google.com.au/
http://instagram.com/username111
https://foursquare.com/explore?cat=food&mode=url&near=Wollongong%2C%20NSW
http://every1-knows-its-butters.tumblr.com/
http://www.tumblr.com/share/link?url={url}&name={title}&description={desc}
skype://username.skype.com
https://soundcloud.com/iambrodydalle
http://www.linkedin.com/shareArticle?url={url}&title={title}
https://github.com/chrisburgess7
http://dribbble.com/MailChimp
https://itunes.apple.com/au/podcast/the-history-of-britain/id802163163?mt=2')
			)
		);
		/*
		 * http://www.linkedin.com/profile/view?id=123456789&snapshotID=&authType=name&authToken=_OJg&ref=NUS&goback=%2Enmp_*1_*1_*1_*1_*1_*1_*1_*1_*1_*1_*1&trk=NUS-body-member-name
		 */
	}

	/**
	 * @dataProvider editURLData
	 * @param $startURLs
	 * @param $urlEdits
	 * @param $result
	 */
	public function testEditURL( $startURLs, $urlEdits, $result ) {
		$ctcPerson = new CTCI_CTCPerson();
		if ( is_array( $startURLs ) ) {
			$ctcPerson->setURLsFromArray( $startURLs );
		} else {
			$ctcPerson->setURLs( $startURLs );
		}
		$this->assertFalse( $ctcPerson->isURLsDirty() );

		foreach ( $urlEdits as $urlEdit ) {
			$ctcPerson->editURL( $urlEdit );
		}

		$this->assertEquals( $result, $ctcPerson->getUrls() );
		$this->assertTrue( $ctcPerson->isURLsDirty() );
	}

}
 