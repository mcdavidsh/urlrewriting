<?php
include_once 'matches.php';

define("SITE_URL", "http://localhost/urlrewriting");
$rewrite = array();
        
function updateSlugForRewrite(&$items, $parentId = "0", $parentItem = array()) {
    // Parent items control
    $isParentItem = false;
    foreach ($items as $item) {
        if ($item['parent'] == $parentId) {
            $isParentItem = true;
            break;
        }
    }

    // Prepare items
    if ($isParentItem) {
        foreach ($items as &$item) {
            if ($item['parent'] == $parentId) {
                $item['slug'] = isset($parentItem['slug']) ? ($parentItem['slug'] . "/" . $item['slug']) : $item['slug'];
                updateSlugForRewrite($items, $item['id'], $item);
            }
        }
    }
}

function updateRewriteRules($additional_rules = array(), $location = "bottom") {
    $rewrite_rules = array();
    //adding additional rules in array at the top
    if (!empty($additional_rules) && $location == 'top') {
        foreach ($additional_rules as $key => $value) {
            $rewrite_rules[$key] = $value;
        }
    }
    /* login, logout & register rewrites */
    $rewrite_rules['login$'] = 'index.php?login=1';
    $rewrite_rules['logout$'] = 'index.php?logout=1';
    $rewrite_rules['register$'] = 'index.php?register=1';
    /* 
     * categories rewrites 
     * categories will be fetched from database dynamically but for testing I am using fixed data
     */
    $categories = array(
        array('id' => "1", 'name' => "Java", 'slug' => "java", 'description' => "", 'parent' => "0"),
        array('id' => "2", 'name' => "JavaFX", 'slug' => "javafx", 'description' => "", 'parent' => "0"),
        array('id' => "3", 'name' => "Projects", 'slug' => "projects", 'description' => "", 'parent' => "0"),
        array('id' => "4", 'name' => "Java Projects", 'slug' => "java-projects", 'description' => "", 'parent' => "3"),
        array('id' => "5", 'name' => "PHP Projects", 'slug' => "php-projects", 'description' => "", 'parent' => "3"),
        array('id' => "6", 'name' => "Uncategorized", 'slug' => "uncategorized", 'description' => "", 'parent' => "0")
    );
    updateSlugForRewrite($categories, 0);
    foreach ($categories as $c) {
        $rewrite_rules['(' . $c['slug'] . ')/page/?([0-9]{1,})/?$'] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
        $rewrite_rules['(' . $c['slug'] . ')/?$'] = 'index.php?category_name=$matches[1]';
    }
    /* tag rewrites */
    $rewrite_rules['tag/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?tag=$matches[1]&paged=$matches[2]';
    $rewrite_rules['tag/([^/]+)/?$'] = 'index.php?tag=$matches[1]';
    /* blogs paging */
    $rewrite_rules['page/?([0-9]{1,})/?$'] = 'index.php?&paged=$matches[1]';
    /* search rewrites */
    $rewrite_rules['search/(.+)/page/?([0-9]{1,})/?$'] = 'index.php?s=$matches[1]&paged=$matches[2]';
    $rewrite_rules['search/(.+)/?$'] = 'index.php?s=$matches[1]';
    /* author rewrites */
    $rewrite_rules['author/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?author_name=$matches[1]&paged=$matches[2]';
    $rewrite_rules['author/([^/]+)/?$'] = 'index.php?author_name=$matches[1]';
    /* year month day wise posts */
    $rewrite_rules['([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/page/?([0-9]{1,})/?$'] = 'index.php?year=$matches[1]&month=$matches[2]&day=$matches[3]&paged=$matches[4]';
    $rewrite_rules['([0-9]{4})/([0-9]{1,2})/([0-9]{1,2})/?$'] = 'index.php?year=$matches[1]&month=$matches[2]&day=$matches[3]';
    $rewrite_rules['([0-9]{4})/([0-9]{1,2})/page/?([0-9]{1,})/?$'] = 'index.php?year=$matches[1]&month=$matches[2]&paged=$matches[3]';
    $rewrite_rules['([0-9]{4})/([0-9]{1,2})/?$'] = 'index.php?year=$matches[1]&month=$matches[2]';
    $rewrite_rules['([0-9]{4})/page/?([0-9]{1,})/?$'] = 'index.php?year=$matches[1]&paged=$matches[2]';
    $rewrite_rules['([0-9]{4})/?$'] = 'index.php?year=$matches[1]';
    /**/
    $rewrite_rules['(.?.+?)/page/?([0-9]{1,})/?$'] = 'index.php?pagename=$matches[1]&paged=$matches[2]';
    $rewrite_rules['(.?.+?)(?:/([0-9]+))?/?$'] = 'index.php?pagename=$matches[1]&page=$matches[2]';
    $rewrite_rules['([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?name=$matches[1]&paged=$matches[2]';
    $rewrite_rules['([^/]+)(?:/([0-9]+))?/?$'] = 'index.php?name=$matches[1]&page=$matches[2]';

    //adding additional rules in array in the bottom
    if (!empty($additional_rules) && $location == 'bottom') {
        foreach ($additional_rules as $key => $value) {
            $rewrite_rules[$key] = $value;
        }
    }
    /*
     * Here rewrite rules I am storing in global variable named rewrite but
     * this can be serialized and stored to database so that it can be accessed wherever needed just like
     * I have called saveConfig in comment for real project to save rewrite rules in Database
     */
    $GLOBALS['rewrite'] = $rewrite_rules;
    //saveConfig('rewrite_rules', serialize($rewrite_rules));
}

function parse_request($extra_query_vars = '') {

    $rewrite_index = "index.php";
    // Process PATH_INFO, REQUEST_URI, and 404 for permalinks.
    // Fetch the rewrite rules.
    /*
     * Here rewrite rules I am fetching from global variable named rewrite but
     * this can be unserialize after fetching from database just like
     * I have called getConfig function to fetch from database and unserialized in real project
     */
    $rewrite = $GLOBALS['rewrite'];
    //$rewrite = unserialize(getConfig("rewrite_rules"));

    if (!empty($rewrite)) {
        // If we match a rewrite rule, this will be cleared.
        $error = '404';

        $pathinfo = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        list( $pathinfo ) = explode('?', $pathinfo);
        $pathinfo = str_replace('%', '%25', $pathinfo);

        list( $req_uri ) = explode('?', $_SERVER['REQUEST_URI']);
        $self = $_SERVER['PHP_SELF'];
        $home_path = trim(parse_url(SITE_URL, PHP_URL_PATH), '/');
        $home_path_regex = sprintf('|^%s|i', preg_quote($home_path, '|'));

        // Trim path info from the end and the leading home path from the
        // front. For path info requests, this leaves us with the requesting
        // filename, if any. For 404 requests, this leaves us with the
        // requested permalink.
        $req_uri = str_replace($pathinfo, '', $req_uri);
        $req_uri = trim($req_uri, '/');
        $req_uri = preg_replace($home_path_regex, '', $req_uri);
        $req_uri = trim($req_uri, '/');
        $pathinfo = trim($pathinfo, '/');
        $pathinfo = preg_replace($home_path_regex, '', $pathinfo);
        $pathinfo = trim($pathinfo, '/');
        $self = trim($self, '/');
        $self = preg_replace($home_path_regex, '', $self);
        $self = trim($self, '/');

        // The requested permalink is in $pathinfo for path info requests and
        //  $req_uri for other requests.
        if (!empty($pathinfo) && !preg_match('|^.*' . $rewrite_index . '$|', $pathinfo)) {
            $requested_path = $pathinfo;
        } else {
            // If the request uri is the index, blank it out so that we don't try to match it against a rule.
            if ($req_uri == $rewrite_index) {
                $req_uri = '';
            }
            $requested_path = $req_uri;
        }
        $requested_file = $req_uri;

        // Look for matches.
        $request_match = $requested_path;
        if (empty($request_match)) {
            // An empty request could only match against ^$ regex
            if (isset($rewrite['$'])) {
                $query = $rewrite['$'];
                $matches = array('');
            }
        } else {
            foreach ((array) $rewrite as $match => $query) {
                // If the requested file is the anchor of the match, prepend it to the path info.
                if (!empty($requested_file) && strpos($match, $requested_file) === 0 && $requested_file != $requested_path) {
                    $request_match = $requested_file . '/' . $requested_path;
                }

                if (preg_match("#^$match#", $request_match, $matches) || preg_match("#^$match#", urldecode($request_match), $matches)) {
                    // Got a match.
                    $matched_rule = $match;
                    break;
                }
            }
        }

        if (isset($matched_rule)) {
            // Trim the query of everything up to the '?'.
            $query = preg_replace('!^.+\?!', '', $query);

            // Substitute the substring matches into the query.
            $matched_query = addslashes(MatchesMapRegex::apply($query, $matches));

            // Parse the query.
            parse_str($matched_query, $perma_query_vars);

            // If we're processing a 404 request, clear the error var since we found something.
            if ('404' == $error) {
                unset($error, $_GET['error']);
            }
        }

        // If req_uri is empty or if it is a request for ourself, unset error.
        if (empty($requested_path) || $requested_file == $self) {
            unset($error, $_GET['error']);
            
            if (isset($perma_query_vars)) {
                unset($perma_query_vars);
            }
        }
    }

    if (isset($perma_query_vars)) {
        return $perma_query_vars;
    }
    return null;
}
