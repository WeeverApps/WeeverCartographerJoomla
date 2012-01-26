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

	public 	$feedData;
	private	$db;
	
	public function __construct()
	{
	
		parent::__construct();
		
		$this->db = &JFactory::getDBO();
		$this->selectMethod(JRequest::getVar('method'));
	
	}
	
	private function selectMethod($method = 'proximity')
	{
	
		switch($method) 
		{
		
			case "geotag":
			
				$this->buildGeotagFeed();
				
				break;
		
			case "proximity":
			default:
			
				$this->buildProximityFeed();
				
				break;
		
		}
	
	}
	
	
	private function buildGeotagFeed() {
	
	
	}
	
	private function buildProximityFeed() {
	
		$component = JRequest::getVar('component');
		$component = JRequest::getVar('component_id');
	
		//$this->db = 
	
		/*
		
		// search code for distance...
		
		SELECT *,  glength( linestringfromwkb( linestring( GeomFromText('POINT(45.123 54.262)'), location ) ) ) as 'distance'
		FROM
		jos_weever_maps
		ORDER BY
		distance
		
		*/
	
	
	}
    
}