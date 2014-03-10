<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 4/03/14
 * Time: 12:29 PM
 */

require_once 'CTCI_GeneralSettingsInterface.php';
require_once 'CTCI_F1APISettingsInterface.php';
require_once 'CTCI_F1PeopleSyncSettingsInterface.php';

class CTCI_SettingsManager
    implements CTCI_GeneralSettingsInterface,
        CTCI_F1APISettingsInterface,
        CTCI_F1PeopleSyncSettingsInterface
{

    const F1PEOPLESYNCENABLED_OPT = 'ctci_f1_people_sync_enabled';
    const F1CONSUMERKEY_OPT = 'ctci_f1_consumer_key';
    const F1CONSUMERSECRET_OPT = 'ctci_f1_consumer_secret';
    const F1USERNAME_OPT = 'ctci_f1_username';
    const F1PASSWORD_OPT = 'ctci_f1_password';
    const F1CHURCHCODE_OPT = 'ctci_f1_church_code';
    const F1MODE_OPT = 'ctci_f1_mode';
    const F1MODE_OPTVALUE_STAGING = 'staging';
    const F1MODE_OPTVALUE_PRODUCTION = 'production';
    const F1SYNCGROUPS_OPT = 'ctci_f1_sync_groups';
    const F1PEOPLELISTS_OPT = 'ctci_f1_people_lists';

    protected $dbal;

    protected $f1PeopleSyncEnabled;
    protected $f1ConsumerKey;
    protected $f1ConsumerSecret;
    protected $f1Username;
    protected $f1Password;
    protected $f1ChurchCode;
    protected $f1Mode;
    protected $f1ServerBaseURL;
    protected $f1SyncGroups;
    protected $f1PeopleLists;

    public function __construct(CTCI_WPALInterface $dbal)
    {
        $this->dbal = $dbal;
        $this->f1PeopleSyncEnabled = $this->dbal->getOption(self::F1PEOPLESYNCENABLED_OPT);
        // if not set in config source file, retrieve from database
        if (!is_null(CTCI_F1AppConfig::$consumer_key) && CTCI_F1AppConfig::$consumer_key !== '') {
            $this->f1ConsumerKey = CTCI_F1AppConfig::$consumer_key;
        } else {
            $this->f1ConsumerKey = $this->dbal->getOption(self::F1CONSUMERKEY_OPT);
        }
        if (!is_null(CTCI_F1AppConfig::$consumer_secret) && CTCI_F1AppConfig::$consumer_secret !== '') {
            $this->f1ConsumerSecret = CTCI_F1AppConfig::$consumer_secret;
        } else {
            $this->f1ConsumerSecret = $this->dbal->getOption(self::F1CONSUMERSECRET_OPT);
        }
        if (!is_null(CTCI_F1AppConfig::$username) && CTCI_F1AppConfig::$username !== '') {
            $this->f1Username = CTCI_F1AppConfig::$username;
        } else {
            $this->f1Username = $this->dbal->getOption(self::F1USERNAME_OPT);
        }
        if (!is_null(CTCI_F1AppConfig::$password) && CTCI_F1AppConfig::$password !== '') {
            $this->f1Password = CTCI_F1AppConfig::$password;
        } else {
            $this->f1Password = $this->dbal->getOption(self::F1PASSWORD_OPT);
        }
        if (!is_null(CTCI_F1AppConfig::$base_url) && CTCI_F1AppConfig::$base_url !== '') {
            $this->f1ServerBaseURL = CTCI_F1AppConfig::$base_url;
        } else {
            switch ($this->dbal->getOption(self::F1MODE_OPT)) {
                case self::F1MODE_OPTVALUE_STAGING:
                    $this->f1ServerBaseURL = sprintf('https://%s.staging.fellowshiponeapi.com', $this->dbal->getOption(self::F1CHURCHCODE_OPT));
                    break;
                case self::F1MODE_OPTVALUE_PRODUCTION:
                    $this->f1ServerBaseURL = sprintf('https://%s.portal.fellowshiponeapi.com', $this->dbal->getOption(self::F1CHURCHCODE_OPT));
                    break;
            }
        }
        $this->f1SyncGroups = $this->dbal->getOption(self::F1SYNCGROUPS_OPT);
        $this->f1PeopleLists = $this->dbal->getOption(self::F1PEOPLELISTS_OPT);
    }

    /************************************
     * General settings
     ***********************************/

    public function f1PeopleSyncEnabled()
    {
        return $this->f1PeopleSyncEnabled;
    }

    /************************************
     * F1 General settings
     ************************************/

    public function getF1ConsumerKey()
    {
        return $this->f1ConsumerKey;
    }

    public function getF1ConsumerSecret()
    {
        return $this->f1ConsumerSecret;
    }

    public function getF1Username()
    {
        return $this->f1Username;
    }

    public function getF1Password()
    {
        return $this->f1Password;
    }

    /*public function getF1ChurchCode()
    {
        return $this->f1ChurchCode;
    }*/

    public function getF1ServerBaseURL()
    {
        return $this->f1ServerBaseURL;
    }

    /***************************************
     * F1 People Sync settings
     ***************************************/

    public function f1SyncGroups()
    {
        return $this->f1SyncGroups;
    }

    public function getF1PeopleLists()
    {
        return $this->f1PeopleLists;
    }

    public function f1SyncPersonPhone()
    {
        // TODO: Implement f1SyncPersonPhone() method.
    }

    public function f1SyncPersonEmail()
    {
        // TODO: Implement f1SyncPersonEmail() method.
    }

    public function f1SyncPersonFacebookURL()
    {
        // TODO: Implement f1SyncPersonFacebookURL() method.
    }

    public function f1SyncPersonTwitterURL()
    {
        // TODO: Implement f1SyncPersonTwitterURL() method.
    }

    public function f1SyncPersonLinkedInURL()
    {
        // TODO: Implement f1SyncPersonLinkedInURL() method.
    }
}