<?php

namespace Iapps\HoldingAccountService\HoldingAccount;

use Iapps\Common\Helper\MessageBroker\BroadcastEventProducer;

class HoldingAccountCreatedEventProducer extends BroadcastEventProducer{

	protected $user_profile_id;

	public function setUserProfileId($user_profile_id)
	{
		$this->user_profile_id = $user_profile_id;
		return $this;
	}

	public function getUserProfileId()
	{
		return $this->user_profile_id;
	}

	public function getMessage()
	{
		$temp['user_profile_id'] = $this->getUserProfileId();
		return json_encode($temp);
	}

	public static function publishHoldingAccountCreated($user_profile_id)
	{
		$e = new HoldingAccountCreatedEventProducer();
		
		$e->setUserProfileId($user_profile_id);
		return $e->trigger(HoldingAccountCreatedEventType::HOLDING_ACCOUNT_CREATED, NULL, $e->getMessage());
	}
}