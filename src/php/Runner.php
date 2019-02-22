<?php

$apipath='http://api.lordsofguilds.com:8081/V1';
$apikey='XXXXXXXXXXX'; // Put your API Key here
$apirelease='1.0.1'; // don'touch. Api release compliancy. 
$headers=array( "Host: lordsofguilds.com", "cache-control: no-cache","Content-Type: application/x-www-form-urlencoded");
$userid=''; //Don't touch
$ai=''; //Don't touch
$players=''; //Don't touch
$counsimu=0; //Don't touch
$fp=0; //Don't touch
$messages=''; //Don't touch
$debug=0; //0=nodebug,1=light,2=medium,3=full
$maxdepth=2; //Recursive depth for AIprocess, be carefull : increasing this can increase a lot process time
$maxaitime=10; //Max AI process time in seconds


////////// Checking CLI getops
$shortopts  = "";
$longopts  = array(
    "ai:",  
    "debug:",  
    "gametype:",
    "killme:",
    "maxdepth:",
    "maxaitime:",
);
$options = getopt($shortopts, $longopts);
if(isset($options['ai']))
	$ai=$options['ai'];
if(!$ai)
	$ai='donkey';
switch($ai){
	case 'donkey':
		require('./DonkeyAI.php');
	break;
	case 'monkey':
		//coming soon ...
	break;
	default:
		require('./DonkeyAI.php');
	break;
}

if(isset($options['debug']))
	$debug=$options['debug'];
if(isset($options['killme']))
	$killme=$options['killme'];
if(isset($options['gametype']))
	$gametype=$options['gametype'];
if(isset($options['maxaitime']))
	$maxaitime=$options['maxaitime'];
if(isset($options['maxdepth']))
	$maxdepth=$options['maxdepth'];
if(!$gametype) $gametype='worldmap';
if($gametype=='worldmap')
	$gametypereq='type=worldmap&release=100';
if($gametype=='versus')
	$gametypereq='type=versus&release=101';
if($debug>=3) var_dump($options);


echo "Starting $ai ...\n";

//////////  Getting /welcome information and checking if game is up
$ret=curlIt($apipath.'/welcome',$headers);
if($debug>=1) print_r($ret);
if($ret->status!='up')
	die("Huumm, game is down \n");
if($ret->api!=$apirelease)
	die("Current API release, seems to be newer than this script. Check new release on git or modify this script \n");

//////////  Getting my token / Bearer for auth
$ret=curlIt($apipath.'/gettoken/'.$apikey,$headers);
if($debug>=1) print_r($ret);
if(!isset($ret->bearer))
	die('Invalid token ? Check it or Go to help !');

/////////   Adding bearer to headers
$headers[]="Authorization: Bearer ".$ret->bearer;
if($debug>=3) print_r($headers);

/////// Getting user information
$ret=curlIt($apipath.'/users/me',$headers);
if($debug>=1) print_r($ret);
if(!isset($ret->userid))
        die('Go to help !');
$userid=$ret->userid;

////// Check if you have pending game results to check
if(checkResults($apipath,$headers)) die();

////// Check if a current pending game exists
$ret=curlIt($apipath.'/games/me',$headers);
if($debug>=1) print_r($ret);
if(!isset($ret->gamespending))
        die('Go to help !');

if(isset($ret->gamespending->idg)){
	$game=$ret->gamespending;
	echo "Pending game found ...\n";
}
else{
	////// Check if a current versus pending game exists
	if(isset($ret->versuspending->idg)){
		$game=waitVersusPending();
	}
	else{
		////// Else create a game
		echo "Creating a new game...\n";
		$ret=curlIt($apipath.'/games/',$headers,'POST',$gametypereq);
		if($debug>=1) print_r($ret);
		if(isset($ret->error))
			die($ret->error."\n");
		///// Sleep 2 seconds...
		sleep(2);
		if($gametype=='versus')
			$game=waitVersusPending();
		$ret=curlIt($apipath.'/games/me?type=worldmap',$headers);
		if($debug>=1) print_r($ret);
		if(count($ret->gamespending)==0)
			die("A bug happened ...?\n");
		$game=$ret->gamespending;
	}
}
if(!isset($game))
	die("No game ...\n");
if($debug>=1) print_r($game);
$playerid=$game->p;


////////////// Connect to the game !
echo "Trying to connect socket ...\n";
$fp = fsockopen($game->host, $game->port, $errno, $errstr, 10);
if (!$fp || $errstr) {
    echo "$errstr ($errno)\n";
    die("error connecting\n");
}

