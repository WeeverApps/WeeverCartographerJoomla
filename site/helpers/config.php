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


final class comWeeverCartographerConst
{

	const VERSION		= "0.1";
	const RELEASE_TYPE	= "stable";
	const RELEASE_NAME	= "A-something";
	const NAME			= "Weever Apps Cartographer for Joomla!";
	const COPYRIGHT_YEAR= "(c) 2012";
	const COPYRIGHT		= "Weever Apps Inc.";
	const COPYRIGHT_URL = "http://www.weeverapps.com/";
	const LICENSE		= "GPL v3.0";
	const LICENSE_URL	= "http://www.gnu.org/licenses/gpl-3.0.html";
	const RELEASE_DATE	= "January 24, 2012";
	
}


class comWeeverHelperJS
{

	public static function loadConfJS($staging = null)
	{
	
		
		$document = &JFactory::getDocument();
		
		if($staging)
			$server = comWeeverConst::LIVE_STAGE;
		else 
			$server = comWeeverConst::LIVE_SERVER;
		
		$document->addCustomTag (
			'<script type="text/javascript">
			
			if (typeof(Joomla) === "undefined") {
				var Joomla = {};
			}
			
			Joomla.comWeeverConst = {
				VERSION: "'.comWeeverConst::VERSION.'",
				RELEASE_TYPE: "'.comWeeverConst::RELEASE_TYPE.'",
				RELEASE_NAME: "'.comWeeverConst::RELEASE_NAME.'",
				NAME: "'.comWeeverConst::NAME.'",
				COPYRIGHT_YEAR: "'.comWeeverConst::COPYRIGHT_YEAR.'",
				COPYRIGHT: "'.comWeeverConst::COPYRIGHT.'",
				COPYRIGHT_URL: "'.comWeeverConst::COPYRIGHT_URL.'",
				LICENSE: "'.comWeeverConst::LICENSE.'",
				LICENSE_URL: "'.comWeeverConst::LICENSE_URL.'",
				RELEASE_DATE: "'.comWeeverConst::RELEASE_DATE.'",
				server: "'.$server.'"
			};
			
			</script>');
		
	
	}

}