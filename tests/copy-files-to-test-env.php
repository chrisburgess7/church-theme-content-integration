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

// name of this plugin being tested
$pluginDirName = 'church-theme-content-integration';
// location of plugin directory in this project
$pluginDir = dirname( __FILE__ ) . '/../' . $pluginDirName;
// base folder for test files in this project
$testFilesDir = dirname( __FILE__ );

// location of wordpress test env
$wordpressDir = dirname( __FILE__ ) . '/../../wordpress';
// locations of plugins directory in wordpress test env
$wordpressPluginsDir = $wordpressDir . '/src/wp-content/plugins';
// directory of this plugin in wordpress test env
$wordpressPluginDir = $wordpressPluginsDir . '/' . $pluginDirName;
// location of test files for this plugin in test wordpress env
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

function rrmdir( $dir, $deleteCurrent = false ) {
	if ( is_dir( $dir ) ) {
		$objects = scandir( $dir );
		foreach ( $objects as $object ) {
			if ( $object != "." && $object != ".." ) {
				if ( filetype( $dir . "/" . $object ) == "dir" ) {
					rrmdir( $dir . "/" . $object, true );
				} else {
					unlink( $dir . "/" . $object );
				}
			}
		}
		reset( $objects );
		if ($deleteCurrent) {
			rmdir( $dir );
		}
	}
}

// clean up old stuff - assumes the plugin directory itself already exists
if ( file_exists( $wordpressPluginDir ) ) {
	rrmdir( $wordpressPluginDir );
}
mkdir( $wordpressPluginTestDir );

copyFiles( $pluginDir, '', $wordpressPluginDir );
copyFiles( $testFilesDir, '', $wordpressPluginDir . DIRECTORY_SEPARATOR . 'tests' );

// copy some test files into the base plugin folder required
//copy( $testFilesDir)

echo PHP_EOL . 'Copy Complete' . PHP_EOL;

// load the wordpress bootstrap file
//require_once $wordpressBootstrapFile;