////send pwd command (userid/pwd)
echo "send pwd command \n";
fwrite($fp, $userid."/".$game->pwd);
echo "Entering loop ...\n";
sleep(1);
while (!feof($fp)) {
	if($debug==0) system('clear');
        $retstr=fgets($fp, 15000);
	if($debug>=1) echo "------------------------------->New loop \n";
	$ret=json_decode(str_replace('|','',$retstr));
	if($debug>=1) echo "<----------------Recieve ".$ret->rettype."\n";
	if($debug>=2) print_r($retstr);
	if($debug>=2) echo "\n";
	///////////// Forgive this game
	if($killme)
		fwrite($fp,"killme|");
	if($debug>0)
		$messages='';
	if(isset($ret->messages))
		foreach($ret->messages as $v)
			 $messages.=$v."\n";
	switch($ret->rettype){
	case 'simulate':
		//print_r($ret);
		//die();
		getPlayers($ret->players);
		fwrite($fp,"getplayable|");
	break;
	case 'getplayable':
		if($debug>=1) echo "trying to play a card...\n";
		$ret=json_decode(str_replace('|','',$retstr),1);
		if($debug>=1) print_r($ret);
		if(count($ret['playable'])){
			fwrite($fp,"players|");
			$retstr=fgets($fp, 15000);
                        $retplayers=json_decode(str_replace('|','',$retstr),true);	
			echo "Start AIprocess ...\n";
			//var_dump($retstr);
			//var_dump($retplayers);
			$data=array_merge(array('getplayable'=>$ret),$retplayers);
			$countsimu=0;
			$useitem=AIprocess($data,$data);
			if($debug>=2) print_r($useitem);
			$useitem=array_pop($useitem);
			$useitem=array_shift($useitem);
			fwrite($fp,"useitem/".$useitem[0]."/".$useitem[1]."|");
			$messages.="\nPlaying ".$ret['playable'][$useitem[0]]['name']." ".$useitem[0]."/".$useitem[1]."\n";
		}
		else{
			echo "Nothing to play, going to skip\n";
			if($debug>=1) echo "---------------->Send whattodo\n";
			fwrite($fp,"whattodo|");
		}
	break;
	case 'check':
		if($ret->ismytime){
			if($debug>=1) echo "Getting playable\n";
			if($debug>=1) echo "---------------->Send getplayable\n";
			fwrite($fp,"getplayable|");
		}	
		else{
			echo "Waiting for opponent...\n";
		}
	break;
	default:
		if(isset($ret->P1))
			getPlayers($ret);
		if($ret->ismytime){
			if($debug>=1) echo "Getting playable\n";
			//if($debug>=1) echo "---------------->Send getplayable\n";
			fwrite($fp,"getplayable|");
		}	
		else{
			if($debug>=1) echo "---------------->Send players\n";
			fwrite($fp,"players|");
			echo "...\n";
		}
			//fwrite($fp,"players|");
	break;
	}
	if($players){
		
		echo "$messages \n*********************************\n".$players;
	}
	sleep(1);
}

if (feof($fp) === true) echo "Socket close\n";
sleep(1);
////// Get result of your game
if(checkResults($apipath,$headers)) die();


function waitVersusPending(){
        global $headers;
        global $apipath;
        $ret=curlIt($apipath.'/games/me',$headers);
        while(isset($ret->versuspending->idg)){
                echo "Versus found, waiting for opponent to join ...\n";
                $ret=curlIt($apipath.'/games/me',$headers);
                sleep(1);
        }
        if(isset($ret->gamespending->idg))
                return $ret->gamespending;
        else
                return 0;
}


function checkResults($apipath,$headers){
	global $debug;
	$ret=curlIt($apipath.'/games/checkresults/me',$headers);
	if($debug>=1) print_r($ret);
	if(isset($ret->gamesresults) && count($ret->gamesresults)>0){
        	echo "You ".$ret->gamesresults->res." : ".$ret->gamesresults->fight." (".$ret->gamesresults->idg.")\n";
        	/// get rewards or loss
        	$ret=curlIt($apipath.'/games/',$headers,'PUT',"id=".$ret->gamesresults->idg."&action=".$ret->gamesresults->res);
		if($debug>=1) print_r($ret);
		return 1;
	}
	return 0;
}

function getPlayers($ret){
	global $players;
	global $debug;
	if($debug>=1) echo "Update players \n";
	$P1=' ';
	$P2=' ';
	if(is_array($ret)){
		if($ret['ismyturn'])
			$P1='*';
		else
			$P2='*';
		
		$players=$P1.getPlayer($ret['P1']);
        	$players.=$P2.getPlayer($ret['P2']);
	}
	else{
		if($ret->ismyturn)
			$P1='*';
		else
			$P2='*';
		$players=$P1.getPlayer($ret->P1);
        	$players.=$P2.getPlayer($ret->P2);
	}
}
function getPlayer($p){
	if(is_array($p))
		return str_pad($p['name'],20)."  Life : ".$p['glif']." Action : ".$p['gaction']." ".$p['hurry']."/".$p['time']."\n";
	else
		return str_pad($p->name,20)."  Life : ".$p->glif." Action : ".$p->gaction." ".$p->hurry."/".$p->time."\n";
}

function curlIt($url,$headers,$verb='GET',$data=""){
	global $debug;
	$curl = curl_init();
	curl_setopt_array($curl, array(
  	CURLOPT_PORT => "8081",
  	CURLOPT_URL => $url,
  	CURLOPT_RETURNTRANSFER => true,
  	CURLOPT_ENCODING => "",
  	CURLOPT_MAXREDIRS => 10,
  	CURLOPT_TIMEOUT => 30,
  	CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  	CURLOPT_CUSTOMREQUEST => $verb,
  	CURLOPT_POSTFIELDS => $data,
  	CURLOPT_HTTPHEADER => $headers,
	));

	$response = curl_exec($curl);
	$err = curl_error($curl);

	curl_close($curl);
	if ($err) {
  		die("cURL Error #:" . $err);
	} else {
		if($debug>=3) var_dump($url);
		if($debug>=3) var_dump($data);
		if($debug>=3) var_dump($verb);
		if($debug>=3) var_dump($headers);
		if($debug>=3) var_dump($response);
  		return  json_decode($response);
	}
}
