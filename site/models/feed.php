<?php
/*	
*	Weever Apps Cartographer for Joomla
*	(c) 2012 Weever Apps Inc. <http://www.weeverapps.com/>
*
*	Author: 	Robert Gerald Porter <rob@weeverapps.com>
*	Version: 	0.1
*   License: 	GPL v3.0
*
*   This extension is free software: you can redistribute it and/or modify
*   it under the terms of the GNU General Public License as published by
*   the Free Software Foundation, either version 3 of the License, or
*   (at your option) any later version.
*
*   This extension is distributed in the hope that it will be useful,
*   but WITHOUT ANY WARRANTY; without even the implied warranty of
*   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*   GNU General Public License for more details <http://www.gnu.org/licenses/>.
*
*/

defined('_JEXEC') or die;

jimport('joomla.application.component.model');

class WeeverCartographerModelFeed extends JModel
{

	public 		$feedData;
	protected	$db;
	
	public function __construct() {
	
		parent::__construct();
		
		$this->db = &JFactory::getDBO();
		$this->selectMethod(JRequest::getVar('method'));
	
	}
	
	public function getFeedData() {
	
		return $this->feedData;
	
	}
	
	private function selectMethod($method = 'proximity') {
	
		switch($method) 
		{
		
		
			case "proximity":
			default:
			
				$this->feedData = $this->buildProximityFeed();
				
				break;
		
		}
	
	}
	
	private function convertToLatLong(&$obj) {
	
		$point = rtrim( ltrim( $obj->location, "(POINT" ), ")" );
		$point = explode(" ", $point);
		$obj->latitude = $point[0];
		$obj->longitude = $point[1];
	
	}
	
	private function buildProximityFeed() {
	
		$com 		= JRequest::getVar('component');
		$comIds 	= explode( ',', JRequest::getVar('id') );
		
		foreach($comIds as $k=>$v)
		{
		
			$id[$v] = 1;
		
		}
		
		if(!$com)
			$com = "com_content";
		
		$query = "SELECT component_id, AsText(location) AS location, address, label, kml, marker, 
					glength( linestringfromwkb( linestring( 
						GeomFromText('POINT(45.123 54.262)'), 
					location ) ) ) as 'distance' ".
				"FROM
					#__weever_maps ".
				"WHERE
					component = ".$this->db->quote($com)." ".
				"ORDER BY
					distance ASC ";

		$this->db->setQuery($query);
		$results = $this->db->loadObjectList();
		
		if( empty($results) )
			return false;
			
		foreach( (array) $results as $k=>$v ) 
		{
		
			if( !isset($id[$v->component_id]) )
				unset($results[$k]);
				
			$this->convertToLatLong($results[$k]);
		
		}
		
		if( empty($results) )
			return false;
		
		return $results;
	
	
	}
    
}