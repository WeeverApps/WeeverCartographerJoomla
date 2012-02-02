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
	
	function getK2Data($ordering = NULL) {

		$user = &JFactory::getUser();
		$aid = $user->get('aid');
		$db = &JFactory::getDBO();
		$params = &WeeverCartographerHelper::getParams('com_k2'); // ###
		$limitstart = JRequest::getInt('limitstart');
		$limit = JRequest::getInt('limit');
		$task = JRequest::getCmd('task');
		
		if($task=='search' && $params->get('googleSearch'))
			return array();

		$jnow = &JFactory::getDate();
		$now = $jnow->toMySQL();
		$nullDate = $db->getNullDate();

		if (JRequest::getWord('format') == 'feed')
				$limit = $params->get('feedLimit');

		$query = "SELECT i.*, c.name as categoryname,c.id as categoryid, c.alias as categoryalias, c.params as categoryparams";
		if ($ordering == 'best')
				$query .= ", (r.rating_sum/r.rating_count) AS rating";

		$query.=" FROM #__k2_items as i LEFT JOIN #__k2_categories AS c ON c.id = i.catid";

		if ($ordering == 'best')
				$query .= " LEFT JOIN #__k2_rating r ON r.itemID = i.id";

		//Changed the query for the tag case for better performance
		//if ($task == 'tag')
		//		$query .= " LEFT JOIN #__k2_tags_xref AS tags_xref ON tags_xref.itemID = i.id LEFT JOIN #__k2_tags AS tags ON tags.id = tags_xref.tagID";

		if($task=='user' && !$user->guest && $user->id==JRequest::getInt('id')){
			$query .= " WHERE ";
		}
		else {
			 $query .= " WHERE i.published = 1 AND ";
		}

		if(WeeverCartographerHelper::joomlaVerson != '1.5'){

			$query .= "i.access IN(".implode(',', $user->authorisedLevels()).")"
			." AND i.trash = 0"
			." AND c.published = 1"
			." AND c.access IN(".implode(',', $user->authorisedLevels()).")"
			." AND c.trash = 0";
								
			$mainframe = &JFactory::getApplication();
			$languageFilter = $mainframe->getLanguageFilter();
			if($languageFilter) {
				$languageTag = JFactory::getLanguage()->getTag();
				$query .= " AND c.language IN (".$db->quote($languageTag).",".$db->quote('*').") 
				AND i.language IN (".$db->quote($languageTag).",".$db->quote('*').")" ;
			}
		}
		else {
				$query .= "i.access <= {$aid}"
			." AND i.trash = 0"
			." AND c.published = 1"
			." AND c.access <= {$aid}"
			." AND c.trash = 0"; 				
		}


		if( !($task=='user' && !$user->guest && $user->id==JRequest::getInt('id') )) {
			$query .= " AND ( i.publish_up = ".$db->Quote($nullDate)." OR i.publish_up <= ".$db->Quote($now)." )";
			$query .= " AND ( i.publish_down = ".$db->Quote($nullDate)." OR i.publish_down >= ".$db->Quote($now)." )";
		}

		//Build query depending on task
		switch ($task) {

				case 'category':
				
						$id = JRequest::getInt('id');

						$category = &JTable::getInstance('K2Category', 'Table');
						$category->load($id);
						$cparams = new JParameter($category->params);

						if ($cparams->get('inheritFrom')) {

								$parent = &JTable::getInstance('K2Category', 'Table');
								$parent->load($cparams->get('inheritFrom'));
								$cparams = new JParameter($parent->params);
						}

						if ($cparams->get('catCatalogMode')) {
								$query .= " AND c.id={$id} ";
						} else {
								$categories = WeeverCartographerModelFeed::getK2CategoryTree($id);
								$sql = @implode(',', $categories);
								$query .= " AND c.id IN ({$sql})";
						}


						break;

				case 'user':
				
						$id = JRequest::getInt('id');
						$query .= " AND i.created_by={$id} AND i.created_by_alias=''";
						$categories = $params->get('userCategoriesFilter', NULL);
						
						if (is_array($categories))
						{
								$categories = array_filter($categories);
							JArrayHelper::toInteger($categories);
								$query .= " AND i.catid IN(".implode(',', $categories).")";
						}
						if (is_string($categories) && $categories>0)
						{
								$query .= " AND i.catid = {$categories}";
						}
						
						break;

				case 'search':
				
						$badchars = array('#', '>', '<', '\\');
						$search = trim(str_replace($badchars, '', JRequest::getString('searchword', null)));
						$sql = WeeverCartographerModelFeed::prepareK2Search($search);
						
						if (! empty($sql)) 
						{
								$query .= $sql;
						}
						else
						{
								$rows = array();
								return $rows;
						}
						
						break;

				case 'date':
					
						if ((JRequest::getInt('month')) && (JRequest::getInt('year'))) 
						{
						
								$month = JRequest::getInt('month');
								$year = JRequest::getInt('year');
								$query .= " AND MONTH(i.created) = {$month} AND YEAR(i.created)={$year} ";
								
								if (JRequest::getInt('day')) 
								{
										$day = JRequest::getInt('day');
										$query .= " AND DAY(i.created) = {$day}";
								}

								if (JRequest::getInt('catid')) 
								{
										$catid = JRequest::getInt('catid');
										$query .= " AND i.catid={$catid}";
								}

						}
						
						break;

				case 'tag':
				
						$tag = JRequest::getString('tag');
						jimport('joomla.filesystem.file');
						
						if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_joomfish'.DS.'joomfish.php') && $task == 'tag') 
						{

								$registry = &JFactory::getConfig();
								$lang = $registry->getValue("config.jflang");

								$sql = " SELECT reference_id FROM #__jf_content as jfc LEFT JOIN #__languages as jfl ON jfc.language_id = jfl.id";
								$sql .= " WHERE jfc.value = ".$db->Quote($tag);
								$sql .= " AND jfc.reference_table = 'k2_tags'";
								$sql .= " AND jfc.reference_field = 'name' AND jfc.published=1";

								$db->setQuery($sql, 0, 1);
								$result = $db->loadResult();

						}
						
						if (!isset($result) || $result < 1) 
						{
								$sql = "SELECT id FROM #__k2_tags WHERE name=".$db->Quote($tag);
								$db->setQuery($sql, 0, 1);
								$result = $db->loadResult();
						}
						
						$query.=" AND i.id IN (SELECT itemID FROM #__k2_tags_xref WHERE tagID=".(int)$result.")";

						/*if (isset($result) && $result > 0) {
								$query .= " AND (tags.id) = {$result}";
						} else {
								$query .= " AND (tags.name) = ".$db->Quote($tag);
						}*/

						$categories = $params->get('categoriesFilter', NULL);
						if (is_array($categories))
						{
							JArrayHelper::toInteger($categories);
								$query .= " AND i.catid IN(".implode(',', $categories).")";
						}
						if ( is_string($categories) )
								$query .= " AND i.catid = {$categories}";
								
						break;

				default:
				
						$searchIDs = $params->get('categories');

						if (is_array($searchIDs) && count($searchIDs)) {

								if ($params->get('catCatalogMode')) {
										$sql = @implode(',', $searchIDs);
										$query .= " AND i.catid IN ({$sql})";
								} else {
										
										$result = WeeverCartographerModelFeed::getK2CategoryTree($searchIDs);
										if (count($result)) {
												$sql = @implode(',', $result);
												$query .= " AND i.catid IN ({$sql})";
										}
								}
						}

						break;
		}

		//Set featured flag
		if ($task == 'category' || empty($task)) {
				if (JRequest::getInt('featured') == '0') {
						$query .= " AND i.featured != 1";
				} else if (JRequest::getInt('featured') == '2') {
						$query .= " AND i.featured = 1";
				}
		}

		//Remove duplicates
		//$query .= " GROUP BY i.id";

		//Set ordering
		switch ($ordering) {

				case 'date':
						$orderby = 'i.created ASC';
						break;

				case 'rdate':
						$orderby = 'i.created DESC';
						break;

				case 'alpha':
						$orderby = 'i.title';
						break;

				case 'ralpha':
						$orderby = 'i.title DESC';
						break;

				case 'order':
						if (JRequest::getInt('featured') == '2')
								$orderby = 'i.featured_ordering';
						else
								$orderby = 'c.ordering, i.ordering';
						break;

				case 'rorder':
						if (JRequest::getInt('featured') == '2')
								$orderby = 'i.featured_ordering DESC';
						else
								$orderby = 'c.ordering DESC, i.ordering DESC';
						break;
						
				case 'featured':
						$orderby = 'i.featured DESC, i.created DESC';
						break;

				case 'hits':
						$orderby = 'i.hits DESC';
						break;

				case 'rand':
						$orderby = 'RAND()';
						break;

				case 'best':
						$orderby = 'rating DESC';
						break;
						
				case 'publishUp':
						$orderby = 'i.publish_up DESC';
						break;

				case 'id':
				default:
						$orderby = 'i.id DESC';
						break;
		}

		$query .= " ORDER BY ".$orderby;
		$db->setQuery($query, $limitstart, $limit);
		$rows = $db->loadObjectList();
		return $rows;
	}
	
	
	public static function getK2CategoryTree($categories) {
	
		$mainframe = &JFactory::getApplication();
		
		$db = &JFactory::getDBO();
		$user = &JFactory::getUser();
		$aid = (int) $user->get('aid');
		
		if(!is_array($categories))
		{
			$categories = (array)$categories;
		}
		
		JArrayHelper::toInteger($categories);
		$categories = array_unique($categories);
		sort($categories);
		
		$key = implode('|', $categories);
		$clientID = $mainframe->getClientId();
		
		static $K2CategoryTreeInstances = array();
		
		if(isset($K2CategoryTreeInstances[$clientID]) && array_key_exists($key, $K2CategoryTreeInstances[$clientID]))
		{
			return $K2CategoryTreeInstances[$clientID][$key];
		}
		
		$array = $categories;
		
		while(count($array))
		{
		
			$query = "SELECT id
					FROM #__k2_categories 
					WHERE parent IN (".implode(',', $array).") 
					AND id NOT IN (".implode(',', $array).") ";
					
			if($mainframe->isSite())
			{
				$query.="
							AND published=1 
							AND trash=0";
							
				if(WeeverCartographerHelper::joomlaVersion != "1.5")
				{
					
					$query.= " AND access IN(".implode(',', $user->authorisedLevels()).")";
					
					if( $mainframe->getLanguageFilter() ) 
						$query.= " AND language IN(".$db->Quote(JFactory::getLanguage()->getTag()).", ".$db->Quote('*').")";
				}
				else
				{
					$query.=" AND access<={$aid}";
				}
			}
			
			$db->setQuery($query);
			$array = $db->loadResultArray();
			$categories = array_merge($categories, $array);
			
		}
		
		JArrayHelper::toInteger($categories);
		$categories = array_unique($categories);
		$K2CategoryTreeInstances[$clientID][$key] = $categories;
		
		return $categories;
		
	}
	
	public static function prepareK2Search($search) {

		jimport('joomla.filesystem.file');
		$db = &JFactory::getDBO();
		$language = &JFactory::getLanguage();
		$defaultLang = $language->getDefault();
		$currentLang = $language->getTag();
		$length = JString::strlen($search);
		$sql = '';
		
		if(JRequest::getVar('categories'))
		{
			$categories = @explode(',', JRequest::getVar('categories'));
			JArrayHelper::toInteger($categories);
			$sql.= " AND i.catid IN (".@implode(',', $categories).") ";
		}
		
		if(empty($search)) 
		{
			return $sql;
		}

		if (JString::substr($search, 0, 1) == '"' && JString::substr($search, $length - 1, 1) == '"') 
		{
			$type = 'exact';
		}
		else 
		{
			$type='any';
		}

		if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_joomfish'.DS.'joomfish.php') && $currentLang != $defaultLang) 
		{

			$conditions = array();
			$search_ignore = array();

			$ignoreFile = $language->getLanguagePath().DS.$currentLang.DS.$currentLang.'.ignore.php';

			if (JFile::exists($ignoreFile)) {
				include $ignoreFile;
			}

			if ($type=='exact') {

				$word = JString::substr($search, 1, $length - 2);

				if (JString::strlen($word) > 3 && !in_array($word, $search_ignore)) 
				{
					$word = $db->Quote('%'.$db->getEscaped($word, true).'%', false);

					$jfQuery = " SELECT reference_id FROM #__jf_content as jfc LEFT JOIN #__languages as jfl ON jfc.language_id = jfl.id";
					$jfQuery .= " WHERE jfc.reference_table = 'k2_items'";
					$jfQuery .= " AND jfl.code=".$db->Quote($currentLang);
					$jfQuery .= " AND jfc.published=1";
					$jfQuery .= " AND jfc.value LIKE ".$word;
					$jfQuery .= " AND (jfc.reference_field = 'title'
							OR jfc.reference_field = 'introtext'
							OR jfc.reference_field = 'fulltext'
							OR jfc.reference_field = 'image_caption'
							OR jfc.reference_field = 'image_credits'
							OR jfc.reference_field = 'video_caption'
							OR jfc.reference_field = 'video_credits'
							OR jfc.reference_field = 'extra_fields_search'
							OR jfc.reference_field = 'metadesc'
							OR jfc.reference_field = 'metakey'
					)";
					$db->setQuery($jfQuery);
					$result = $db->loadResultArray();
					$result = @array_unique($result);
					JArrayHelper::toInteger($result);
					
					if (count($result)) 
					{
						$conditions[] = "i.id IN(".implode(',', $result).")";
					}

				}

			} 
			else 
			{
			
				$search = explode(' ', JString::strtolower($search));
				foreach ($search as $searchword) 
				{

					if (JString::strlen($searchword) > 3 && !in_array($searchword, $search_ignore)) 
					{

						$word = $db->Quote('%'.$db->getEscaped($searchword, true).'%', false);

						$jfQuery = " SELECT reference_id FROM #__jf_content as jfc LEFT JOIN #__languages as jfl ON jfc.language_id = jfl.id";
						$jfQuery .= " WHERE jfc.reference_table = 'k2_items'";
						$jfQuery .= " AND jfl.code=".$db->Quote($currentLang);
						$jfQuery .= " AND jfc.published=1";
						$jfQuery .= " AND jfc.value LIKE ".$word;
						$jfQuery .= " AND (jfc.reference_field = 'title'
								OR jfc.reference_field = 'introtext'
								OR jfc.reference_field = 'fulltext'
								OR jfc.reference_field = 'image_caption'
								OR jfc.reference_field = 'image_credits'
								OR jfc.reference_field = 'video_caption'
								OR jfc.reference_field = 'video_credits'
								OR jfc.reference_field = 'extra_fields_search'
								OR jfc.reference_field = 'metadesc'
								OR jfc.reference_field = 'metakey'
						)";
					
						$db->setQuery($jfQuery);
						$result = $db->loadResultArray();
						$result = @array_unique($result);
						foreach ($result as $id) 
						{
							$allIDs[] = $id;
						}

						if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_joomfish'.DS.'joomfish.php') && $currentLang != $defaultLang) 
						{

							if (isset($allIDs) && count($allIDs)) 
							{
								JArrayHelper::toInteger($allIDs);
								$conditions[] = "i.id IN(".implode(',', $allIDs).")";
							}

						}


					}

				}


			}

			if (count($conditions)) {
				$sql .= " AND (".implode(" OR ", $conditions).")";
			}

		}
		else {

			$sql .= " AND MATCH(i.title, i.introtext, i.`fulltext`,i.image_caption,i.image_credits,i.video_caption,i.video_credits,i.extra_fields_search,i.metadesc,i.metakey) ";
			if ($type=='exact') {
				$text = JString::trim($search,'"');
				$text = $db->Quote('"'.$db->getEscaped($text, true).'"', false);
			}
			else {
				$search = JString::str_ireplace('*', '', $search);
				$words = explode(' ', $search);
				for($i=0; $i<count($words); $i++){
					$words[$i].= '*';
				}
				$search = implode(' ', $words);
				$text = $db->Quote($db->getEscaped($search, true), false);
			}
			$sql.= " AGAINST ({$text} IN BOOLEAN MODE)";

		}
		
		return $sql;
	}
	

}