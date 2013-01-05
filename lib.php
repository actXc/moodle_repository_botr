<?php

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This plugin is used to access botr videos
 *
 * @since 2.0
 * @package    repository
 * @subpackage botr (BitsOnTheRun.com)
 * @copyright  2013 Guido Hornig based on the botr repository of  2009 Dongsheng Cai
 * @author     Guido Hornig hornig@actxc.de, Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later except the botr-API
 */

require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->dirroot . '/repository/botr/botrapi/api.php');


/**
 * repository_botr class
 *
 * @package    repository
 * @subpackage botr (BitsOnTheRun.com)
 * @copyright  2013 Guido Hornig based on the botr repository of  2009 Dongsheng Cai
 * @author     Guido Hornig hornig@actxc.de, Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later except the botr-API
 */


class repository_botr extends repository {
    /** @var int maximum number of thumbs per page */

    const BOTR_THUMBS_PER_PAGE = 8;
    /**
     * botr plugin constructor
     * @param int $repositoryid
     * @param object $context
     * @param array $options
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        // options['mimetypes'] =>'*';
		parent::__construct($repositoryid, $context, $options);
    }

    public function check_login() {
        return !empty($this->keyword);
    }

    /**
     * Return search results
     * @param string $search_text
     * @return array
     */
    public function search($search_text, $page = 0) {
        global $SESSION;
		
        $sort = optional_param('botr_sort', '', PARAM_TEXT);
 
        $sess_keyword = 'botr_'.$this->id.'_keyword';
        $sess_sort = 'botr_'.$this->id.'_sort';

        // This is the request of another page for the last search, retrieve the cached keyword and sort
        if ($page && !$search_text && isset($SESSION->{$sess_keyword})) {
            $search_text = $SESSION->{$sess_keyword};
        }
        if ($page && !$sort && isset($SESSION->{$sess_sort})) {
            $sort = $SESSION->{$sess_sort};
        }
        if (!$sort) {
            $sort = 'title'; // default
        }

        // Save this search in session
        $SESSION->{$sess_keyword} = $search_text;
        $SESSION->{$sess_sort} = $sort;

        $this->keyword = $search_text;
        $ret  = array();
        $ret['nologin'] = true;
        $ret['page'] = (int)$page;
        if ($ret['page'] < 1) {
            $ret['page'] = 1;
        }
        $start = ($ret['page'] - 1) * self::BOTR_THUMBS_PER_PAGE + 1;
        $max = self::BOTR_THUMBS_PER_PAGE;
		
        $ret['list'] = $this->_get_collection($search_text, $start, $max, $sort);
 
        $ret['norefresh'] = false;
        $ret['nosearch'] = false;
		$ret['dynloading'] = true;
        $ret['pages'] = -1;
 		
        return $ret;
    }

    /**
     * Private method to get botr search results
     * @param string $keyword
     * @param int $start
     * @param int $max max results
     * @param string $sort
     * @return array
     */
    private function _get_collection($keyword, $start, $max, $sort) {
//		$botr_api = new BotrAPI($api_key,$api_secret);
    	$botr_api = new BotrAPI('xxxxxxxx', 'xxxxxxxxxxxxxxxxxxxxxxxxx');
		
 
		$list = array();
		$response = array();
		$params = array(
			'result_limit'=>intval($max),
			'result_offset'=>intval($start)-1,
/*			'tags_mode'=>'all',
			'tags'=>$keyword,
*/			'text'=>$keyword,
			'order_by'=>$sort,
			'search'=>'*'
		);
 		
		$response = $botr_api->call("/videos/list",$params);

		if ($response['status'] == "error") { die(print_r("BOTR API Fehler".$response)); }
        
		for($i=0; $i<sizeof($response['videos']); $i++) {
			$video = $response['videos'][$i];
			
			# calculate the duration in format
			$Sekundenzahl = round($video['duration']);
			$duration = sprintf("%02d:%02d:%02d",($Sekundenzahl/60/60)%24,($Sekundenzahl/60)%60,$Sekundenzahl%60);
			
			$list[] = array(
                'shorttitle'=>$video['title'],
                'thumbnail_title'=>$video['description']." ".$duration." Views:".$video['views'],
                'title'=>$video['title']."              .mp4", 
                'thumbnail'=>"http://cdn.actxc.de/thumbs/".$video['key']."-120.jpg",
                'thumbnail_width'=>120,
                'thumbnail_height'=>90,
                'size'=>$video['size'],
                'date'=>$video['date'],
				'tags'=>$video['tags'],
                'source'=>"[cdn ".$video['key']."]",
				'url' => $video['key'],
				'author'=>$video['author']
            );
        }
		

        return $list;
    }

