<?php

namespace BotMan\Drivers\Messagebird;

use MessageBird\Client;
use MessageBird\Common\HttpClient;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Attachments\Audio;
use BotMan\BotMan\Messages\Attachments\Image;
use MessageBird\Objects\Conversation\Content;
use MessageBird\Objects\Conversation\Message;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class MessagebirdWhatsappDriver extends MessagebirdDriver
{
    const DRIVER_NAME = 'MessagebirdWhatsapp';

    const CONVERSATIONSAPI_ENDPOINT = 'https://conversations.messagebird.com/v1';
    const ENABLE_CONVERSATIONSAPI_WHATSAPP_SANDBOX = 'ENABLE_CONVERSATIONSAPI_WHATSAPP_SANDBOX';
    const CONVERSATIONSAPI_WHATSAPP_SANDBOX_ENDPOINT = 'https://whatsapp-sandbox.messagebird.com/v1';

    const MESSAGE_TEXT = 'text';
    const MESSAGE_TYPE_IMAGE = 'image';
    const MESSAGE_TYPE_AUDIO = 'audio';

    /** @var array */
    protected $messages = [];

    /** @var \MessageBird\Client */
    protected $client;

    /** @var \MessageBird\Common\HttpClient */
    protected $ConversationsAPIHttpClient;

    /** @var int */
    protected $clientConnectionTimeout = 10;

    /** @var int */
    protected $clientTimeout = 15;


    public function matchesRequest()
    {
        if (isset($this->event->get('message')['platform'])) {
            return ($this->event->get('message')['platform'] == 'whatsapp') && $this->isSignatureValid();
        }

        return false;
    }

    /**
     * Get Messagebird Client to interact with their API.
     *
     * @param \MessageBird\Common\HttpClient $httpClient
     * @return \MessageBird\Client
     */
    public function getClient(HttpClient $httpClient = null)
    {
        $clientConfig = $this->config->get('is_sandbox_enabled') === true
            ? Client::ENABLE_CONVERSATIONSAPI_WHATSAPP_SANDBOX
            : '';

        if (! $this->client) {
            $this->client = new Client(
                $this->config->get('access_key'),
                $httpClient,
                [$clientConfig]
            );
        }

        return $this->client;
    }

    /**
     * Get underlying HTTP client responsible of cURL calls.
     * It is configured to interact with messagebird's conversations endpoints.
     *
     * @return \MessageBird\Common\HttpClient
     */
    public function getConversationsAPIHttpClient()
    {
        if (! $this->ConversationsAPIHttpClient) {
            $clientEndpoint = $this->config->get('is_sandbox_enabled') === true
                ? Client::CONVERSATIONSAPI_WHATSAPP_SANDBOX_ENDPOINT
                : Client::CONVERSATIONSAPI_ENDPOINT;

            return new HttpClient(
                $clientEndpoint,
                $this->config->get('timeout') ?? $this->clientTimeout,
                $this->config->get('connection_timeout') ?? $this->clientConnectionTimeout
            );
        }

        return $this->ConversationsAPIHttpClient;
    }

    public function getMessages()
    {
        if (empty($this->messages)) {
            $message = $this->event->get('message');
            $text = '';

            // init message
            $sender = $message['from'];
            $recipient = $message['to'];

            $incomingMessage = new IncomingMessage($text, $sender, $recipient, $this->payload);

            // add content based upon type
            switch ($message['type']) {
                case (self::MESSAGE_TEXT):
                    $text = $message['content']['text'];
                    break;

                case (self::MESSAGE_TYPE_IMAGE):
                    $imageUrl = $message['content']['image']['url'];
                    $imageCaption = $message['content']['image']['caption'] ?? '';

                    $image = new Image($imageUrl);
                    $image->title($imageCaption);
                    $text = Image::PATTERN;
                    $incomingMessage->setImages([$image]);
                    break;

                case (self::MESSAGE_TYPE_AUDIO):
                    $audioUrl = $message['content']['audio']['url'];

                    $audio = new Audio($audioUrl);
                    $text = Audio::PATTERN;
                    $incomingMessage->setAudio([$audio]);
                    break;

                default:
                    $text = 'MESSAGE TYPE NOT HANDLED.';
                    break;
            }

            $incomingMessage->setText($text);

            $this->messages = [$incomingMessage];
        }

        return $this->messages;
    }

    /**
     * @param  OutgoingMessage $outgoingMessage
     * @param  IncomingMessage $incomingMessage
     * @param  array $additionalParameters
     * @return array
     */
    public function buildServicePayload($outgoingMessage, $incomingMessage, $additionalParameters = [])
    {
        $parameters = $additionalParameters;
        $text = '';

        $parameters['recipient'] = trim($incomingMessage->getSender(), '+'); // get phone number without '+'

        if (isset($this->payload['message']['channelId'])) {
            $parameters['channelId'] = $this->payload['message']['channelId'];
        }
        elseif (array_key_exists('sender_channel_id', $additionalParameters)) {
            $senderChannelId = $additionalParameters['sender_channel_id'];
            $parameters['channelId'] = $senderChannelId;
        }

        if ($outgoingMessage instanceof OutgoingMessage) {
            $text = $outgoingMessage->getText();
        }

        $parameters['text'] = $text;

        return $parameters;
    }

    public function sendPayload($payload)
    {
        $content = new Content();
        $content->text = $payload['text'];

        $message = new Message();
        $message->channelId = $payload['channelId'];
        $message->content = $content;
        $message->to = $payload['recipient']; // Channel-specific, e.g. MSISDN for SMS.
        $message->type = 'text';

        // may throw exception
        $conversation = $this->getClient($this->getConversationsAPIHttpClient())->conversations->start($message);
    }

    public function getConversationAnswer(IncomingMessage $message)
    {
        $answer = Answer::create($message->getText())
                ->setValue($message->getText())
                ->setInteractiveReply(true)
                ->setMessage($message);

        return $answer;
    }

    public function messagesHandled() {}

    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage) {}

    public function types(IncomingMessage $matchingMessage) {}

    public function typesAndWaits(IncomingMessage $matchingMessage, float $seconds) {}

    public function hasMatchingEvent() {}

}
