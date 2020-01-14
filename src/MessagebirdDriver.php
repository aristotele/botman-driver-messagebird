<?php

namespace BotMan\Drivers\Messagebird;

use MessageBird\Client;
use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Messages\Attachments\Image;
use BotMan\BotMan\Messages\Incoming\Answer;
use MessageBird\Objects\Conversation\Content;
use MessageBird\Objects\Conversation\Message;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class MessagebirdDriver extends HttpDriver
{
    const DRIVER_NAME = 'Messagebird';

    const MESSAGE_TEXT = 'text';
    const MESSAGE_TYPE_IMAGE = 'image';

    /** @var array */
    protected $messages = [];

    /** @var Client */
    protected $client;

    public function buildPayload(Request $request)
    {
        $this->payload = $request->request->all();
        $this->event = Collection::make($this->payload);
        $this->files = Collection::make($request->files->all());
        $this->config = Collection::make($this->config->get('messagebird', []));
    }

    public function getClient(\MessageBird\Common\HttpClient $httpClient = null)
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

    public function getUser(IncomingMessage $matchingMessage)
    {
        return new User($matchingMessage->getSender());
    }

    public function matchesRequest()
    {
        if (isset($this->event->get('message')['platform'])) {
            return $this->event->get('message')['platform'] == 'whatsapp';
        }

        return false;
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

                default:
                    $text = 'MESSAGE TYPE NOT HANDLED.';
                    break;
            }

            $incomingMessage->setText($text);

            $this->messages = [$incomingMessage];
        }

        return $this->messages;
    }

    public function isBot()
    {
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
        $parameters['channelId'] = $this->payload['message']['channelId'];


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

        try {
            $conversation = $this->getClient()->conversations->start($message);
        } catch (\Exception $e) {
            echo sprintf("%s: %s", get_class($e), $e->getMessage());
        }
    }

    public function messagesHandled()
    {
    }

    public function isConfigured()
    {
        return !empty($this->config->get('access_key'));
    }

    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
    }

    public function types(IncomingMessage $matchingMessage)
    {
    }

    public function typesAndWaits(IncomingMessage $matchingMessage, float $seconds)
    {
    }

    public function getConversationAnswer(IncomingMessage $message)
    {
        // need review...what does exactly do?
        $answer = Answer::create($message->getText())
            ->setValue($message->getText())
            ->setInteractiveReply(true)
            ->setMessage($message);

        return $answer;
    }

    public function hasMatchingEvent()
    {
    }
}
