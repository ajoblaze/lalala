<?php 
/* Created by a.riccia
   on 19 / 09 / 2014
*/
class Zend_View_Helper_Generate extends Zend_View_Helper_Abstract 
{
	var $PARAM_BREADCRUMB = "GEN_BREADCRUMB";
	var $PARAM_BOOKTYPE = "GEN_BOOKTYPE";
	
	public function generateBreadcrumb($array)
	{
		$li = "";
		foreach($array["item"] as $key=>$val) :
			if ($val != "active") {
				$li.="<li><a class='text-biru' href='".$val."'>".$key."</a></li>";
			} else {
				$li.="<li class='active'>".$key."</li>";
			}
			
		endforeach;
		return "<div class='".$array['class']."' style='".$array['style']."'><ul class='breadcrumb'>".$li."</ul></div>";
	}
	
	public function generateBookType($data)
	{
		switch($data['cat'])
		{
			case $data['config']->slime->category->magazine : $category = "( Magazines )";break;
			case $data['config']->slime->category->newspaper : $category = "( Newspaper )";break;
			case $data['config']->slime->category->book : $category = "( Book )";break;
			default : $category = ""; break;
		}
		return $category;
	}
	
	public function generate($param, $x = "") 
	{
		switch($param)
		{
			case $this->PARAM_BREADCRUMB :  return $this->generateBreadcrumb($x);
			case $this->PARAM_BOOKTYPE : return $this->generateBookType($x);
			default : return "";
		}
	}
}