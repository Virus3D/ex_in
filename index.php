<?php

require_once 'vendor/autoload.php';

function set_var(&$result, $var, $type, $multibyte = false)
{
	if ($type == 'boolean')
	{
		if ($var == 'false')$var = false;
		if ($var == 'true') $var = true;
	}
	settype($var, $type);
	$result = $var;

	if ($type == 'string')
	{
		$result = trim(htmlspecialchars(str_replace(["\r\n", "\r", "\0"], ["\n", "\n", ''], $result), ENT_COMPAT, 'UTF-8'));
		$result = str_replace(['&quot;', '\"', '\\\'', '\'', '"'], '"', $result);
		$result = str_replace(['\\'], '', $result);

		if (! empty($result))
		{
			if ($multibyte)
			{
				if (! preg_match('/^./u', $result))
				{
					$result = '';
				}
			}
			else
			{
				$result = preg_replace('/[\x80-\xFF]/', '?', $result);
			}
		}

		//$result = (STRIP) ? stripslashes($result) : $result;
	}
}
/**
 * Получение переданные переменные из $ _GET, $ _POST или $ _COOKIE
 */
function request_var($var_name, $default, $multibyte = true, $cookie = false)
{
	if (! $cookie && isset($_COOKIE[$var_name]))
	{
		if (! isset($_GET[$var_name]) && ! isset($_POST[$var_name]))
		{
			return (is_array($default)) ? [] : $default;
		}
		$_REQUEST[$var_name] = isset($_POST[$var_name]) ? $_POST[$var_name] : $_GET[$var_name];
	}

	$super_global = ($cookie) ? '_COOKIE' : '_REQUEST';
	if (! isset($GLOBALS[$super_global][$var_name]) || is_array($GLOBALS[$super_global][$var_name]) != is_array($default))
	{
		return (is_array($default)) ? [] : $default;
	}

	$var = $GLOBALS[$super_global][$var_name];
	if (! is_array($default))
	{
		$type = gettype($default);
	}
	else
	{
		list($key_type, $type) = each($default);
		$type                  = gettype($type);
		$key_type              = gettype($key_type);
		if ($type == 'array')
		{
			reset($default);
			$default                       = current($default);
			list($sub_key_type, $sub_type) = each($default);
			$sub_type                      = gettype($sub_type);
			$sub_type                      = ($sub_type == 'array') ? 'NULL' : $sub_type;
			$sub_key_type                  = gettype($sub_key_type);
		}
	}

	if (is_array($var))
	{
		$_var = $var;
		$var  = [];

		foreach ($_var as $k => $v)
		{
			set_var($k, $k, $key_type);
			if ($type == 'array' && is_array($v))
			{
				foreach ($v as $_k => $_v)
				{
					if (is_array($_v))
					{
						$_v = null;
					}
					set_var($_k, $_k, $sub_key_type, $multibyte);
					set_var($var[$k][$_k], $_v, $sub_type, $multibyte);
				}
			}
			else
			{
				if ($type == 'array' || is_array($v))
				{
					$v = null;
				}
				set_var($var[$k], $v, $type, $multibyte);
			}
		}
	}
	else
	{
		set_var($var, $var, $type, $multibyte);
	}

	return $var;
}

$task = request_var('task', 'build');
$api  = new \app\api();
if ($task && method_exists($api, $task))
{
	$api->$task();
}
