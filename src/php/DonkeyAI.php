<?
function AIprocess($init,$data,$path=array(),$depth=0,$startime=0){
	global $fp;
	global $players;
	global $debug;
	global $maxdepth;
	global $maxaitime;
	global $countsimu;
	$depth++;
	if(!$startime)
		$startime=(int)microtime(true);
	if(($timing=checkTiming($startime))==-1)
		return array();
	$loader="/\-|";
	if($debug>=1) echo "---------------->Into AIprocess ----> $depth \n";
	$allsimu=array();
	$allres=array();
	if($depth>$maxdepth){
		if($debug>=1) echo "Max depth reached\n";
		return array();
	}
	if(!isset($data['getplayable'])){
		die("Error on simulate/getplayable data \n");
	}
        foreach($data['getplayable']['playable'] as $k=>$v){
                foreach($v['tg'] as $z){
			echo "\r".$loader[rand(0,3)]. " ".$timing."/$maxaitime s - $countsimu simulations";
			if(($timing=checkTiming($startime))>=0){
                        	if($debug>=1) echo $k."\n";
                        	$dbid=str_replace('I','',$k);
                        	if($debug>=1) echo "$depth *********************************\n";
                        	if($debug>=1) echo "Simulate ".$v['name']."\n";
				$path2=array_merge($path,array(array($k,$z,$v['name'])));
                        	if($debug>=1) echo "---------------->Send simulate\n";
                        	fwrite($fp,"simulate/".json_encode($path2)."|");
                        	if($debug>=1) echo "simulate/".json_encode($path2)."|\n";
                        	$retstr=fgets($fp, 15000);
                        	$retsimu=json_decode(str_replace('|','',$retstr),true);
				$data2=$retsimu;
                        	if($debug>=1) getPlayers($retsimu['players']);
                        	if($debug>=1) echo $players;
                        	$allsimu[]=array('id'=>$k,
                            	    'name'=>$v['name'],
                                	'target'=>$z,
                                	'simulation'=>$retsimu);
				$countsimu++;
				$newres=AIprocess($init,$data2,$path2,$depth,$startime);
				$allres=$allres+$newres;
                	}
		}
        }
	if($debug>=2) print_r($allsimu);
	foreach($allsimu as $v){
		$carac=array('glif','gaction','gcon','gint','gstr','gdex','gwiz');
		$score=0;
		foreach($carac as $k){
			if(in_array($k,array('glif'))){
				$score-=2*($init['P1'][$k]-$v['simulation']['players']['P1'][$k]);
				$score+=2*($init['P2'][$k]-$v['simulation']['players']['P2'][$k]);
			}
			else{
				$score-=$init['P1'][$k]-$v['simulation']['players']['P1'][$k];
				$score+=$init['P2'][$k]-$v['simulation']['players']['P2'][$k];
				//print_r($v['simulation']['path']);
				//print_r($init['P2']);
				//print_r($v['simulation']['players']['P2']);
			}
		}
		//echo "score $score \n";
		$allres[$score]=$v['simulation']['path'];
	}
	ksort($allres);
        if($debug>=1) print_r($allres);
	return $allres;
}

function checkTiming($startime){
	global $maxaitime;
	$timing=(int)(microtime(true)-$startime);
        if($maxaitime<$timing){
                if($debug>=1) echo "Max AI time  reached\n";
                return -1;
        }
	else
		return $timing;
}

