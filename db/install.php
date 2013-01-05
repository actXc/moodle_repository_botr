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
 * Installation file for the botr repository.
 *
 * @package    repository
 * @subpackage botr (BitsOnTheRun.com)
 * @copyright  2013 Guido Hornig based on the Youtube repository of  2009 Dongsheng Cai
 * @author     Guido Hornig hornig@actxc.de, Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later except the botr-API
 */

/**
 * Create a default instance of the youtube repository
 *
 * @return bool A status indicating success or failure
 */
function xmldb_repository_botr_install() {
    global $CFG;
    $result = true;
    require_once($CFG->dirroot.'/repository/lib.php');
    $botrplugin = new repository_type('botr', array(), true);
    if(!$id = $botrplugin->create(true)) {
        $result = false;
    }
    return $result;
}
