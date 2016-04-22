<?php
function get_http_response_code($url) {
    $headers = get_headers($url);
    return substr($headers[0], 9, 3);
}
function search($array, $key, $value)
{
    $results = array();

    if (is_array($array)) {
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }
        foreach ($array as $subarray) {
            $results = array_merge($results, search($subarray, $key, $value));
        }
    }
    return $results;
}

//Function to assign values for best placed team to worst
function getTier($tier) {
    switch($tier) {
        case 'CHALLENGER':
            return 1;
        case 'MASTER':
            return 2;
        case 'DIAMOND':
            return 3;
        case 'PLATINUM':
            return 4;
        case 'GOLD':
            return 5;
        case 'SILVER':
            return 6;
        case 'BRONZE':
            return 7;
    }
    return 0;
}
function getDiv($div) {
    switch($div) {
        case 'I':
            return 1;
        case 'II':
            return 2;
        case 'III':
            return 3;
        case 'IV':
            return 4;
        case 'V':
            return 5;
    }
    return 0;
}
function compareTeams($teamA, $teamB) {
    $a = getTier($teamA['tier']);
    $b = getTier($teamB['tier']);
    if ($a == $b) {
        $aDiv = getDiv($teamA['entries'][0]['division']);
        $bDiv = getDiv($teamB['entries'][0]['division']);
        if ($aDiv == $bDiv) {
            $aLp = $teamA['entries'][0]['leaguePoints'];
            $bLp = $teamB['entries'][0]['leaguePoints'];
            if ($aLp == $bLp){
                return 0;
            }
            return ($aLp < $bLp) ? 1 : -1;
        }
        return ($aDiv < $bDiv) ? -1 : 1;  
    }
    return ($a < $b) ? -1 : 1;
}

function compareChamps($champA, $champB) {
    $a = $champA['stats']['totalSessionsPlayed'];
    $b = $champB['stats']['totalSessionsPlayed'];
        if ($a == $b) {
            $aWins = $champA['stats']['totalSessionsWon'];
            $bWins = $champB['stats']['totalSessionsWon'];
            if ($aWins == $bWins){
                return 0;
            }
            return ($aWins < $bWins) ? 1 : -1;
        }
    return ($a < $b) ? 1 : -1;
}

function gameMode($mode){
    switch($mode){
        case 'NONE':
            return 'Custom';
        case 'NORMAL':
            return 'Normal 5v5';
        case 'NORMAL_3x3':
            return 'Normal 3v3';
        case 'ODIN_UNRANKED':
            return 'Dominion';
        case 'ARAM_UNRANKED_5x5':
            return 'ARAM';
        case 'BOT':
        case 'BOT_3x3':
            return 'Co-op vs AI';
        case 'RANKED_SOLO_5x5':
            return 'Ranked 5v5';
        case 'RANKED_TEAM_5x5':
            return 'Ranked Team 5v5';
        case 'RANKED_TEAM_3x3':
            return 'Ranked Team 3v3';
        case 'CAP_5x5':
            return 'Team Builder';
        default: 
            return 'Featured Game Mode';
    }
}

function timeFormat($time){
    $min = floor($time/60);
    $sec = $time % 60;
    if ($sec < 10){
        $sec = '0'.$sec;
    }
    return $min.'m:'.$sec.'s';
}

function keyExists($array, $key) {
    foreach ($array as $item){
        if (isset($item[$key]))
            return true;
    }
    return false;
}

function getImageUrl($array, $id){
    foreach ($array['data'] as $champ){
        if ($champ['id'] == $id){
            return $champ['image']['full'];
        }
    }
}

function getChampName($array, $id){
    foreach ($array['data'] as $champ){
        if ($champ['id'] == $id){
            return $champ['name'];
        }
    }
}

function time_ago($time_ago) {
    $time_ago = strtotime($time_ago);
    $cur_time   = time();
    $time_elapsed   = $cur_time - $time_ago;
    $seconds    = $time_elapsed ;
    $minutes    = round($time_elapsed / 60 );
    $hours      = round($time_elapsed / 3600);
    $days       = round($time_elapsed / 86400 );
    $weeks      = round($time_elapsed / 604800);
    $months     = round($time_elapsed / 2600640 );
    $years      = round($time_elapsed / 31207680 );
    // Seconds
    if($seconds <= 60){
        return "just now";
    }
    //Minutes
    else if($minutes < 60){
        if($minutes==1){
            return "One minute ago";
        }
        else{
            return "$minutes minutes ago";
        }
    }
    //Hours
    else if($hours < 24){
        if($hours==1){
            return "An hour ago";
        }else{
            return "$hours hours ago";
        }
    }
    //Days
    else if($days <  7){
        if($days==1){
            return "1 day ago";
        }else{
            return "$days days ago";
        }
    }
    //Weeks
    else if($weeks < 4.3){
        if($weeks==1){
            return "A week ago";
        }else{
            return "$weeks weeks ago";
        }
    }
    //Months
    else if($months < 12){
        if($months==1){
            return "A month ago";
        }else{
            return "$months months ago";
        }
    }
    //Years
    else{
        if($years==1){
            return "One year ago";
        }else{
            return "$years years ago";
        }
    }
}

function getPlatformId($region) {
    switch($region){
        case 'na':
            return 'NA1';
        case 'br':
            return 'BR1';
        case 'eune':
            return 'EUN1';
        case 'euw':
            return 'EUW1';
        case 'kr':
            return 'KR';
        case 'lan':
            return 'LA1';
        case 'las':
            return 'LA2';
        case 'oce':
            return 'OC1';
        case 'ru':
            return 'RU';
        case 'jp':
            return 'JP1';
        case 'tr':
            return 'TR1';
    }
}
?>