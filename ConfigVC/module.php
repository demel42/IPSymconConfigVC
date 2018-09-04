<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

if (@constant('IPS_BASE') == null) {
    // --- BASE MESSAGE
    define('IPS_BASE', 10000);							// Base Message
    define('IPS_KERNELSHUTDOWN', IPS_BASE + 1);			// Pre Shutdown Message, Runlevel UNINIT Follows
    define('IPS_KERNELSTARTED', IPS_BASE + 2);			// Post Ready Message
    // --- KERNEL
    define('IPS_KERNELMESSAGE', IPS_BASE + 100);		// Kernel Message
    define('KR_CREATE', IPS_KERNELMESSAGE + 1);			// Kernel is beeing created
    define('KR_INIT', IPS_KERNELMESSAGE + 2);			// Kernel Components are beeing initialised, Modules loaded, Settings read
    define('KR_READY', IPS_KERNELMESSAGE + 3);			// Kernel is ready and running
    define('KR_UNINIT', IPS_KERNELMESSAGE + 4);			// Got Shutdown Message, unloading all stuff
    define('KR_SHUTDOWN', IPS_KERNELMESSAGE + 5);		// Uninit Complete, Destroying Kernel Inteface
    // --- KERNEL LOGMESSAGE
    define('IPS_LOGMESSAGE', IPS_BASE + 200);			// Logmessage Message
    define('KL_MESSAGE', IPS_LOGMESSAGE + 1);			// Normal Message
    define('KL_SUCCESS', IPS_LOGMESSAGE + 2);			// Success Message
    define('KL_NOTIFY', IPS_LOGMESSAGE + 3);			// Notiy about Changes
    define('KL_WARNING', IPS_LOGMESSAGE + 4);			// Warnings
    define('KL_ERROR', IPS_LOGMESSAGE + 5);				// Error Message
    define('KL_DEBUG', IPS_LOGMESSAGE + 6);				// Debug Informations + Script Results
    define('KL_CUSTOM', IPS_LOGMESSAGE + 7);			// User Message
}

if (!defined('vtBoolean')) {
    define('vtBoolean', 0);
    define('vtInteger', 1);
    define('vtFloat', 2);
    define('vtString', 3);
    define('vtArray', 8);
    define('vtObject', 9);
}

class ConfigVC extends IPSModule
{
    use ConfigVCCommon;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('url', '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $vpos = 0;
		$this->MaintainVariable('State', $this->Translate('State'), vtBoolean, '~Alert.Reversed', $vpos++, true);
        $this->MaintainVariable('Summary', $this->Translate('Summary of last action'), vtString, '', $vpos++, true);
        $this->MaintainVariable('Timestamp', $this->Translate('Timestamp of last action'), vtInteger, '~UnixTimestamp', $vpos++, true);

        $this->SetStatus(102);
    }

    public function GetConfigurationForm()
    {
        $formElements = [];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'url', 'caption' => 'Git-Repository'];

        $formActions = [];
        $formActions[] = ['type' => 'Label', 'label' => 'Action takes up to 1 minute (depending on amount of data)'];
        $formActions[] = ['type' => 'Button', 'label' => 'Perform', 'onClick' => 'CVC_Perform($id);'];
        $formActions[] = ['type' => 'Label', 'label' => '____________________________________________________________________________________________________'];
        $formActions[] = [
                            'type'    => 'Button',
                            'caption' => 'Module description',
                            'onClick' => 'echo "https://github.com/demel42/IPSymconConfigVC/blob/master/README.md";'
                        ];

        $formStatus = [];
        $formStatus[] = ['code' => '101', 'icon' => 'inactive', 'caption' => 'Instance getting created'];
        $formStatus[] = ['code' => '102', 'icon' => 'active', 'caption' => 'Instance is active'];
        $formStatus[] = ['code' => '104', 'icon' => 'inactive', 'caption' => 'Instance is inactive'];
        $formStatus[] = ['code' => '201', 'icon' => 'inactive (invalid configuration)', 'caption' => 'Instance is inactive (invalid configuration)'];

