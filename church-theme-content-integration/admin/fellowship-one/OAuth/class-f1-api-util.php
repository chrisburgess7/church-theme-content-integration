<?php

/**
 *
 * Copyright 2009 Fellowship Technologies
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may obtain a copy of the License at http://www.apache.org/licenses/LICENSE-2.0
 * Unless required by applicable law or agreed to in writing, software distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and limitations under the License.
 *
 * Description of CTCI_F1APIUtil
 *
 * @author Jaskaran Singh (Jas)
 */
class CTCI_F1APIUtil {
	public static function urlencode_rfc3986( $input ) {
		if ( is_array( $input ) ) {
			return array_map( array( 'CTCI_F1APIUtil', 'urlencode_rfc3986' ), $input );
		} else {
			if ( is_scalar( $input ) ) {
				return str_replace( '+', ' ',
					str_replace( '%7E', '~', rawurlencode( $input ) ) );
			} else {
				return '';
			}
		}
	}

	/**
	 * parses the url and rebuilds it to be
	 * scheme://host/path
	 */
	public static function getNormalizedHttpUrl( $httpUrl ) {
		$parts = parse_url( $httpUrl );

		$port = @$parts[ 'port' ];
		$scheme = @$parts[ 'scheme' ];
		$host = @$parts[ 'host' ];
		$path = @$parts[ 'path' ];

		$port or $port = ( $scheme == 'https' ) ? '443' : '80';

		if ( ( $scheme == 'https' && $port != '443' )
			|| ( $scheme == 'http' && $port != '80' )
		) {
			$host = "$host:$port";
		}
		return "$scheme://$host$path";
	}

	public static function getPort( $httpUrl ) {
		$parts = parse_url( $httpUrl );
		$port = @$parts[ 'port' ];
		$scheme = $parts[ 'scheme' ];
		$port or $port = ( $scheme == 'https' ) ? '443' : '80';
		return $port;
	}

	public static function getHostName( $httpUrl ) {
		$parts = parse_url( $httpUrl );
		$host = $parts[ 'host' ];
		return $host;
	}

	public static function getGuid() {

		// The field names refer to RFC 4122 section 4.1.2

		return sprintf( '%04x%04x-%04x-%03x4-%04x-%04x%04x%04x',
			mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), // 32 bits for "time_low"
			mt_rand( 0, 65535 ), // 16 bits for "time_mid"
			mt_rand( 0, 4095 ), // 12 bits before the 0100 of (version) 4 for "time_hi_and_version"
			bindec( substr_replace( sprintf( '%016b', mt_rand( 0, 65535 ) ), '01', 6, 2 ) ),
			// 8 bits, the last two of which (positions 6 and 7) are 01, for "clk_seq_hi_res"
			// (hence, the 2nd hex digit after the 3rd hyphen can only be 1, 5, 9 or d)
			// 8 bits for "clk_seq_low"
			mt_rand( 0, 65535 ), mt_rand( 0, 65535 ), mt_rand( 0, 65535 ) // 48 bits for "node"
		);
	}
}

?>
