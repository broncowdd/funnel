<?php
/**
 * Syndication Export
 * Export RSS and atom feed to PHP array or JSON
 * PHP 5
 *
 * @author     MILLET Maxime <maxime@milletmaxime.net>
 * @license    CC-BY-SA see http://www.milletmaxime.net
 * @version    2.0 (05/26/2013)
 * @doc		   http://milletmaxime.net/syndexport/
 * Thanks to : quent1.fr
 */
 class SyndExport
{
	private $p_feed;
	private $p_type;
	private $p_count;
	private $p_items;
	private $p_ns;
	private $p_list_ns;
	private $p_ext_ns=true;
	public function __construct($feed,$isurl=true)
	{
		if($isurl)// if $feed is an url
		{
        	if(!$feed=file_get_contents($feed))
			{
				throw new Exception("Failed to open the feed",00); //file_get_contents can't open the url
        	}
		}
		if(preg_match("~<rss(.*)</rss>~si", $feed))			$this->p_type="RSS";//RSS ?
		elseif(preg_match("~<feed(.*)</feed>~si", $feed))	$this->p_type="ATOM";//ATOM ?
		else throw new Exception("This file is not a feed",01);//if the feed isn't rss or atom
		
		if($this->p_feed = new SimpleXMLElement($feed, LIBXML_NOCDATA))//new SimpleXML instance
		{
			//counts number of entries
			if($this->p_type == "RSS") $this->p_count = count($this->p_feed->channel->item);
			elseif($this->p_type == "ATOM") $this->p_count = count($this->p_feed->entry);
		}
		else throw new Exception("Failed to use the feed (SimpleXML)",02);//if SimpleXML can't open the feed
		$this->p_ns = $this->p_feed->getNamespaces(true);
	}
	public function returnType()//returns RSS or ATOM
	{
		return $this->p_type;
	}
	public function countItems()//returns number of entries
	{
		return $this->p_count;
	}
	private function extractAtomInfos($other=NULL) //Extracts ATOM information
	{
		if(!empty($this->p_feed->title))		 	$infos["title"] 	  =	(string)$this->p_feed->title;
		if(!empty($this->p_feed->logo))		 		$infos["titleImage"]  =	(string)$this->p_feed->logo;
		if(!empty($this->p_feed->icon))		 		$infos["icon"] 		  =	(string)$this->p_feed->icon;
		if(!empty($this->p_feed->subtitle)) 		$infos["description"] = (string)$this->p_feed->subtitle;
		if(!empty($this->p_feed->link["href"])) 	$infos["link"]		  = (string)$this->p_feed->link["href"];
		if(!empty($this->p_feed->language))			$infos["language"]	  = (string)$this->p_feed->language;
		if(!empty($this->p_feed->author->name)) 	$infos["author"]	  =	(string)$this->p_feed->author->name;
		if(!empty($this->p_feed->author->email))	$infos["email"] 	  = (string)$this->p_feed->author->email;
		if(!empty($this->p_feed->updated))			$infos["last"] 		  = (string)$this->p_feed->updated;
		if(!empty($this->p_feed->rights))			$infos["copyright"]	  = (string)$this->p_feed->rights;
		if(!empty($this->p_feed->generator))		$infos["generator"]	  = (string)$this->p_feed->generator;
		if(!empty($this->p_feed->$other))			$infos["other"]	 	  = (string)$this->p_feed->$other;
		if($this->p_ext_ns)
		{
			$namespaces = array();
			for($j=0;$j!=count($this->p_list_ns);$j++)
			{
				$k=$this->p_list_ns[$j];
				if(!empty($this->p_ns[$k]) && !empty($namespaces[$k]))	
				{
					$tmp = (array)$this->p_feed->children($this->p_ns["$k"]);
					if(!empty($tmp)) $namespaces[$k] =	$tmp;
				}
			}
			if(!empty($namespaces)) $infos["ns"] = $namespaces;
			unset($namespaces, $tmp);
		}
		return $infos;
	}
	private function extractRssInfos($other=NULL) //Extracts RSS information
	{
		if(!empty($this->p_feed->channel->title))		  $infos["title"]		 = (string)$this->p_feed->channel->title;
		if(!empty($this->p_feed->channel->image->url))	  $infos["titleImage"]	 = (string)$this->p_feed->channel->image->url;
		if(!empty($this->p_feed->channel->description))	  $infos["description"]	 = (string)$this->p_feed->channel->description;
		if(!empty($this->p_feed->channel->link))	 	  $infos["link"]		 = (string)$this->p_feed->channel->link;
		if(!empty($this->p_feed->channel->language))	  $infos["language"]	 = (string)$this->p_feed->channel->language;
		if(!empty($this->p_feed->channel->author))		  $infos["email"]		 = (string)$this->p_feed->channel->author;
		if(!empty($this->p_feed->channel->managingEditor))$infos["author"]	 	 = (string)$this->p_feed->channel->managingEditor;
		if(!empty($this->p_feed->channel->lastBuildDate)) $infos["last"] 	 	 = (string)$this->p_feed->channel->lastBuildDate;
		if(!empty($this->p_feed->channel->copyright))	  $infos["copyright"] 	 = (string)$this->p_feed->channel->copyright;
		if(!empty($this->p_feed->channel->generator))	  $infos["generator"] 	 = (string)$this->p_feed->channel->generator;
		if(!empty($this->p_feed->channel->$other))	  	  $infos["other"] 		 = (string)$this->p_feed->channel->$other;
		if($this->p_ext_ns)
		{
			$namespaces = array();
			for($j=0;$j!=count($this->p_list_ns);$j++)
			{
				$k=$this->p_list_ns[$j];
				if(!empty($this->p_ns[$k]))	
				{
					$tmp = (array)$this->p_feed->channel->children($this->p_ns[$k]);
					if(!empty($tmp)) $namespaces[$k] =	$tmp;
				}
			}
			if(!empty($namespaces)) $infos["ns"] = $namespaces;
			unset($namespaces, $tmp);
		}
		return $infos;
	}
	private function extractAtomItems($max,$other=NULL)//Extracts ATOM items
	{
		$this->p_feed->registerXPathNamespace('atom', $this->p_ns[""]);
		for($i=0;$i!=$max;$i++)
		{
			$entry=$this->p_feed->entry[$i];
			if(!empty($entry->title))		$items[$i]["title"] 	 = (string)$entry->title;
			if(!empty($entry->summary)) 	$items[$i]["description"]= (string)$entry->summary;
			if(!empty($entry->content)) 	$items[$i]["content"] 	 = (string)$entry->content;
			if(!empty($entry->link["href"]))$items[$i]["link"] 		 = (string)$entry->link["href"];
			if(!empty($entry->author)) 	  	$items[$i]["author"]	 = (string)$entry->author;
			if(!empty($entry->updated)) 	$items[$i]["date"]		 = (string)$entry->updated;
			if(!empty($entry->id))			$items[$i]["guid"]		 = (string)$entry->id;
			/****Start Enclosure****/
			$j = $i+1;
			$enclosure = $this->p_feed->xpath("atom:entry[$j]/atom:link[@rel='enclosure']");
			if(!empty($enclosure[0]["href"]))
			{
				$items[$i]["media"]["url"]   =	(string)$enclosure[0]["href"];
				if(!empty($enclosure[0]["type"]))	 $items[$i]["media"]["type"]=	(string)$enclosure[0]["type"];
				if(!empty($enclosure[0]["length"]))$items[$i]["media"]["length"]=	(string)$enclosure[0]["length"];
			}
			/****end Enclosure****/
			if($this->p_ext_ns)
			{
				$namespaces = array();
				for($j=0;$j!=count($this->p_list_ns);$j++)
				{
					$k=$this->p_list_ns[$j];
					if(!empty($this->p_ns[$k]) && !empty($namespaces[$k]))	
					{
						$tmp = (array)$entry->children($this->p_ns[$k]);
						if(!empty($tmp)) $namespaces[$k] =	$tmp;
					}
				}
				if(!empty($namespaces)) $items[$i]["ns"] = $namespaces;
				unset($namespaces, $tmp);
			}
		}
		if(isset($items)) return $items;
		else return array();
	}
	private function extractRssItems($max,$other=NULL)//Extracts RSS items
	{
		
		for($i=0;$i!=$max;$i++)
		{
			$item=$this->p_feed->channel->item[$i];
			if(!empty($item->title))	  		$items[$i]["title"]			= (string)$item->title;
			if(!empty($item->description))		$items[$i]["description"]	= (string)$item->description;
			if(!empty($item->link))				$items[$i]["link"] 			= (string)$item->link;
			if(!empty($item->author))			$items[$i]["author"] 		= (string)$item->author;
			if(!empty($item->pubDate))			$items[$i]["date"]			= (string)$item->pubDate;
			if(!empty($item->guid))				$items[$i]["guid"] 			= (string)$item->guid;
			if(!empty($item->$other))	 		$items[$i]["$other"]		= (string)$item->$other;
			/****Start Enclosure****/
			 	if(!empty($item->enclosure["url"]))
				{
			  		$items[$i]["media"]["url"] =(string)$item->enclosure["url"];

					if(!empty($item->enclosure["type"])){
			  			$items[$i]["media"]["type"]=(string)$item->enclosure["type"]; }

					if(!empty($item->enclosure["length"])){
			  			$items[$i]["media"]["length"]=(int)$item->enclosure["length"]; }
				}
			/****End Enclosure****/
			if($this->p_ext_ns)
			{
				for($j=0;$j!=count($this->p_list_ns);$j++)
				{
					$k=$this->p_list_ns[$j];
					if(!empty($this->p_ns[$k]))	
					{
						$tmp = (array)$item->children($this->p_ns[$k]);
						if(!empty($tmp)) $namespaces[$k] =	$tmp;
					}
				}
				if(!empty($namespaces)) $items[$i]["ns"] = $namespaces;
				unset($namespaces, $tmp);
			}
		}
		if(isset($items)) return $items;
		else return array();
	}
	public function exportInfos($type="array",$other=NULL)//function which exports feed information
	{
		if($type=="json")
		{
			if($this->p_type == "RSS") 		return json_encode($this->extractRssInfos($other));
			elseif($this->p_type == "ATOM") return json_encode($this->extractAtomInfos($other));
		}
		else
		{
			if($this->p_type == "RSS") 		return $this->extractRssInfos($other);
			elseif($this->p_type == "ATOM") return $this->extractAtomInfos($other);
		}
	}
	public function exportItems($max=20,$type="array",$other=NULL) //function which exports feed items
													               //$max unmetered when is equal -1
	{
		if($max > $this->p_count || $max==-1) $max = $this->p_count;
		if($type=="json")
		{
			if($this->p_type == "RSS") 		return json_encode($this->extractRssItems($max,$other));
			elseif($this->p_type == "ATOM") return json_encode($this->extractAtomItems($max,$other));
		}
		else
		{
			if($this->p_type == "RSS") 		return $this->extractRssItems($max,$other);
			elseif($this->p_type == "ATOM") return $this->extractAtomItems($max,$other);
		}
	}
	public function exportOtherInfo($info)
	{
		if($this->p_type == "RSS")
		{
			if(!empty($this->p_feed->channel->$info))$export = (string)$this->p_feed->channel->$info;
			else $export = NULL;
			return $export;
		}
		if($this->p_type == "ATOM")
		{
			if(!empty($this->p_feed->$info))$export = (string)$this->p_feed->$info;
			else $export = NULL;
			return $export;
		}
	}
	public function returnNamespaces()
	{
		 return $this->p_list_ns;
	}
	public function exportNamespaces($bool)
	{
		if($bool) $this->p_ext_ns = true;
		else $this->p_ext_ns = false;
	}
}
?>