    /**
     * botr plugin doesn't support global search
     */
    public function global_search() {
        return false;
    }

    public function get_listing($path='', $page = '') {
        return array();
    }

    /**
     * Generate search form
     */
    public function print_login($ajax = true) {

        $ret = array();
        $search = new stdClass();
        $search->type = 'text';
        $search->id   = 'botr_search';
        $search->name = 's';
        $search->label = get_string('search', 'repository_botr').': ';
        $sort = new stdClass();
        $sort->type = 'select';
        $sort->options = array(
            (object)array(
                'value' => 'title',
                'label' => get_string('sorttitle', 'repository_botr')
            ),
            (object)array(
                'value' => 'date',
                'label' => get_string('sortdate', 'repository_botr')
            ),
            (object)array(
                'value' => 'author',
                'label' => get_string('sortauthor', 'repository_botr')
            ),
            (object)array(
                'value' => 'views',
                'label' => get_string('sortviews', 'repository_botr')
            )
        );

        $sort->id = 'botr_sort';
        $sort->name = 'botr_sort';
        $sort->label = get_string('sortby', 'repository_botr').': ';
        $ret['login'] = array($search, $sort);
        $ret['login_btn_label'] = get_string('search');
        $ret['login_btn_action'] = 'search';
        $ret['allowcaching'] = true; // indicates that login form can be cached in filepicker.js

        return $ret;
    }
	
	    /**
     * Prepare file reference information
     *
     * @param string $source
     * @return string file referece
     */
    public function get_file_reference($source) {
        return $source;
    }

	
	/**
	* prepare the signed link to the video
	*
	**/
//	get_link($url)

    /**
     * file types supported by botr plugin
     * @return array
     */
    public function supported_filetypes() {
        return array('video');
    }

    /**
     * botr plugin only return external links
     * @return int
     */
 
	public function supported_returntypes() {
        return FILE_EXTERNAL;
    }
	
	public static function get_type_option_names() {
		return array_merge(parent::get_type_option_names(), array(
			   'API_Key',
			   'API_Secret'
			   )
			);
	}

	public static function type_config_form($mform) {
		parent::type_config_form($mform);
	 
		$API_Key = get_config('repository_botr', 'API_Key');
		$mform->addElement('text', 'API_Key', get_string('API-Key', 'repository_botr'), array('size' => '40'));
		$mform->setDefault('API_Key', $API_Key);
		
		$API_Secret = get_config('repository_botr', 'API_Secret');
		$mform->addElement('text', 'API_Secret', get_string('API-Secret', 'repository_botr'), array('size' => '40'));
		$mform->setDefault('API_Secret', $API_Secret);
		
		
	}/**/
	
	public static function type_form_validation($mform, $data, $errors) {
		if (!isset($data['API-Key'])) {
			$errors['API-Key'] = get_string('invalidAPI-Key', 'repository_botr');
		}
		if (!isset($data['API-Secret'])) {
			$errors['API-Secret'] = get_string('invalidAPI-Secret', 'repository_botr');
		}
		return $errors;
	}
	
	public static function get_instance_option_names() {
		return array('Owner'); // From repository_filesystem
	}
	
	public static function instance_config_form(&$mform) {
        $mform->addElement('text', 'Owner', get_string('Owner', 'repository_botr_public'));
        $mform->addRule('Owner', get_string('required'), 'required', null, 'client');
    }/**/
	
	
	public static function plugin_init() {
        //here we create a default repository instance. The last parameter is 1 in order to set the instance as readonly.
        repository_static_function('botr','create', 'botr', 0, get_system_context(), 
                                    array('name' => 'default instance','Owner' => null),1);
     }
	
}
