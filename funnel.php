<?php 
# Funnel (feed tunnel)
# @version: 1.0
# @author: bronco@warriordudimanche.net
# @url: http://www.warriordudimanche.net
# 
# 

# Agréger plusieurs flux rss en un seul en conservant la hierarchie des dates.
# (utilise l'excellente lib syndexport.php http://milletmaxime.net/syndexport/ )
# 

## CONFIG
$feeds=array(
	'http://www.olissea.com/mb/1-links/?do=rss',
	'http://lehollandaisvolant.net/rss.php?mode=links',
	'http://sebsauvage.net/links/index.php?do=rss',
	);

date_default_timezone_set ('Europe/Paris');
define('FUNNEL_FEED_NAME','Warriordudimanche: le flux complet');
define('FUNNEL_FEED_DESCRIPTION','Tout ce qui est posté par Bronco sur WDD');
define('WEBSITE_REFERENCE_URL','http://warriordudimanche.net');
define('FUNNEL_FEED_URL','http://'.$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF']);
define('ALLOW_GET',false);
## TEMPLATES
$r="\n";
define('TPL_RSS','<?xml version="1.0" encoding="utf-8" ?>'.$r.'<rss version="2.0"  xmlns:content="http://purl.org/rss/1.0/modules/content/">'.$r.'<channel>'.$r.'<title><![CDATA['.FUNNEL_FEED_NAME.']]></title>'.$r.'<link>'.FUNNEL_FEED_URL.'</link>'.$r.'<description>'.FUNNEL_FEED_DESCRIPTION.'</description>'.$r);
define('TPL_RSS_ITEM','<item>'.$r.'<title><![CDATA[#TITLE]]></title>'.$r.'<guid isPermaLink="false"><![CDATA[#GUID]]></guid>'.$r.'<link>#LIEN</link>'.$r.'<description><![CDATA[#DESCRIPTION]]></description>'.$r.'<pubDate>#DATE</pubDate>'.$r.'</item>'.$r);
define('TPL_RSS_FOOTER','</channel></rss>'.$r);
## FUNCTIONS
function aff($a,$stop=true){echo 'Arret a la ligne '.__LINE__.' du fichier '.__FILE__.'<pre>';var_dump($a);echo '</pre>';if ($stop){exit();}}
function file_curl_contents($url){$ch = curl_init();curl_setopt($ch, CURLOPT_HEADER, 0);curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,  FALSE);curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);curl_setopt($ch, CURLOPT_URL, $url);if (!ini_get("safe_mode") && !ini_get('open_basedir') ) {curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);}curl_setopt($ch, CURLOPT_MAXREDIRS, 10); $data = curl_exec($ch);curl_close($ch);return $data;}  
function load_feed($url){
	try {
		$flux=file_curl_contents($url);
		$flux =new SyndExport($flux,false);
		$type = $flux->returnType();
	}
	catch(Exception $e){
		return false;
	}
	$contenu['infos']=$flux->exportInfos();
	$contenu['items']=$flux->exportItems(-1);
	return $contenu;
}
function make_rss($array){
	header("Content-Type: application/rss+xml");
	echo TPL_RSS;
	foreach($array as $key=>$item){
		if(!isset($item['date'])){$item['date']='';}
		if(!isset($item['description'])){$item['description']='';}
		if(!isset($item['guid'])){$item['guid']='';}
		if(!isset($item['link'])){$item['link']='';}
		if(!isset($item['title'])){$item['title']='';}

		$a=array(
			'#DATE'=>$item['date'],
			'#DESCRIPTION'=>$item['description'].'<br/><a href="'.$item['source_url'].'">[via '.$item['source'].']</a>',
			'#GUID'=>$item['guid'],
			'#LIEN'=>$item['link'],
			'#TITLE'=>$item['title'],
		);
		echo str_replace(array_keys($a),array_values($a),TPL_RSS_ITEM);
	}
	echo TPL_RSS_FOOTER;
}



include('syndexport.php');

## ENGINE
$funnel_array=array();
if (isset($_GET['feeds'])&&ALLOW_GET){
	$feeds=explode(' ',urldecode($_GET['feeds']));
}elseif(!isset($feeds)||count($feeds)==0){
	exit('no feeds given');
}

foreach ($feeds as $feed){ # pour chaque flux
	if ($contenu=load_feed($feed)){
		foreach($contenu['items'] as $item){ 
			# on ajoute les items à un tableau commun, 
			# avec la date comme clé (classement global ultérieur)
			$item['source']=$contenu['infos']['title'];
			if(isset($contenu['infos']['link'])){$item['source_url']=$contenu['infos']['link'];}
			$funnel_array[strtotime($item['date']).'_'.$contenu['infos']['title']]=$item;
		}
	}
}
krsort($funnel_array);
make_rss($funnel_array);






?>