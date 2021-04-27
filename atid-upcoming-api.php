<?php

/**
 * Plugin Name: Atid Upcoming API
 * Plugin URI: https://rizoma.dev/
 * Description: This plugin is developed for the use of Atid School only.
 * Version: 0.9.7-alpha
 * Author: Emilio Rosas Parra
 * Author URI: https://emilioparra.dev
 */
require 'vendor/autoload.php';

// Generic function to get a category
// The query is ordered by id and DESC
function get_last_cat($index)
{
    $args = array(
        'orderby' => 'id',
        'order' => 'DESC',
        "hide_empty" => 0,
    );
    $cat = array();

    $last = get_categories($args)[$index];

    $cat['category']['_id'] = $last->term_id;
    $cat['category']['name'] = $last->name;
    $cat['category']['slug'] = $last->slug;
    $cat['category']['status'] = $last->description;

    return $cat;
}

function get_cat_by($_id)
{
    $raw = get_category($_id);

    $cat['category']['_id'] = $raw->term_id;
    $cat['category']['name'] = $raw->name;
    $cat['category']['slug'] = $raw->slug;
    $cat['category']['status'] = $raw->description;

    return $cat;
}

// Generic function to get all the available tags
function get_all_tags()
{
    $all = get_tags(array('get' => 'all'));
    $i = 0;
    $tags = array();
    foreach ($all as $tag) {
        $tags[$i]['_id'] = $tag->term_id;
        $tags[$i]['name'] = $tag->name;
        $tags[$i]['slug'] = $tag->slug;
        $i++;
    }

    return $tags;
}

// Generic function to get tags by slug
function get_tag_name($slug)
{
    $all = get_tags(array('get' => 'all'));
    $i = 0;
    $res = array();
    foreach ($all as $key => $value) {
        if ($value->slug == $slug) {
            return $value->name;
        }
    }
    return 'Undefined';
}


function build_html($html_content)
{ 
    $padding = 'padding: 0 20px 20px 10px;';
    $images = array();
    $videos = array();
    preg_match('/(<img .*?>)/', $html_content, $images);
    preg_match('/(<video .*?>)/', $html_content, $videos);
    if (count($images) > 0) 
    {
    	$padding = 'padding: 0;';
    }
    if (count($videos) > 0) 
    {
    	$padding = 'padding: 0;';
    }
    $body = "width:100%, height:100%,font-family: Open+Sans;";
    $head = "text-align:center; font-family: 'Raleway', sans-serif; margin-bottom: 0;";
    $text = "font-size: 24px; text-align:justify; font-weight:300; width: 100%; margin: 0;";
    $html_style = "<STYLE>html,body{".$body."} h1,h2,h3,h4,h5,h6{".$head."} p,span{".$text."} img, video{width:100%; height: auto;} div{background-color: white; border-radius:3px;". $padding."}</STYLE>";
    $html_start = "<HTML><HEAD><meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, shrink-to-fit=no\">" . $html_style . "</HEAD><BODY><div>";
    $html_end = "</div></BODY></HTML>";
    $html_string = $html_start . '<span>' . $html_content . '</span>' . $html_end;	
    return $html_string;
}

// Generic function to get posts by a category_id and a tag_id
function get_posts_by($cat, $tag)
{
    $args = array(
        'post_type' => 'post',
        'cat' => $cat,
        'tag' => $tag,
        'posts_per_page' => -1,
        'orderby' => 'modified',
        'order' => 'DESC',
    );

    $post_query = new WP_Query($args);

    if ($post_query->have_posts()) {
        $posts = $post_query->posts;
        $i = 0;
        foreach ($posts as $post) {
            $data[$i]['_id'] = $post->ID;
            $data[$i]['title'] = $post->post_title;
            $content = preg_replace('(\r\n|\r|\n)', '<br>',$post->post_content);
            //$content = preg_replace('/(&nbsp;)*(\r\n|\r|\n)*/', '',$post->post_content);
            $data[$i]['content'] = build_html($content);
            $data[$i]['slug'] = $post->post_name;
            $data[$i]['status'] = $post->post_status;
            $data[$i]['date'] = date('M d, Y', strtotime($post->post_date));
            $data[$i]['modified'] = date('M d, Y h:m:s', strtotime($post->post_modified));
            $data[$i]['height'] = 0;

            $i++;
        }

        return $data;
    } else {
        return null;
    }
}


function get_available_tags($isAll)
{
    if ($isAll) {
        return array(
            'general',
            'preschool',
            'elementary',
            'lower',
            'middle',
            'upper',
            'nursery',
            'k1',
            'k2',
            'k3',
            '1st',
            '2nd',
            '3rd',
            '4th',
            '5th',
            '6th',
            '7th' ,
            '8th',
            '9th',
            '10th',
            '11th',
            '12th',
            'jewish',
            'cultural-recommendations'
        );
    } else {
        return array(
            'general'
        );
    }
}

