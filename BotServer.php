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

class BotServer {

    private $bot;

    private $hash;

    private $client;

    private $secret;

    private $config;


    /**
     * Load the configuration, create a new bot of the given type and do the login
     * @param string $channelID The channel ID
     */
    public function __construct( $channelID ) {

        // 1. Load the config
        $this->loadConfig();

        // 2. Init our bot depending of the channel
        $this->bot = Bot::getBotByChannel( $channelID, $this->config ); // TODO Try / catch para controlar los errores de que no encuentre la clase

        // 3. Do the auth
        $this->bot->authenticate();

    }


    /**
     * Load the configuration from the config.php file
     */
    private function loadConfig() {

        /*
        $this->hash = \hatxor\BotFramework\Options::get('hash');

        $this->client = \hatxor\BotFramework\Options::get('app_client_id');

        $this->secret = \hatxor\BotFramework\Options::get('app_secret_id');
        */

        $this->config = config('botframework.live');

    }

    /**
     * ######################################
     * YOU CAN CREATE FROM HERE
     * ######################################
     */


    /**
     * Send a normal message with text to the given user
     * @param  string $to      Recipient ID
     * @param  string $message The message to send
     * @return array           HTTP response
     */
    public function sendMessage( $to, $message ) {

        return $this->bot->addMessage( $to, $message );

    }

    public function sendImage( $to, $message ) {

        return $this->bot->addAttachment( $to, 'image', $message );

    }

    public function getBot() {

        return $this->bot;
        
    }

}