        return json_encode(['elements' => $formElements, 'actions' => $formActions, 'status' => $formStatus]);
    }

    public function Perform()
    {
		$b = $this->doVC('/home/pi/ipsymcon', true, $msg);
		echo 'status=' . ($b ? 'true' : 'false') . ', msg=' . $msg . PHP_EOL;
    }

	function loadFile($fname)
	{
		if (!file_exists($fname)) {
			echo "file not found";
			return false;
		}
		$ok = false;
		for ($n = 0; $n < 3; $n++) {
			$fp = fopen($fname, "r");
			if (!$fp) {
				$this->SendDebug(__FUNCTION__, 'unable to create file ' . $fname, 0);
				return false;
			}
			$pre_stat = fstat($fp);
			$n = $pre_stat['size'];
			$data = fread($fp, $n);
			if ($n != strlen($data)) {
				$this->SendDebug(__FUNCTION__, 'unable to read ' . $n . ' bytes to file ' . $fname, 0);
				return false;
			}
			if (!fclose($fp)) {
				$this->SendDebug(__FUNCTION__, 'unable to close file ' . $fname, 0);
				return false;
			}
			$post_stat = stat($fname);
			if ($pre_stat['mtime'] == $post_stat['mtime']) {
				$ok = true;
				break;
			}
			sleep ($n);
		}
		if (!$ok) {
			$this->SendDebug(__FUNCTION__, 'file changed during read', 0);
			return false;
		}
		return [ 'stat' => $pre_stat, 'data' => $data ];
	}

	function saveFile($fname, $data, $mtime, $onlyChanged)
	{
		if ($onlyChanged && file_exists($fname)) {
			$r = $this->loadFile($fname);
			if (!$r) {
				$this->SendDebug(__FUNCTION__, 'error loading file ' . $fname, 0);
				return false;
			}
			$src_stat = $r['stat'];
			$src_data = $r['data'];
			if ($src_stat['size'] == strlen($data) && $src_data == $data) {
				return true;
			}
		}
		$fp = fopen($fname, "w");
		if (!$fp) {
			$this->SendDebug(__FUNCTION__, 'unable to create file ' . $fname, 0);
			return false;
		}
		if (!fwrite($fp, $data)) {
			$this->SendDebug(__FUNCTION__, 'unable to write ' . strlen($data) . ' bytes to file ' . $fname, 0);
			return false;
		}
		if (!fclose($fp)) {
			$this->SendDebug(__FUNCTION__, 'unable to close file ' . $fname, 0);
			return false;
		}
		if ($mtime && !touch($fname, $mtime)) {
			$this->SendDebug(__FUNCTION__, 'unable to set mtime of file' . ' ' . $fname, 0);
			return false;
		}
		$this->SendDebug(__FUNCTION__, 'fname=' . $fname, 0);
		return true;
	}

	function checkDir($path, $autoCreate)
	{
		if (file_exists($path)) {
			if (!is_dir($path)) {
				$this->SendDebug(__FUNCTION__, $path . ' is not a directory', 0);
				return false;
			}
		} else {
			if (!$autoCreate) {
				$this->SendDebug(__FUNCTION__, 'missing directory ' . $path, 0);
				return false;
			}
			if (!mkdir($path)) {
				$this->SendDebug(__FUNCTION__, 'unable to create directory ' . $path, 0);
				return false;
			}
		}
		return true;
	}

	function changeDir($path)
	{
		if (!chdir($path)) {
			$this->SendDebug(__FUNCTION__, 'can\'t change to direactory ' . $path, 0);
			return false;
		}
		return true;
	}

	function execute($cmd, &$output)
	{
		$this->SendDebug(__FUNCTION__, utf8_decode($cmd), 0);

		$time_start = microtime(true);
		$data = exec($cmd, $out, $exitcode);
		$duration = floor((microtime(true) - $time_start) * 100) / 100;

		if ($exitcode) {
			$ok = false;
			$err = $data;
			$out = '';
			$output = '';
		} else {
			$ok = true;
			$err = '';
			$output = $out;
		}

		if ($ok) {
			foreach ($output as $s) {
				$this->SendDebug(__FUNCTION__, '  ' . $s, 0);
			}
		} else {
			$this->SendDebug(__FUNCTION__, ' ... , exitcode=' . $exitcode . ', err=' . $err . ',output=' . utf8_decode(print_r($output, true)), 0);
		}
		return $ok;
	}

	function doZip($dirname, &$zp, $pfxlen)
	{
		$dp = opendir($dirname);
		while ($f = readdir($dp)) {
			if ($f == '.' || $f == '..')
				continue;
			$fullpath = $dirname . DIRECTORY_SEPARATOR . $f;
			$subpath = substr($fullpath, $pfxlen);
			if (is_file($fullpath)) {
				if (!$zp->addFile($fullpath, $subpath)) {
					$this->SendDebug(__FUNCTION__, 'unable to add file ' . $fullpath . ' to zip-archive', 0);
					return false;
				}
			} elseif (is_dir($fullpath)) {
				if (!$zp->addEmptyDir($subpath)) {
					$this->SendDebug(__FUNCTION__, 'unable to add directory ' . $fullpath . ' to zip-archive', 0);
					return false;
				}
				if (!$this->doZip($fullpath, $zp, $pfxlen))
					return false;
			}
		}
		closedir($dp);
		return true;
	}

	function buildZip($sourcePath, $zipFile)
	{
		if (substr($sourcePath, 0, 1) != DIRECTORY_SEPARATOR)
			$sourcePath = '.' . DIRECTORY_SEPARATOR . $sourcePath;

		$pathInfo = pathInfo($sourcePath);
		$basename = $pathInfo['basename'];
		$dirname = $pathInfo['dirname'];
		$pfxlen = strlen($dirname) + 1;
		
		$real_mtime = 0;
		$directory = new RecursiveDirectoryIterator($sourcePath);
		$objects = new RecursiveIteratorIterator($directory);
		foreach($objects as $object) {
			$fn = $object->getFilename();
			if ($fn == '..') continue;
			$fn = $object->getPathname();
			$fn = preg_replace('/\/\.$/', '', $fn);
			$m = filemtime($fn);
			if ($m > $real_mtime)
				$real_mtime = $m;
		}

		if (file_exists($zipFile)) {
			$arch_mtime = filemtime($zipFile);
			if ($arch_mtime == $real_mtime)
				return true;
		}

		$zp = new ZipArchive();
		if (!$zp->open($zipFile, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE)) {
			$this->SendDebug(__FUNCTION__, 'unable to create zip-archiv ' . $zipFile, 0);
			return false;
		}
		if (!$zp->addEmptyDir($basename)) {
			$this->SendDebug(__FUNCTION__, 'unable to add directory ' . $basename . ' to zip-archive ' . $zipFile, 0);
			//return false;
		}
		$mtime = 0;
		if (!$this->doZip($sourcePath, $zp, $pfxlen)) {
			$this->SendDebug(__FUNCTION__, 'unable to build zip-archiv ' . $zipFile . ' for directory ' . $sourcePath, 0);
			return false;
		}
		if (!$zp->close()) {
			$this->SendDebug(__FUNCTION__, 'unable to close zip-archiv ' . $zipFile, 0);
			return false;
		}

		if (!touch($zipFile, $real_mtime)) {
			$this->SendDebug(__FUNCTION__, 'unable to set mtime of file' . ' ' . $zipFile, 0);
			return false;
		}

		return true;
	}

	function doVC($gitPath, $withModulesZip, &$msg)
	{
		$msg = '';

		$ipsScriptPath = IPS_GetKernelDir() . 'scripts';
		$ipsSettingsPath = IPS_GetKernelDir() . 'settings.json';
		$ipsModulesPath = IPS_GetKernelDir() . 'modules';

		$gitScriptDir = 'scripts';
		$gitScriptPath = $gitPath . DIRECTORY_SEPARATOR . $gitScriptDir;

		$gitSettingsName = 'settings.json';

		$gitSettingsDir = 'settings';
		$gitSettingsPath = $gitPath . DIRECTORY_SEPARATOR . $gitSettingsDir;

		$gitObjectsDir = $gitSettingsDir . DIRECTORY_SEPARATOR . 'objects';
		$gitObjectsPath = $gitPath . DIRECTORY_SEPARATOR . $gitObjectsDir;

		$gitModulesDir = 'modules';
		$gitModulesPath = $gitPath . DIRECTORY_SEPARATOR . $gitModulesDir;

		$gitModulesList = 'modules.json';

		$now = time();

		if (!$this->checkDir($gitPath, false))
			return false;

		if (!$this->checkDir($gitScriptPath, true))
			return false;

		if (!$this->checkDir($gitModulesPath, true))
			return false;

		if (!$this->checkDir($gitSettingsPath, true))
			return false;

		if (!$this->checkDir($gitObjectsPath, true))
			return false;

		if (!$this->changeDir($gitPath))
			return false;

		$time_start = microtime(true);

		// .../symcon/scripts

		$time_start_scripts = microtime(true);

		$newScripts = [];
		$filenames = scandir($ipsScriptPath, 0);
		foreach ($filenames as $filename) {
			$path = $ipsScriptPath . DIRECTORY_SEPARATOR . $filename;
			if (is_dir($path))
				continue;
			if ($filename == '__generated.inc.php')
				continue;
			$newScripts[] = $filename;
		}

		$oldScripts = [];
		$filenames = scandir($gitScriptDir, 0);
		foreach ($filenames as $filename) {
			$path = $gitScriptPath . DIRECTORY_SEPARATOR . $filename;
			if (is_dir($path))
				continue;
			$oldScripts[] = $filename;
		}

		foreach ($newScripts as $filename) {
			$src = $ipsScriptPath . DIRECTORY_SEPARATOR . $filename;
			$dst = $gitScriptDir . DIRECTORY_SEPARATOR . $filename;

			$r = $this->loadFile($src);
			if (!$r) {
				$this->SendDebug(__FUNCTION__, 'error loading file ' . $src, 0);
				continue;
			}
			$src_stat = $r['stat'];
			$src_data = $r['data'];
			$exists = file_exists($dst);
			if (!$this->saveFile($dst, $src_data, $src_stat['mtime'], true)) {
				echo "error saving file $dst\n";
				continue;
			}
			if (!$exists) {
				if (!$this->execute('git add ' . $dst, $output))
					return false;
			}
		}
		foreach ($oldScripts as $filename) {
			if (in_array($filename, $newScripts))
				continue;
			$fname = $gitScriptDir . DIRECTORY_SEPARATOR . $filename;
			if (!unlink($fname)) {
				$this->SendDebug(__FUNCTION__, 'unable to delete file ' . $fname, 0);
				continue;
			}
			if (!$this->execute('git rm ' . $fname, $output))
				return false;
		}

		$duration_scripts = floor((microtime(true) - $time_start_scripts) * 100) / 100;

		// .../symcon/settings.json

		$time_start_settings = microtime(true);

		if (!$this->changeDir($gitPath))
			return false;

		$data = file_get_contents($ipsSettingsPath);
		$r = $this->loadFile($ipsSettingsPath);
		if (!$r) {
			$this->SendDebug(__FUNCTION__, 'error loading file ' . $src, 0);
			return false;
		}
		$stat = $r['stat'];
		$data = $r['data'];

		$exists = file_exists($gitSettingsName);
		if (!$this->saveFile($gitSettingsName, $data, $stat['mtime'], false)) {
			$this->SendDebug(__FUNCTION__, 'error saving file ' . $gitSettingsName, 0);
			return false;
		}
		if (!$exists) {
			if (!$this->execute('git add ' . $gitSettingsName, $output))
				return false;
		}

		$duration_settings = floor((microtime(true) - $time_start_settings) * 100) / 100;

		// Objects

		$time_start_objects = microtime(true);

		if (!$this->changeDir($gitPath))
			return false;

		$oldObjects = [];
		$filenames = scandir($gitObjectsPath, 0);
		foreach ($filenames as $filename) {
			$path = $gitObjectsPath . DIRECTORY_SEPARATOR . $filename;
			if (is_dir($path))
				continue;
			$oldObjects[] = $filename;
		}

		$data = file_get_contents($ipsSettingsPath);
		$udata = utf8_encode ($data);
		$jdata = json_decode($udata, true);
		foreach ($jdata as $key => $elem) {
			if ($key == 'objects')
				continue;
			$selem = json_encode($elem, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			$uelem = utf8_decode($selem);
			$fname = $gitSettingsDir . DIRECTORY_SEPARATOR . $key . '.json';
			$exists = file_exists($fname);
			if (!$this->saveFile($fname, $uelem, 0, true)) {
				$this->SendDebug(__FUNCTION__, 'error saving file ' . $fname, 0);
				return false;
			}
			if (!$exists) {
				if (!$this->execute('git add ' . $fname, $output))
					return false;
			}
		}

		$newObjects = [];
		$objIDs = IPS_GetObjectList();
		foreach ($objIDs as $objID) {
			$mtime = 0;
			$obj = IPS_GetObject($objID);
			switch ($obj['ObjectType']) {
				case '0':
					break;
				case '1':
					$obj['data'] = IPS_GetInstance($objID);
					$mtime = $obj['data']['InstanceChanged'];
					$obj['data']['InstanceChanged'] = 0;
					break;
				case '2':
					$obj['data'] = IPS_GetVariable($objID);
					$mtime = $obj['data']['VariableUpdated'];
					$obj['data']['VariableChanged'] = 0;
					$obj['data']['VariableUpdated'] = 0;
					$obj['data']['VariableValue'] = '';
					break;
				case '3':
					$obj['data'] = IPS_GetScript($objID);
					$mtime = $obj['data']['ScriptExecuted'];
					$obj['data']['ScriptExecuted'] = 0;
					break;
				case '4':
					$obj['data'] = IPS_GetEvent($objID);
					$mtime = $obj['data']['LastRun'];
					$obj['data']['LastRun'] = 0;
					$obj['data']['NextRun'] = 0;
					break;
				case '5':
					$obj['data'] = IPS_GetMedia($objID);
					$mtime = $obj['data']['MediaUpdated'];
					$obj['data']['MediaUpdated'] = 0;
					break;
				case '6':
					$obj['data'] = IPS_GetLink($objID);
					break;
				default:
					break;
			}

			$newObjects[] = $objID . '.json';

			$sobj = json_encode($obj, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			if ($sobj == '') {
				$err = json_last_error();
				$this->SendDebug(__FUNCTION__, 'unable to json-encode object ' . $objID . '(error=' . $err . ')', 0);
				continue;
			}
			$uobj = utf8_decode($sobj);

			$fname = $gitObjectsDir . DIRECTORY_SEPARATOR . $objID . '.json';
			$exists = file_exists($fname);
			if (!$this->saveFile($fname, $uobj, $mtime, true)) {
				$this->SendDebug(__FUNCTION__, 'error saving file ' . $fname, 0);
				return false;
			}
			if (!$exists) {
				if (!$this->execute('git add ' . $fname, $output))
					return false;
			}
		}

		foreach ($oldObjects as $filename) {
			if (in_array($filename, $newObjects))
				continue;
			$fname = $gitObjectsDir . DIRECTORY_SEPARATOR . $filename;
			if (!unlink($fname)) {
				$this->SendDebug(__FUNCTION__, 'unable to delete file ' . $fname, 0);
				continue;
			}
			if (!$this->execute('git rm ' . $fname, $output))
				return false;
		}

		$duration_objects = floor((microtime(true) - $time_start_objects) * 100) / 100;

		// .../symcon/modules

		$time_start_modules = microtime(true);
		$duration_zipfiles = 0;

		$modules = [];

		$dirnames = scandir($ipsModulesPath, 0);
		foreach ($dirnames as $dirname) {
			if ($dirname == '.' || $dirname == '..')
				continue;
			$path = $ipsModulesPath . DIRECTORY_SEPARATOR . $dirname;
			if (!is_dir($path))
				continue;
			$this->SendDebug(__FUNCTION__, 'check module "' . $dirname . '"', 0);
			if (!$this->changeDir($path))
				return false;

			if (!$this->execute('git config --get remote.origin.url', $output))
				return false;
			$url = '';
			foreach ($output as $s) {
				$url = $s;
				break;
			}
			if ($url == '') {
				$this->SendDebug(__FUNCTION__, '  ... no git-repository-information - ignore', 0);
				continue;
			}

			if (!$this->execute('git branch', $output))
				return false;
			$branch = '';
			foreach ($output as $s) {
				if (substr($s, 0, 1) == '*') {
					$branch = substr($s, 2);
					break;
				}
			}
			if ($branch == '') {
				$this->SendDebug(__FUNCTION__, '  ... no git-branch-information - ignore', 0);
				continue;
			}

			$modules[] = [ 'name' => $dirname, 'url' => $url, 'branch' => $branch ];

			if ($withModulesZip) {
				$time_start_zipfile = microtime(true);
				if (!$this->changeDir($ipsModulesPath))
					return false;
				$path = $gitModulesPath . DIRECTORY_SEPARATOR . $dirname . '.zip';
				$exists = file_exists($path);
				if (!$this->buildZip($dirname, $path))
					return false;
				if (!$exists) {
					if (!$this->changeDir($gitPath))
						return false;
					$path = $gitModulesDir . DIRECTORY_SEPARATOR . $dirname . '.zip';
					if (!$this->execute('git add ' . $path, $output))
						return false;
				}
				$duration_zipfile = floor((microtime(true) - $time_start_zipfile) * 100) / 100;
				$duration_zipfiles += $duration_zipfile;
			}
		}

		$sdata = json_encode($modules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		$data = utf8_decode($sdata);

		$fname = $gitModulesPath . DIRECTORY_SEPARATOR . $gitModulesList;
		$exists = file_exists($fname);
		if (!$this->saveFile($fname, $data, 0, true)) {
			$this->SendDebug(__FUNCTION__, 'error saving file ' . $fname, 0);
			return false;
		}
		if (!$exists) {
			if (!$this->changeDir($gitPath))
				return false;
			$fname = $gitModulesDir . DIRECTORY_SEPARATOR . $gitModulesList;
			if (!$this->execute('git add ' . $fname, $output))
				return false;
		}

		$duration_modules = floor((microtime(true) - $time_start_modules) * 100) / 100;

		// final git-commands

		if (!$this->changeDir($gitPath))
			return false;

		$n_modified = 0;
		$n_added = 0;
		$n_deleted = 0;
		$n_untracked = 0;
		$n_erroneous = 0;

		if (!$this->execute('git status --porcelain', $output))
			return false;
		foreach ($output as $s) {
			$p = substr($s, 0, 2);
			switch ($p) {
				case ' M':
					$n_modified++;
					break;
				case 'A ':
				case 'AM':
					$n_added++;
					break;
				case 'D ':
					$n_deleted++;
					break;
				case '??':
					$n_untracked++;
					break;
				default:
					$this->SendDebug(__FUNCTION__, 'erroneous status "' . $p . '"', 0);
					$n_erroneous++;
					break;
			}
		}

		$n_commitable = $n_modified + $n_added + $n_deleted;
		if ($n_commitable) {
			$m = 'Änderungen vom ' . date('d.m.Y H:i:s', $now);
			if (!$this->execute('git commit -a -m \'' . $m . '\'', $output))
				return false;
			if (!$this->execute("git push 2>&1", $output))
				return false;
		}

		$duration = floor((microtime(true) - $time_start) * 100) / 100;

		echo __FUNCTION__, 'files: modified='. $n_modified
			. ', added=' . $n_added
			. ', deleted=' . $n_deleted
			. ', untracked=' . $n_untracked
			. ', erroneous=' . $n_erroneous
			. PHP_EOL;
		echo __FUNCTION__, 'duration: total=' . $duration . 's'
			. ', scripts=' . $duration_scripts . 's'
			. ', settings=' . $duration_settings . 's'
			. ', objects=' . $duration_objects . 's'
			. ', modules=' . $duration_modules . 's (including zip-files=' . $duration_zipfiles . 's)'
			. PHP_EOL;

		return true;
	}

	/*

	https://git-scm.com/book/de/v1/Git-auf-dem-Server-Einrichten-des-Servers
	https://www.linux.com/learn/how-run-your-own-git-server

	sudo adduser git
	<passwort eingeben und merken>
	sudo mkdir -p ~git/repositories/ipsymcon.git
	sudo cd ~git/repositories/ipsymcon.git
	sudo git init --bare
	sudo chown -R git:users  ~git/repositories


	mkdir <verzeichnis für lokales respoitory>
	cd  <verzeichnis für lokales respoitory>
	git init
	git clone ssh://git@ips-dev:/home/git/repositories/ipsymcon.git

	ssh-keygen -t rsa -b 2048
	ssh-copy-id git@ips-prod.damsky.home

	*/
}