function get_tag_color($slug)
{
    $color = '';
    switch ($slug) {
        case 'general':
        case 'jewish':
        case 'cultural-recommendations':
            $color = '00847B';
            break;
        case 'preschool':
        case 'nursery':
        case 'k1':
        case 'k2':
        case 'k3':
            $color = 'EC008C';
            break;
        case 'elementary':
        case '1st':
        case '2nd':
        case '3rd':
        case '4th':
        case '5th':
        case '6th':
            $color = '149447';
            break;
        case 'lower':
        case '7th':
        case '8th':
        case '9th':
        case '10th':
            $color = 'F7941E';
            break;
        case 'upper':
        case '11th':
        case '12th':
            $color = '2AA9E0';
            break;
        default:
            $color = '00847B';
            break;
    }
    return $color;
}

function atid_tags()
{
    return wp_send_json(array('data' => get_all_tags()));
}


function atid_cat(WP_REST_Request $request)
{
    $params = $request->get_query_params();
    $paged = isset($params['paged']) ? absint((int) $params['paged']) : 0;
    $data = get_last_cat($paged);

    return wp_send_json(array('data' => $data));
}

//    This function calls Atid's Alpha API
function get_students($token)
{
    $client = new \GuzzleHttp\Client();
    $response = $client->get(
        'https://atid.edu.mx/mobileAPI/api/v1/mobile/upcoming',
        [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $token,
            ],
        ]
    );
    $body = $response->getBody();

    return json_decode($body);
}

//    This function calls Atid's Alpha API
function get_images($token)
{
    $client = new \GuzzleHttp\Client();
    $response = $client->get(
        'https://atid.edu.mx/mobileAPI/api/v1/mobile/icon',
        [
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
                'Authorization' => $token,
            ],
        ]
    );
    $body = $response->getBody();

    return json_decode($body);
}

function atid_posts(WP_REST_Request $request)
{

    $params = $request->get_query_params();
    $token = $request->get_header('Authorization');
    $paged = isset($params['paged']) ? absint((int) $params['paged']) : 0;
    $isTest = isset($params['isTest']) && $params['isTest'] == 'true' ? (bool) $params['isTest'] : false;
    $i = 0;
    $students = get_students($token);
    $images = get_images($token);

    $slugs = get_available_tags(false);
    // This is used to arrange the order of the slugs.
    foreach ($students->data as $student) {
        $mask = $student->general_slugs;
        array_push($slugs, $mask);
    }
    foreach ($students->data as $student) {
        $mask = $student->grade_slugs;
        array_push($slugs, $mask);
    }

    $slugs = array_unique($slugs);
    $slugs = array_merge($slugs, array('jewish', 'cultural-recommendations'));

    //  This is used to arrange titles, images and post array in the correct section.
    $data = array();
    $cat = '';
    if ($isTest) {
        $cat = get_cat_by(55);  //  Category with _id 55 is "Upcoming Pruebas"
    } else {
        $cat = get_last_cat($paged)['category'];
    }

    $data = array('category' => $cat);
    $posts = array();
    foreach ($slugs as $slug) {
        $flag = true;
        $temp = get_posts_by($cat['_id'], $slug);
        if (isset($temp)) {

            foreach ($students->data as $student) {
                if ($student->grade_slugs == $slug) {
                    $title = 'Announcements for ' . $student->nombre . ', ' . get_tag_name($slug) . ' Grade for this week:';
                    $img = 'https://www.atid.edu.mx/community/images/alumno/' . $student->clave_alumno . '.jpg';
                    $color = get_tag_color($slug);
                    $posts[$i] = array('title' => $title, 'img' => $img, 'color' => $color, 'posts' => $temp, 'isAlumno' => true);
                    $flag = false;
                    $i++;
                }
            }

            if ($flag) {
                $img = 'https://www.atid.edu.mx/mobile/assets/icons/icon_general.png';
                foreach ($images as $item) {
                    if ($item->slug == $slug) {
                        $img = 'https://www.atid.edu.mx/mobile/assets/icons/icon_' . $slug . '.png';
                    }
                }
                $color = get_tag_color($slug);
                $posts[$i] = array('title' => get_tag_name($slug), 'img' => $img, 'color' => $color, 'posts' => $temp, 'isAlumno' => false);
                $i++;
            }
        }
    }
    $data['tags'] = $posts;

    return wp_send_json(array('data' => $data));
}



function atid_test()
{
    return 'Esto es un endpoint wei!';
}

add_action('rest_api_init', function () {
    register_rest_route('atid/v1', 'test', [
        'methods' => 'GET',
        'callback' => 'atid_testing',
    ]);
    register_rest_route('atid/v1', 'tags', [
        'methods' => 'GET',
        'callback' => 'atid_tags',
    ]);
    register_rest_route('atid/v1', 'cat', [
        'methods' => 'GET',
        'callback' => 'atid_cat',
    ]);
    register_rest_route('atid/v1', 'posts', [
        'methods' => 'GET',
        'callback' => 'atid_posts',
    ]);
});
