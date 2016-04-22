<html>
<head>
<link rel="stylesheet" type="text/css" href="/css/summonerstyle.css">
<script type="text/javascript" src="https://code.jquery.com/jquery-1.12.3.min.js"></script>
</head>
<body>
<div id='navbar'>
<div class='navbar_wrapper'>
  <div class='search_wrapper'>
	<form action='summoner.php' method='GET'>
		<input class='search_field' type='text' name='search' placeholder='Search for a summoner'>
		<select id='region' name='region'>
			<option name="na" value="na">NA</option>
			<option name="euw" value="euw">EUW</option>
			<option name="eune" value="eune">EUNE</option>
    		<option name="br" value="br">BR</option>
        	<option name="jp" value="jp">JP</option>
    		<option name="kr" value="kr">KR</option>
    		<option name="lan" value="lan">LAN</option>
    		<option name="las" value="las">LAS</option>		
    		<option name="oce" value="oce">OCE</option>
    		<option name="ru" value="ru">RU</option>
    		<option name="tr" value="tr">TR</option>
  		</select>
		<input class='submit' type='submit' value=" ">
	</form>
</div>
</div>
</div>
<?php
	include('functions.php');
	include('cache.php');
	error_reporting(1);
	$apiKey = getenv('api');
	$region = $_GET['region'];
	$platformId = getPlatformId($region);
	$summonerName = strtolower($_GET['search']);
	$summonerName = str_replace(' ', '', $summonerName);
	if(empty($_GET['search'])){
		echo '<div class="error"><b>X Please enter a name</b></div>';
	} else {
	//Cache each api call for 30 mins
	$summoner_file_name = $summonerName.'.data';
	$summoner_file_exists = $s3->doesObjectExist($bucket, 'regions/'.$region.'/'.$summoner_file_name);
	if ($summoner_file_exists) {
		$summonerObject = $s3->getObject(array(
    		'Bucket' => $bucket,
    		'Key' => 'regions/'.$region.'/'.$summoner_file_name
  		));
  		$summonerBody = $summonerObject['Body'];
    	$summonerData = unserialize($summonerBody);
    	if ($summonerData['timestamp'] > time() - 30 * 60) {
       		$summoner_result = $summonerData['summoner_info'];
       		$ranked_result = $summonerData['ranked_info'];
       		$ranked_stats = $summonerData['ranked_stats'];
       		$mastery_result = $summonerData['mastery_info'];
       		$match_result = $summonerData['match_info'];
    	}
	}
	if (!isset($summoner_result)) {
    	//summoner info
    	$summoner_result = file_get_contents('https://'.$region.'.api.pvp.net/api/lol/'. $region .'/v1.4/summoner/by-name/' . $summonerName . '?api_key=' . $apiKey);
    	$summoner_data = json_decode($summoner_result, true);		
		if($summoner_result == ''){
    		echo '<div class="error"><b>No summoner found!</b></div>';
    		exit;
    	} else {
    		//league info
    		$ranked_result = file_get_contents('https://'.$region.'.api.pvp.net/api/lol/'.$region.'/v2.5/league/by-summoner/'.$summoner_data[$summonerName]['id'].'/entry?api_key='.$apiKey);
    		$ranked_data = json_decode($ranked_result, true);
    		//ranked stats
    		$ranked_stats_result = file_get_contents('https://'.$region.'.api.pvp.net/api/lol/'.$region.'/v1.3/stats/by-summoner/'.$summoner_data[$summonerName]['id'].'/ranked?api_key='.$apiKey);
    		$ranked_stats_data = json_decode($ranked_stats_result, true);	
    		//mastery
    		$mastery_result = file_get_contents('https://'.$region.'.api.pvp.net/championmastery/location/'.$platformId.'/player/'.$summoner_data[$summonerName]['id'].'/topchampions?api_key='.$apiKey);
    		$mastery_data = json_decode($mastery_result, true);
    		//match history
    		$match_result = file_get_contents('https://'.$region.'.api.pvp.net/api/lol/'. $region .'/v1.3/game/by-summoner/'. $summoner_data[$summonerName]['id'] .'/recent/?&api_key='.$apiKey);
    		$match_data = json_decode($match_result, true);
   		 	//add data to summoner's file
   		 	$summonerData = array ('summoner_info' => $summoner_data, 'ranked_info' => $ranked_data, 'ranked_stats' => $ranked_stats_data, 'mastery_info' => $mastery_data, 'match_info' => $match_data, 'timestamp' => time());
    		$summoner_file = serialize($summonerData);
    		$upload = $s3->putObject(array(
      			'Bucket' => $bucket, 
      			'Key' => 'regions/'.$region.'/'.$summoner_file_name,
      			'Body' => $summoner_file
    		));
    		$summonerObject = $s3->getObject(array(
    			'Bucket' => $bucket,
    			'Key' => 'regions/'.$region.'/'.$summoner_file_name
  			));
  			$summonerBody = $summonerObject['Body'];
    		$summonerData = unserialize($summonerBody);
    		$summoner_result = $summonerData['summoner_info'];
    	   	$ranked_result = $summonerData['ranked_info'];
    	   	$ranked_stats = $summonerData['ranked_stats'];
    	   	$mastery_result = $summonerData['mastery_info'];
    	   	$match_result = $summonerData['match_info'];
    	}
    }

	//ranked stats
	echo '<div class="summoner_wrapper">';
	echo '<div class="ranked_wrapper">';
	echo '<div class="rankedStats">';
	echo '<div class="title">Top champions</div>';
	if(!isset($summonerData['ranked_stats'])){
    		echo '<div class="notFound">No ranked stats</div>';
    } else {
    	//sort champions by most wins
    	usort($ranked_stats['champions'], 'compareChamps');
    	//loop through top 3 champs
    	for ($i = 1; $i < 6; $i++){
    		$champID = $ranked_stats['champions'][$i]['id'];
    		$champImage = getImageUrl($champ_result, $champID);
			$champUrl = 'images/champions/'.$champImage;
			$name = getChampName($champ_result, $champID);
			$wins = $ranked_stats['champions'][$i]['stats']['totalSessionsWon'];
			$losses = $ranked_stats['champions'][$i]['stats']['totalSessionsLost'];
			$played = $ranked_stats['champions'][$i]['stats']['totalSessionsPlayed'];
			$kills = $ranked_stats['champions'][$i]['stats']['totalChampionKills'];
			$assists = $ranked_stats['champions'][$i]['stats']['totalAssists'];
			$deaths = $ranked_stats['champions'][$i]['stats']['totalDeathsPerSession'];
			$avgKills = round($kills/$played, 1);
			$avgAssists = round($assists/$played, 1);
			$avgDeaths = round($deaths/$played, 1);
			$avgKDA = round(($kills + $assists)/$deaths, 2);
			$winrate = round(($wins/$played)*100);
			if ($champUrl == 'images/champions/'){
				$champUrl = '';
			} else {
				echo '<div class="stats_wrapper">';
				echo '<div class="stats">';
				echo '<image height="55" width="55" src='.$champUrl.'>';
				echo '<div class="ranked_name">'.$name.'<br><div class="win_rate">'.$winrate.'%</div></div>';
				echo '<div class="ranked_winloss">Wins: <span class="w">'.$wins.'</span><br><div class="ranked_losses">Losses: <span class="l">'.$losses.'</span></div></div>';
				echo '<div class="ranked_kda">'.$avgKDA.' KDA<br>'.$avgKills.'/'.$avgDeaths.'/'.$avgAssists.'</div>';
				echo '</div>';
				echo '</div>';
			}
		}
		
    }
	echo '</div>';
	//mastery
	echo '<div class="mastery_wrapper">';
	echo '<div class="title">Champion Mastery</div>';
	echo '<div class="mastery">';
	//mastery points
    foreach ($summonerData['mastery_info'] as $mastery) { 	
    	if(!isset($summonerData['mastery_info'])){
    		echo '<div class="notFound">No champion mastery</div>';
    	} else {

    		$champId = $mastery['championId'];
    		$masteryLevel = $mastery['championLevel'];
    		$masteryPoints = $mastery['championPoints'];
   			$champImage = getImageUrl($champ_result, $champId);
			$champUrl = 'images/champions/'.$champImage;
			$champName = getChampName($champ_result, $champId);
			if ($masteryPoints >= 1000000){
				$masteryPoints = round($masteryPoints/1000000, 2).'m';
			} else if ($masteryPoints >= 10000){
				$masteryPoints = round($masteryPoints/1000, 1).'k';
			} 
			echo '<div class="champ_mastery">';
			echo '<div class="mastery_info">';
			echo '<image height="100" width="100" src='.$champUrl.'>';
			echo '<div class="champ_name">'.$champName.' ('.$masteryLevel.')</div>';
			echo '<div class="points">'.$masteryPoints.'</div>';
			echo '</div>';
			echo '</div>';
    	}
    }
	echo '</div>';
	echo '</div>';
	echo '</div>';

	echo '<div class="summoner">';
	$id = $summoner_result[$summonerName]['id'];
	$name = $summoner_result[$summonerName]['name'];
	$level = $summoner_result[$summonerName]['summonerLevel'];
	$avatar = $summoner_result[$summonerName]['profileIconId'];
	$profUrl = 'http://ddragon.leagueoflegends.com/cdn/'.$dd.'/img/profileicon/'.$avatar.'.png';
	//Summoner profile
	echo '<div class="info">';
	echo '<image height="64" width="64" src='.$profUrl.'>';
	echo '<div class="profInfo">'.$name.'<br><span class="level">Level: '.$level.'</span></div>';
	echo '</div>';
	//Ranked Leagues
	if(!isset($summonerData['ranked_info'])){
    			echo '<div class="ranked">';		
    			echo '<div id="threes">';
				echo '<div class="title">Ranked Team 3v3</div>';
				echo '<image src="/images/tiers/provisional.png">';
				echo '<div class="rankedinfo">';
   		  		echo 'Unranked<br>';
    	   		echo '0LP<br>';
    			echo '<span class="w">W</span> - <span class="l">L</span><br>';
				echo '</div>';
				echo '</div>';
				echo '<div id="soloq">';
				echo '<div class="title">Ranked Solo Queue</div>';
				echo '<image src="/images/tiers/provisional.png">';
				echo '<div class="rankedinfo">';
   	 	  		echo 'Unranked<br>';
    	   		echo '0LP<br>';
    	   		echo '<span class="w">W</span> - <span class="l">L</span><br>';
				echo '</div>';
				echo '</div>';
				echo '<div id="fives">';
				echo '<div class="title">Ranked Team 5v5</div>';
				echo '<image src="/images/tiers/provisional.png">';
				echo '<div class="rankedinfo">';
    	   		echo 'Unranked<br>';
    	   		echo '0LP<br>';
    	   		echo '<span class="w">W</span> - <span class="l">L</span><br>';
				echo '</div>';
				echo '</div>';
				echo '</div>';
	} else {
		echo '<div class="ranked">';
		foreach ($summonerData['ranked_info'] as $league){
		  	usort($league, 'compareTeams');
			if(array_search('RANKED_TEAM_3x3', array_column($league, 'queue')) !== FALSE){
        		$threeskey = array_search('RANKED_TEAM_3x3', array_column($league, 'queue'));
            	//--Ranked 3's
        		$tier3 = strtolower($league[$threeskey]['tier']);
				$tier3 = $tier3;
				$leagueUrl3 = '/images/tiers/'.$tier3.'.png';
				$div3 = $league[$threeskey]['entries'][0]['division'];
        		$lp3 = $league[$threeskey]['entries'][0]['leaguePoints'];
        		$wins3 = $league[$threeskey]['entries'][0]['wins'];
        		$losses3 = $league[$threeskey]['entries'][0]['losses'];
        		echo '<div id="threes">';
				echo '<div class="title">Ranked Team 3v3</div>';
				echo '<image src='.$leagueUrl3.'>';
				echo '<div class="rankedinfo">';
       			echo ucfirst($tier3).' '.$div3.'<br>';
      	 		echo $lp3.' LP<br>';
       			echo '<span class="w">'.$wins3.'W</span> - <span class="l">'.$losses3.'L</span><br>';
				echo '</div>';
				echo '</div>';
        	} else{
        		echo '<div id="threes">';
				echo '<div class="title">Ranked Team 3v3</div>';
				echo '<image src="/images/tiers/provisional.png">';
				echo '<div class="rankedinfo">';
   	    		echo 'Unranked<br>';
    	   		echo '0LP<br>';
    	   		echo '<span class="w">W</span> - <span class="l">L</span><br>';
				echo '</div>';
				echo '</div>';
        	}
        	if(array_search('RANKED_SOLO_5x5', array_column($league, 'queue')) !== FALSE){
        		$soloqkey = array_search('RANKED_SOLO_5x5', array_column($league, 'queue'));
        		$tier = strtolower($league[$soloqkey]['tier']);
				$tier = $tier;
				$leagueUrl = '/images/tiers/'.$tier.'.png';
				$div = $league[$soloqkey]['entries'][0]['division'];
        		$lp = $league[$soloqkey]['entries'][0]['leaguePoints'];
        		$wins = $league[$soloqkey]['entries'][0]['wins'];
        		$losses = $league[$soloqkey]['entries'][0]['losses'];	
        		echo '<div id="soloq">';
				echo '<div class="title">Ranked Solo Queue</div>';
				echo '<image src='.$leagueUrl.'>';
				echo '<div class="rankedinfo">';
       			echo ucfirst($tier).' '.$div.'<br>';
	       		echo $lp.' LP<br>';
   	    		echo '<span class="w">'.$wins.'W</span> - <span class="l">'.$losses.'L</span><br>';
				echo '</div>';
				echo '</div>';
    	    	} else{
    	   		echo '<div id="soloq">';
				echo '<div class="title">Ranked Solo Queue</div>';
				echo '<image src="/images/tiers/provisional.png">';
				echo '<div class="rankedinfo">';
    	   		echo 'Unranked<br>';
    	   		echo '0LP<br>';
    	   		echo '<span class="w">W</span> - <span class="l">L</span><br>';
				echo '</div>';
				echo '</div>';
       		 	}
        	if(array_search('RANKED_TEAM_5x5', array_column($league, 'queue')) !== FALSE){
				$fiveskey = array_search('RANKED_TEAM_5x5', array_column($league, 'queue'));
   		     	$tier5 = strtolower($league[$fiveskey]['tier']);
				$tier5 = $tier5;
				$leagueUrl5 = '/images/tiers/'.$tier5.'.png';
				$div5 = $league[$fiveskey]['entries'][0]['division'];
    	    	$lp5 = $league[$fiveskey]['entries'][0]['leaguePoints'];
    	    	$wins5 = $league[$fiveskey]['entries'][0]['wins'];
    	    	$losses5 = $league[$fiveskey]['entries'][0]['losses'];
    	    	echo '<div id="fives">';
				echo '<div class="title">Ranked Team 5v5</div>';
				echo '<image src='.$leagueUrl5.'>';
				echo '<div class="rankedinfo">';
		       	echo ucfirst($tier5).' '.$div5.'<br>';
		       	echo $lp5.' LP<br>';
   		    	echo '<span class="w">'.$wins5.'W</span> - <span class="l">'.$losses5.'L</span><br>';
				echo '</div>';
				echo '</div>';
    	    } else{
    	    	echo '<div id="fives">';
				echo '<div class="title">Ranked Team 5v5</div>';
				echo '<image src="/images/tiers/provisional.png">';
				echo '<div class="rankedinfo">';
    	   		echo 'Unranked<br>';
    	   		echo '0LP<br>';
    	   		echo '<span class="w">W</span> - <span class="l">L</span><br>';
				echo '</div>';
				echo '</div>';
    	    	}
        		echo '</div>';
		}
	}

	//Match History	
	if(!isset($summonerData['match_info'])){
    	echo '<div class="match_history">';
    	echo '<div class="notFound">No match history found</div>';
    	echo '</div>';
	} else {
		foreach($summonerData['match_info']['games'] as $game) {
			echo '<div class="match_history">';
			if(keyExists($game, 'championsKilled') !== FALSE){
				$kills = $game['stats']['championsKilled'];
			} else {
				$kills = 0;
			}
			if(keyExists($game, 'numDeaths') !== FALSE){
				$deaths = $game['stats']['numDeaths'];
			} else {
				$deaths = 0;
			}
			if(keyExists($game, 'assists') !== FALSE){
				$assists = $game['stats']['assists'];
			} else {
				$assists = 0;
			}
			$kda = round(($kills + $assists)/$deaths, 2);
			$duration = timeFormat($game['stats']['timePlayed']);
			$created = $game['createDate'];
			$epoch = substr($created,0,10);
			$date = date('Y-m-d H:i:s', $epoch);
			$timeAgo = time_ago($date);
			$gameMode = gameMode($game['subType']);
			$champId = $game['championId'];
			$champImage = getImageUrl($champ_result, $champId);
			$champUrl = 'images/champions/'.$champImage;
			$champName = getChampName($champ_result, $champId);
			$spellOneUrl = '/images/summonerSpells/'.$game['spell1'].'.png';
			$spellTwoUrl = '/images/summonerSpells/'.$game['spell2'].'.png';
			$itemUrl0 = 'images/items/'.$game['stats']['item0'].'.png';
			$itemUrl1 = 'images/items/'.$game['stats']['item1'].'.png';
			$itemUrl2 = 'images/items/'.$game['stats']['item2'].'.png';
			$itemUrl3 = 'images/items/'.$game['stats']['item3'].'.png';
			$itemUrl4 = 'images/items/'.$game['stats']['item4'].'.png';
			$itemUrl5 = 'images/items/'.$game['stats']['item5'].'.png';
			$trinket = 'images/items/'.$game['stats']['item6'].'.png';
			$cs = $game['stats']['minionsKilled'] + $game['stats']['neutralMinionsKilled'] ;
			$level = $game['stats']['level'];
			$dmg = $game['stats']['totalDamageDealtToChampions'];
			
			//format damage
			if ($dmg >= 1000){
				$dmgDealt = round($dmg/1000, 1).'k';
			} else {
				$dmgDealt = $dmg;
			}

			//format gold
			$goldEarned = $game['stats']['goldEarned'];
			if ($goldEarned < 1000){
				$gold = $goldEarned;
			} else {
				$gold = round($goldEarned/1000, 1).'k';
			}
			//missing items
			if (!file_exists($itemUrl0)) {
   				$itemUrl0 = 'images/items/empty.png';
			}
			if (!file_exists($itemUrl1)) {
   				$itemUrl1 = 'images/items/empty.png';
			}
			if (!file_exists($itemUrl2)) {
   				$itemUrl2 = 'images/items/empty.png';
			}
			if (!file_exists($itemUrl3)) {
   				$itemUrl3 = 'images/items/empty.png';
			}
			if (!file_exists($itemUrl4)) {
   				$itemUrl4 = 'images/items/empty.png';
			}
			if (!file_exists($itemUrl5)) {
   				$itemUrl5 = 'images/items/empty.png';
			}
			if (!file_exists($trinket)) {
   				$trinket = 'images/items/empty.png';
			}
			if ($game['stats']['win'] == 1){
			echo '<div class="win">';
			echo '<div class="headerW"><div class="outcome">Victory</div><div class="gameMode">'.$gameMode.'</div><div class="duration">'.$duration.'</div><div class="timeAgo">'.$timeAgo.'</div></div>';
			echo '<div class="gameInfo"><div class="champ"><div class="block"><image height="70" width="70" src='.$champUrl.'></div>';
			echo '<div class="block"><image class="spellImg" src='.$spellOneUrl.'><image class="spellImg" src='.$spellTwoUrl.'></div><br>';
			echo '<div class="name">'.$champName.'</div>';
			echo '</div>'; //champ
			echo '<div class="score">';//scores
			echo '<span class="scores">'.$kills.'/'.$deaths.'/'.$assists.'<br><span class="kda">'.$kda.' <b>KDA</b></span></span>';
			echo '</div>';
			echo '<div class="items">';
			echo '<image height="34" width="34" src='.$itemUrl0.'>';
			echo '<image height="34" width="34" src='.$itemUrl1.'>';
			echo '<image height="34" width="34" src='.$itemUrl2.'>';
			echo '<image height="34" width="34" src='.$itemUrl3.'>';
			echo '<image height="34" width="34" src='.$itemUrl4.'>';
			echo '<image height="34" width="34" src='.$itemUrl5.'>';
			echo '</div>';
			echo '<div class="trinket"><image height="34" width="34" src='.$trinket.'></div>';
			echo '<div class="otherInfo">';
			echo '<span class="cs"><b>'.$cs.' </b>Cs</span><br>';
			echo '<span class="gold"><b>'.$gold.'</b> Gold</span><br>';
			echo '</div>';
			echo '<div class="dmg">';
			echo '<span><b>'.$dmgDealt.'</b> Damage<br> to champs</span>';
			echo '</div>';
			echo '</div>'; 
			echo '</div>'; 
			} else {
			echo '<div class="loss">';
			echo '<div class="headerL"><div class="outcome">Defeat</div><div class="gameMode">'.$gameMode.'</div><div class="duration">'.$duration.'</div><div class="timeAgo">'.$timeAgo.'</div></div>';
			echo '<div class="gameInfo"><div class="champ"><div class="block"><image height="70" width="70" src='.$champUrl.'></div>';
			echo '<div class="block"><image class="spellImg" src='.$spellOneUrl.'><image class="spellImg" src='.$spellTwoUrl.'></div><br>';
			echo '<div class="name">'.$champName.'</div>';
			echo '</div>'; //champ
			echo '<div class="score">';//scores
			echo '<span class="scores">'.$kills.'/'.$deaths.'/'.$assists.'<br><span class="kda">'.$kda.' <b>KDA</b></span></span>';
			echo '</div>';
			echo '<div class="items">';//items
			echo '<image height="34" width="34" src='.$itemUrl0.'>';
			echo '<image height="34" width="34" src='.$itemUrl1.'>';
			echo '<image height="34" width="34" src='.$itemUrl2.'>';
			echo '<image height="34" width="34" src='.$itemUrl3.'>';
			echo '<image height="34" width="34" src='.$itemUrl4.'>';
			echo '<image height="34" width="34" src='.$itemUrl5.'>';
			echo '</div>';
			echo '<div class="trinket"><image height="34" width="34" src='.$trinket.'></div>';
			echo '<div class="otherInfo">';
			echo '<span class="cs"><b>'.$cs.' </b>Cs</span><br>';
			echo '<span class="gold"><b>'.$gold.' </b>Gold</span><br>';
			echo '</div>';
			echo '<div class="dmg">';
			echo '<span><b>'.$dmgDealt.'</b> Damage<br> to champs</span>';
			echo '</div>';
			echo '</div>';
			echo '</div>'; 
			}
		echo '</div>';
		}
		echo '</div>';
		echo '</div>';
	}
}
?>

<!--Remember the selected region-->
<script type="text/javascript" >
    document.getElementById('region').value = "<?php echo $region;?>";
</script>

<script type="text/javascript" >
   	$(document).ready(function(){
	setTimeout(function(){
		$('.error').fadeOut(300, function(){
			$(this).remove();
		});
	}, 1500);
});
</script>
<div id="footer">
    This app isn't endorsed by Riot Games and doesn't reflect the views or opinions of Riot Games or anyone officially involved in producing or managing League of Legends. League of Legends and Riot Games are trademarks or registered trademarks of Riot Games, Inc. League of Legends &copy Riot Games, Inc.
</div>
</body>
</html>