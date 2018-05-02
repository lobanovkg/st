<?php
/**
 * Created by PhpStorm.
 * User: Kyryll Lobanov
 * Date: 07.01.18
 * Time: 16:30
 */

declare(strict_types=1);

namespace SocialTrackerBundle\Recording\Event\Validator;

use SocialTrackerBundle\Exception\ValidateAccountException;
use SocialTrackerBundle\Recording\Event\EventData;

/**
 * Validate social accounts
 */
class Validate
{
    const ACCOUNT_TYPE_SLUG_INSTAGRAM = 'in';
    const ACCOUNT_TYPE_SLUG_TWITTER = 'tw';

    const ERROR_MESSAGE_EMPTY_ARRAY = 'Empty array!';

    public static $accountSlugs = [
        self::ACCOUNT_TYPE_SLUG_INSTAGRAM,
        self::ACCOUNT_TYPE_SLUG_TWITTER,
    ];

    /** @var $eventData EventData Social event data */
    private $eventData;

    /** @var array Validator type */
    private $validator = [];

    /**
     * Set event data
     *
     * @param EventData $eventData Social event data
     *
     * @return $this;
     */
    public function setData(EventData $eventData)
    {
        $this->eventData = $eventData;

        return $this;
    }

    /**
     * Set instagram validator
     *
     * @param SocialValidateInterface $validator Instagram validator
     *
     * @return $this;
     */
    public function setInstagramValidator(SocialValidateInterface $validator)
    {
        $this->validator[self::ACCOUNT_TYPE_SLUG_INSTAGRAM] = $validator;

        return $this;
    }

    /**
     * Set twitter validator
     *
     * @param SocialValidateInterface $validator Twitter validator
     *
     * @return $this;
     */
    public function setTwitterValidator(SocialValidateInterface $validator)
    {
        $this->validator[self::ACCOUNT_TYPE_SLUG_TWITTER] = $validator;

        return $this;
    }

    /**
     * Validate social event data
     *
     * @return bool|string
     */
    public function validate()
    {
        try {
            $accounts = $this->eventData->getAccounts();
            $this->validateInt($this->eventData->getId());
            $this->validateInt($this->eventData->getActive());
            $this->validateArray($this->eventData->getAccounts());
            $this->validateAccountsFormat($accounts);
            $this->validateAccounts($accounts);
            $this->eventData->setAccounts($accounts);
        } catch (ValidateAccountException $e) {
            return $e->getMessage();
        }

        return true;
    }

    /**
     * Validate social account
     *
     * @param array $accounts Social account data
     *
     * @return bool
     */
    private function validateAccounts(array &$accounts): bool
    {
        foreach ($accounts as $key => $account) {
            if (!isset($account[EventData::ACCOUNT_TYPE_KEY]) || !isset($this->validator[$account[EventData::ACCOUNT_TYPE_KEY]])) {
                continue;
            }

            if (false === $this->validator[$account[EventData::ACCOUNT_TYPE_KEY]]->validateUserAccount($account[EventData::ACCOUNT_USER_NAME_KEY])) {
                unset($accounts[$key]);
            }
        }
        $this->validateArray($accounts);

        return true;
    }

    /**
     * Validate social account data format
     *
     * @param array $array Social account data
     *
     * @return bool
     */
    private function validateAccountsFormat(array &$array)
    {
        foreach ($array as $key => $value) {
            if (!isset($value[EventData::ACCOUNT_USER_NAME_KEY]) || !isset($value[EventData::ACCOUNT_TYPE_KEY])) {
                unset($array[$key]);
                continue;
            }
            if (!in_array($value[EventData::ACCOUNT_TYPE_KEY], self::$accountSlugs)) {
                unset($array[$key]);
                continue;
            }
            if (empty($value[EventData::ACCOUNT_TYPE_KEY])) {
                unset($array[$key]);
                continue;
            }
        }
        $this->validateArray($array);

        return true;
    }

    /**
     * Validate array
     *
     * @param array $array Social account info
     *
     * @return bool
     *
     * @throws ValidateAccountException
     */
    private function validateArray(array $array)
    {
        if (count($array) === 0) {
            throw new ValidateAccountException(self::ERROR_MESSAGE_EMPTY_ARRAY);
        }

        return true;
    }

    /**
     * Validate int type
     *
     * @param int $int Checked variable
     *
     * @return bool
     *
     * @throws ValidateAccountException
     */
    private function validateInt($int)
    {
        if (filter_var($int, FILTER_VALIDATE_INT) === false) {
            throw new ValidateAccountException('Variable not integer!');
        }

        return true;
    }
}
