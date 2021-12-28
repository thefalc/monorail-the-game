<?php
// application constants
define("_APP_NAME", "Monorail");

define("_APP_URL", "http://www.monoraildev.com");

function formatRelativeDate($date, $now = null) {
    $date = strtotime($date);
    if($now == null) {
        $now = strtotime(date("Y-m-d H:i:s"));
    }
    else {
        $now = strtotime($now);
    }
    
    $diff = abs($now - $date);
    
    $formatted_time = "";

    $days = floor($diff / (60*60*24));
    if($days == 0) {
        $hours = floor($diff / (60*60));

        if($hours == 0) {
            $minutes = floor($diff / (60));
            
            if($minutes == 0) {
                $seconds = $diff;
                if($seconds == 0) $seconds = 1;
                $formatted_time = ($seconds == 1 ? $seconds . " second ago" : $seconds . " seconds ago");
            }
            else {
                $formatted_time = ($minutes == 1 ? $minutes . " minute ago" : $minutes . " minutes ago");
            }
        }
        else {
            $formatted_time = ($hours == 1 ? $hours . " hour ago" : $hours . " hours ago");
        }
    }
    else {
        if($days == 1) {
            $formatted_time = "Yesterday";
        }
        else if($days <= 7) {
            $formatted_time = $days . " days ago";
        }
        else if($days <= 14) {
            $formatted_time =  "1 week ago";
        }
        else if($days <= 21) {
            $formatted_time =  "2 weeks ago";
        }
        else if($days <= 28) {
            $formatted_time =  "3 weeks ago";
        }
        else {
            $formatted_time = date("F d, Y", $date);
        }
    }

    return $formatted_time;
}
?>