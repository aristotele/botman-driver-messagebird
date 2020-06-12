<?php

namespace Tests;

use Mockery as m;
use MessageBird\Client;
use Illuminate\Support\Str;
use BotMan\BotMan\Http\Curl;
use PHPUnit\Framework\TestCase;
use MessageBird\Common\HttpClient;
use BotMan\BotMan\Messages\Attachments\Image;
use Symfony\Component\HttpFoundation\Request;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;
use BotMan\Drivers\Messagebird\MessagebirdWhatsappDriver;

class MessagebirdWhatsappDriverTest extends TestCase
{
    const DAVID_NUMBER = '393381234567';
    const SANDBOX_NUMBER = '447418310508';
    const SANDBOX_CHANNEL_ID = '1cf5b8c9f58b499fa3cdafaaaaaaaaaa';

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver();

        $this->assertSame('MessagebirdWhatsapp', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request_with_signature()
    {
        $driver = $this->getValidDriverWith('text');

        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getValidDriverWith('text');
        $this->assertTrue($driver->matchesRequest());

        $driver = $this->getValidDriverWith('image');
        $this->assertTrue($driver->matchesRequest());

        $driver = $this->getValidDriverWith('audio');
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_can_handle_incoming_message_of_type_text()
    {
        $driver = $this->getValidDriverWith('text');
        $this->assertTrue(is_array($driver->getMessages()));
        $this->assertNotEmpty($driver->getMessages());

        /** @var IncomingMessage */
        $message = $driver->getMessages()[0];
        $this->assertInstanceOf(IncomingMessage::class, $message);
        $this->assertEquals('text', $message->getPayload()['message']['type']);

        $this->assertEquals('Hei dude, from postman!', $message->getText());
    }

    /** @test */
    public function it_can_handle_incoming_message_of_type_image()
    {
        $driver = $this->getValidDriverWith('image');
        $this->assertTrue(is_array($driver->getMessages()));
        $this->assertNotEmpty($driver->getMessages());

        /** @var IncomingMessage */
        $message = $driver->getMessages()[0];
        $this->assertInstanceOf(IncomingMessage::class, $message);
        $this->assertNotEmpty($message->getImages());

        $image = $message->getImages()[0];
        $this->assertEquals(Image::PATTERN, $message->getText());
        $this->assertEquals(
            'https://internationaltreefoundation.org/wp-content/uploads/2016/05/tree-576848_1280.png',
            $image->getUrl()
        );

        $this->assertEquals('Just a tree...', $image->getTitle());
    }

    /** @test */
    public function it_can_handle_incoming_message_of_type_audio()
    {
        $driver = $this->getValidDriverWith('audio');
        $this->assertTrue(is_array($driver->getMessages()));
        $this->assertNotEmpty($driver->getMessages());

        /** @var IncomingMessage */
        $message = $driver->getMessages()[0];
        $this->assertInstanceOf(IncomingMessage::class, $message);
        $this->assertEquals('audio', $message->getPayload()['message']['type']);

        $this->assertTrue(Str::startsWith($message->getAudio()[0]->getUrl(), 'http'));
    }

    /** @test */
    public function it_returns_the_user_object()
    {
        $driver = $this->getValidDriverWith('text');
        $message = $driver->getMessages()[0];
        $user = $driver->getUser($message);

        $this->assertSame('+' . self::SANDBOX_NUMBER, $user->getId());
    }

    /** @test */
    public function it_is_configured()
    {
        $driver = $this->getValidDriverWith('text');

        $this->assertTrue($driver->isConfigured(), "Put your correct access_key @getDriver()");
    }

    /** @test */
    public function it_returns_the_sender_phone_number()
    {
        $driver = $this->getValidDriverWith('image');

        $this->assertSame('+447418310508', $driver->getMessages()[0]->getSender());
    }

    /** @test */
    public function it_returns_the_recipient_phone_number()
    {
        $driver = $this->getValidDriverWith('image');

        $this->assertSame('+393381234567', $driver->getMessages()[0]->getRecipient());
    }

    /** @test */
    public function it_can_build_payload()
    {
        $driver = $this->getValidDriverWith('image');

        $incomingMessage = new IncomingMessage(
            'message from Aris',
            self::DAVID_NUMBER,
            self::SANDBOX_NUMBER
        );
        $outgoingMessage = new OutgoingMessage('test reply from sandbox');

        $payload = $driver->buildServicePayload(
            $outgoingMessage,
            $incomingMessage
        );

        $this->assertEquals($outgoingMessage->getText(), $payload['text']);

        // destinatario
        $this->assertEquals(self::DAVID_NUMBER, $payload['recipient']);

        // id canale
        $this->assertEquals(self::SANDBOX_CHANNEL_ID, $payload['channelId']);
    }

    /** @test */
    public function it_can_originate_message_from_a_webhook()
    {
        $recipient = '347';
        $textMessage = 'Message from space';
        $additionalParameters = [
            'sender_channel_id' => '338'
        ];
        $sender = $additionalParameters['sender_channel_id'];

        $driver = $this->getDriver();

        // mimic botman@say
        $incomingMessage = new IncomingMessage('', $recipient, '');

        // mimic botman@reply
        $outgoingMessage = new OutgoingMessage($textMessage);
        $payload = $driver->buildServicePayload(
            $outgoingMessage,
            $incomingMessage,
            $additionalParameters
        );

        $this->assertEquals($textMessage, $payload['text']);

        // destinatario
        $this->assertEquals($recipient, $payload['recipient']);

        // id canale
        $this->assertEquals($sender, $payload['channelId'] );
    }

    /** @test */
    public function it_can_send_payload()
    {
        $driver = $this->getValidDriverWith('text');

        // $mock = m::mock(\MessageBird\Common\HttpClient::class, ['https://whatsapp-sandbox.messagebird.com/v1'])->makePartial();
        // $mock->shouldReceive('performHttpRequest')
        //     ->withArgs(function ($method, $resourceName, $query = null, $body = null) {

        //     })
        //     ->andReturn();

        // $driver->getClient($mock);

        $incomingMessage = new IncomingMessage(
            'message from Aris',
            self::DAVID_NUMBER,
            self::SANDBOX_NUMBER
        );
        $outgoingMessage = new OutgoingMessage('test reply from sandbox');

        $payload = $driver->buildServicePayload(
            $outgoingMessage,
            $incomingMessage
        );

        // $response = $driver->sendPayload($payload);
        // var_export($response);

        // $this->assertEquals($outgoingMessage->getText(), $payload['message']->getText());
    }

    /** @test */
    public function it_can_get_conversation_answers_of_text_only()
    {
        $driver = $this->getValidDriverWith('text');

        $incomingMessage = new IncomingMessage('This is my answer', '123456', '987654');
        $answer = $driver->getConversationAnswer($incomingMessage);

        $this->assertSame('This is my answer', $answer->getText());
    }

    /** @test */
    public function it_can_get_conversation_answers_of_image_and_text()
    {
        $driver = $this->getValidDriverWith('image');

        $incomingMessage = $driver->getMessages()[0];
        $answer = $driver->getConversationAnswer($incomingMessage);

        $this->assertEquals(Image::PATTERN, $answer->getText());
        $this->assertEquals(
            'https://internationaltreefoundation.org/wp-content/uploads/2016/05/tree-576848_1280.png',
            $incomingMessage->getImages()[0]->getUrl()
        );

        $this->assertEquals('Just a tree...', $incomingMessage->getImages()[0]->getTitle());
    }

    /** @test */
    public function it_has_a_messagebird_client()
    {
        $driver = $this->getValidDriverWith('text');

        $this->assertInstanceOf(Client::class, $driver->getClient());
    }

    /** @test */
    public function it_has_an_http_client()
    {
        $driver = $this->getValidDriverWith('text');

        $httpClient = $driver->getConversationsAPIHttpClient();

        // $this->assertEquals(10, $httpClient->connectionTimeout);
        // $this->assertEquals(15, $httpClient->timeout);

        $this->assertInstanceOf(HttpClient::class, $httpClient);
    }

    /** HELPER FUNCTIONS */
    private function getDriver($parameters = [], $htmlInterface = null)
    {
        $request = Request::create('', 'POST', $parameters, [], [], [], $this->getDummyRequestBody());

        $request->query->add([]);
        $request->headers->set('messagebird-signature', 'it26SSv/qkoxQ5xfPe5MYF9nddcHbqMTcToqLL4+Udk=');
        $request->headers->set('messagebird-request-timestamp', '1591949471');

        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        $config = [
            'messagebird' => [
                'access_key' => '',
                'is_sandbox_enabled' => true,
                'signing_key' => 'tN3jfxydr6DtgJhUX0zsiiGZaREoRFud',
                'connection_timeout' => 10,
                'timeout' => 15
            ]
        ];

        $driver = new MessagebirdWhatsappDriver($request, $config, $htmlInterface);

        return $driver;
    }

    private function getValidDriverWith($requestType)
    {
        switch ($requestType) {
            case 'image':
                return $this->getDriver($this->getImageMessageFakeRequest());

            case 'audio':
                return $this->getDriver($this->getAudioMessageFakeRequest());

            default:
                return $this->getDriver($this->getTextMessageFakeRequest());
        }
    }

    private function getTextMessageFakeRequest()
    {
        return [
            'contact' => [
                'id' => '56ffa994eed94acc8248070f59a5deba',
                'href' => null,
                'msisdn' => 393381234567,
                'displayName' => '393381234567',
                'firstName' => null,
                'lastName' => null,
                'customDetails' => [],
                'attributes' => [],
                'createdDatetime' => '2019-12-18T11:22:26Z',
                'updatedDatetime' => '2019-12-18T11:22:26Z',
            ],

            'conversation' => [
                'id' => '24728835c0424a6985714a2a172eb01f',
                'contactId' => '56ffa994eed94acc8248070f59a5deba',
                'status' => 'active',
                'createdDatetime' => '2019-12-18T11:22:26Z',
                'updatedDatetime' => '2019-12-26T15:21:51.661632042Z',
                'lastReceivedDatetime' => '2019-12-26T15:23:31.953871499Z',
                'lastUsedChannelId' => '1cf5b8c9f58b499fa3cdafaaaaaaaaaa',
                'messages' => [
                    'totalCount' => 0,
                    'href' => 'https://whatsapp-sandbox.messagebird.com/v1/conversations/24728835c0424a6985714a2a172eb01f/messages',
                ],
            ],

            'message' => [
                'id' => '588f97839c8d4cf98f204768691084bb',
                'conversationId' => '24728835c0424a6985714a2a172eb01f',
                'platform' => 'whatsapp',
                'to' => "+393381234567",
                'from' => "+447418310508",
                'channelId' => '1cf5b8c9f58b499fa3cdafaaaaaaaaaa',
                'type' => 'text',
                'content' => [
                    'text' => 'Hei dude, from postman!',
                ],
                'direction' => 'sent',
                'status' => 'pending',
                'createdDatetime' => '2019-12-26T15:23:31.953871499Z',
                'updatedDatetime' => '2019-12-26T15:23:31.964338664Z',
            ],

            'type' => 'message.created',
        ];
    }

    private function getImageMessageFakeRequest()
    {
        return [
            'contact' => [
                'id' => '56ffa994eed94acc8248070f59a5deba',
                'href' => null,
                'msisdn' => 393381234567,
                'displayName' => '393381234567',
                'firstName' => null,
                'lastName' => null,
                'customDetails' => [],
                'attributes' => [],
                'createdDatetime' => '2019-12-18T11:22:26Z',
                'updatedDatetime' => '2019-12-18T11:22:26Z',
            ],

            'conversation' => [
                'id' => '24728835c0424a6985714a2a172eb01f',
                'contactId' => '56ffa994eed94acc8248070f59a5deba',
                'status' => 'active',
                'createdDatetime' => '2019-12-18T11:22:26Z',
                'updatedDatetime' => '2019-12-30T11:04:23.157720227Z',
                'lastReceivedDatetime' => '2019-12-30T11:09:43.667144441Z',
                'lastUsedChannelId' => '1cf5b8c9f58b499fa3cdafaaaaaaaaaa',
                'messages' => [
                    'totalCount' => 0,
                    'href' => 'https://whatsapp-sandbox.messagebird.com/v1/conversations/24728835c0424a6985714a2a172eb01f/messages',
                ],
            ],

            'message' => [
                'id' => '7ec96a68b8c94148a2672b035adf0d60',
                'conversationId' => '24728835c0424a6985714a2a172eb01f',
                'platform' => 'whatsapp',
                'to' => '+393381234567',
                'from' => '+447418310508',
                'channelId' => '1cf5b8c9f58b499fa3cdafaaaaaaaaaa',
                'type' => 'image',
                'content' => [
                    'image' => [
                        // 'url' => 'https://media.messagebird.com/v1/media/c17d6c2f-60da-498a-985f-90c6d759a4ad',
                        'url' => 'https://internationaltreefoundation.org/wp-content/uploads/2016/05/tree-576848_1280.png',
                        'caption' => 'Just a tree...',
                    ],
                ],
                'direction' => 'sent',
                'status' => 'pending',
                'createdDatetime' => '2019-12-30T11:09:43.667144441Z',
                'updatedDatetime' => '2019-12-30T11:09:43.676592022Z',
            ],

            'type' => 'message.created',
        ];
    }

    private function getAudioMessageFakeRequest()
    {
        return [
            'contact' => [
                'id' => '30d5cb6b79984a13b7c6f990e781722a',
                'href' => NULL,
                'msisdn' => 393383342437,
                'displayName' => '393383342437',
                'firstName' => NULL,
                'lastName' => NULL,
                'customDetails' => [],
                'attributes' => [],
                'createdDatetime' => '2019-12-18T11:22:26Z',
                'updatedDatetime' => '2020-04-24T11:43:16Z',
            ],

            'conversation' => [
                'id' => '6118481c213d440ba93868557fca1dc1',
                'contactId' => '30d5cb6b79984a13b7c6f990e781722a',
                'status' => 'active',
                'createdDatetime' => '2019-12-18T11:22:26Z',
                'updatedDatetime' => '2020-06-09T16:06:37.486504642Z',
                'lastReceivedDatetime' => '2020-06-09T16:09:19.846679813Z',
                'lastUsedChannelId' => '11a976e320d04baaa01382783727e8c5',
                'messages' => [
                    'totalCount' => 0,
                    'href' => 'https://whatsapp-sandbox.messagebird.com/v1/conversations/6118481c213d440ba93868557fca1dc1/messages',
                ],
            ],

            'message' => [
                'id' => 'c4a5cb6c9acc4eca9dbee9f619f9c1d7',
                'conversationId' => '6118481c213d440ba93868557fca1dc1',
                'platform' => 'whatsapp',
                'to' => '+447418310508',
                'from' => '+393383342437',
                'channelId' => '11a976e320d04baaa01382783727e8c5',
                'type' => 'audio',
                'content' => [
                    'audio' => [
                        'url' => 'https://media.messagebird.com/v1/media/52296e61-9e62-479b-9ce0-9cadebe40598',
                    ],
                ],
                'direction' => 'received',
                'status' => 'received',
                'createdDatetime' => '2020-06-09T16:09:17Z',
                'updatedDatetime' => '2020-06-09T16:09:19.8536614Z',
            ],

            'type' => 'message.created',
        ];
    }

    private function getDummyRequestBody()
    {
        return '{"contact":{"id":"30d5cb6b79984a13b7c6f990e781722a","href":"","msisdn":393383342437,"displayName":"393383342437","firstName":"","lastName":"","customDetails":{},"attributes":{},"createdDatetime":"2019-12-18T11:22:26Z","updatedDatetime":"2020-04-24T11:43:16Z"},"conversation":{"id":"6118481c213d440ba93868557fca1dc1","contactId":"30d5cb6b79984a13b7c6f990e781722a","status":"active","createdDatetime":"2019-12-18T11:22:26Z","updatedDatetime":"2020-06-12T08:10:07.744164323Z","lastReceivedDatetime":"2020-06-12T08:11:10.925995073Z","lastUsedChannelId":"11a976e320d04baaa01382783727e8c5","messages":{"totalCount":0,"href":"https://whatsapp-sandbox.messagebird.com/v1/conversations/6118481c213d440ba93868557fca1dc1/messages"}},"message":{"id":"b88ff4db73be4bd58c2c56cb3bbb1004","conversationId":"6118481c213d440ba93868557fca1dc1","platform":"whatsapp","to":"+447418310508","from":"+393383342437","channelId":"11a976e320d04baaa01382783727e8c5","type":"text","content":{"text":"hi"},"direction":"received","status":"received","createdDatetime":"2020-06-12T08:11:10Z","updatedDatetime":"2020-06-12T08:11:10.935687353Z"},"type":"message.created"}';
    }
}
