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
 * DSPACE class
 * class for communication with DSPACE Commons API
 *
 * @author Tasos <tk@eummena.org>, Azmat <azmat@eummena.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
class dspace {

    private $_conn = null;
    private $_param = array();

    public function __construct($url = '') {
        if (empty($url)) {
            $this->api = get_config('dspace', 'dspace_url');
        } else {
            $this->api = $url;
        }
        $this->_param['format'] = 'php';
        $this->_param['redirects'] = true;
        $this->_conn = new curl(array('cache' => true, 'debug' => false));
    }

    public function login($user, $pass) {
        /* $this->_param['action']   = 'login';
          $this->_param['lgname']   = $user;
          $this->_param['lgpassword'] = $pass;
          $content = $this->_conn->post($this->api, $this->_param);
          $result = unserialize($content);
          if (!empty($result['result']['sessionid'])) {
          $this->userid = $result['result']['lguserid'];
          $this->username = $result['result']['lgusername'];
          $this->token = $result['result']['lgtoken'];
          return true;
          } else {
          return false;
          } */
        return true;
    }

    public function logout() {
        /* $this->_param['action']   = 'logout';
          $content = $this->_conn->post($this->api, $this->_param);
          return;
         */
    }

    public function get_image_url($titles) {
        $image_urls = array();
        $this->_param['action'] = 'query';
        if (is_array($titles)) {
            foreach ($titles as $title) {
                $this->_param['titles'] .= ('|' . urldecode($title));
            }
        } else {
            $this->_param['titles'] = urldecode($titles);
        }
        $this->_param['prop'] = 'imageinfo';
        $this->_param['iiprop'] = 'url';
        $content = $this->_conn->post($this->api, $this->_param);
        $result = unserialize($content);
        foreach ($result['query']['pages'] as $page) {
            if (!empty($page['imageinfo'][0]['url'])) {
                $image_urls[] = $page['imageinfo'][0]['url'];
            }
        }
        return $image_urls;
    }

    public function get_images_by_page($title) {
        $image_urls = array();
        $this->_param['action'] = 'query';
        $this->_param['generator'] = 'images';
        $this->_param['titles'] = urldecode($title);
        $this->_param['prop'] = 'images|info|imageinfo';
        $this->_param['iiprop'] = 'url';
        $content = $this->_conn->post($this->api, $this->_param);
        $result = unserialize($content);
        if (!empty($result['query']['pages'])) {
            foreach ($result['query']['pages'] as $page) {
                $image_urls[$page['title']] = $page['imageinfo'][0]['url'];
            }
        }
        return $image_urls;
    }

    /**
     * Generate thumbnail URL from image URL.
     *
     * @param string $image_url
     * @param int $orig_width
     * @param int $orig_height
     * @param int $thumb_width
     * @param bool $force When true, forces the generation of a thumb URL.
     * @global object OUTPUT
     * @return string
     */
    public function get_thumb_url($image_url, $orig_width, $orig_height, $thumb_width = 75, $force = false) {
        global $OUTPUT;

        if (!$force && $orig_width <= $thumb_width && $orig_height <= $thumb_width) {
            return $image_url;
        } else {
            $thumb_url = '';
            $commons_main_dir = $this->api . '/jspui/bitstream/';
            if ($image_url) {
                $short_path = str_replace($commons_main_dir, '', $image_url);
                $extension = strtolower(pathinfo($short_path, PATHINFO_EXTENSION));
                if (strcmp($extension, 'gif') == 0) {  //no thumb for gifs
                    return $OUTPUT->image_url(file_extension_icon('.gif', $thumb_width))->out(false);
                }
                $dir_parts = explode('/', $short_path);
                $file_name = end($dir_parts);
                if ($orig_height > $orig_width) {
                    $thumb_width = round($thumb_width * $orig_width / $orig_height);
                }
                $thumb_url = $commons_main_dir . 'thumb/' . implode('/', $dir_parts) . '/' . $thumb_width . 'px-' . $file_name;
                if (strcmp($extension, 'svg') == 0) {  //png thumb for svg-s
                    $thumb_url .= '.png';
                }
            }
            return $thumb_url;
        }
    }

    /**
     * Search for content and return record array.
     *
     * @param string $keyword
     * @param int $page
     * @param array $params additional query params
     * @return array
     */
    public function search_contents($keyword, $page = 0, $params = array()) {
        global $OUTPUT;
        $files_array = array();
        $this->_param['action'] = 'query';
        $this->_param['generator'] = 'search';
        // $this->_param['gsrsearch'] = $keyword;
        $this->_param += $params;

        if ($keyword != '') { // with search.
            $this->_param['query_field[]'] = "dc.name";
            $this->_param['query_op[]'] = "contains";
            $this->_param['query_val[]'] = $keyword;
            $urlstring = $this->api . '/rest/filtered-items/';
            $content = $this->_conn->get($urlstring, $this->_param);
        } else { // Without search.
            $content = $this->_conn->get($this->api . '/rest/items', $this->_param);
        }

        $content = json_decode($content);
        $condition = ($keyword == '') ? $content : $content->items;
        foreach ($condition as $cn) {

            $locate = $this->api . $cn->link . '?expand=bitstreams';
            $content1 = $this->_conn->get($locate, '');
            $content1 = json_decode($content1);

            // Useful variables.		
            $handle = $cn->handle;
            $itemid = $cn->uuid;
            $lastmodified = $cn->lastModified;
            $itemlink = $cn->link;
            $type = $content1->bitstreams[1]->type;
            $size = $content1->bitstreams[1]->sizeBytes;
            $title = $content1->bitstreams[1]->name;
            $sequence = $content1->bitstreams[1]->sequenceId;
            $fileurl = $this->api . '/jspui/bitstream/' . $handle . '/' . $sequence . '/' . $title;
            $fileurl = str_replace(' ', '%20', $fileurl);

            if ($content1->bitstreams[0]->sequenceId) {
                $thumbnail = $this->api . '/jspui/bitstream/' . $handle . '/' . $content1->bitstreams[0]->sequenceId . '/' .
                        $content1->bitstreams[0]->name;
            }
            // Prevent .txt files from being pushed into $files_array.

            if (!preg_match("/(\.txt)$/im", $title)) {
                $files_array[] = array(
                        'title' => $title,
                        'url' => $fileurl,
                        'type' => $type,
                        'datemodified' => strtotime($lastmodified),
                        'license' => 'cc-sa',
                        'author' => 'Eummena Content Team',
                        'source' => $fileurl,
                        'size' => $size,
                        'thumbnail' => $thumbnail,
                        'realicon' => $thumbnail,
                );
            }

        }

        return $files_array;
    }

}
