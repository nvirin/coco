<?php namespace Derduesseldorf\Fontello;

use Symfony\Component\Process\Exception\LogicException;

/**
 * Class Fontello
 * @package Derduesseldorf\Fontello
 * @author Mirko Düßeldorf <rheingestalter@gmail.com>
 * @version 1.1.0.0
 */
class Fontello
{

    /** FONTELLO_URL */
    const FONTELLO_URL = 'http://fontello.com/';

    /** @var  string $_configFile */
    protected $_configFile;

    /** @var bool $_download */
    protected $_download = false;

    /** @var string $_fontelloStorage */
    protected $_fontelloStorage;

    /** @var string $_fontelloZipArchive */
    protected $_fontelloZipArchive;

    /** @var  string $_tempFolder */
    protected $_tempFolder;

    /**
     * Construct
     * - set $_fontelloStorage
     * - set $_fontelloZipArchive
     */
    public function __construct() {
        $this->_fontelloStorage = \Config::get('fontello::config.storage');
        $this->_fontelloZipArchive = \Config::get('fontello::config.storage') . \Config::get('fontello::config.archive');
        return $this;
    }

    /**
     * Check if config file exists
     * @return bool
     */
    public function configFileExists() {
        $_fileName = \Config::get('fontello::config.file');
        $_folderName = \Config::get('fontello::config.folder');
        if (\File::exists($_folderName . $_fileName)) {
            $this->_configFile = $_folderName . $_fileName;
            return true;
        }
        return false;
    }

    /**
     * Retrieve Fontello session id
     * @return string fontello session id
     */
    public function getFontelloSessionId() {
        $_response = null;
        $_curlRequest = curl_init();
        curl_setopt($_curlRequest, CURLOPT_URL, self::FONTELLO_URL);
        curl_setopt($_curlRequest, CURLOPT_POST, true);
        curl_setopt($_curlRequest, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($_curlRequest, CURLOPT_POSTFIELDS, array(
            'config' => $this->_getConfigFileContent(),
            'url' => route('fontello.callback.import'),
        ));
        $_response = curl_exec($_curlRequest);
        curl_close($_curlRequest);
        $_curlRequest = null;
        if ($_response) {
            \Session::set(\Config::get('fontello::config.session'), $_response);
            \File::put(public_path('fontello/') . 'last_used_session.txt', $_response);
            return $_response;
        }
    }

    /**
     * Retrieve Fontello zip file
     * @return string // Hopefully
     */
    public function getFontelloZipFile() {
        $_curlRequest = curl_init();
        curl_setopt($_curlRequest, CURLOPT_URL, self::FONTELLO_URL . $this->getFontelloSession() . '/get');
        curl_setopt($_curlRequest, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($_curlRequest, CURLOPT_HEADER, false);
        $_response = curl_exec($_curlRequest);
        curl_close($_curlRequest);
        $_curlRequest = null;
        return $_response;
    }

    /**
     * Create zip file from curl response
     * @param null $data
     * @return Fontello
     */
    public function zipFontelloArchive($data = null) {
        if (\File::isDirectory(\File::deleteDirectory($this->_fontelloStorage))) {
            \File::deleteDirectory($this->_fontelloStorage);
        }
        \File::makeDirectory($this->_fontelloStorage, 0777, true, true);
        if (\File::exists($this->_fontelloZipArchive)) {
            \File::delete($this->_fontelloZipArchive);
        }
        file_put_contents($this->_fontelloZipArchive, $data);
        return $this;
    }

    /**
     * Unzip fontello archive
     * @return Fontello
     */
    public function unzipArchive() {
        if (\File::exists($this->_fontelloZipArchive)) {
            $zip = new \ZipArchive();
            $zip->open($this->_fontelloZipArchive);
            $zip->extractTo($this->_fontelloStorage);
            $zip->close();
            return $this;
        }
    }

    /**
     * cleanup structure and move files
     */
    public function moveFontelloFiles() {

        $this->_clearDirectory(array(
            'assets/fontello',
            'fontello'
        ));

        foreach (glob($this->_fontelloStorage . 'fontello-*/*', GLOB_NOSORT) as $file) {
            if (str_contains($file, 'config.json')) {
                if (\File::exists(public_path('fontello/') . 'config.json')) {
                    \File::delete(public_path('fontello/') . 'config.json');
                }
                \File::move($file, public_path('fontello/') . 'config.json');
            }
            if (is_dir($file)) {
                foreach (glob($file) as $index => $path) {
                    $fileName = explode('/', $path);
                    \File::move($path, public_path('assets/fontello/') . end($fileName));
                }
            }
        }
        \File::deleteDirectory($this->_fontelloStorage);
    }

    /**
     * Retrieve Fontello session
     * @return string|bool Fontello Sessionid
     */
    public function getFontelloSession() {
        if (\Session::has(\Config::get('fontello::config.session'))) {
            return \Session::get(\Config::get('fontello::config.session'));
        }
        return false;
    }

    /**
     * Retrieve config file
     * @return mixed
     */
    public function getConfigFile() {
        return $this->_configFile;
    }

    /**
     * Retrieve just the filename of config
     */
    public function getConfigFileName() {
        return \Config::get('fontello::config.file');
    }

    public function getLastUsedSessionId() {
        if (\File::exists(public_path('fontello/') . 'last_used_session.txt')) {
            return \File::get(public_path('fontello/') . 'last_used_session.txt');
        }
        return 'No SessionId has been retrieved yet';
    }

    /**
     * Retrieve all fontello css files as link elements
     * @return string
     */
    public function styles() {
        $styles = array();
        if (\File::isDirectory(public_path('assets/fontello/css'))) {
            foreach (glob(public_path('assets/fontello/css/') . '*', GLOB_BRACE) as $path) {
                $file = explode('/', $path);
                $styles[] = \HTML::style('public/assets/fontello/css/' . end($file));
            }

            return join("\n", $styles);
        }
    }

    /**
     * Clear Directory
     * @param string|array $directory
     */
    private function _clearDirectory($directory) {
        if (is_array($directory)) {
            $_directories = $directory;
            foreach ($_directories as $directory) {
                if (\File::isDirectory(public_path($directory))) {
                    \File::deleteDirectory(public_path($directory));
                }
                \File::makeDirectory(public_path($directory), 0777, true);
            }
        } elseif (is_string($directory)) {
            if (\File::isDirectory(public_path($directory))) {
                \File::deleteDirectory(public_path($directory));
            }
            \File::makeDirectory(public_path($directory), 0777, true);
        }
    }

    /**
     * Retrieve configfile content
     * @return string
     */
    private function _getConfigFileContent() {
        return '@' . $this->getConfigFile();
    }

}