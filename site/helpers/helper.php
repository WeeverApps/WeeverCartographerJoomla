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


class WeeverCartographerHelper {

	
	public static function wrapJsonP($string, $callback = 'callback') 
	{
	
		return $callback."(".$string.")";	
	
	}
	
	public static function joomlaVersion() 
	{
	
		$version = new JVersion;
		$joomla = $version->getShortVersion();
		
		return $joomla;
	
	}
	
	function getParams($option){
		
		if(self::joomlaVersion != "1.5")
		{
		
			$app = JFactory::getApplication('site');
			$params =  &$app->getParams($option);
			
		}
		else 
		{
			$params = &JComponentHelper::getParams($option);
		}
		
		return $params;
		
	}


}

class WeeverCartographerJsonResult {

	public	$results;

}