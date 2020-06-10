<?php

namespace BotMan\Drivers\Messagebird;

use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;

abstract class MessagebirdDriver extends HttpDriver
{
	public function buildPayload(Request $request)
	{
		$this->payload = $request->request->all();
		$this->event = Collection::make($this->payload);
		$this->files = Collection::make($request->files->all());
		$this->config = Collection::make($this->config->get('messagebird', []));
	}

	public function getUser(IncomingMessage $matchingMessage)
	{
		return new User($matchingMessage->getSender());
	}

	public function isBot()
	{
	}

	public function isConfigured()
	{
		return !empty($this->config->get('access_key'));
	}
}
