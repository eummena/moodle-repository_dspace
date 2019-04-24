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
 * This plugin is used to access the FG LOR based on DSpace
 *
 * @since Moodle 3.5
 * @package    repository_dspace
 * @copyright  2010 Enovation Solutions
 * @copyright  2018 eummena
 * @copyright  2018 tetco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot . '/repository/lib.php');
require_once(__DIR__ . '/dspace.php');

/**
 * Repository to access DSpace files
 *
 * @package    repository_dspace
 * @copyright  2010 Enovation Solutions
 * @copyright  2018 eummena
 * @copyright  2018 tetco
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class repository_dspace extends repository {

    public $response;

    /** @var array mimetype filter */
    private $mimetypes = array();

    /**
     * Constructor
     *
     * @param int $repositoryid repository instance id
     * @param int|stdClass $context a context id or context object
     * @param array $options repository options
     */
    // public function __construct($repositoryid, $context = SYSCONTEXTID, $options = array()) {
    //     global $USER, $DB;
    //     parent::__construct($repositoryid, $context, $options);
    //     $this->dspace_url = $this->get_option('dspace_url');
    //     // $this->username = $this->get_option('dspace_username');
    //     // $this->password = $this->get_option('dspace_password');
    // }

    /**
     * Return names of the general options.
     * By default: no general option name
     *
     * @return array
     */
    public static function get_type_option_names() {
        $option_names = array('dspace_url', 'pluginname');
        // $option_names = array('dspace_url', 'pluginname', 'dspace_password', 'dspace_username');

        return $option_names;
    }

    /**
     * Add Plugin settings input to Moodle form.
     *
     * @inheritDocs
     */
    public static function type_config_form($mform, $classname = 'repository') {
        parent::type_config_form($mform);
        $dspace_url = get_config('dspace', 'dspace_url') || '';

        // $dspace_username = get_config('dspace', 'dspace_username');
        // $dspace_password = get_config('dspace', 'dspace_password');

        $strrequired = get_string('required');

        $mform->addElement('text', 'dspace_url', get_string('dspaceurl', 'repository_dspace'),
                array('value' => $dspace_url, 'size' => '50'));
        $mform->addRule('dspace_url', $strrequired, 'required', null, 'client');
        $str_getkey = get_string('dspaceurlinfo', 'repository_dspace');
        $mform->addElement('static', null, '', $str_getkey);

        // $mform->addElement('text', 'dspace_username', get_string('dspaceusername', 'repository_dspace'), array('value' => $dspace_username, 'size' => '50'));
        // $mform->addElement('password', 'dspace_password', get_string('dspacepassword', 'repository_dspace'), array('value' => $dspace_password, 'size' => '50'));
        // $mform->addRule('dspace_username', $strrequired, 'required', null, 'client');
        // $mform->addRule('dspace_password', $strrequired, 'required', null, 'client');
    }

    public function check_login() {
        global $SESSION;
        $this->keyword = optional_param('dspace_keyword', '', PARAM_RAW);
        if (empty($this->keyword)) {
            $this->keyword = optional_param('s', '', PARAM_RAW);
        }
        $sess_keyword = 'dspace_' . $this->id . '_keyword';
        if (empty($this->keyword) && optional_param('page', '', PARAM_RAW)) {
            // This is the request of another page for the last search, retrieve the cached keyword.
            if (isset($SESSION->{$sess_keyword})) {
                $this->keyword = $SESSION->{$sess_keyword};
            }
        } else if (!empty($this->keyword)) {
            // Save the search keyword in the session so we can retrieve it later.
            $SESSION->{$sess_keyword} = $this->keyword;
        }
        return !empty($this->keyword);

        return true;
    }

    /**
     * Given a path, and perhaps a search, get a list of resources.
     *
     * See details on {@link http://docs.moodle.org/dev/Repository_plugins}
     *
     * @param string $path this parameter can be a category name
     * @param string $page the page number of file list
     * @return array the list of resources, including meta infomation, containing the following keys
     *           manage, url to manage url
     *           client_id
     *           login, login form
     *           repo_id, active repository id
     *           login_btn_action, the login button action
     *           login_btn_label, the login button label
     *           total, number of results
     *           perpage, items per page
     *           page
     *           pages, total pages
     *           issearchresult, is it a search result?
     *           list, file list
     *           path, current path and parent path
     */
    public function get_listing($path = '', $page = '1') {
        $client = new dspace;
        $list = array();
        $list['page'] = (int) $page;
        if ($list['page'] < 1) {
            $list['page'] = 1;
        }
        $list['list'] = $client->search_contents($this->keyword, $list['page'] - 1, array('iiurlwidth' => 2,
                'iiurlheight' => 2));

        $list['nologin'] = true;
        $list['norefresh'] = false;
        $list['nosearch'] = true;
        /* if (!empty($list['list'])) {
          $list['pages'] = -1; // means we don't know exactly how many pages there are but we can always jump to the next page
          } else if ($list['page'] > 1) {
          $list['pages'] = $list['page']; // no images available on this page, this is the last page
          } else {
          $list['pages'] = 0; // no paging
          }
         */

        return $list;
    }

    public function logout() {
        return false;
    }

    public function get_name() {
        return get_string('pluginname', 'repository_dspace');
    }

    //    public function supported_filetypes() {
    //        return '*';
    //    }

    /**
     * Set options.
     *
     * @param   array $options
     * @return  mixed
     */
    public function set_option($options = array()) {
        if (!empty($options['dspace_url'])) {
            set_config('dspace_url', trim($options['dspace_url']), 'dspace');
        }
        // if (!empty($options['dspace_username'])) {
        //     set_config('dspace_username', trim($options['dspace_username']), 'dspace');
        // }
        // if (!empty($options['dspace_password'])) {
        //     set_config('dspace_password', trim($options['dspace_password']), 'dspace');
        // }

        unset($options['dspace_url']);
        // unset($options['dspace_username']);
        // unset($options['dspace_password']);
        $ret = parent::set_option($options);
        return $ret;
    }

    /**
     *
     * @param string $config
     * @return mixed
     */
    public function get_option($config = '') {
        if (preg_match('/^dspace_/', $config)) {
            return trim(get_config('dspace', $config));
        }

        $options = parent::get_option($config);
        return $options;
    }

    public function restCall($url) {
        $sac_curl = curl_init();

        curl_setopt($sac_curl, CURLOPT_URL, $url);
        curl_setopt($sac_curl, CURLOPT_VERBOSE, true);
        curl_setopt($sac_curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($sac_curl, CURLOPT_HEADER, false);
        $resp = curl_exec($sac_curl);
        @curl_close($sac_curl);
        //  return $resp;
    }

    // My Code
    // if check_login returns false,
    // this function will be called to print a login form.
    public function print_login() {
        $keyword = new stdClass();
        $keyword->label = get_string('keyword', 'repository_dspace') . ': ';
        $keyword->id = 'input_text_keyword';
        $keyword->type = 'text';
        $keyword->name = 'dspace_keyword';
        $keyword->value = '';
        if ($this->options['ajax']) {
            $form = array();
            $form['login'] = array($keyword);
            $form['nologin'] = true;
            $form['norefresh'] = true;
            $form['nosearch'] = true;
            $form['allowcaching'] = false; // indicates that login form can NOT
            // be cached in filepicker.js (maxwidth and maxheight are dynamic)
            return $form;
        } else {
            echo <<<EOD
    <table>
        <tr>
            <td>{$keyword->label}</td><td><input name="{$keyword->name}" type="text" /></td>
        </tr>
    </table>
            <input type="submit" />
EOD;
        }
    }

    //search
    // if this plugin support global search, if this function return
    // true, search function will be called when global searching working
    public function global_search() {
        return false;
    }

    public function search($search_text, $page = 0) {
        $client = new dspace;
        $search_result = array();
        $search_result['list'] = $client->search_contents($search_text);
        return $search_result;
    }

    // FILE_INTERNAL - the file is uploaded/downloaded and stored directly within the Moodle file system.
    // FILE_EXTERNAL - the file stays in the external repository and is accessed from there directly.
    // FILE_REFERENCE - the file stays in the external repository but may be cached locally. In that case it should be synchronised automatically, as required, with any changes to the external original.
    // FILE_CONTROLLED_LINK - the file remains in the external repository. By "uploading" it, ownership of the file (in the remote system) is changed so that the system account in the external repository becomes the new owner of the file

    function supported_returntypes() {
        return FILE_INTERNAL | FILE_EXTERNAL;
    }

    /**
     * Return the source information
     *
     * @param stdClass $url
     * @return string|null
     */
    public function get_file_source_info($url) {
        return $url;
    }

    /**
     * Is this repository accessing private data?
     *
     * @return bool
     */
    public function contains_private_data() {
        return false;
    }

    /**
     * Overriding get_file function to customize exception message
     *
     * By Shareef 11-12-2018
     */

    public function get_file($url, $filename = '') {
        global $CFG;

        $path = $this->prepare_file($filename);
        $c = new curl;

        $result = $c->download_one($url, null, array('filepath' => $path, 'timeout' => $CFG->repositorygetfiletimeout));
        if ($result !== true) {
            // We changed the default behaviour of moodle_exception function to display our custom message
            throw new moodle_exception(get_string('errorwhiledownload', 'repository_dspace'), 'Error 1');
        }
        return array('path' => $path, 'url' => $url);
    }

}
