<?php
/*

Dynect-PHP - a simple PHP library for using the Dynect API
		http://dyn.com/developer

   Copyright 2011 Scott Merrill

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.
*/
class dynect
{

	private $api_url;
	private $token;
	private $credentials;
	public $result;

	/*
	 * instantiate a Dynect object
	 * @credentials array Dynect credentials
	 * @return object a Dynect object
	 */
	public function __construct( $credentials )
	{
		$this->api_url = 'https://api2.dynect.net/REST';
		$this->credentials = $credentials;
	}

	/*
	 * execute a call to the Dynect API
	 * @command string the API command to invoke
	 * @crud string HTTP verb to use (GET, PUT, POST, or DELETE)
	 * @args array associative array of data to send
	 * @return mixed the Dynect response
	 */
	private function execute( $command, $crud, $args = array() )
	{
		// empty result cache
		$this->result = '';
		$headers = array( 'Content-Type: application/json' );
		if ( ! empty( $this->token ) ) {
			$headers[] = 'Auth-Token: ' . $this->token;
		}
		$ch = curl_init();
		// return the transfer as a string of the return value 
		// instead of outputting it out directly. 
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		// Do not fail silently. We want a response regardless
		curl_setopt( $ch, CURLOPT_FAILONERROR, false );
		// disables response header and only returns the response body 
		curl_setopt( $ch, CURLOPT_HEADER, false );
		// Set the content type of the post body via HTTP headers
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $crud );
		// API endpoint to use
		curl_setopt( $ch, CURLOPT_URL, $this->api_url . "/$command/" );
		if ( ! empty( $args ) ) {
			curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $args ) );
		}
		$result = curl_exec( $ch );
		$this->result = $result;
		curl_close( $ch );
		return json_decode( $result );
	}

	/*
	 * log into the Dynect API and obtain an API token
	 * @return bool success or failure
	 */
	public function login()
	{
		$result = $this->execute( 'Session', 'POST', $this->credentials );
		if ( 'success' == $result->status )
		{
			$this->token = $result->data->token;
			return true;
		}
		return false;
	}

	/*
	 * logout, destroying a Dynect API token
	 * @return bool success or failure
	 */
	public function logout()
	{
		$result = $this->execute( 'Session', 'DELETE' );
		if ( 'success' == $result->status )
		{
			return true;
		}
		return false;
	}

