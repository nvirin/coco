<?php namespace Derduesseldorf\Fontello\Controllers;

use Illuminate\Session\TokenMismatchException;

/**
 * Class FontelloImportController
 * Examples
 * @package Derduesseldorf\Fontello\Controllers
 * @author Mirko Düßeldorf <rheingestalter@gmail.com>
 * @version 1.1.0.0
 */
class FontelloImportController extends \BaseController {

    /** @var array $_data */
    protected $_data = array();

    /**
     * Index Action
     * @param null $fontellosession
     * @return \Illuminate\View\View
     */
    public function getIndex($fontellosession = null) {

        if(\Fontello::configFileExists()) {
            $this->_data['hasSession'] = false;
            $this->_data['configFile'] = \Fontello::getConfigFileName();
            $this->_data['lastUsedSession'] = \Fontello::getLastUsedSessionId();

            if(\Fontello::getFontelloSession()) {
                $this->_data['hasSession'] = true;
                $this->_data['fontelloSessionId'] = \Fontello::getFontelloSession();
            }
        }

        return \View::make('fontello::sites.importstart', $this->_data);
    }

    /**
     * Run Sessionid Import
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Illuminate\Session\TokenMismatchException
     */
    public function getRunImport() {
        if(\Session::token() == \Input::get('_token') && \Fontello::configFileExists()) {
            $_response = \Fontello::getFontelloSessionId();
            if($_response && is_string($_response)) {
                return \Redirect::route('fontello.start.import');
            }
        }

        else {
            throw new TokenMismatchException('Token has been changed');
        }
    }

    /**
     * Callback action
     * Retrieve data
     * setup fontello files
     * @return \Illuminate\Http\RedirectResponse
     */
    public function getCallback() {
        if($_fontelloData = \Fontello::getFontelloZipFile()) {
            try {
                \Fontello::zipFontelloArchive($_fontelloData);
                \Fontello::unzipArchive();
                \Fontello::moveFontelloFiles();
                return \Redirect::route('fontello.start.import');
            }

            catch(\Exception $e) {
                return \Redirect::route('fontello.start.import')->withErrors(array('Failed to setup fontello files'));
            }
        }
    }


}