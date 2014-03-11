<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 11/03/14
 * Time: 10:41 AM
 *
 * A quick script to copy all plugin and test files into a wordpress development setup
 * (https://github.com/tierra/wordpress-plugin-tests) for testing against wordpress itself.
 */

$wordpressDir = dirname( __FILE__ ) . '/../../wordpress';
$wordpressPluginsDir = $wordpressDir . '/src/wp-content/plugins';
$pluginDirName = 'church-theme-content-integration';
$pluginDir = dirname( __FILE__ ) . '/../' . $pluginDirName;
$wordpressPluginDir = $wordpressPluginsDir . '/' . $pluginDirName;
$testFilesDir = dirname( __FILE__ );
$wordpressPluginTestDir = $wordpressPluginDir . '/tests';

function copyFiles( $baseDir, $subDir, $destDir ) {
	if ( $subDir != '' ) {
		$currSrcDir = $baseDir . DIRECTORY_SEPARATOR . $subDir;
	} else {
		$currSrcDir = $baseDir;
	}
	$cdir = scandir( $currSrcDir );
	foreach ( $cdir as $value ) {
		if ( !in_array( $value, array( ".", ".." ) ) ) {
			if ( is_dir( $currSrcDir . DIRECTORY_SEPARATOR . $value ) ) {
				mkdir( $destDir . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . $value );
				copyFiles( $baseDir, $subDir . DIRECTORY_SEPARATOR . $value, $destDir );
			} else {
				if ( $subDir !== '' ) {
					$destination = $destDir . DIRECTORY_SEPARATOR . $subDir . DIRECTORY_SEPARATOR . $value;
				} else {
					$destination = $destDir . DIRECTORY_SEPARATOR . $value;
				}

				copy(
					$currSrcDir . DIRECTORY_SEPARATOR . $value,
					$destination
				);
			}
		}
	}
}

function rrmdir( $dir ) {
	if ( is_dir( $dir ) ) {
		$objects = scandir( $dir );
		foreach ( $objects as $object ) {
			if ( $object != "." && $object != ".." ) {
				if ( filetype( $dir . "/" . $object ) == "dir" ) {
					rrmdir( $dir . "/" . $object );
				} else {
					unlink( $dir . "/" . $object );
				}
			}
		}
		reset( $objects );
		rmdir( $dir );
	}
}

// clean up old stuff
if ( file_exists( $wordpressPluginDir ) ) {
	rrmdir( $wordpressPluginDir );
}
mkdir( $wordpressPluginDir );
mkdir( $wordpressPluginTestDir );

copyFiles( $pluginDir, '', $wordpressPluginDir );
copyFiles( $testFilesDir, '', $wordpressPluginDir . DIRECTORY_SEPARATOR . 'tests' );
