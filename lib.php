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
 * requirement: installed moodle_filter_botr and setup.
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

    const BOTR_THUMBS_PER_PAGE =20;
    /**
     * botr plugin constructor
     * @param int $repositoryid
     * @param object $context
     * @param array $options
     */
    public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
        // options['mimetypes'] =>'video';
        parent::__construct($repositoryid, $context, $options);
    }

    public function check_login() {
        return !empty($this->keyword);
    }

    /**
     * Return search results
     * @param string $search_text
     * @param int $page
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
//        $ret['help'] = get_string('helpURL','repository_botr');
//       $ret['manage'] = get_string('manageURL','repository_botr');
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
//        $ret['object'] = array('type'=>'txt/html','url'=>'http://lern-net.de/agb/botr/');
        if (!empty($ret['list'])) {
            $ret['pages'] = -1; // means we don't know exactly how many pages there are but we can always jump to the next page
        } else if ($ret['page'] > 1) {
            $ret['pages'] = $ret['page']; // no images available on this page, this is the last page
        } else {
            $ret['pages'] = 0; // no paging
        }
//		$ret['upload'] = array ('label'=>'uptitle','id'=>'uptitleid');
 		
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
        global $CFG;
        // get connected to the botr-PLatfrom via  botr_api
        // the credentials can be entered in the moodle_filter_botr plugin
        $botr_api = new BotrAPI($CFG->botr_key,$CFG->botr_secret);

		$list = array();
		$response = array();

        // prepare the parameter for the botr api search
        $params = array(
			'result_limit'=>intval($max),
			'result_offset'=>intval($start)-1,   //botr api counts from zero
			'tags_mode'=>'all',   // all the owner tags must be in the list
			'tags'=>repository::get_option('owner'), // with the owner tags, you could select by tags and give a owner a tag
			'order_by'=>$sort
		);

        // if a search term is specified add this to the search:
        if (!empty($keyword)) {
                $params['text'] = $keyword;
                $params['search'] = '*'; // here could be something more efficient See http://developer.longtailvideo.com/botr/system-api/methods/videos/list.html
        }
 		// get all videos that fits $params search
		$response = $botr_api->call("/videos/list",$params);
//        print_object($response);
        if ($response['status']== "error") {
        die(json_encode(array('e'=>get_string('botrApiProblem', 'repository_botr').$response['message'])));
        }
		for($i=0; $i<sizeof($response['videos']); $i++) {  // walk though all found videos
			$video = $response['videos'][$i];
			
			# calculate the duration in format
			$Sekundenzahl = round($video['duration']);
			$duration = sprintf("%02d:%02d:%02d",($Sekundenzahl/60/60)%24,($Sekundenzahl/60)%60,$Sekundenzahl%60);

            // check for player option and add a '-' if the player is given
            $player = repository::get_option('player');
            if (!empty($player)){ $player = "-".$player;}
//print_object($player);
			$list[] = array( // get all video data from the api into the file picker list
                'shorttitle'=>$video['title'],
                'thumbnail_title'=>$video['description']."\n ⌛   ".$duration."\n ▷  ".$video['views'],
                'title'=>$video['title']." \t \t \t \t \t \t \t \t \t .mp4", // hack to get it through the filepicker: we pretend to be a video mime type
                'thumbnail'=>"http://$CFG->botr_dnsmask/thumbs/".$video['key']."-120.jpg",  // thumbs are prepared at botr platform
                'thumbnail_width'=>110, // try to fit 5 in a row
                'thumbnail_height'=>"50px", //
                'size'=>$video['size'],
                'date'=>$video['date'],
				'tags'=>$video['tags'],
                'source'=>"[botr ".$video['key'].$player."]",
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
        return array('pluginname');
    }

/*	public static function type_config_form($mform) {
		parent::type_config_form($mform);
        $mform->addHelpButton('pluginname','pluginname','repository_botr'); // not necessary
    }/**/


	public static function get_instance_option_names() {
		return array('owner','player'); // From repository_filesystem
	}
	
	public static function instance_config_form($mform) {
        $mform->addElement('text', 'owner', get_string('owner', 'repository_botr'));
        $mform->addElement('text', 'player', get_string('player', 'repository_botr'), array('size' => '8'));

        // here we should test for real tags
//        $mform->addRule('owner', get_string('callback'), 'callback', an api call to botr_api, 'client');

        $mform->addRule('player', get_string('playerrule','repository_botr'), 'nopunctuation', null, 'client');
        $mform->addHelpButton('owner','owner','repository_botr');
        $mform->addHelpButton('player','player','repository_botr');
        $mform->addHelpButton('name','defaultname','repository_botr');
    }/**/
	
	
	public static function plugin_init() {
        //here we create a default repository instance. The last parameter is 1 in order to set the instance as readonly.
        /** @noinspection PhpDeprecationInspection */
        $id = repository::static_function('botr','create', 'botr', 0, get_system_context(),
                                    array('name' => get_string('defaultname','repository_botr'),
                                          'owner' => null,
                                          'player' => null,
                                    ),
              1);

        if (empty($id)) {
            return false;
        } else {
            return true;
        }

    }

}
