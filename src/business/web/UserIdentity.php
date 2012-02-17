<?php
namespace Blocks;

/**
 * UserIdentity represents the data needed to identity a user.
 * It contains the authentication method that checks if the provided
 * data can identity the user.
 */
class UserIdentity extends \CUserIdentity
{
	private $_id;
	private $_model;
	private $_authToken;

	public $loginName;
	public $password;

	/**
	 * Constructor.
	 * @param string $loginName
	 * @param string $password
	 */
	public function __construct($loginName, $password)
	{
		$this->loginName = $loginName;
		$this->password = $password;
	}

	/**
	 * Returns the display name for the identity.
	 * The default implementation simply returns {@link loginName}.
	 * This method is required by {@link IUserIdentity}.
	 * @return string the display name for the identity.
	 */
	public function getName()
	{
		return $this->loginName;
	}

	/**
	 * Authenticates a user against the database.
	 *
	 * @return boolean whether authentication succeeds.
	 */
	public function authenticate()
	{
		$user = User::model()->find(array(
			'condition' => 'username=:userName OR email=:email',
			'params' => array(':userName' => $this->loginName, ':email' => $this->loginName),
		));

		if ($user === null)
			$this->errorCode = self::ERROR_USERNAME_INVALID;
		else
		{
			$checkPassword = Blocks::app()->security->checkPassword($this->password, $user->password, $user->enc_type);

			if (!$checkPassword)
				$this->errorCode = self::ERROR_PASSWORD_INVALID;
			else
			{
				$this->_id = $user->id;
				$this->username = $user->username;
				$this->errorCode = self::ERROR_NONE;

				$authSessionToken = crypt(uniqid(rand(), true));
				$this->_authToken = $authSessionToken;
				$user->auth_session_token = $authSessionToken;
				$user->last_login_date = DateTimeHelper::currentTime();
				if (!$user->save())
				{
					$errorMsg = '';
					foreach ($user->errors as $errorArr)
						$errorMsg .= implode(' ', $errorArr);

					throw new Exception('There was a problem logging you in:'.$errorMsg);
				}

				$this->setState('authSessionToken', $authSessionToken);
			}
	}

		return !$this->errorCode;
	}

	/**
	 * @return mixed
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @return mixed
	 */
	public function getModel()
	{
		return $this->_model;
	}
}