/***** ZONES *****/

	/*
	 * create a new Dynect zone
	 * @contact string email address for the contact of this zone
	 * @name string the name of the zone
	 * @ttl int the default TTL to set for this zone
	 * @return bool success or failure
	 */
	public function zoneCreate( $contact, $name, $ttl = 3600 )
	{
		if ( empty( $contact) || empty( $name ) ) {
			return false;
		}
		$result = $this->execute( "Zone/$name", 'POST', array( 'rname' => $contact, 'zone' => $name, 'ttl' => $ttl ) );
		if ( 'success' == $result->status )
		{
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * delete a Dynect zone
	 * @zone string name of the zone to delete
	 * @return bool success or failure
	 */
	public function zoneDelete( $zone )
	{
		$result = $this->execute( "Zone/$zone", 'DELETE' );
		if ( 'success' == $result->status )
		{
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * publish a zone
	 * @zone string name of the zone to publish
	 * @return bool success or failure
	 */
	public function zonePublish ( $zone )
	{
		$result = $this->execute( "Zone/$zone", 'PUT', array( 'publish' => 'TRUE' ) );
		if ( 'success' == $result->status )
		{
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * freeze a zone, preventing changes
	 * @zone string Zone name
	 * @return bool success or failure
	 */
	public function zoneFreeze( $zone )
	{
		$result = $this->execute( "Zone/$zone", 'PUT', array( 'freeze' => 'TRUE' ) );
		if ( 'success' == $result->status )
		{
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * thaw a zone, permitting changes
	 * @zone string Zone name
	 * @return bool success or failure
	 */
	public function zoneThaw( $zone )
	{
		$result = $this->execute( "Zone/$zone", 'PUT', array( 'thaw' => 'TRUE' ) );
		if ( 'success' == $result->status )
		{
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * get a list of zones
	 * @return mixed Array of avialable zones or boolean false
	 */
	public function zoneGetList()
	{
		$result = $this->execute( "Zone", 'GET' );
		if ( 'success' == $result->status )
		{
			$domains = array();
			foreach ( $result->data as $value )
			{
				$domains[] = rtrim( str_replace( '/REST/Zone/', '', $value ), '/' );
			}
			return $domains;
		}
		return FALSE;
	}

	/*
	 * get details of a specific zone
	 * @zone string Zone name
	 * @return mixed Object of zone data or boolean false
	 */
	public function zoneGet( $zone )
	{
		$result = $this->execute( "Zone/$zone", 'GET' );
		if ( 'success' == $result->status )
		{
			return $result->data;
		}
		return FALSE;
	}

/***** NODES *****/

	/*
	 * delete a node, any records in it, and any nodes underneath it
	 * @zone string Zone containing the node
	 * @fqdn string FQDN of the node to delete
	 * @return bool success or failure
	 */
	public function nodeDelete( $zone, $fqdn )
	{
		$result = $this->execute( "Node/$zone/$fqdn", 'DELETE' );
		if ( 'success' == $result->status )
		{
			return TRUE;
		}
		return FALSE;
	}
 
	/*
	 * list all the nodes in a zone
	 * @zone string the zone to query
	 * @fqdn string a top-level node in the zone
	 * @return mixed Array of node data, or boolean false
	 */
	public function nodeList( $zone, $fqdn = '' )
	{
		$command = "NodeList/$zone";
		if ( ! empty( $fqdn ) )
		{
			$command .= "/$fqdn";
		}
		$result = $this->execute( $command, 'GET' );
		if ( 'success' == $result->status )
		{
			return $result->data;
		}
		return FALSE;
	}

/***** A RECORDS *****/
	/*
	 * create a new A record in a zone
	 * @zone string name of the zone to contain the record
	 * @fqdn string FQDN of the A record to create
	 * @ip string IP address of the A record to create
	 * @ttl int TTL value for the record
	 * @return bool success or failure 
	 */
	public function arecordAdd ( $zone, $fqdn, $ip, $ttl = 0 )
	{
		$record = array( 'rdata' => array( 'address' => $ip, ),
				 'ttl' => $ttl,
				);
		$result = $this->execute( "ARecord/$zone/$fqdn", 'POST', $record );
		if ( 'success' == $result->status )
		{
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * delete an A record
	 * @zone string name of the zone containing the A record
	 * @fqdn string FQDN of the A record to delete
	 * @id int Dynect ID of the A record to delete
	 * @return bool success or failure
	 */
	public function arecordDelete ( $zone, $fqdn, $id )
	{
		$result = $this->execute( "ARecord/$zone/$fqdn/$id", 'DELETE' );
		if ( 'success' == $result->status )
		{
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * get a list of A record IDs for an FQDN
	 * @zone string name of the zone containing the A record
	 * @fqdn string FQDN fo the A record to query
	 * @return mixed array of Dynect IDs or boolean false
	 */
	public function arecordGetList( $zone, $fqdn ) 
	{
		$result = $this->execute( "ARecord/$zone/$fqdn", 'GET' );
		if ( 'success' == $result->status )
		{
			if ( empty( $result->data ) )
			{
				return FALSE;
			}
			$records = array();
			foreach ( $result->data as $data )
			{
				$records[] = str_replace( "/REST/ARecord/$zone/$fqdn/", '', $data );
			}
			return $records;
		}
		return FALSE;
	}

	/*
	 * get data about a specific A record
	 * @zone string name of the zone containing the A record
	 * @fqdn string FQDN of the A record to query
	 * @id int Dynect ID of the record
	 * @return mixed Object of record data, or boolean false
	 */
	public function arecordGet( $zone, $fqdn, $id = '' )
	{
		$result = $this->execute( "ARecord/$zone/$fqdn/$id", 'GET' );
		if ( 'success' == $result->status )
		{
			return $result->data;
		}
		return FALSE;
	}

/***** CNAMEs *****/

	/*
	 * create a new CNAME record
	 * @zone string the name of the zone to contain the CNAME
	 * @fqdn string the FQDN of the target of the CNAME record
	 * @cname string the FQDN of the CNAME to create
	 * @ttl int the TTL for the CNAME
	 * @return bool success or failure
	 */
	public function cnameAdd ( $zone, $fqdn, $cname, $ttl = 0 )
	{
		$record = array( 'rdata' => array( 'cname' => $cname ),
				'ttl' => $ttl,
				);
		$result = $this->execute( "CNAMERecord/$zone/$fqdn", 'POST', $record );
		if ( 'success' == $result->status )
		{
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * delete a CNAME
	 * @zone string name of the zone containing the CNAME
	 * @fqdn string FQDN of the CNAME to delete
	 * @id int Dynect ID of the CNAME
	 * @return bool success or failure
	 */
	public function cnameDelete( $zone, $fqdn, $id )
	{
		$result = $this->execute( "CNAMERecord/$zone/$fqdn/$id", 'DELETE' );
		if ( 'success' == $result->status )
		{
			return TRUE;
		}
		return FALSE;
	}

	/*
	 * get a list of CNAME records
	 * @zone string the name of the zone to query
	 * @fqdn string FQDN of the CNAME
	 * @return mixed array of Dynect IDs or boolean false
	 */
	public function cnameGetList( $zone, $fqdn )
	{
		$result = $this->execute( "CNAMERecord/$zone/$fqdn", 'GET' );
		if ( 'success' == $result->status )
		{
			if ( empty( $result->data ) )
			{
				return FALSE;
			}
			$records = array();
			foreach ( $result->data as $data );
			{
				$records[] = str_replace( "/REST/CNAMERecord/$zone/$fqdn/", '', $data );
			}
			return $records;
		}
		return FALSE;
	}

	/*
	 * get data about a specific CNAME
	 * @zone string name of the zone containing the CNAME
	 * @fqdn string FQDN of the CNAME
	 * @id int Dynect ID of the CNAME
	 * @return mixed Object of Dynect data or boolean false
	 */
	public function cnameGet( $zone, $fqdn, $id )
	{
		$result = $this->execute( "CNAMERecord/$zone/$fqdn/$id", 'GET' );
		if ( 'success' == $result->status )
		{
			return $result->data;
		}
		return FALSE;
	}

/***** HTTP Redirect *****/

	/*
	 * create a new HTTP redirect
	 */

	/*
	 * delete an HTTP redirect
	 */

	/*
	 * get a list of HTTP redirects
	 */

	/*
	 * get details of a specific HTTP redirect
	 */
}
?>