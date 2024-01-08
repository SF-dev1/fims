<?php

class debug
{

	function do_pre($content)
	{
		echo '<pre>';
		var_dump($content);
		echo '</pre>';
	}
}
