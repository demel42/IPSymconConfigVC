<?php

require_once __DIR__ . '/../libs/common.php';  // globale Funktionen

if (!defined('vtBoolean')) {
    define('vtBoolean', 0);
    define('vtInteger', 1);
    define('vtFloat', 2);
    define('vtString', 3);
    define('vtArray', 8);
    define('vtObject', 9);
}

if (!defined('otCategory')) {
    define('otCategory', 0);
    define('otInstance', 1);
    define('otVariable', 2);
    define('otScript', 3);
    define('otEvent', 4);
    define('otMedia', 5);
    define('otLink', 6);
}

class ConfigVC extends IPSModule
{
    use ConfigVCCommon;

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyString('url', '');
        $this->RegisterPropertyString('user', '');
        $this->RegisterPropertyString('password', '');
        $this->RegisterPropertyInteger('port', '22');
        $this->RegisterPropertyString('path', '');
        $this->RegisterPropertyBoolean('with_webfront_user_zip', false);
        $this->RegisterPropertyBoolean('with_db', false);

        $this->CreateVarProfile('ConfigVC.Duration', vtInteger, ' sec', 0, 0, 0, 0, '');
    }

    public function ApplyChanges()
    {
        parent::ApplyChanges();

        $vpos = 0;
        $this->MaintainVariable('State', $this->Translate('State'), vtBoolean, '~Alert.Reversed', $vpos++, true);
        $this->MaintainVariable('Summary', $this->Translate('Summary of last adjustment'), vtString, '', $vpos++, true);
        $this->MaintainVariable('Duration', $this->Translate('Duration of last adjustment'), vtInteger, 'ConfigVC.Duration', $vpos++, true);
        $this->MaintainVariable('Timestamp', $this->Translate('Timestamp of last adjustment'), vtInteger, '~UnixTimestamp', $vpos++, true);

        $this->SetStatus(102);
    }

    public function GetConfigurationForm()
    {
        $formElements = [];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'url', 'caption' => 'Git-Repository'];
        $formElements[] = ['type' => 'Label', 'label' => 'for http/https and ssh'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'user', 'caption' => ' ... User'];
        $formElements[] = ['type' => 'Label', 'label' => 'for http/https only'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'password', 'caption' => ' ... Password'];
        $formElements[] = ['type' => 'Label', 'label' => 'for ssh only'];
        $formElements[] = ['type' => 'NumberSpinner', 'name' => 'port', 'caption' => ' ... Port'];
        $formElements[] = ['type' => 'ValidationTextBox', 'name' => 'path', 'caption' => 'local path'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_webfront_user_zip', 'caption' => 'save webfront/user as zip-archive'];
        $formElements[] = ['type' => 'CheckBox', 'name' => 'with_db', 'caption' => 'save database'];

        $formActions = [];
        $formActions[] = ['type' => 'Label', 'label' => 'Action takes up several minutes (depending on amount of data)'];
        $formActions[] = ['type' => 'Button', 'label' => 'Perform adjustment', 'onClick' => 'CVC_CallAdjustment($id, true);'];
        $formActions[] = ['type' => 'Button', 'label' => 'Clone Repository', 'onClick' => 'CVC_CloneRepository($id);'];
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

    public function CloneRepository()
    {
        $url = $this->ReadPropertyString('url');
        $user = $this->ReadPropertyString('user');
        $password = $this->ReadPropertyString('password');
        if (substr($url, 0, 8) == 'https://') {
            $s = substr($url, 8);
            $url = 'https://';
            if ($user != '') {
                $url .= rawurlencode($user);
                if ($password != '') {
                    $url .= ':';
                    $url .= rawurlencode($password);
                }
                $url .= '@';
            }
            $url .= $s;
        }
        if (substr($url, 0, 7) == 'http://') {
            $s = substr($url, 7);
            $url = 'https://';
            if ($user != '') {
                $url .= rawurlencode($user);
                if ($password != '') {
                    $url .= ':';
                    $url .= rawurlencode($password);
                }
                $url .= '@';
            }
            $url .= $s;
        }
        $port = $this->ReadPropertyInteger('port');
        if (substr($url, 0, 6) == 'ssh://' && $port != '') {
            $s = substr($url, 6);
            $pos = strpos($s, '/');
            $srv = substr($s, 0, $pos);
            $path = substr($s, $pos);
            $url = 'ssh://';
            if ($user != '') {
                $url .= rawurlencode($user);
                $url .= '@';
            }
            $url .= $srv;
            if ($port != '') {
                if ($port != '') {
                    $url .= ':';
                    $url .= $port;
                }
            }
            $url .= $path;
        }

        $path = $this->ReadPropertyString('path');
        $ipsPath = $path . '/' . basename($url, '.git');

        if (file_exists($ipsPath)) {
            $directory = new RecursiveDirectoryIterator($ipsPath, FilesystemIterator::SKIP_DOTS);
            $objects = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($objects as $object) {
                $fname = $object->getPathname();
                if (is_dir($fname)) {
                    if (!rmdir($fname)) {
                        $this->SendDebug(__FUNCTION__, 'unable to delete firectory ' . $fname, 0);
                        return false;
                    }
                } else {
                    if (!unlink($fname)) {
                        $this->SendDebug(__FUNCTION__, 'unable to delete file ' . $fname, 0);
                        return false;
                    }
                }
            }
            if (!rmdir($ipsPath)) {
                $this->SendDebug(__FUNCTION__, 'unable to delete firectory ' . $ipsPath, 0);
                return false;
            }
        }
        if (!$this->changeDir($path)) {
            return false;
        }
        if (!$this->execute('git clone ' . $url . ' 2>&1', $output)) {
            return false;
        }

        $name = 'IP-Symcon';
        if (!$this->execute('git config --global user.name ' . $name, $output)) {
            return false;
        }
        $email = 'root@' . gethostname();
        if (!$this->execute('git config --global user.email ' . $email, $output)) {
            return false;
        }

        return true;
    }

    public function CallAdjustment(bool $with_zip)
    {
        $this->SendDebug(__FUNCTION__, 'with_zip=' . ($with_zip ? 'true' : 'false'), 0);

        $r = $this->performAdjustment($with_zip);
        $state = $r['state'];
        $msg = isset($r['msg']) ? $r['msg'] : '';
        $duration = isset($r['duration']) ? $r['duration'] : 0;
        if ($state) {
            $log = 'status=ok, duration=' . $duration . ' sec';

            $n_modified = $r['files']['modified'];
            $n_added = $r['files']['added'];
            $n_renamed = $r['files']['renamed'];
            $n_deleted = $r['files']['deleted'];
            $n_untracked = $r['files']['untracked'];
            $n_erroneous = $r['files']['erroneous'];

            $s = '';
            if ($n_modified) {
                $s .= ($s != '' ? ', ' : '') . $n_modified . ' modified';
            }
            if ($n_added) {
                $s .= ($s != '' ? ', ' : '') . $n_added . ' added';
            }
            if ($n_renamed) {
                $s .= ($s != '' ? ', ' : '') . $n_renamed . ' renamed';
            }
            if ($n_deleted) {
                $s .= ($s != '' ? ', ' : '') . $n_deleted . ' deleted';
            }
            if ($n_untracked) {
                $s .= ($s != '' ? ', ' : '') . $n_untracked . ' untracked';
            }
            if ($n_erroneous) {
                $s .= ($s != '' ? ', ' : '') . $n_erroneous . ' erroneous';
            }
            $summary = 'affected files: ' . ($s != '' ? $s : 'no changes');
        } else {
            $log = 'status=fail';
            $summary = $msg;
        }

        IPS_LogMessage(__CLASS__ . __FUNCTION__, $log . PHP_EOL . $summary);

        $this->SetValue('State', $state);
        $this->SetValue('Summary', $summary);
        $this->SetValue('Duration', $duration);
        $this->SetValue('Timestamp', time());
    }

    private function loadFile($fname)
    {
        if (!file_exists($fname)) {
            echo 'file not found';
            return false;
        }
        $ok = false;
        for ($n = 0; $n < 3; $n++) {
            $fp = fopen($fname, 'r');
            if (!$fp) {
                $this->SendDebug(__FUNCTION__, 'unable to create file ' . $fname, 0);
                return false;
            }
            $pre_stat = fstat($fp);
            // $this->SendDebug(__FUNCTION__, 'fname=' . $fname . ', stat=' . print_r($pre_stat, true), 0);
            $n = $pre_stat['size'];
            $data = $n > 0 ? fread($fp, $n) : '';
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
            sleep($n);
        }
        if (!$ok) {
            $this->SendDebug(__FUNCTION__, 'file changed during read', 0);
            return false;
        }
        return ['stat' => $pre_stat, 'data' => $data];
    }

    private function saveFile($fname, $data, $mtime, $onlyChanged)
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
        $fp = fopen($fname, 'w');
        if (!$fp) {
            $this->SendDebug(__FUNCTION__, 'unable to create file ' . $fname, 0);
            return false;
        }
        if (strlen($data) > 0 && !fwrite($fp, $data)) {
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

    private function copyFile($src, $dst, $onlyChanged)
    {
        $r = $this->loadFile($src);
        if (!$r) {
            $this->SendDebug(__FUNCTION__, 'error loading file ' . $src, 0);
            return false;
        }
        $stat = $r['stat'];
        $data = $r['data'];
        if (!$this->saveFile($dst, $data, $stat['mtime'], $onlyChanged)) {
            $this->SendDebug(__FUNCTION__, 'error saving file ' . $dst, 0);
            return false;
        }

        return true;
    }

    private function checkDir($path, $autoCreate)
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

    private function changeDir($path)
    {
        if (!chdir($path)) {
            $this->SendDebug(__FUNCTION__, 'can\'t change to direactory ' . $path, 0);
            return false;
        }
        return true;
    }

    private function execute($cmd, &$output)
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
                $this->SendDebug(__FUNCTION__, '  ' . utf8_decode($s), 0);
            }
        } else {
            $this->SendDebug(__FUNCTION__, ' ... , exitcode=' . $exitcode . ', err=' . utf8_decode($err) . ',output=' . utf8_decode(print_r($output, true)), 0);
        }
        return $ok;
    }

    private function doZip($dirname, &$zp, $pfxlen)
    {
        $dp = opendir($dirname);
        while ($f = readdir($dp)) {
            if (substr($f, 0, 1) == '.') {
                continue;
            }
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
                if (!$this->doZip($fullpath, $zp, $pfxlen)) {
                    return false;
                }
            }
        }
        closedir($dp);
        return true;
    }

    private function buildZip($sourcePath, $zipFile, $real_mtime)
    {
        if (substr($sourcePath, 0, 1) != DIRECTORY_SEPARATOR) {
            $sourcePath = '.' . DIRECTORY_SEPARATOR . $sourcePath;
        }

        $pathInfo = pathinfo($sourcePath);
        $basename = $pathInfo['basename'];
        $dirname = $pathInfo['dirname'];
        $pfxlen = strlen($dirname) + 1;

        if (file_exists($zipFile)) {
            $arch_mtime = filemtime($zipFile);
            if ($arch_mtime == $real_mtime) {
                return true;
            }
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

    private function stripFilename($fname)
    {
        $fname = str_replace(DIRECTORY_SEPARATOR, '_', $fname);
        $fname = str_replace('.', '_', $fname);
        return $fname;
    }

    private function mtime4dir($dirname)
    {
        $mtime = filemtime($dirname);
        $directory = new RecursiveDirectoryIterator($dirname, FilesystemIterator::SKIP_DOTS);
        $objects = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($objects as $object) {
            $m = filemtime($object->getPathname());
            if ($m > $mtime) {
                $mtime = $m;
            }
        }
        return $mtime;
    }

    private function scanDir($dir)
    {
        $filelist = [];
        $filenames = scandir($dir, 0);
        foreach ($filenames as $filename) {
            $path = $dir . DIRECTORY_SEPARATOR . $filename;
            if (is_dir($path)) {
                continue;
            }
            $filelist[] = $filename;
        }
        return $filelist;
    }

    private function cleanupDir($gitPath, $oldFiles, $newFiles)
    {
        if (!$this->changeDir($gitPath)) {
            return false;
        }
        foreach ($oldFiles as $filename) {
            if (in_array($filename, $newFiles)) {
                continue;
            }
            if (!unlink($filename)) {
                $this->SendDebug(__FUNCTION__, 'unable to delete file ' . $filename, 0);
                continue;
            }
            if (!$this->execute('git rm ' . $filename, $output)) {
                return false;
            }
        }
        return true;
    }

    private function saveDir($ipsPath, $gitPath, $gitDir, $with_zip, $check4git)
    {
        $oldFiles = $this->scanDir($gitPath);
        $newFiles = [];
        $dirnames = scandir($ipsPath, 0);
        foreach ($dirnames as $dirname) {
            if (substr($dirname, 0, 1) == '.') {
                continue;
            }
            $path = $ipsPath . DIRECTORY_SEPARATOR . $dirname;
            if (!is_dir($path)) {
                continue;
            }
            $this->SendDebug(__FUNCTION__, 'check module "' . $dirname . '"', 0);
            if (!$this->changeDir($path)) {
                return false;
            }

            $mtime = $this->mtime4dir('.');

            if ($check4git && is_dir('.git')) {
                if (!$this->execute('git config --get remote.origin.url', $output)) {
                    return false;
                }
                $url = '';
                foreach ($output as $s) {
                    $url = $s;
                    break;
                }
                if ($url == '') {
                    $this->SendDebug(__FUNCTION__, '  ... no git-repository - ignore', 0);
                    continue;
                }

                if (!$this->execute('git branch', $output)) {
                    return false;
                }
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

                if (!$this->execute('git rev-parse --verify HEAD', $output)) {
                    return false;
                }
                $commitID = '';
                foreach ($output as $s) {
                    $commitID = $s;
                    break;
                }
                if ($commitID == '') {
                    $this->SendDebug(__FUNCTION__, '  ... no git-commitID-information - ignore', 0);
                    continue;
                }

                $jdata = [
                        'name'     => $dirname,
                        'url'      => $url,
                        'branch'   => $branch,
                        'commitID' => $commitID,
                        'mtime'    => $mtime
                    ];

                $fname = $gitPath . DIRECTORY_SEPARATOR . $dirname . '.json';
                if (!$this->saveJson($jdata, $fname, 0)) {
                    return ['state' => false];
                }
                $newFiles[] = $dirname . '.json';
            } else {
                if ($with_zip) {
                    $this->SendDebug(__FUNCTION__, '  ... no git-repository - build zip', 0);
                    if (!$this->changeDir($ipsPath)) {
                        return false;
                    }

                    $path = $gitPath . DIRECTORY_SEPARATOR . $dirname . '.zip';
                    if (!$this->buildZip($dirname, $path, $mtime)) {
                        return false;
                    }
                } else {
                    $this->SendDebug(__FUNCTION__, '  ... no git-repository - skip', 0);
                }
                $newFiles[] = $dirname . '.zip';
            }
        }

        return $this->cleanupDir($gitPath, $oldFiles, $newFiles);
    }

    private function saveJson($data, $fname, $mtime)
    {
        $sdata = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($sdata == '') {
            $err = json_last_error();
            $this->SendDebug(__FUNCTION__, 'unable to json-encode data for file ' . $fname . ' (error=' . $err . ')', 0);
            return false;
        }
        $udata = utf8_decode($sdata);
        if (!$this->saveFile($fname, $udata, $mtime, true)) {
            $this->SendDebug(__FUNCTION__, 'error saving file ' . $fname, 0);
            return false;
        }
        return true;
    }

    private function performAdjustment($with_zip)
    {
        $with_webfront_user_zip = $this->ReadPropertyBoolean('with_webfront_user_zip');
        $with_db = $this->ReadPropertyBoolean('with_db');

        $url = $this->ReadPropertyString('url');
        $path = $this->ReadPropertyString('path');

        $gitBasePath = $path . '/' . basename($url, '.git');

        $msg = '';

        $ipsAdditionalFiles = ['settings.json', 'php.ini'];
        $ipsBasePath = IPS_GetKernelDir();
        $ipsScriptPath = $ipsBasePath . 'scripts';
        $ipsModulesPath = $ipsBasePath . 'modules';
        $ipsMediaPath = $ipsBasePath . 'media';
        $ipsWebfrontPath = $ipsBasePath . 'webfront';
        $ipsWebfrontUserPath = $ipsWebfrontPath . DIRECTORY_SEPARATOR . 'user';
        $ipsWebfrontSkinsPath = $ipsWebfrontPath . DIRECTORY_SEPARATOR . 'skins';
        $ipsDbPath = $ipsBasePath . DIRECTORY_SEPARATOR . 'db';

        $gitScriptDir = 'scripts';
        $gitScriptPath = $gitBasePath . DIRECTORY_SEPARATOR . $gitScriptDir;

        $gitSettingsName = 'settings.json';

        $gitModulesDir = 'modules';
        $gitModulesPath = $gitBasePath . DIRECTORY_SEPARATOR . $gitModulesDir;

        $gitSettingsDir = 'settings';
        $gitSettingsPath = $gitBasePath . DIRECTORY_SEPARATOR . $gitSettingsDir;

        $gitObjectsDir = $gitSettingsDir . DIRECTORY_SEPARATOR . 'objects';
        $gitObjectsPath = $gitBasePath . DIRECTORY_SEPARATOR . $gitObjectsDir;

        $gitProfilesDir = $gitSettingsDir . DIRECTORY_SEPARATOR . 'profiles';
        $gitProfilesPath = $gitBasePath . DIRECTORY_SEPARATOR . $gitProfilesDir;

        $gitMediaDir = 'media';
        $gitMediaPath = $gitBasePath . DIRECTORY_SEPARATOR . $gitMediaDir;

        $gitWebfrontDir = 'webfront';
        $gitWebfrontPath = $gitBasePath . DIRECTORY_SEPARATOR . $gitWebfrontDir;

        $gitWebfrontUserDir = $gitWebfrontDir . DIRECTORY_SEPARATOR . 'user';
        $gitWebfrontUserPath = $gitBasePath . DIRECTORY_SEPARATOR . $gitWebfrontUserDir;

        $gitWebfrontSkinsDir = $gitWebfrontDir . DIRECTORY_SEPARATOR . 'skins';
        $gitWebfrontSkinsPath = $gitBasePath . DIRECTORY_SEPARATOR . $gitWebfrontSkinsDir;

        $gitDbDir = 'db';
        $gitDbPath = $gitBasePath . DIRECTORY_SEPARATOR . $gitDbDir;

        $now = time();

        if (!$this->checkDir($gitBasePath, false)) {
            return ['state' => false];
        }

        $dirs = [$gitScriptPath, $gitModulesPath, $gitSettingsPath, $gitObjectsPath, $gitProfilesPath, $gitMediaPath, $gitWebfrontPath, $gitWebfrontSkinsPath, $gitWebfrontUserPath, $gitDbPath];
        foreach ($dirs as $dir) {
            if (!$this->checkDir($dir, true)) {
                return ['state' => false];
            }
        }

        $time_start = microtime(true);

        // global files

        if (!$this->changeDir($gitBasePath)) {
            return ['state' => false];
        }

        foreach ($ipsAdditionalFiles as $filename) {
            $src = $ipsBasePath . DIRECTORY_SEPARATOR . $filename;
            $dst = $gitBasePath . DIRECTORY_SEPARATOR . $filename;
            if (!$this->copyFile($src, $dst, true)) {
                $this->SendDebug(__FUNCTION__, 'error copy file ' . $filename, 0);
                return ['state' => false];
            }
        }

        // .../symcon/scripts

        $oldScripts = $this->scanDir($gitScriptDir);
        $newScripts = [];
        $filenames = scandir($ipsScriptPath, 0);
        foreach ($filenames as $filename) {
            if ($filename == '__generated.inc.php') {
                continue;
            }

            $src = $ipsScriptPath . DIRECTORY_SEPARATOR . $filename;
            if (is_dir($src)) {
                continue;
            }
            $dst = $gitScriptDir . DIRECTORY_SEPARATOR . $filename;
            if (!$this->copyFile($src, $dst, true)) {
                return ['state' => false];
            }
            $newScripts[] = $filename;
        }

        if (!$this->cleanupDir($gitScriptDir, $oldScripts, $newScripts)) {
            return ['state' => false];
        }

        // Objects

        if (!$this->changeDir($gitBasePath)) {
            return ['state' => false];
        }

        $sdata = IPS_GetSnapshot();
        $udata = utf8_encode($sdata);
        $snapshot = json_decode($udata, true);

        // global optiones
        $fname = $gitSettingsDir . DIRECTORY_SEPARATOR . 'options.json';
        if (!$this->saveJson($snapshot['options'], $fname, 0)) {
            return ['state' => false];
        }

        // profiles
        $fname = $gitSettingsDir . DIRECTORY_SEPARATOR . 'profiles.json';
        if (!$this->saveJson($snapshot['profiles'], $fname, 0)) {
            return ['state' => false];
        }

        $oldProfiles = $this->scanDir($gitProfilesPath);
        $newProfiles = [];
        $profiles = $snapshot['profiles'];
        foreach ($profiles as $key => $profile) {
            $profile['name'] = $key;
            $name = $this->stripFilename($key);
            $newProfiles[] = $name . '.json';

            $fname = $gitProfilesDir . DIRECTORY_SEPARATOR . $name . '.json';
            if (!$this->saveJson($profile, $fname, 0)) {
                return ['state' => false];
            }
        }

        if (!$this->cleanupDir($gitProfilesDir, $oldProfiles, $newProfiles)) {
            return ['state' => false];
        }

        $oldObjects = $this->scanDir($gitObjectsPath);
        $newObjects = [];
        $objects = $snapshot['objects'];
        foreach ($objects as $key => $object) {
            $objID = substr($key, 2);
            $mtime = 0;
            $objType = $object['type'];
            switch ($objType) {
                case otCategory:
                    break;
                case otInstance:
                    $mtime = $object['data']['lastChange'];
                    $object['data']['lastChange'] = 0;
                    break;
                case otVariable:
                    $mtime = $object['data']['lastUpdate'];
                    $object['data']['lastChange'] = 0;
                    $object['data']['lastUpdate'] = 0;
                    $object['data']['value'] = '';
                    break;
                case otScript:
                    $mtime = $object['data']['lastExecute'];
                    $object['data']['lastExecute'] = 0;
                    break;
                case otEvent:
                    $mtime = $object['data']['lastRun'];
                    $object['data']['lastRun'] = 0;
                    $object['data']['nextRun'] = 0;
                    break;
                case otMedia:
                    $mtime = $object['data']['lastUpdate'];
                    $object['data']['lastUpdate'] = 0;
                    break;
                case otLink:
                    break;
                default:
                    break;
            }

            $newObjects[] = $objID . '.json';

            $fname = $gitObjectsPath . DIRECTORY_SEPARATOR . $objID . '.json';
            if (!$this->saveJson($object, $fname, 0)) {
                return ['state' => false];
            }
        }

        if (!$this->cleanupDir($gitObjectsPath, $oldObjects, $newObjects)) {
            return ['state' => false];
        }

        // .../symcon/modules

        if (!$this->saveDir($ipsModulesPath, $gitModulesPath, $gitModulesDir, $with_zip, true)) {
            return ['state' => false];
        }

        // .../symcon/webfront/skins

        if (!$this->saveDir($ipsWebfrontSkinsPath, $gitWebfrontSkinsPath, $gitWebfrontSkinsDir, $with_zip, true)) {
            return ['state' => false];
        }

        // .../symcon/webfront/user

        if ($with_zip && $with_webfront_user_zip) {
            $oldWebfrontUserDirs = $this->scanDir($gitWebfrontUserPath);
            $newWebfrontUserDirs = [];
            $dirnames = scandir($ipsWebfrontUserPath, 0);
            foreach ($dirnames as $dirname) {
                if (substr($dirname, 0, 1) == '.') {
                    continue;
                }
                $path = $ipsWebfrontUserPath . DIRECTORY_SEPARATOR . $dirname;
                if (!is_dir($path)) {
                    continue;
                }
                if (!$this->changeDir($ipsWebfrontUserPath)) {
                    return ['state' => false];
                }
                $mtime = $this->mtime4dir($dirname);
                $path = $gitWebfrontUserPath . DIRECTORY_SEPARATOR . $dirname . '.zip';
                if (!$this->buildZip($dirname, $path, $mtime)) {
                    return ['state' => false];
                }
                $newWebfrontUserDirs[] = $dirname . '.zip';
            }

            if (!$this->cleanupDir($gitWebfrontUserPath, $oldWebfrontUserDirs, $newWebfrontUserDirs)) {
                return ['state' => false];
            }
        }

        // .../symcon/media

        $oldMedia = $this->scanDir($gitMediaPath);
        $newMedia = [];
        $filenames = scandir($ipsMediaPath, 0);
        foreach ($filenames as $filename) {
            if (substr($filename, 0, 1) == '.') {
                continue;
            }
            $path = $ipsMediaPath . DIRECTORY_SEPARATOR . $filename;
            if (is_dir($path)) {
                continue;
            }
            $src = $ipsMediaPath . DIRECTORY_SEPARATOR . $filename;
            $dst = $gitMediaPath . DIRECTORY_SEPARATOR . $filename;
            if (!$this->copyFile($src, $dst, true)) {
                $this->SendDebug(__FUNCTION__, 'error copy file ' . $filename, 0);
                return ['state' => false];
            }
            $newMedia[] = $filename;
        }
        if (!$this->cleanupDir($gitMediaPath, $oldMedia, $newMedia)) {
            return ['state' => false];
        }

        // .../symcon/db

        if ($with_db) {
            $yearDirs = scandir($ipsDbPath, 0);
            foreach ($yearDirs as $yearDir) {
                if (substr($yearDir, 0, 1) == '.') {
                    continue;
                }
                $ipsDbYearDir = $ipsDbPath . DIRECTORY_SEPARATOR . $yearDir;
                if (!is_dir($ipsDbYearDir)) {
                    continue;
                }
                $gitDbYearDir = $gitDbPath . DIRECTORY_SEPARATOR . $yearDir;
                if (!$this->checkDir($gitDbYearDir, true)) {
                    return ['state' => false];
                }

                $monthDirs = scandir($ipsDbYearDir, 0);
                foreach ($monthDirs as $monthDir) {
                    if (substr($monthDir, 0, 1) == '.') {
                        continue;
                    }
                    $ipsDbMonthDir = $ipsDbYearDir . DIRECTORY_SEPARATOR . $monthDir;
                    if (!is_dir($ipsDbMonthDir)) {
                        continue;
                    }
                    $gitDbMonthDir = $gitDbYearDir . DIRECTORY_SEPARATOR . $monthDir;
                    if (!$this->checkDir($gitDbMonthDir, true)) {
                        return ['state' => false];
                    }

                    $oldDbDirs = $this->scanDir($gitDbMonthDir);
                    $newDbDirs = [];
                    $filenames = scandir($ipsDbMonthDir, 0);
                    foreach ($filenames as $filename) {
                        if (is_dir($filename)) {
                            continue;
                        }
                        $src = $ipsDbMonthDir . DIRECTORY_SEPARATOR . $filename;
                        $dst = $gitDbMonthDir . DIRECTORY_SEPARATOR . $filename;
                        if (!$this->copyFile($src, $dst, true)) {
                            $this->SendDebug(__FUNCTION__, 'error copy file ' . $filename, 0);
                            return ['state' => false];
                        }

                        $newDbDirs[] = $filename;
                    }
                    if (!$this->cleanupDir($gitDbMonthDir, $oldDbDirs, $newDbDirs)) {
                        return ['state' => false];
                    }
                }
            }
        }

        // final git-commands

        if (!$this->changeDir($gitBasePath)) {
            return ['state' => false];
        }

        $fn_modified = [];
        $fn_added = [];
        $fn_renamed = [];
        $fn_deleted = [];
        $fn_untracked = [];
        $fn_erroneous = [];

        if (!$this->execute('git add . 2>&1', $output)) {
            return ['state' => false];
        }
        if (!$this->execute('git status --porcelain', $output)) {
            return ['state' => false];
        }
        foreach ($output as $s) {
            $st = substr($s, 0, 2);
            $fn = substr($s, 3);
            switch ($st) {
                case ' M':
                case 'M ':
                    $fn_modified[] = $fn;
                    break;
                case 'A ':
                case 'AM':
                    $fn_added[] = $fn;
                    break;
                case 'R ':
                    $fn_renamed[] = $fn;
                    break;
                case 'D ':
                    $fn_deleted[] = $fn;
                    break;
                case '??':
                    $fn_untracked[] = $fn;
                    break;
                default:
                    $this->SendDebug(__FUNCTION__, 'erroneous status "' . $st . '"', 0);
                    $fn_erroneous[] = $fn;
                    break;
            }
        }

        $n_modified = count($fn_modified);
        $n_added = count($fn_added);
        $n_renamed = count($fn_renamed);
        $n_deleted = count($fn_deleted);
        $n_untracked = count($fn_untracked);
        $n_erroneous = count($fn_erroneous);

        $n_commitable = $n_modified + $n_added + $n_renamed + $n_deleted;
        if ($n_commitable) {
            $m = 'Änderungen vom ' . date('d.m.Y H:i:s', $now);

            $s = $m . "\n";
            $s .= "\n";
            $s .= 'betroffene Dateien:' . "\n";
            $s .= "\n";
            if ($n_modified) {
                $s .= '- geändert: ' . $n_modified . '<br>' . PHP_EOL;
                foreach ($fn_modified as $fn) {
                    $s .= '  ' . $fn . '<br>' . PHP_EOL;
                }
            }
            if ($n_added) {
                $s .= '- hinzugefügt: ' . $n_added . '<br>' . PHP_EOL;
                foreach ($fn_added as $fn) {
                    $s .= '  ' . $fn . '<br>' . PHP_EOL;
                }
            }
            if ($n_renamed) {
                $s .= '- umbenannt: ' . $n_renamed . '<br>' . PHP_EOL;
                foreach ($fn_renamed as $fn) {
                    $s .= '  ' . $fn . '<br>' . PHP_EOL;
                }
            }
            if ($n_deleted) {
                $s .= '- gelöscht: ' . $n_deleted . '<br>' . PHP_EOL;
                foreach ($fn_deleted as $fn) {
                    $s .= '  ' . $fn . '<br>' . PHP_EOL;
                }
            }
            if ($n_untracked) {
                $s .= '- unversioniert: ' . $n_untracked . '<br>' . PHP_EOL;
                foreach ($fn_untracked as $fn) {
                    $s .= '  ' . $fn . '<br>' . PHP_EOL;
                }
            }
            if ($n_erroneous) {
                $s .= '- fehlerhaft: ' . $n_erroneous . '<br>' . PHP_EOL;
                foreach ($fn_erroneous as $fn) {
                    $s .= '  ' . $fn . '<br>' . PHP_EOL;
                }
            }

            if (!$this->saveFile('README.md', $s, 0, false)) {
                $this->SendDebug(__FUNCTION__, 'error saving file README.md', 0);
                return ['state' => false];
            }

            if (!$this->execute('git commit -a -m \'' . $m . '\'', $output)) {
                return ['state' => false];
            }
            if (!$this->execute('git push 2>&1', $output)) {
                return ['state' => false];
            }
        }

        $duration = floor((microtime(true) - $time_start) * 100) / 100;

        $s = 'files: '
            . 'modified=' . $n_modified
            . ', '
            . 'added=' . $n_added
            . ', '
            . 'renamed=' . $n_renamed
            . ', '
            . 'deleted=' . $n_deleted
            . ', '
            . 'untracked=' . $n_untracked
            . ', '
            . 'erroneous=' . $n_erroneous;
        $this->SendDebug(__FUNCTION__, $s, 0);

        $s = 'duration: ' . $duration . 's';
        $this->SendDebug(__FUNCTION__, $s, 0);

        if ($msg != '') {
            $this->SendDebug(__FUNCTION__, 'msg=' . $msg, 0);
        }

        $r = [
                'state'        => true,
                'msg'          => $msg,
                'duration'     => $duration,
                'files'        => [
                    'modified'     => $n_modified,
                    'added'        => $n_added,
                    'renamed'      => $n_renamed,
                    'deleted'      => $n_deleted,
                    'untracked'    => $n_untracked,
                    'erroneous'    => $n_erroneous,
                ],
            ];
        return $r;
    }
}
