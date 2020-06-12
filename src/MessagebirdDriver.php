<?php

namespace BotMan\Drivers\Messagebird;

use BotMan\BotMan\Users\User;
use MessageBird\RequestValidator;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use MessageBird\Objects\SignedRequest;
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

		// Request Validation purpose
		$this->query_string = $request->query->all();
		$this->messagebird_signature = $request->headers->get('messagebird-signature');
		$this->messagebird_request_timestamp = (int) $request->headers->get('messagebird-request-timestamp');
		$this->request_body = $request->getContent();
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
		return !empty($this->config->get('access_key')) && !empty($this->config->get('signing_key'));
	}

	public function isSignatureValid()
	{
		$request = SignedRequest::create(
			$this->query_string,
			$this->messagebird_signature,
			$this->messagebird_request_timestamp,
			$this->request_body
		);

		$validator = new RequestValidator($this->config->get('signing_key'));

		return $validator->verify($request);
	}
}
