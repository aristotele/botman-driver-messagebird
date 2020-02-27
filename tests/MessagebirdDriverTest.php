<?php

namespace Tests;

use Mockery as m;
use MessageBird\Client;
use BotMan\BotMan\Http\Curl;
use PHPUnit\Framework\TestCase;
use BotMan\BotMan\Messages\Attachments\Image;
use Symfony\Component\HttpFoundation\Request;
use BotMan\Drivers\Messagebird\MessagebirdDriver;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class MessagebirdDriverTest extends TestCase
{
    const DAVID_NUMBER = '393381234567';
    const SANDBOX_NUMBER = '447418310508';
    const SANDBOX_CHANNEL_ID = '1cf5b8c9f58b499fa3cdafaaaaaaaaaa';

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver();

        $this->assertSame('Messagebird', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getValidDriverWith('text');

        $this->assertTrue($driver->matchesRequest());

        $driver = $this->getValidDriverWith('image');

        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_can_handle_incoming_message_with_text_only()
    {
        $driver = $this->getValidDriverWith('text');
        $this->assertTrue(is_array($driver->getMessages()));
        $this->assertNotEmpty($driver->getMessages());

        $message = $driver->getMessages()[0];
        $this->assertInstanceOf(IncomingMessage::class, $message);
        $this->assertEquals('Hei dude, from postman!', $message->getText());
    }

    /** @test */
    public function it_can_handle_incoming_message_with_image_and_text()
    {
        $driver = $this->getValidDriverWith('image');
        $this->assertTrue(is_array($driver->getMessages()));
        $this->assertNotEmpty($driver->getMessages());

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

        $this->assertTrue($driver->isConfigured());
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
            'sender' => '338'
        ];
        $sender = $additionalParameters['sender'];

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
        $this->assertEquals(
            $driver->getConfig()->get('channels')['whatsapp'][$sender],
            $payload['channelId']
        );
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

        $driver->getClient();

        $this->assertInstanceOf(Client::class, $driver->getClient());
    }

    /** HELPER FUNCTIONS */
    private function getDriver($parameters = [], $htmlInterface = null)
    {
        $request = Request::create('', 'POST', $parameters);
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        $config = [
            'messagebird' => [
                'access_key' => 'pm3CSy12hRXRWbfRsGU2Jza7A',
                'is_sandbox_enabled' => true,
                'channels' => [
                    'whatsapp' => [
                        '338' => 'aaa',
                        '333' => 'bbb'
                    ]
                ]
            ]
        ];

        $driver = new MessagebirdDriver($request, $config, $htmlInterface);

        return $driver;
    }

    private function getValidDriverWith($requestType)
    {
        switch ($requestType) {
            case 'image':
                return $this->getDriver($this->getImageMessageFakeRequest());

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
}
