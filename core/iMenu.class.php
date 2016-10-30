<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2012 idreamsoft.com iiimon Inc. All rights reserved.
*
* @author coolmoo <idreamsoft@qq.com>
* @site http://www.idreamsoft.com
* @licence http://www.idreamsoft.com/license.php
* @version 6.0.0
* @$Id: iMenu.class.php 2334 2014-01-04 12:18:19Z coolmoo $
*/
class iMenu {
    public $menu_array = array();
    public $href_array = array();

	function __construct() {
        $this->get_cache();
        // $this->menu_array(true);
	}
    function menu_data($path){
        $json  = file_get_contents($path);
        $json  = str_replace("<?php defined('iPHP') OR exit('What are you doing?');?>\n", '', $json);
        return json_decode($json,ture);
    }

    function menu_array($cache=false){
        $variable = array();
        foreach (glob(iPHP_APP_DIR."/*/etc/iMenu.*.php",GLOB_NOSORT) as $index=> $filename) {
            $array = $this->menu_data($filename);
            $array && $variable[]= $this->menu_id($array,$index);
        }
        if($variable){
            $variable = call_user_func_array('array_merge_recursive',$variable);
            array_walk($variable,array($this,'menu_item_unique'));
            $this->menu_item_order($variable);
            $this->menu_href_array($variable,$this->href_array);
            $this->menu_array = $variable;
            unset($variable);
            if($cache){
                $iCache = iCache::sysCache();
                $iCache->add('iCMS/iMenu/menu_array', $this->menu_array,0);
                $iCache->add('iCMS/iMenu/href_array', $this->href_array,0);
            }
        }
    }
    function cache(){
        $this->menu_array(true);
    }
    function get_cache(){
         $cache = iCache::sysCache();
         $this->menu_array  = $cache->get('iCMS/iMenu/menu_array');
         $this->href_array  = $cache->get('iCMS/iMenu/href_array');
         if(empty($this->menu_array)||empty($this->href_array)){
            $this->cache();
         }
    }
    function menu_href_array($variable,&$out,$id=null){
        // $array = array();
        foreach ($variable as $key => $value) {
            $_id = $id?$id:$value['id'];

            if(!$value['-'] && $value['href']){
                // $array[]= $value['href'];
                $out[$value['href']] = $_id;
            }
            if($value['children']){
                // $array[$value['id']]=
                $this->menu_href_array($value['children'],$out,$_id);
            }

        }
        // return $array;
    }
    function menu_item_order(&$variable){
        uasort ($variable,array($this,'array_order'));
    	foreach ($variable as $key => $value) {
    		if($value['children']){
	    		$this->menu_item_order($variable[$key]['children']);
    		}
    	}
    }
    function array_order($a,$b){
        if ( $a['order']  ==  $b['order'] ) {
            return  0 ;
        }
        return ( $a['order']  <  $b['order'] ) ? - 1  :  1 ;
        // return @strnatcmp($a['order'],$b['order']);
    }
    function menu_item_unique (&$items){
        if(is_array($items)){
            foreach ($items as $key => $value) {
                if(in_array($key, array('id','name','icon','caption','order'))){
                    is_array($value) &&$items[$key] = $value[0];
                }
                if(is_array($items['children'])){
                    array_walk ($items['children'],array($this,'menu_item_unique'));
                }
            }
        }
    }
    function menu_id($variable,$index=0){
        if(empty($variable)) return;
        if(is_array($variable)){
            $i=0;
            foreach ($variable as $key => $value) {
                $value = (array)$value;

                isset($value['order']) OR $value['order'] = $index*100+$i;
                if($value['children']){
                    $value['children'] = $this->menu_id($value['children'],$i);
                }
                $variable[$key] = $value;
                if($value['id']){
                    $variable[$value['id']]= $value;
                    unset($variable[$key]);
                }
                $i++;
            }
            return $variable;
        }else{
            return $this->menu_id($variable,$index);
        }
    }

