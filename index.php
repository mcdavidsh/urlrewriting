<?php
include_once 'config.php';

/*
 * This function can be called where updatation of rewrite rules required 
 * and can be updated to database i.e while creating new Category or updating permalink, etc
 */
updateRewriteRules();

$request = parse_request();
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title></title>
    </head>
    <body>
        <?php
        if ($request == null || empty($request)) {
            //Process home page
            echo "include home page here"; //include('home.php');
        } else if (isset($request['category_name'])) {
            //Process category page
            echo "inclue category page here"; //include('category.php');
        } else if (isset($request['tag'])) {
            //Process tag page
            echo "include tag page here"; //include('tag.php');
        } else if (isset($request['s'])) {
            //Process search page
            echo "include search page here"; //include('search.php');
        } else if (isset($request['author_name'])) {
            //Process author page
            echo "include author page here"; //include('author.php');
        } else if (isset($request['pagename'])) {
            //Process page/post page
            echo "page/post page here and show page or post according to post_type"; //include('post.php');
        } else if (isset($request['year']) || isset($request['month']) || isset($request['day'])) {
            //Process page/post page
            echo "include posts page here"; //include('post.php');
        } else if (isset($request['login'])) {
            //Process product page
            echo "include login page here"; //include('login.php');
        } else if (isset($request['logout'])) {
            //Process logout page
            echo "logout code or logout page here"; //include('logout.php');
        } else if (isset($request['register'])) {
            //Process register page
            echo "include registration page here"; //include('register.php');
        } else {
            //Process 404 page
            echo "include Page not found template here"; //include('404.php');
        }
        ?>
    </body>
</html>
