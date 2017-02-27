<?php

namespace App\Classes;

use hatxor\BotFramework\Bot;
use hatxor\BotFramework\Config;
use hatxor\BotFramework\Helpers;
use hatxor\BotFramework\SkypeBot;
use hatxor\BotFramework\FacebookBot;
use hatxor\BotFramework\WebchatBot;
use hatxor\BotFramework\EmailBot;
use hatxor\BotFramework\TelegramBot;
use hatxor\BotFramework\SlackBot;

use App\Models\Test;
use App\Models\Message;
use App\Models\Chanel;

class BotClient {

    public $input;

    private $bot;

    private $hash;

    private $client;

    private $secret;

    private $config;


    /**
     * Load the configuration, create a new bot of the given type and do the login
     */
    public function __construct($test = false) {

        // 1. Load the config
        $this->loadConfig($test);

        // 2. We take the input data
        $this->input = $this->getPostRaw();

        // 3. Check the hash
        if( !isset( $_GET['hash'] ) || $_GET['hash'] != $this->config['hash'] )

            die("Unauthorized!"); // TODO Change by an exception

        // 4. Check the channel
        if( !isset( $this->input->channelId ) )

            die("Channel not found."); // TODO Change by an exception

        // 5. Init our bot depending of the channel
        $this->bot = Bot::getBotByChannel( $this->input->channelId, $this->config ); // TODO Try / catch to manage other errors
    }

    /**
     * [init description]
     * @return [type] [description]
     */
    public function init() {


        if( !isset( $this->input->type ) )

            die("Method not found."); // TODO Cambiar por una excepcion

        // Get the type of request and execute the right method
        return $this->fireEventHandler( $this->input->type );

    }


    /**
     * Load the configuration from the config.php file
     */
    private function loadConfig($test = false) {
        /*
        $this->hash = \hatxor\BotFramework\Options::get('hash');

        $this->client = \hatxor\BotFramework\Options::get('app_client_id');

        $this->secret = \hatxor\BotFramework\Options::get('app_secret_id');
        */
        if($test) {
            $this->config = config('botframework.test');
        } else {
            $this->config = config('botframework.live');
        }

    }


    /**
     * Get the info from the bot response
     * @param  boolean $to_object To enable if the response must ve given in object or in raw
     * @return mixed              String or Object depending. Response from the bot api.
     */
    private function getPostRaw( $to_object = true ) {

        $postRaw = file_get_contents('php://input');

        if( $to_object === true && Helpers::isJson( $postRaw ) === true )

            return json_decode( $postRaw );

        else

            return $postRaw;

    }


    /**
     * Manage the event firing the right method
     * @param  string $type Method to fire
     * @return mixed        The response of the selected method
     */
    private function fireEventHandler ( $type ) {

        $methodName = $type . 'EventHandler';

        $specificMethodName = $this->input->channelId . ucfirst($type) . 'EventHandler';

        if ( !method_exists( $this, $specificMethodName ) ) {

            if ( !method_exists( $this, $methodName ) ) {

                $botClassName = Bot::getBotName( $this->input->channelId );

                // If not exist in the client, we search for it in the Bot
                if ( !method_exists( $this->bot, $methodName ) )

                    die("No se encuentra el método!!"); // TODO Cambiar por una excepcion

                else

                    return $this->bot->$methodName( $this->input );

            }

            else

                return $this->$methodName();

        }

        else {

            return $this->$specificMethodName();
        }



    }

    /**
     * ######################################
     * YOU CAN CREATE FROM HERE
     * ######################################
     */


    /**
     * Default skype message event handler
     */
    public function skypeMessageEventHandler() {
        /*

        $to = $this->input->from->id;

        $message = "I have received \"" .  $this->input->text . "\" from " . $this->input->from->name . "!";

        $this->bot->addMessage( $to, $message );
        */

        $this->recordMessage();

        return 0;

    }

    /**
     * Default telegram message event handler
     */
    public function telegramMessageEventHandler() {

        $this->recordMessage();

        return 0;

    }

    /**
     * Default webchat message event handler
     */
    public function webchatMessageEventHandler() {

        $this->recordMessage();

        return 0;

    }

    /**
     * Default webchat message event handler
     */
    public function facebookMessageEventHandler() {

        $this->recordMessage();

        return 0;

    }


    /**
     * Default skype conversation update event handlers
     */
    public function skypeConversationUpdateEventHandler_() {

        // Members added in group
        if( isset( $this->input->conversation->isGroup ) && $this->input->conversation->isGroup == true ) {

            $to = $this->input->conversation->id;

            if( isset( $this->input->membersAdded ) ) {

                $message = "Hi guys!! :)";

                $this->bot->addMessage( $to, $message );

            }

        }

        return 0;

    }

    /**
     * Default skype contact relation update event handlers
     * @return [type] [description]
     */
    public function skypeContactRelationUpdateEventHandler_() {

        $to = $this->input->from->id;

        $name = explode(" ", $this->input->from->name)[0];

        $message = "Hey " . $name . "! What's up?? :)";

        $this->bot->addMessage( $to, $message );

        return 0;

    }

    private function recordMessage () {

        //$chat_id = $this->input->from->id;
        $chat_id = $this->input->conversation->id;

        $lastClientMsgCount = Message::lastClientMsgCount($chat_id);

        $messageModel = new Message;
        $messageModel->msg = $this->input->text;
        $messageModel->client_id = $chat_id;
        $messageModel->chanel_id = Chanel::getByBotChannel($this->input->channelId);
        $messageModel->save();

        if(!$lastClientMsgCount)
        {
            //$msg = "Привет ".$this->input->from->name.". Я бот, но через 15 секунд Вам ответит живой человек. Пока можете написать вопрос, который Вас интересует.\n";
            //$msg .= "Hi ".$this->input->from->name.". I am a bot, but after 15 seconds the operator will answer You. While you can write a question that interests You.";

            $msg = "Привет. Я бот, но через 15 секунд Вам ответит живой человек. Пока можете написать вопрос, который Вас интересует.\n";
            $msg .= "Hi. I am a bot, but after 15 seconds the operator will answer You. While you can write a question that interests You.";

            $this->bot->addMessage( $chat_id, $msg );
        }


    }

}