<?php
/**
 * Created by PhpStorm.
 * User: Chris
 * Date: 5/03/14
 * Time: 10:08 AM
 */

require_once 'CTCI_PersonInterface.php';

class CTCI_Person implements CTCI_PersonInterface
{
    protected $id;
    protected $title;
    protected $namePrefix;
    protected $firstName;
    protected $goesByName;
    protected $middleName;
    protected $lastName;
    protected $nameSuffix;
    protected $nameFormat;
    protected $syncName;
    protected $position;
    protected $syncPosition;
    protected $phone;
    protected $syncPhone;
    protected $email;
    protected $syncEmail;
    protected $facebookURL;
    protected $syncFacebookURL;
    protected $twitterURL;
    protected $syncTwitterURL;
    protected $linkedInURL;
    protected $syncLinkedInURL;

    protected $groups = array();

    public function __construct($id = 0)
    {
        $this->setId($id);
        $this->syncName = true;
        $this->syncPosition = false;
        $this->syncPhone = false;
        $this->syncEmail = false;
        $this->syncFacebookURL = false;
        $this->syncTwitterURL = false;
        $this->syncLinkedInURL = false;
        $this->groups = array();
        $this->setNameFormat();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function id()
    {
        return $this->id;
    }

    public function setTitle($value = '')
    {
        $this->title = $value;
        return $this;
    }

    public function setNamePrefix($value = '')
    {
        $this->namePrefix = $value;
        return $this;
    }

    public function setFirstName($value = '')
    {
        $this->firstName = $value;
        return $this;
    }

    public function setGoesByName($value = '')
    {
        $this->goesByName = $value;
        return $this;
    }

    public function setMiddleName($value = '')
    {
        $this->middleName = $value;
        return $this;
    }

    public function setLastName($value = '')
    {
        $this->lastName = $value;
        return $this;
    }

    public function setNameSuffix($value = '')
    {
        $this->nameSuffix = $value;
        return $this;
    }

    public function setNameFormat($format = 'F L')
    {
        $this->nameFormat = $format;
    }

    /**
     * Return the name with the given format. If format string is null or empty, will use the internal
     * format set by setNameFormat(). If goes by name does not exist, will revert to first name if
     * first name has not been used.
     * Use the following letters:
     *
     * T - title
     * P - prefix
     * F - first name
     * I - first name initial*
     * G - goes by name
     * Q - if goes by name exists, adds a space followed by goes by name surrounded by quotes (so typically write FQ)
     * M - middle name
     * J - middle name initial*
     * L - last name
     * K - last name initial*
     * S - suffix
     *
     * *initials only work if mbstring extension is enabled.
     * Note also avoid including anything format specifiers recognised by sprintf, as that may cause issues.
     *
     * Note to self: don't add a format option with lowercase 's'. That WILL break this function. Or numbers.
     * Or anything else likely to be recognised by sprintf.
     *
     * @param string $format
     * @return string
     */
    public function getName($format = '')
    {
        if ($format === null || $format === '') {
            $nameFormat = $this->nameFormat;
        } else {
            $nameFormat = $format;
        }
        // title
        $nameFormat = str_replace('T', '%1$s', $nameFormat);
        // prefix
        $nameFormat = str_replace('P', '%2$s', $nameFormat);
        // first name
        $firstNameDisplay = $this->firstName;
        $firstNameUsed = false;
        if (strpos($nameFormat, 'F') !== false) {
            $nameFormat = str_replace('F', '%3$s', $nameFormat);
            $firstNameUsed = true;
        } elseif (strpos($nameFormat, 'I') !== false) {
            // this only extracts the initial if mbstring is enabled, o.w. we risk pulling out a garbled initial letter
            $firstNameDisplay = $this->extractInitial($this->firstName);
            $nameFormat = str_replace('I', '%3$s', $nameFormat);
            $firstNameUsed = true;
        }
        // goes by name
        $goesByNameDisplay = $this->goesByName;
        if (strpos($nameFormat, 'G') !== false) {
            // if we're told to use goes by, but it doesn't exist, and first name not used, then revert to first name
            if (!$firstNameUsed && ($this->goesByName === null || $this->goesByName === '')) {
                $goesByNameDisplay = $this->firstName;
            }
            $nameFormat = str_replace('G', '%4$s', $nameFormat);
        }
        // quoted goes by name
        if ($this->goesByName !== null && $this->goesByName !== '') {
            $nameFormat = str_replace('Q', ' "%4$s"', $nameFormat);
        } else {
            $nameFormat = str_replace('Q', '', $nameFormat);
        }
        // middle name
        $middleNameDisplay = $this->middleName;
        if (strpos($nameFormat, 'M') !== false) {
            $nameFormat = str_replace('M', '%5$s', $nameFormat);
        } elseif (strpos($nameFormat, 'J') !== false) {
            $middleNameDisplay = $this->extractInitial($this->middleName);
            $nameFormat = str_replace('J', '%5$s', $nameFormat);
        }
        // last name
        $lastNameDisplay = $this->lastName;
        if (strpos($nameFormat, 'L') !== false) {
            $nameFormat = str_replace('L', '%6$s', $nameFormat);
        } elseif (strpos($nameFormat, 'K') !== false) {
            $lastNameDisplay = $this->extractInitial($this->lastName);
            $nameFormat = str_replace('K', '%6$s', $nameFormat);
        }
        // suffix
        $nameFormat = str_replace('S', '%7$s', $nameFormat);

        $name = sprintf($nameFormat,
            $this->title, $this->namePrefix, $firstNameDisplay, $goesByNameDisplay, $middleNameDisplay,
            $lastNameDisplay, $this->nameSuffix
        );
        return $name;
    }

    protected function extractInitial($name)
    {
        if (extension_loaded('mbstring')) {
            // ***IMPORTANT NOTE: this assumes data coming from the api is in UTF-8***
            // check this for future apis (and overwrite this function in child class if needed)
            return mb_substr($name, 0, 1, 'UTF-8');
        } else {
            return $name;
        }
    }

    public function setPosition($value = '')
    {
        $this->position = $value;
        return $this;
    }

    public function setPhone($value = '')
    {
        $this->phone = $value;
        return $this;
    }

    public function setEmail($value = '')
    {
        $this->email = $value;
        return $this;
    }

    public function setFacebookURL($value = '')
    {
        $this->facebookURL = $value;
        return $this;
    }

    public function setTwitterURL($value = '')
    {
        $this->twitterURL = $value;
        return $this;
    }

    public function setLinkedInURL($value = '')
    {
        $this->linkedInURL = $value;
        return $this;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function getPhone()
    {
        return $this->phone;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getFacebookURL()
    {
        return $this->facebookURL;
    }

    public function getTwitterURL()
    {
        return $this->twitterURL;
    }

    public function getLinkedInURL()
    {
        return $this->linkedInURL;
    }

    public function setSyncName($sync = true)
    {
        $this->syncName = $sync;
        return $this;
    }

    public function setSyncPosition($sync = true)
    {
        $this->syncPosition = $sync;
        return $this;
    }

    public function setSyncPhone($sync = true)
    {
        $this->syncPhone = $sync;
        return $this;
    }

    public function setSyncEmail($sync = true)
    {
        $this->syncEmail = $sync;
        return $this;
    }

    public function setSyncFacebookURL($sync = true)
    {
        $this->syncFacebookURL = $sync;
        return $this;
    }

    public function setSyncTwitterURL($sync = true)
    {
        $this->syncTwitterURL = $sync;
        return $this;
    }

    public function setSyncLinkedInURL($sync = true)
    {
        $this->syncLinkedInURL = $sync;
        return $this;
    }

    public function syncName()
    {
        return $this->syncName;
    }

    public function syncPosition()
    {
        return $this->syncPosition;
    }

    public function syncPhone()
    {
        return $this->syncPhone;
    }

    public function syncEmail()
    {
        return $this->syncEmail;
    }

    public function syncFacebookURL()
    {
        return $this->syncFacebookURL;
    }

    public function syncTwitterURL()
    {
        return $this->syncTwitterURL;
    }

    public function syncLinkedInURL()
    {
        return $this->syncLinkedInURL;
    }

    public function addGroup(CTCI_PeopleGroupInterface $group)
    {
        $this->groups[] = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroups()
    {
        return $this->groups;
    }
}