    function href($a){
        $a['href'] && $href = __ADMINCP__.'='.$a['href'];
        $a['target']=='iPHP_FRAME' && $href.='&frame=iPHP';
        $a['href']=='__SELF__' && $href = __SELF__;
        $a['href'] OR $href = 'javascript:;';
        strstr($a['href'], 'http://') && $href = $a['href'];
        return $href;
    }
	function a($a){
		if(empty($a)||$a['-']) return;

        $a['title'] OR $a['title'] = $a['caption'];
		$a['icon'] && $icon='<i class="'.$a['icon'].'"></i> ';
		$link = '<a href="'.$this->href($a).'"';
		$a['title']  && $link.= ' title="'.$a['title'].'"';
		$link.= ' class="tip-bottom '.$a['a_class'].'"';
		$link.='>';
		return $link.$icon.' '.$a['caption'].'</a>';
	}
    function search_href(){
        $path =  str_replace(__ADMINCP__.'=', '', iPHP_REQUEST_URI);
        foreach ($this->href_array as $key => $value) {
            if($path==$key){
                return $value;
            }
        }
        foreach ($this->href_array as $key => $value) {
            if(strpos($path,$key)!==false){
                return $value;
            }
        }
    }
    function app_memu($app){
        $path  = iPHP_APP_DIR."/{$app}/etc/iMenu.main.php";
        $array = $this->menu_data($path);
        $array = $this->menu_id($array);
        $key   = $this->search_href();
        $array = $array[$key]['children'][$app]['children'];

        foreach((array)$array AS $_array) {
            $nav.= $this->li('sidebar',$_array,0);
        }
        return $nav;

    }
	function sidebar(){
        $key= $this->search_href();
        $menu_array = $this->menu_array[$key]['children'];
        foreach((array)$menu_array AS $array) {
            $nav.= $this->li('sidebar',$array,0);
        }
        return $nav;
	}
	function nav(){
        foreach((array)$this->menu_array AS $array) {
            $nav.= $this->li('nav',$array,0);
        }
		return $nav;
	}

    function children_count($variable){
        $count = 0;
        foreach ((array)$variable as $key => $value) {
            $value['-'] OR $count++;
        }
        return $count;
    }
	function li($mType,$a,$level = 0){
		// if(!admincp::MP($id)) return false;

        $a = (array)$a;
		if($a['-']){
			return '<li data-order="'.$a['order'].'" class="'.(($level||$mType=='sidebar')?'divider':'divider-vertical').'"></li>';
		}

        $href = $this->href($a);
		$children = count($a['children']);

		if($children && $mType=='nav'){
			$a['class']	= $level?'dropdown-submenu':'dropdown';
			$a['a_class'] = 'dropdown-toggle';
			$level==0 && $caret = true;
		}

		if($mType=='sidebar' && $children && $level==0){
			$href		= 'javascript:;';
			$a['class']	= 'submenu';
			$label		= '<span class="label">'.$this->children_count($a['children']).'</span>';
		}

		if($mType=='tab'){
			$href = "#".$a['href'];
		}


		$li = '<li class="'.$a['class'].'" title="'.$a['title'].'" menu-level="'.$level.'" menu-id="'.$a['id'].'" menu-order="'.$a['order'].'">';

		$link = '<a href="'.$href.'"';
		$a['title']  && $link.= ' title="'.$a['title'].'"';
		$a['a_class']&& $link.= ' class="'.$a['a_class'].'"';
		$a['target'] && $link.= ' target="'.$a['target'].'"';

		if($a['data-toggle']=='modal'){
			$link.= ' data-toggle="modal"';
			$link.= ' data-target="#iCMS-MODAL"';
			$a['data-meta']  && $link.= " data-meta='".$a['data-meta']."'";

		}elseif($mType=='nav'){
			$children && $link.= ' data-toggle="dropdown"';
		}elseif($mType=='tab'){
			$link.= ' data-toggle="tab"';
		}
		$link.=">";
		$li.=$link;
		$a['icon'] && $li.='<i class="fa fa-'.$a['icon'].'"></i> ';
		$li.='<span>'.$a['caption'].'</span>'.$label;
		$caret && $li.='<b class="caret"></b>';
		$li.='</a>';
		if($children){
			$SMli	= '';
			foreach((array)$a['children'] AS $id=>$ca) {
				$SMli.= $this->li($mType,$ca,$level+1);
			}
			$mType =='nav' && $SMul='<ul class="dropdown-menu">'.$SMli.'</ul>';
			if($mType=='sidebar'){
				$SMul = $level>1?$SMli:'<ul style="display: none;">'.$SMli.'</ul>';
			}
		}
		$li.=$SMul.'</li>';
		return $li;
	}

    function check_power($p){
    	return is_array($p)?array_intersect((string)$p,$this->power):in_array((string)$p,$this->power);
    }
}
