<?php

namespace App\Classes;
use GuzzleHttp\Client as HttpClient;
use Exception;


class IziTravel {

    /**
     * Guzzle client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;


    protected $options = [];

    public function __construct(array $config = []) {
        $this->client = new HttpClient([
            'base_uri' => config('izi.travel.base_uri'),
            'headers' => ['X-IZI-API-KEY' => config('izi.travel.api_key')]
        ]);
    }

    public function getNearest($lat, $lng, $items = null, $type = null, $needAudioUrl = false) {
        $return = [];

        $radius[] = 10000; //10 km

        if(!empty($items)) {
            $radius = [
                500,
                1000,
                2000,
                3000,
                4000,
                5000
            ];
        }

        $query = [
            'includes' => 'all',
            'version' => '1.6',
            'limit' => $items,
            'lat_lon' => $lat.', '.$lng,
            'languages' => 'ru',
            "status" => "published"
        ];

        if(!empty($type)) {
            $query['type'] = $type;
        } else {
            $query['type'] = 'museum, exhibit, story_navigation, tour, tourist_attraction, collection';
        }

        foreach($radius as $r) {

            $query['radius'] = $r;

            $response = $this->client->request('GET', 'mtg/objects/search', [
                'query' => $query
            ]);

            $return = json_decode($response->getBody()->getContents());

            if(!empty($items) && count($return) >= $items) {
                break;
            }
        }

        if($needAudioUrl) {
            foreach($return as $key => $r) {
                $return[$key]->audioUrl = $this->getAudioUrl($r);
            }
        }

        $i = 0;

        return $return;
    }

    public function getExposition($uuid, $needAudioUrl = false) {
        try{
            $response = $this->client->request('GET', 'mtgobjects/'.$uuid, [
                'query' => [
                    'languages' => 'ru',
                    'includes' => 'all',
                    'playback' => 'sequential'
                ]
            ]);
        } catch(Exception $e) {
            return [];
        }

        $return = json_decode($response->getBody()->getContents());

        if($needAudioUrl) {
            foreach($return as $key => $r) {
                if(!empty($r->content[0]->children)) {
                    foreach($r->content[0]->children as $c_key => $children) {
                        $return[$key]->content[0]->children[$c_key]->audioUrl = $this->getAudioUrl($children);
                    }
                }
            }
        }

        return $return;
    }

    private function getAudioUrl($object) {
        $url = "";

        if(empty($object->content)) {
            $exposition = $this->getExposition($object->uuid);
        } else {
            $exposition = $object;
        }

        if(!empty($exposition[0]->content[0]->audio[0])) {
            $url = "https://media.izi.travel/".$exposition[0]->content_provider->uuid."/".$exposition[0]->content[0]->audio[0]->uuid.".m4a";
        }

        return $url;
    }
}