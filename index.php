<html>
<head>
<link rel="stylesheet" type="text/css" href="/css/indexstyle.css">
</head>
<body>
  <div class="index_wrapper">
  <div class='search_wrapper'>
  <image src='/images/logo.png'>
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
<div id="footer">
    This app isn't endorsed by Riot Games and doesn't reflect the views or opinions of Riot Games or anyone officially involved in producing or managing League of Legends. League of Legends and Riot Games are trademarks or registered trademarks of Riot Games, Inc. League of Legends &copy Riot Games, Inc.
</div>
</body>
</html>

<?php
  include('cache.php');
?>