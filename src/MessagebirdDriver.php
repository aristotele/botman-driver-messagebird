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
		// dd($request->getContent());

		$this->payload = $request->request->all();
		$this->event = Collection::make($this->payload);
		$this->files = Collection::make($request->files->all());
		$this->config = Collection::make($this->config->get('messagebird', []));

		$this->query_string = $request->headers->get('QUERY_STRING');
		$this->messagebird_signature = $request->headers->get('HTTP_MESSAGEBIRD_SIGNATURE');
		$this->messagebird_request_timestamp = (int) $request->headers->get('HTTP_MESSAGEBIRD_REQUEST_TIMESTAMP');
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

	public function isSignatureValid()
	{
		$requestSignature = '2bl+38H4oHVg03pC3bk2LvCB0IHFgfC4cL5HPQ0LdmI=';
		$requestTimestamp = 1547198231;
		$requestBody = '{"foo":"bar"}';

		// dd($this->payload);

		// $signedReq = SignedRequest::create(
		// 	$this->query_string,
		// 	$this->messagebird_signature,
		// 	$this->messagebird_request_timestamp,
		// 	$this->payload
		// );

		// dd($signedReq);

		$request = SignedRequest::create(
			$this->query_string,
			$this->messagebird_signature,
			$this->messagebird_request_timestamp,
			'{"contact":{"id":"30d5cb6b79984a13b7c6f990e781722a","href":"","msisdn":393383342437,"displayName":"393383342437","firstName":"","lastName":"","customDetails":{},"attributes":{},"createdDatetime":"2019-12-18T11:22:26Z","updatedDatetime":"2020-04-24T11:43:16Z"},"conversation":{"id":"6118481c213d440ba93868557fca1dc1","contactId":"30d5cb6b79984a13b7c6f990e781722a","status":"active","createdDatetime":"2019-12-18T11:22:26Z","updatedDatetime":"2020-06-09T17:37:37.936130193Z","lastReceivedDatetime":"2020-06-09T17:38:13.440541836Z","lastUsedChannelId":"11a976e320d04baaa01382783727e8c5","messages":{"totalCount":0,"href":"https://whatsapp-sandbox.messagebird.com/v1/conversations/6118481c213d440ba93868557fca1dc1/messages"}},"message":{"id":"20c2f1bf12654e49bd06af8f61e301f3","conversationId":"6118481c213d440ba93868557fca1dc1","platform":"whatsapp","to":"+393383342437","from":"+447418310508","channelId":"11a976e320d04baaa01382783727e8c5","type":"text","content":{"text":"Hello!"},"direction":"sent","status":"pending","createdDatetime":"2020-06-09T17:38:13Z","updatedDatetime":"2020-06-09T17:38:13.44772125Z"},"type":"message.created"}'
			// \json_encode($this->payload)
		);
		$validator = new RequestValidator('tN3jfxydr6DtgJhUX0zsiiGZaREoRFud');
		$result = $validator->verify($request);

		// die($result);
	}
}
