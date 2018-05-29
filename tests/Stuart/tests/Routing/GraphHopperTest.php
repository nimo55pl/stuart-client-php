<?php

namespace Stuart\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Stuart\DropOff;
use Stuart\Job;
use Stuart\Pickup;
use Stuart\Routing\GraphHopper;

class GraphHopperTest extends \PHPUnit_Framework_TestCase
{

    private $container;

    public function setUp()
    {
        $this->container = array();
    }

    public function test_blabla()
    {
        // given
        $pickup = new Pickup();
        $pickup->setAddress('26 rue taine 75012 paris');

        // when
        $dropoffs = [
            $this->dropoff('23 rue de richelieu 75002 paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:40:00')),
            $this->dropoff('3 rue d\'edimbourg 75008 paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:45:00')),
            $this->dropoff('156 rue de charonne 75012 paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:30:00')),
            $this->dropoff('8 rue sidi brahim 75012 paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 14:30:00')),
            $this->dropoff('5 passage du chantier 75012 paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:30:00')),
            $this->dropoff('Hôpital Saint-Louis, 75010 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 13:20:00')),
            $this->dropoff('1 Rue des Deux Gares, 75010 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:30:00')),
            $this->dropoff('137 Rue la Fayette, 75010 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:30:00')),
            $this->dropoff('34 Rue Pierre Semard, 75009 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:00:00')),
            $this->dropoff('46 Rue Lecourbe, 75015 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:30:00')),
            $this->dropoff('178 Rue Lecourbe, 75015 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 13:00:00')),
            $this->dropoff('43 Rue des Alouettes 75019 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:30:00')),
            $this->dropoff('50 Rue Durantin, 75018 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:30:00')),
            $this->dropoff('47-33 Rue des Abbesses, 75018 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 13:30:00')),
            $this->dropoff('2 Boulevard de la Villette, 75019 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 14:30:00')),
            $this->dropoff('172 Rue de Charonne, 75011 Paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 15:30:00'))
        ];

        $config = array(
            'graphhopper_api_key' => 'f8b0585b-1bed-4cda-aede-dfdd2c4899a9',
            'vehicle_count' => 10,
            'max_dropoffs' => 8,
            'slot_size_in_minutes' => 60,
            'max_distance' => 15000
        );

        $pricingStacking = 0;
        $graphHopper = new GraphHopper($pickup, $dropoffs, $config);
        $result = $graphHopper->findRounds();
        foreach ($result->jobs as $job) {
            $job->setTransportType('bike');
            $res = $this->getPricing($job);
            $pricingStacking += $res->amount;
        }
        print_r('Total pricing with stacking is: ' . $pricingStacking . ', Waste count is: ' . count($result->waste) . '. ');

        $pricingNoStacking = 0;
        foreach ($dropoffs as $dropoff) {
            $job = new Job();
            $job->setTransportType('bike');
            $job->pushPickup($pickup);
            $job->pushDropoff($dropoff);
            $res = $this->getPricing($job);
            $pricingNoStacking += $res->amount;
        }
        print_r('Total pricing without stacking is: ' . $pricingNoStacking);

    }

    private function getPricing($job)
    {
        $environment = \Stuart\Infrastructure\Environment::SANDBOX;
        $api_client_id = 'c6058849d0a056fc743203acb8e6a850dad103485c3edc51b16a9260cc7a7688'; // can be found here: https://admin-sandbox.stuart.com/client/api
        $api_client_secret = 'aa6a415fce31967501662c1960fcbfbf4745acff99acb19dbc1aae6f76c9c619'; // can be found here: https://admin-sandbox.stuart.com/client/api
        $authenticator = new \Stuart\Infrastructure\Authenticator($environment, $api_client_id, $api_client_secret);

        $httpClient = new \Stuart\Infrastructure\HttpClient($authenticator);
        $client = new \Stuart\Client($httpClient);

        return $client->getPricing($job);
    }

    private function dropoff($address, $dropoffAt)
    {
        $dropoff = new DropOff();
        $dropoff->setAddress($address)
            ->setDropoffAt($dropoffAt);
        return $dropoff;
    }


    public function ahtest_calls_graphhopper_api_with_correct_parameters()
    {
        // given
        $pickup = new Pickup();
        $pickup->setAddress('26 rue taine 75012 paris');

        $dropoffs = [
            $this->dropoff('23 rue de richelieu 75002 paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:40:00')),
            $this->dropoff('3 rue d\'edimbourg 75008 paris', \DateTime::createFromFormat('Y-m-d H:i:s', '2018-05-30 12:45:00')),
        ];

        $config = array(
            'graphhopper_api_key' => 'f8b0585b-1bed-4cda-aede-dfdd2c4899a9',
            'vehicle_count' => 1,
            'max_dropoffs' => 8,
            'slot_size_in_minutes' => 60,
            'max_distance' => 15000
        );

        $graphHopper = new GraphHopper($pickup, $dropoffs, $config, $this->OKClient());

        // when
        $graphHopper->findRounds();

        // then
        foreach ($this->container as $transaction) {
            print_r($transaction);
        }
    }


    private function OKClient()
    {
        $history = Middleware::history($this->container);
        $mock = new MockHandler([
            new Response(200, [], $this->geocodeSampleResponse()),
            new Response(200, [], $this->geocodeSampleResponse()),
            new Response(200, [], $this->geocodeSampleResponse()),
            new Response(200, [], $this->geocodeSampleResponse()),
            new Response(200, []),
            new Response(200, [], $this->solutionSampleResponse())
        ]);
        $handler = HandlerStack::create($mock);
        $handler->push($history);

        return new Client(['handler' => $handler]);
    }

    private function geocodeSampleResponse()
    {
        return '{
            "hits": [
                {
                    "point": {
                        "lng": 1234,
                        "lat": 5478
                    }
                }
            ],
            "took": 8
        }';
    }

    private function solutionSampleResponse()
    {
        return '{
            "status": "finished"
        }';
    }
}